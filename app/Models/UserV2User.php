<?php
namespace App\Models;
use App\Models\BaseModel;
class UserV2User extends BaseModel
{
    public $table = 'user_v2_users';
    public $timestamp = false;
    protected $protected = [];
    protected $fillable = ['user_id','v2_user_account_holder_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
