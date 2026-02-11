<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasFile;
use Illuminate\Support\Facades\Storage;

class Comment extends Model
{
    use HasFactory, HasFile;

    protected $fillable = ['content', 'user_id', 'post_id'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $media = $this->media->where('collection', 'default')->first();
        if ($media) {
            return Storage::disk($media->disk)->url($media->file_path);
        }
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

}
