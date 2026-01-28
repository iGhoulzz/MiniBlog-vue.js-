<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\post;;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $fillable = [
        'file_path',
        'file_type',
        'disk',
        'mediable_type',
        'mediable_id',
        'collection',
    ];

    public function mediable()
    {
        return $this->morphTo();
    }
}
