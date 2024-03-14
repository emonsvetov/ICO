<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

use App\Http\Requests\UnitNumberRequest;
// use App\Services\UnitNumberService;

use App\Models\Organization;
use App\Models\Program;
use App\Models\UnitNumber;

class UnitNumberController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        return response( $program->unit_numbers()->get() );
    }

    public function store(UnitNumberRequest $unitNumberRequest, Organization $organization, Program $program)
    {
        $data = $unitNumberRequest->validated();
        return response( (new \App\Services\UnitNumberService)->create($data + ['program_id' => $program->id]) );
    }

    public function update(UnitNumberRequest $unitNumberRequest, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        $data = $unitNumberRequest->validated();
        return $data;
        return response( (new \App\Services\UnitNumberService)->update($unitNumber, $data) );
    }

    public function delete(UnitNumberRequest $unitNumberRequest, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        $data = $unitNumberRequest->validated();
        return response( (new \App\Services\UnitNumberService)->delete($unitNumber, $data) );
    }
}
