<?php
namespace App\Http\Traits;

trait CreatedUpdatedBy {

    public static function bootCreatedUpdatedBy()
    {
        static::creating(function ($model) {
            if (!$model->isDirty('created_by')) {
                $user = auth('api')->user();
                if ($user){
                    $model->created_by = $user->account_holder_id;
                }

            }
        });

        static::updating(function ($model) {
            if (!$model->isDirty('updated_by')) {
                $user = auth('api')->user();
                if ($user) {
                    $model->updated_by = $user->account_holder_id;
                }
            }
        });
    }

}
