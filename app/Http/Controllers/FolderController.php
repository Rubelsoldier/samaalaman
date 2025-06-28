<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function getSubFolders(File $folder)
    {
        $subfolders = $folder->children()
            ->where('is_folder', true)
            ->get();

        return $subfolders->map(function($folder) {
            $hasChildren = $folder->children()->where('is_folder', true)->exists();
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'has_children' => $hasChildren,
                'parent_id' => $folder->parent_id
            ];
        });
    }
}
