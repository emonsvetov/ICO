<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

use App\Http\Requests\UnitNumberAssignRequest;
use App\Http\Requests\UnitNumberRequest;
// use App\Services\UnitNumberService;

use App\Models\Organization;
use App\Models\Program;
use App\Models\UnitNumber;

class UnitNumberController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        $query =  $program->unit_numbers()->withCount('users');
        $assignable = request()->get('assignable', false);
        if( $assignable && !$program->allow_multiple_participants_per_unit )    {
            $query =  $query->having('users_count', '=', 0);
        }
        return response( $query->get() );
    }

    public function store(UnitNumberRequest $unitNumberRequest, Organization $organization, Program $program)
    {
        $data = $unitNumberRequest->validated();
        return response( (new \App\Services\UnitNumberService)->create($data + ['program_id' => $program->id]) );
    }

    public function show(Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        return response( $unitNumber );
    }

    public function update(UnitNumberRequest $unitNumberRequest, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        $data = $unitNumberRequest->validated();
        return response( (new \App\Services\UnitNumberService)->update($unitNumber, $data) );
    }

    public function delete(UnitNumberRequest $unitNumberRequest, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        $data = $unitNumberRequest->validated();
        return response( (new \App\Services\UnitNumberService)->delete($unitNumber, $data) );
    }

    public function assign(UnitNumberAssignRequest $unitNumberAssignRequest, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        $data = $unitNumberAssignRequest->validated();
        return response( (new \App\Services\UnitNumberService)->assign($unitNumber, $data) );
    }

    public function unassign(UnitNumberAssignRequest $unitNumberAssignRequest, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        $data = $unitNumberAssignRequest->validated();
        return response( (new \App\Services\UnitNumberService)->unassign($unitNumber, $data) );
    }
}
