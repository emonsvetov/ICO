<?php

namespace App\Http\Controllers\API;

use App\Models\ProgramMediaType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Program;

class ProgramMediaTypeController extends Controller
{
    public function index(
        Organization $organization,
        Program $program,
        Request $request)
    {
        $media = ProgramMediaType::where([
            'deleted' => 0,
            'program_id' => $program->id
        ])->get();

        if($media->isNotEmpty()){
            return response($media);
        }

        return response([]);
    }

    public function store(Request $request, Organization $organization, Program $program)
    {

        try {
            $name = $request->get('name');
            $programMediaType = ProgramMediaType::create([
                "program_id" => $program->id,
                "name"       => $name
            ]);
        } catch (\Exception $e) {
            return response(['errors' => 11], 422);
        }

        return response()->json($programMediaType);
    }
}
