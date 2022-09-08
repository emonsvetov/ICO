<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;

trait MediaUploadTrait
{
    public function handleMediaUpload($request, $model, $updating = false): array
    {
        $uploads = [];
        foreach ($model->getImageFields() as $field) {
            if ($request->hasFile($field)) {
                $logo = $request->file($field);
                if ($logo->isValid()) {
                    $uploads[$field] = $logo->store($model->getImagePath() . '/' . $model['id']);
                    if ($updating) {
                        $oldFile = $model[$field];
                        if ($oldFile) {
                            Storage::delete($oldFile);
                        }
                    }
                }
            }
        }
        return $uploads;
    }
}
