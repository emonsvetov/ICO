<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\ReferralRequest;
//use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Referral;
use App\Models\Program;

class ReferralController extends Controller
{
    public function store(ReferralRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        $newReferral = Referral::create(
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
}
