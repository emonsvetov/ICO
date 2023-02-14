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
