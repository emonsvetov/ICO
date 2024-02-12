<?php

namespace App\Models;

class UsersLog extends BaseModel
{
    protected $table = 'users_log';

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'user_account_holder_id',
        'parent_program_id',
        'email',
        'first_name',
        'last_name',
        'type',
        'old_user_status_id',
        'new_user_status_id',
        'updated_by',
        'technical_reason_id',
        'created_at',
        'updated_at',
    ];

}
