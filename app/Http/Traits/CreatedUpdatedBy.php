<?php
namespace App\Http\Traits;

trait CreatedUpdatedBy {

    public static function bootCreatedUpdatedBy()
    {
        static::creating(function ($model) {
            if (!$model->isDirty('created_by')) {
                $model->created_by = auth('api')->user()->account_holder_id;
            }
        });

        static::updating(function ($model) {
            if (!$model->isDirty('updated_by')) {
                $model->updated_by = auth('api')->user()->account_holder_id;
            }
        });
    }

}
