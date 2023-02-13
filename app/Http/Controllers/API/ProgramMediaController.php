<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramMediaRequest;
use App\Models\ProgramMedia;
use App\Models\ProgramMediaType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProgramPaymentReverseRequest;
use App\Http\Requests\ProgramTransferMoniesRequest;
use App\Http\Requests\ProgramPaymentRequest;
use App\Http\Requests\ProgramMoveRequest;
use App\Services\ProgramPaymentService;
use App\Http\Requests\ProgramRequest;
use App\Http\Controllers\Controller;
use App\Services\ProgramService;
use App\Events\ProgramCreated;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\Invoice;
use DB;
use Illuminate\Support\Str;

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
            'program_media_type_id' => $programMediaType->program_media_type_id
        ])->where('deleted', 0)->get();

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

    private function saveFile($file, $name, $program)
    {
        $pathInfo = pathinfo($file['name']);
        $hash = Str::random(40);
        $name = $name ? $name . '.' . $pathInfo['extension'] : $hash . '.' . $pathInfo['extension'];
        $oldPath = 'programMedia/tmp/' . date('Y-m-d') . '/' . $file['id'] . '/' . $file['name'];
        $newPath = 'programMedia/' . $program->id . '/' . $name;
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

            $filePath = $this->saveFile($file, $name, $program);
            $iconPath = $this->saveFile($icon, '', $program);
            $programMedia = ProgramMedia::create([
                "name" => $name,
                "path" => $filePath,
                "program_id" => $program->id
            ]);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }

        return response()->json($programMedia);
    }

    public function delete(Organization $organization, Program $program)
    {
        $program->delete();
        $program->update(['status' => 'deleted']);
        return response(['delete' => true]);
    }

}
