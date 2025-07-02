<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function getSubFolders(File $folder)
    {
        return File::query()
            ->where('parent_id', $folder->id)
            ->where('is_folder', 1)
            ->where('created_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'path' => $folder->path,
                    'has_children' => $folder->children()->where('is_folder', 1)->exists(),
                    '_rgt' => $folder->_rgt
                ];
            });
    }
}
