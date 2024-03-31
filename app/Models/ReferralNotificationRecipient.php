<?php

namespace App\Models;

use App\Notifications\ReferralNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ReferralNotificationRecipient extends BaseModel
{
    use HasFactory;
    protected $guarded = [];
    use SoftDeletes;
    //protected $table = 'team';
    
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    /**
     * @param Organization $organization
     * @param Program $program
     * @param array $params
     * @return mixed
     */
    public static function getIndexData(Organization $organization, Program $program, array $params)
    {
        $query = self::where('organization_id', $organization->id)
            ->where('program_id', $program->id);
        return $query->orderBy('referral_notification_recipient_name')
            ->get();
    }

    public function sendReferralNotification($notification)
    {
        $user = User::where('email', $this->referral_notification_recipient_email)->first();
        $user->notify(new ReferralNotification($notification));
    }
}