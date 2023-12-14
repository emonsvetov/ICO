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
            $isMenuItem = $request->get('is_menu_item');
            $menu_link =  $request->get('menu_link');
            $programMediaType = ProgramMediaType::create([
                "program_id" => $program->id,
                "name"       => $name,
                "is_menu_item" => $isMenuItem,
                "menu_link" => $menu_link
            ]);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }

        return response()->json($programMediaType);
    }

    public function delete(Organization $organization, Program $program, ProgramMediaType $programMediaType) {
        $programMediaType->delete();
        return response(['deleted' => true]);
    }
}
