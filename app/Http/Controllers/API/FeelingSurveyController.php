<?php
namespace App\Http\Controllers\API;

use App\Http\Requests\FeelingSurveyRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Program;

class FeelingSurveyController extends Controller
{
    public function store(FeelingSurveyRequest $request, Organization $organization, Program $program )
    {
        $data = $request->validated();
        $newFeelingSurvey = (new \App\Services\FeelingSurveyService)->submit($organization, $program,
            $data +
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]
        );

        if ( !$newFeelingSurvey )
        {
            return response(['errors' => 'FeelingSurvey creation failed'], 422);
        }

        return response([ 'feelingSurvey' => $newFeelingSurvey ]);
    }
}
