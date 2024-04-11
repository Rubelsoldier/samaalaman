<?php

namespace App\Models;

use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCreatorAndUpdater;

class File extends Model
{
    use HasFactory,HasCreatorAndUpdater,NodeTrait,SoftDeletes;

    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

}
