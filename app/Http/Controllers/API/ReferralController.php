<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\ReferralRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReferRequest;
use App\Models\Organization;
use App\Models\Program;

class ReferralController extends Controller
{
    public function store(ReferralRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        $newReferral = (new \App\Services\ReferralService)->refer($organization, $program,
            $data +
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]
        );

        if ( !$newReferral )
        {
            return response(['errors' => 'Referral creation failed'], 422);
        }

        return response([ 'referral' => $newReferral ]);
    }

    public function refer(ReferRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        [$newReferral, $newUser] = (new \App\Services\ReferralService)->referParticipant($organization, $program,
            $data +
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]
        );

        if ( !$newUser )
        {
            return response(['errors' => 'Invitation failed'], 422);
        }
        else if ( !$newReferral )
        {
            return response(['errors' => 'Referral creation failed'], 422);
        }

        return response([ 'referral' => $newReferral, 'user' => $newUser ]);
    }
}
