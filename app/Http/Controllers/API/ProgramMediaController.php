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
    public function index( Organization $organization, Program $program, ProgramMediaType $programMediaType, Request $request)
    {
        print_r($programMediaType);
        die;

        $programMedia = ProgramMedia::where([
            'program_id' => $program->id,
            'program_media_type_id' => $programMediaType->id
        ])->where('deleted', 0)->get();

        if ( $programMedia->isNotEmpty() )
        {
            return response( $programMedia );
        }

        return response( [] );
    }

    public function store(Request $request, Organization $organization, Program $program)
    {
        try {
            $responseFiles = [];
            if($request->has('file') && $request->has('fileId')) {
                $file = $request->file('file');
                $fileId = $request->get('fileId');
                $name = $file->getClientOriginalName();
                $file->storeAs('programMedia/tmp/'.date('Y-m-d').'/'.$fileId, $name);
                $responseFiles[] = $file;
            } elseif($request->has('submit') && $request->has('files')) {
                $files = $request->get('files');
                foreach ($files as $file){
                    $pathInfo = pathinfo($file['name']);
                    $hash = Str::random(40);
                    $name = $hash.'.'.$pathInfo['extension'];
                    $oldPath = 'programMedia/tmp/'.date('Y-m-d').'/'.$file['id'].'/'.$file['name'];
                    $newPath = 'programMedia/'.$program->id.'/'.$name;
                    Storage::move($oldPath, $newPath);
                    $files[] = ProgramMedia::create([
                        "name" => $name,
                        "path" => $newPath,
                        "program_id" => $program->id
                    ]);
                }
            }

        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }

        return response()->json($responseFiles);
    }

    public function show( Organization $organization, Program $program )
    {

        if ( $program )
        {
            if( !request()->get('only') ){
                $program->load(['domains', 'merchants', 'organization', 'address', 'status']);
            }
            // $program->getTemplate();
            return response( $program );
        }

        return response( [] );
    }

    public function update(ProgramRequest $request, Organization $organization, Program $program, ProgramService $programService )
    {
        $program = $programService->update( $program, $request->validated());
        return response([ 'program' => $program ]);
    }

    public function move(ProgramMoveRequest $request, Organization $organization, Program $program )
    {
        // return $request->all();
        // return $request->validated();
        $program->update( $request->validated() );
        return response([ 'program' => $program ]);
    }

    public function delete(Organization $organization, Program $program )
    {
        $program->delete();
        $program->update(['status'=>'deleted']);
        return response([ 'delete' => true ]);
    }

    public function restore(Organization $organization, Program $program )
    {
        $program->restore();
        $program->update(['status'=>'active']);
        return response([ 'success' => true ]);
    }

    public function getPayments(Organization $organization, Program $program, ProgramPaymentService $programPaymentService)  {
        $payments = $programPaymentService->getPayments($program);
        return response($payments);
    }

    public function submitPayments(ProgramPaymentRequest $request, Organization $organization, Program $program, ProgramPaymentService $programPaymentService)  {
        $result = $programPaymentService->submitPayments($program, $request->validated());
        return response($result);
    }

    public function reversePayment(ProgramPaymentReverseRequest $request, Organization $organization, Program $program, Invoice $invoice, ProgramPaymentService $programPaymentService)  {
        $result = $programPaymentService->reversePayment($program, $invoice, $request->validated());
        return response($result);
    }

    public function getTransferMonies(Organization $organization, Program $program, ProgramService $programService)  {
        $result = $programService->getTransferMonies($program);
        return response($result);
    }

    public function submitTransferMonies(ProgramTransferMoniesRequest $request, Organization $organization, Program $program, ProgramService $programService)  {
        $result = $programService->submitTransferMonies($program, $request->validated());
        return response($result);
    }
}
