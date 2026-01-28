<?php

namespace App\Traits;
use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait HasFile
{

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

        public function addMedia($file, string $folder  ='uploads', string $collection = 'default' ,$disk = 'public')
    {
        $path = $file->store($folder, $disk);
        $media = $this->media()->create([
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'disk' => $disk,
            'collection' => $collection,

        ]);
        return $media;
    }

        public function removeMedia(Media $media){
            Storage::disk($media->disk)->delete($media->file_path);
            return $media->delete();

        }

        public function updateSingleMedia($file, string $collection = 'default', string $folder = 'uploads', string $disk = 'public'){
            $existingMedia = $this->media()->where('collection',  $collection)->first();
            if ($existingMedia){
                $this->removeMedia($existingMedia);
            }
            return $this->addMedia($file, $folder, $collection, $disk);
        }


}
