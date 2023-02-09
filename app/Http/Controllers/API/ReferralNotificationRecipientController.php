<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\ReferralNotificationRecipientRequest;
//use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Program;
use App\Models\ReferralNotificationRecipient;
use DB;

class ReferralNotificationRecipientController extends Controller
{
    public function index( Organization $organization, Program $program, Request $request )
    {
        return response(ReferralNotificationRecipient::getIndexData($organization, $program, $request->all()) ?? []);
    }

    public function store(ReferralNotificationRecipientRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        $newReferralNotificationRecipient =ReferralNotificationRecipient::create( 
            $data + 
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]
        );

        if ( !$newReferralNotificationRecipient )
        {
            return response(['errors' => 'ReferralNotificationRecipient creation failed'], 422);
        }
        return response([ 'referral' => $newReferralNotificationRecipient ]);
    }

    public function show( Organization $organization, Program $program,ReferralNotificationRecipient $referral_notification_recipient )
    {
        if ($referral_notification_recipient ) 
        {
            return response($referral_notification_recipient );
        }

        return response( [] );
    }

    public function update(ReferralNotificationRecipientRequest $request, Organization $organization, Program $program,ReferralNotificationRecipient $referral_notification_recipient )
    {
        $data = $request->validated();
        try {
            $referral_notification_recipient->update( $data );
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Referral Creation failed', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
        return response(['referral' =>$referral_notification_recipient ]);
    }

    public function delete(Organization $organization, Program $program,ReferralNotificationRecipient $referral_notification_recipient)
    {
        $referral_notification_recipient->delete();
        return response(['success' => true]);
    }
}
