<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\File;
use App\Models\User;
use Inertia\Inertia;
use App\Models\FileShare;
use App\Models\StarredFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Mail\ShareFilesMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Resources\FileResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreFileRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\TrashFilesRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\AddToFavouritesRequest;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    public function myFiles(Request $request, string $folder = null) {
        
        $search = $request->get('search');

        if($folder){
            $folder = File::query()
                ->where('created_by',Auth::id())
                ->where('path',$folder)
                ->firstOrFail();
        }
        
        if(!$folder){
        $folder = $this->getRoot();
        }

        $favourites = (int)$request->get('favourites');

        $query = File::query()
            ->select('files.*')
            ->with('starred')
            ->where('created_by',Auth::id())
            ->where('_lft', '!=', 1)
            ->orderBy('is_folder','desc')
            ->orderBy('files.created_at','desc')
            ->orderBy('files.id','desc');

            if ($search) {
                $query->where('name', 'like', "%$search%");
            } else {
                $query->where('parent_id', $folder->id);
            }

            if ($favourites === 1) {
                $query->join('starred_files', 'starred_files.file_id', '=', 'files.id')
                    ->where('starred_files.user_id', Auth::id());
            }
            
        $files = $query->paginate(10);
        $files = FileResource::collection($files);
        
        if ($request->wantsJson()) {
            return $files;
        }
        
        // Get ancestors by following parent_id chain
        $ancestors = collect();
        $current = $folder;
        while ($current) {
            if ($current->is_folder) {
                $ancestors->prepend($current);
            }
            $current = $current->parent;
        }
        
        $ancestors = FileResource::collection($ancestors);
        $folder = new FileResource($folder);
        
        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
    }

    public function favourites(Request $request){        
        $search = $request->get('search');

        $query = File::query()
            ->select('files.*')
            ->join('starred_files', 'starred_files.file_id', '=', 'files.id')
            ->where('starred_files.user_id', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('files.created_at', 'desc');

        if ($search) {
            $query->where('files.name', 'like', "%$search%");
        }

        $files = $query->paginate(10);
        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('Favourites', compact('files'));
    }

    public function trash(Request $request)
    {
        $search = $request->get('search');

        $query = File::onlyTrashed()
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('deleted_at', 'desc')
            ->orderBy('files.id', 'desc')    ;                   

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(10);

        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('Trash', compact('files'));
    }

    public function createFolder( StoreFolderRequest $request ) {

        $data = $request->validated();
        $parent = $request->parent;

        if (!$parent) {
            $parent = $this->getRoot();
        }
        
        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);

    }
    public function store(StoreFileRequest $request){
        $data = $request->validated();
        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        if (!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach ($data['files'] as $file) {
                /** @var \Illuminate\Http\UploadedFile $file */

                $this->saveFile($file, $user, $parent);
            }
        }
    }
    
    private function saveFile($file, $user, $parent): void
    {
        $path = $file->store('/files/' . $user->id, 'local');

        $model = new File();
        $model->storage_path = $path;
        $model->is_folder = false;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();
        // $model->uploaded_on_cloud = 0;

        $parent->appendNode($model);

        // UploadFileToCloudJob::dispatch($model);
    }

    private function getRoot()
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }

    public function saveFileTree($fileTree, $parent, $user)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;

                $parent->appendNode($folder);
                $this->saveFileTree($file, $folder, $user);
            } else {
                $this->saveFile($file, $user, $parent);
            }
        }
    }

    public function destroy(FilesActionRequest $request) {
        
        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $children = $parent->children;

            foreach ($children as $child) {
                $child->moveToTrash();
            }
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    $file->moveToTrash();
                }
            }
        }

        return to_route('myFiles', ['folder' => $parent->path]);
    }

    public function download(FilesActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];
        if (!$all & empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        $url = null;
        $filename = null;

        if ($all) {
            $filename = $parent->name . '.zip';
            $url = $this->createZip($parent->children,$filename);
        } else {
            if (count($ids) === 1) {
                $file = File::find($ids[0]);
                if ($file->is_folder) {
                    if ($file->children->count() === 0) {
                        return [
                            'message' => 'The folder is empty'
                        ];
                    }                    
                    $filename = $file->name . '.zip';
                    $url = $this->createZip($file->children,$filename);
                } else {
                    $path = 'public/' . pathinfo($file->storage_path, PATHINFO_BASENAME);
                    $dest = $path;
                    Storage::copy($file->storage_path, $dest);

                    $url = asset(Storage::url($dest, $file->name));
                    $filename = $file->name;
                }
            } else {
                $files = File::query()->whereIn('id', $ids)->get();
                $filename = $parent->name . '.zip';
                $url = $this->createZip($files,$filename);                
            }
        }

        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    private function createZip($files,$filename): string
    {
        // dd(' file name in create zip '.$filename);
        // Path to the output ZIP file
        $zipPath = 'zip/' . $filename . '.zip';
        $publicPath = "public/$zipPath";

        if (!is_dir(dirname($publicPath))) {
            Storage::makeDirectory(dirname($publicPath));
        }
        $zipFile = Storage::path($publicPath);

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }

        // Close the ZIP archive
        $zip->close();

        return asset(Storage::url($zipPath));
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors . $file->name . '/');
            } else {
                $zip->addFile(Storage::path($file->storage_path), $ancestors . $file->name);
            }
        }
    }

    // private function getDownloadUrl(array $ids, $zipName)
    // {
    //     if (count($ids) === 1) {
    //         $file = File::find($ids[0]);
    //         if ($file->is_folder) {
    //             if ($file->children->count() === 0) {
    //                 return [
    //                     'message' => 'The folder is empty'
    //                 ];
    //             }
    //             $url = $this->createZip($file->children);
    //             $filename = $file->name . '.zip';
    //             dd(' getdownloadurl func '.$url);
    //         } else {
    //             $dest = pathinfo($file->storage_path, PATHINFO_BASENAME);
    //             if ($file->uploaded_on_cloud) {
    //                 $content = Storage::get($file->storage_path);
    //             } else {
    //                 $content = Storage::disk('local')->get($file->storage_path);
    //             }

    //             // Log::debug("Getting file content. File:  " .$file->storage_path).". Content: " .  intval($content);

    //             $success = Storage::disk('public')->put($dest, $content);
    //             // Log::debug('Inserted in public disk. "' . $dest . '". Success: ' . intval($success));
    //             $url = asset(Storage::disk('public')->url($dest));
    //             // Log::debug("Logging URL " . $url);
    //             $filename = $file->name;
    //         }
    //     } else {
    //         $files = File::query()->whereIn('id', $ids)->get();
    //         $url = $this->createZip($files);

    //         $filename = $zipName . '.zip';
    //     }

    //     return [$url, $filename];
    // }    

    public function restore(TrashFilesRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->get();
            foreach ($children as $child) {
                $child->restore();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->restore();
            }
        }

        return to_route('trash');
    }

    public function deleteForever(TrashFilesRequest $request)
    {
        $data = $request->validated();
        if ($data['all']) {
            $children = File::onlyTrashed()->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        }

        return to_route('trash');
    }

    public function addToFavourites(AddToFavouritesRequest $request)
    {
        $data = $request->validated();

        $id = $data['id'];
        $file = File::find($id);
        $user_id = Auth::id();

        $starredFile = StarredFile::query()
            ->where('file_id', $file->id)
            ->where('user_id', $user_id)
            ->first();

        if ($starredFile) {
            $starredFile->delete();
        } else {
            StarredFile::create([
                'file_id' => $file->id,
                'user_id' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return redirect()->back();
    }

    public function share(ShareFilesRequest $request) {
        
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $email = $data['email'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to share'
            ];
        }        
        
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return redirect()->back();
        }
        
        if ($all) {
            $files = $parent->children;
        } else {
            $files = File::find($ids);
        }

        $data = [];
        $ids = Arr::pluck($files, 'id');
        $existingFileIds = FileShare::query()
            ->whereIn('file_id', $ids)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('file_id');

        foreach ($files as $file) {
            if ($existingFileIds->has($file->id)) {
                continue;
            }
            $data[] = [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        FileShare::insert($data);

        Mail::to($user)->send(new ShareFilesMail($user, Auth::user(), $files));

        return redirect()->back();

    }

    public function sharedWithMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedWithMe();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(10);

        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedWithMe', compact('files'));
    }

    public function sharedByMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedByMe();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(10);
        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedByMe', compact('files'));
    }

    public function downloadSharedWithMe(FilesActionRequest $request)
    {
        $data = $request->validated();

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        $zipName = 'shared_with_me';
        if ($all) {
            $files = File::getSharedWithMe()->get();
            $filename = $zipName . '.zip';
            $url = $this->createZip($files,$filename);
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $zipName);
        }

        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    public function downloadSharedByMe(FilesActionRequest $request)
    {
        $data = $request->validated();

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        $zipName = 'shared_by_me';
        if ($all) {
            $files = File::getSharedByMe()->get();
            $filename = $zipName . '.zip';
            $url = $this->createZip($files,$filename);
        } else {
            [$url, $filename] = $this->getDownloadUrl($ids, $zipName);
        }

        return [
            'url' => $url,
            'filename' => $filename
        ];
    }
    private function getDownloadUrl(array $ids, $zipName)
    {
        if (count($ids) === 1) {
            $file = File::find($ids[0]);
            if ($file->is_folder) {
                if ($file->children->count() === 0) {
                    return [
                        'message' => 'The folder is empty'
                    ];
                }
                $filename = $file->name . '.zip';
                $url = $this->createZip($file->children,$filename);
            } else {
                $dest = pathinfo($file->storage_path, PATHINFO_BASENAME);
                if ($file->uploaded_on_cloud) {
                    $content = Storage::get($file->storage_path);
                } else {
                    $content = Storage::disk('local')->get($file->storage_path);
                }

                //Log::debug("Getting file content. File:  " .$file->storage_path).". Content: " .  intval($content);

                $success = Storage::disk('public')->put($dest, $content);
                //Log::debug('Inserted in public disk. "' . $dest . '". Success: ' . intval($success));
                $url = asset(Storage::disk('public')->url($dest));
                //Log::debug("Logging URL " . $url);
                $filename = $file->name;
            }
        } else {
            $files = File::query()->whereIn('id', $ids)->get();

            $filename = $zipName . '.zip';
            $url = $this->createZip($files,$filename);
        }

        return [$url, $filename];
    }

    public function move(Request $request) {
        try {
            $data = $request->validate([
                'parent_id' => 'required|exists:files,id',
                'parent_rgt' => 'required|integer',
                'width' => 'required|integer',
                'selectedFile_lft' => 'required|integer',
                'selectedFile_rgt' => 'required|integer'
            ]);

            DB::beginTransaction();

            // Get the moving node's ID
            $movingNode = DB::table('files')
                ->where('_lft', $data['selectedFile_lft'])
                ->first();

            // 1. Mark nodes that are being moved
            DB::statement("UPDATE files 
                        SET _lft = -_lft, 
                            _rgt = -_rgt 
                        WHERE _lft >= ? AND _lft <= ?", 
                        [$data['selectedFile_lft'], $data['selectedFile_rgt']]);

            // 2. Close gaps
            $width = $data['selectedFile_rgt'] - $data['selectedFile_lft'] + 1;
            DB::statement("UPDATE files 
                        SET _lft = _lft - ? 
                        WHERE _lft > ?", 
                        [$width, $data['selectedFile_rgt']]);
            
            DB::statement("UPDATE files 
                        SET _rgt = _rgt - ? 
                        WHERE _rgt > ?", 
                        [$width, $data['selectedFile_rgt']]);

            // 3. Make space for the nodes
            DB::statement("UPDATE files 
                        SET _lft = _lft + ? 
                        WHERE _lft > ?", 
                        [$width, $data['parent_rgt'] - 1]);
            
            DB::statement("UPDATE files 
                        SET _rgt = _rgt + ? 
                        WHERE _rgt > ?", 
                        [$width, $data['parent_rgt'] - 1]);

            // 4. Move the nodes to their new position, only update parent_id for the moving node
            $offset = $data['parent_rgt'] - $data['selectedFile_lft'];
            DB::statement("UPDATE files 
                        SET _lft = -_lft + ?,
                            _rgt = -_rgt + ?,
                            parent_id = CASE 
                                WHEN id = ? THEN ?
                                ELSE parent_id 
                            END
                        WHERE _lft < 0", 
                        [$offset, $offset, $movingNode->id, $data['parent_id']]);

            // --- Update path for moved node and descendants ---
            // Get the moved node and its descendants
            $movedNode = File::find($movingNode->id);
            $descendants = File::where('_lft', '>=', $movedNode->_lft)
                ->where('_rgt', '<=', $movedNode->_rgt)
                ->orderBy('_lft')
                ->get();    

            // Get new parent path
            $parent = File::find($data['parent_id']);
            $parentPath = $parent ? $parent->path : '';

            // Update path for moved node and descendants
            foreach ($descendants as $descendant) {
                if ($descendant->id == $movedNode->id) {
                    // Moved node: new path is parent path + '/' + name
                    $newPath = rtrim($parentPath, '/') . '/' . $descendant->name;
                } else {
                    // Descendant: replace old ancestor path with new ancestor path
                    $oldAncestorPath = $descendant->path;
                    $relativePath = ltrim(Str::after($descendant->path, $movedNode->path), '/');
                    $newPath = rtrim($parentPath, '/') . '/' . $movedNode->name;
                    if ($relativePath) {
                        $newPath .= '/' . $relativePath;
                    }
                }
                $descendant->path = $newPath;
                $descendant->save();
            }
            // --- end path update ---

            DB::commit();
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

}
