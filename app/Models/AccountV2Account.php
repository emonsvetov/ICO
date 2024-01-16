<?php
namespace App\Models;
use App\Models\BaseModel;
class AccountV2Account extends BaseModel
{
    public $table = 'account_v2_accounts';
    public $timestamp = false;
    protected $protected = [];
    protected $fillable = ['v2_account_id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
