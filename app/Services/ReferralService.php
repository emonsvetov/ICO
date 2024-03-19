<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use App\Notifications\ReferralNotification;
use App\Models\Referral;
use App\Models\Program;
use App\Models\ReferralNotificationRecipient;

class ReferralService
{
    public function refer(Organization $organization, Program $program, $data )  {
        $referral = Referral::create($data);
        $sender = \App\Models\User::select(['first_name', 'last_name'])->find($data['sender_id']);

        $notification =  [
            'sender_first_name' => $sender ? $sender->first_name : 'firstname',
            'sender_last_name' => $sender ? $sender->last_name : 'lastname',
            'recipient_first_name' => $referral->recipient_first_name,
            'recipient_last_name' => $referral->recipient_last_name,
            'recipient_email' => $referral->recipient_email,
            'recipient_area_code' => $referral->recipient_area_code,
            'recipient_phone' => $referral->recipient_phone,
            'program' => $program
        ];

        $referralManagers = ReferralNotificationRecipient::getIndexData($organization, $program, []);
        if($referralManagers->isNotEmpty()){
            foreach( $referralManagers as $referralManager )  {
                $referralManager->sendReferralNotification((object)(
                    $notification +
                    [
                        'contactFirstName' => $referralManager->referral_notification_recipient_name
                    ]
            ));
            }
        }
        else if( $program->getManagers()->isNotEmpty() )   {
            foreach( $program->getManagers() as $manager )  {
                $manager->notify(new ReferralNotification((object)(
                    $notification + 
                    [
                        'contactFirstName' => $manager->first_name
                    ]
                )));
            }
        }
        else if($program->parent()->exists()){
            $parent = $program->parent()->first();
            if($parent -> getManagers()->isNotEmpty()){
                foreach( $parent->getManagers() as $manager )  {
                    $manager->notify(new ReferralNotification((object)(
                        $notification + 
                        [
                            'contactFirstName' => $manager->first_name
                        ]
                    )));
                }
            }
        }
        return $referral;
    }
}

