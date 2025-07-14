<?php

namespace App\Models;

use App\Models\FileShare;
use App\Models\StarredFile;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Kalnoy\Nestedset\NodeTrait;
use App\Traits\HasCreatorAndUpdater;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory,HasCreatorAndUpdater,NodeTrait,SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    public function starred()
    {
        return $this->hasOne(StarredFile::class, 'file_id', 'id')
            ->where('user_id', Auth::id());
    }
    
    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    public function isRoot()
    {
        return $this->parent_id === null;
    } 

    public function get_file_size()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public function owner(): Attribute
    {
        return Attribute::make(
            get: function(mixed $value, array $attributes) {
                return $attributes['created_by'] === request()->user()->id ? "me" : $this->user->name;
            }
        );
    }    
        
    public function moveToTrash()
    {
        $this->deleted_at = Carbon::now();

        return $this->save();
    }
    
    public function deleteForever()
    {
        // Recursively delete children first
        foreach ($this->children as $child) {
            $child->deleteForever();
        }

        // Delete related records for this file
        FileShare::where('file_id', $this->id)->delete();
        StarredFile::where('file_id', $this->id)->delete();

        // Delete file from storage
        $this->deleteFilesFromStorage([$this]);

        // Permanently delete the file record
        $this->forceDelete();
    }

    public function deleteFilesFromStorage($files)
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->deleteFilesFromStorage($file->children);
            } else {
                Storage::delete($file->storage_path);
            }
        }
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->parent) {
                return;
            }
            $model->path = ( !$model->parent->isRoot() ? $model->parent->path . '/' : '' ) . Str::slug($model->name);
        });
        
       static::deleted(function(File $model) {
           if (!$model->is_folder) {
               Storage::delete($model->storage_path);
           }
       });        
    }   

    

    public static function getSharedWithMe()
    {
        return File::query()
            ->select('files.*')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->where('file_shares.user_id', Auth::id())
            ->orderBy('file_shares.created_at', 'desc')
            ->orderBy('files.id', 'desc');
    }

    public static function getSharedByMe()
    {
        return File::query()
            ->select('files.*')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->where('files.created_by', Auth::id())
            ->orderBy('file_shares.created_at', 'desc')
            ->orderBy('files.id', 'desc')
            ;
    }
    
}
