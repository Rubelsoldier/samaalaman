<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\File;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\FileResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreFileRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\StoreFolderRequest;

class FileController extends Controller
{
    public function myFiles(Request $request, string $folder = null) {
        
        if($folder){
            $folder = File::query()
                ->where('created_by',Auth::id())
                ->where('path',$folder)
                ->firstOrFail();
        }
        
        if(!$folder){
        $folder = $this->getRoot();
        }

        $files = File::query()
            ->where('parent_id',$folder->id)
            ->where('created_by',Auth::id())
            ->orderBy('is_folder','desc')
            ->orderBy('created_at','desc')
            ->paginate(5);

        $files = FileResource::collection($files);
        
        if ($request->wantsJson()) {
            return $files;
        }

        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);

        $folder = new FileResource($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
    }

    public function trash(Request $request)
    {
        // $search = $request->get('search');
        $files = File::onlyTrashed()
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('deleted_at', 'desc')
            ->paginate(10);
            // ->orderBy('files.id', 'desc');

        // if ($search) {
        //     $query->where('name', 'like', "%$search%");
        // }

        // $files = $query->paginate(10);

        $files = FileResource::collection($files);

        // if ($request->wantsJson()) {
        //     return $files;
        // }

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
                if($file){
                    $file->delete();
                }
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

    private function getRoot()
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }
}
