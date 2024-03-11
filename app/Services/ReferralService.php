<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Notifications\ReferralNotification;
use App\Models\Referral;
use App\Models\Program;

class ReferralService
{
    public function refer(Program $program, $data )  {
        $referral = Referral::create($data);
        $sender = \App\Models\User::select(['first_name', 'last_name'])->find($data['sender_id']);

        $notification =  [
            'sender_name' => $sender ? $sender->name : 'unknown',
            'recipient_first_name' => $referral->recipient_first_name,
            'recipient_last_name' => $referral->recipient_last_name,
            'recipient_email' => $referral->recipient_email,
            'recipient_area_code' => $referral->recipient_area_code,
            'recipient_phone' => $referral->recipient_phone,
            'message' => $referral->message
        ];
        // DB::enableQueryLog();
        // pr($program->getManagers());
        // pr(toSql(DB::getQueryLog()));
        if( $program->getManagers() )   {
            foreach( $program->getManagers() as $manager )  {
                $manager->notify(new ReferralNotification((object)$notification));
            }
        }

        // $notification['awarder_last_name'] = $awarder->last_name;
        // $awardee->notify(new ReferralNotification((object)$notification));
        return $referral;
    }
}

