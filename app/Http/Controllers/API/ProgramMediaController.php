<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramMediaRequest;
use App\Models\ProgramMedia;
use App\Models\ProgramMediaType;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Program;

class ProgramMediaController extends Controller
{
    public function index(
        Organization $organization,
        Program $program,
        ProgramMediaType $programMediaType,
        Request $request
    ) {
        $programMedia = ProgramMedia::where([
            'program_id' => $program->id,
            'program_media_type_id' => $programMediaType->program_media_type_id,
            'deleted' => 0
        ])->orderBy('created_at', 'desc')->get();

        if ($programMedia->isNotEmpty()) {
            return response($programMedia);
        }

        return response([]);
    }

    public function upload(Request $request, Organization $organization, Program $program)
    {
        try {
            $responseFiles = [];
            if ($request->has('file') && $request->has('fileId')) {
                $file = $request->file('file');
                $fileId = $request->get('fileId');
                $name = $file->getClientOriginalName();
                $file->storeAs('programMedia/tmp/' . date('Y-m-d') . '/' . $fileId, $name);
                $responseFiles[] = $file;
            }
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }

        return response()->json($responseFiles);
    }

    private function saveFile($file, $name, $program, $mediaType, $isIcon)
    {
        $pathInfo = pathinfo($file['name']);
        $name = str_replace(" ", "_", $name) . '.' . $pathInfo['extension'];
        $oldPath = 'programMedia/tmp/' . date('Y-m-d') . '/' . $file['id'] . '/' . $file['name'];
        $newPathDir = 'programMedia/' . $program->id . '/' . $mediaType . '/';
        if($isIcon){
            $newPathDir .= 'icon/';
        }
        $newPath = $newPathDir . $name;
        Storage::move($oldPath, $newPath);
        return $newPath;
    }

    public function store(ProgramMediaRequest $request, Organization $organization, Program $program)
    {
        try {
            $file = (array)json_decode($request->get('file'));
            $icon = (array)json_decode($request->get('icon'));
            $name = $request->get('name');
            $mediaType = $request->get('mediaType');
            $filePath = $this->saveFile($file, $name, $program, $mediaType, false);
            $iconPath = $this->saveFile($icon, $name, $program, $mediaType, true);
            $programMedia = ProgramMedia::create([
                "program_id" => $program->id,
                'program_media_type_id' => $mediaType,
                "name"       => $name,
                "icon_path"  => $iconPath,
                "path"       => $filePath
            ]);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }

        return response()->json($programMedia);
    }

    public function delete(Organization $organization, Program $program, ProgramMedia $programMedia )
    {
        if(Storage::exists($programMedia->icon_path)){
            Storage::delete($programMedia->icon_path);
        }
        if(Storage::exists($programMedia->path)){
            Storage::delete($programMedia->path);
        }

        $programMedia->delete();
        return response(['deleted' => true]);
    }
}
