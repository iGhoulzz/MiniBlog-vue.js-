<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasFile;
use Illuminate\Support\Facades\Storage;


class Post extends Model
{
    use HasFile;

    protected $fillable = ['content', 'user_id'];

    protected $appends = ['image_url','reaction_summary','reaction_count'];




    public function getReactionSummaryAttribute()
{
    if ($this->relationLoaded('reactions')) {
        return $this->reactions->countBy('type');
    }

    return collect();
}


    public function getReactionCountAttribute()
    {
        if ($this->relationLoaded('reactions')) {
            return $this->reactions->count();
        }

        return 0;
    }

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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }
    protected static function booted()
{
    static::deleting(function ($post) {
        foreach ($post->media as $media) {
            $post->removeMedia($media);
        }
    });

}

}
