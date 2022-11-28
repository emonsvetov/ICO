<?php

namespace App\Http\Controllers\API;

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

class ProgramController extends Controller
{
    public function index( Organization $organization, ProgramService $programService)
    {
        $programs = $programService->index( $organization );

        if ( $programs->isNotEmpty() )
        {
            return response( $programs );
        }

        return response( [] );
    }

    public function store(ProgramRequest $request, Organization $organization)
    {
        if ( $organization )
        {
            $newProgram = Program::createAccount(
                $request->validated() +
                ['organization_id' => $organization->id]
            );
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        if ( !$newProgram )
        {
            return response(['errors' => 'Program Creation failed'], 422);
        }

        ProgramCreated::dispatch( $newProgram );

        return response([ 'program' => $newProgram ]);
    }

    public function show( Organization $organization, Program $program )
    {

        if ( $program )
        {
            $program->load(['domains', 'merchants', 'template', 'organization', 'address']);
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
