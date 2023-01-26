<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateType extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted',
    ];

    const EMAIL_TEMPLATE_TYPE_ACTIVATION_REMINDER = 'Activation Reminder';
    const EMAIL_TEMPLATE_TYPE_AWARD = 'Award';
    const EMAIL_TEMPLATE_TYPE_AWARD_BADGE = 'Award Badge';
    const EMAIL_TEMPLATE_TYPE_GIFT_CODE = 'Gift Code';
    const EMAIL_TEMPLATE_TYPE_GOAL_STATUS = 'Goal Status';
    const EMAIL_TEMPLATE_TYPE_INVITE_MANAGER = 'Invite Manager';
    const EMAIL_TEMPLATE_TYPE_INVITE_PARTICIPANT = 'Invite Participant';
    const EMAIL_TEMPLATE_TYPE_PASSWORD_RESET = 'Password Reset';
    const EMAIL_TEMPLATE_TYPE_PEER_ALLOCATION = 'Peer Allocation';
    const EMAIL_TEMPLATE_TYPE_PEER_AWARD = 'Peer Award';
    const EMAIL_TEMPLATE_TYPE_REWARD_EXPIRATION_NOTICE = 'Reward Expiration Notice';
    const EMAIL_TEMPLATE_TYPE_WELCOME = 'Welcome';

    const EMAIL_TEMPLATE_TYPE_CLASS_MAP = [
        self::EMAIL_TEMPLATE_TYPE_ACTIVATION_REMINDER => 'ActivationReminderEmail',
        self::EMAIL_TEMPLATE_TYPE_AWARD => 'AwardEmail',
        self::EMAIL_TEMPLATE_TYPE_AWARD_BADGE => 'AwardBadgeEmail',
        self::EMAIL_TEMPLATE_TYPE_GIFT_CODE => 'GiftCodeEmail',
        self::EMAIL_TEMPLATE_TYPE_GOAL_STATUS => 'GoalStatusEmail',
        self::EMAIL_TEMPLATE_TYPE_INVITE_MANAGER => 'InviteManagerEmail',
        self::EMAIL_TEMPLATE_TYPE_INVITE_PARTICIPANT => 'InviteParticipantEmail',
        self::EMAIL_TEMPLATE_TYPE_PASSWORD_RESET => 'PasswordResetEmail',
        self::EMAIL_TEMPLATE_TYPE_PEER_ALLOCATION => 'PeerAllocationEmail',
        self::EMAIL_TEMPLATE_TYPE_PEER_AWARD => 'PeerAwardEmail',
        self::EMAIL_TEMPLATE_TYPE_REWARD_EXPIRATION_NOTICE => 'RewardExpirationNoticeEmail',
        self::EMAIL_TEMPLATE_TYPE_WELCOME => 'WelcomeEmail'
    ];

    public static function getIdByType( $type, $insert = false ) {
        $first = self::where('type', $type)->first();
        if( $first) return $first->id;
        if( $insert )    {
            return self::insertGetId([
                'type' => $type
            ]);
        }
    }
}
