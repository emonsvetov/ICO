<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AwardRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Award;
Use Exception;

class AwardController extends Controller
{
    public function store(AwardRequest $request, Organization $organization, Program $program )
    {
        $newAward = Award::create(
            (object) ($request->validated() + 
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]),
            $program,
            auth()->user()
        );

        if ( !$newAward )
        {
            return response(['errors' => 'Award creation failed'], 422);
        }

        return $newAward;

        return response([ 'award' => $newAward ]);
    }
}
