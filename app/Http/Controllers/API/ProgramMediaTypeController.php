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

    public function saveLink(Request $request, Organization $organization, Program $program,ProgramMediaType $programMediaType)
    {       
        try {
            // $programMediaType->menu_link = $request->get('menu_link');
            // $programMediaType->program_media_type_id = $request->get('program_media_type_id');
            // $programMediaType->program_id = $program->id;
            // $programMediaType->name = $request->get('name');
            // $programMediaType->is_menu_item = $request->get('is_menu_item');
            // $programMediaType->save();
            $menu_link = $request->get('menu_link');
            $program_media_type_id = $request->get('program_media_type_id');
            $name = $request->get('name');
            $is_menu_item = $request->get('is_menu_item');

            ProgramMediaType::where('program_media_type_id', $program_media_type_id)
            ->update(['menu_link' => $menu_link, 'name' => $name, 'is_menu_item' => $is_menu_item]);

        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }

        return response()->json($programMediaType);
    }

    public function delete(Request $request, Organization $organization, Program $program, ProgramMediaType $programMediaType) {
        $program_media_type_id = $request->program_media_type_id;

        ProgramMediaType::where('program_media_type_id', $program_media_type_id)
            ->update(['menu_link' => ""]);
        // $programMediaType->delete();
        return response(['deleted' => true]);
    }
}
