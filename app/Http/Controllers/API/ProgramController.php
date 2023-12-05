<?php

namespace App\Http\Controllers\API;

use App\Mail\templates\WelcomeEmail;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\ProgramPaymentReverseRequest;
use App\Http\Requests\ProgramTransferMoniesRequest;
use App\Http\Requests\ProgramPaymentRequest;
use App\Http\Requests\ProgramDepositRequest;
use App\Http\Requests\ProgramMoveRequest;
use App\Services\ProgramPaymentService;
use App\Services\reports\ReportHelper;
use App\Http\Requests\ProgramRequest;
use App\Http\Controllers\Controller;
use App\Services\ProgramService;
use App\Services\AccountService;
use App\Services\AwardService;
use App\Models\SocialWallPost;
use App\Events\ProgramCreated;
use App\Models\ProgramBudget;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Leaderboard;
use App\Models\Giftcode;
use App\Models\Program;
use App\Models\Posting;
use App\Models\Invoice;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ProgramController extends Controller
{
    public function index(Organization $organization, ProgramService $programService, Request $request)
    {
        $programs = $programService->index($organization, $request->all());

        if ($programs->isNotEmpty()) {
            return response($programs);
        }

        return response([]);
    }

    public function all(ProgramService $programService, Request $request)
    {
        $programs = $programService->index(null, $request->all());

        if ($programs->isNotEmpty()) {
            return response($programs);
        }

        return response([]);
    }

    public function store(ProgramRequest $request, Organization $organization, ProgramService $programService)
    {
        if ($organization) {

            if($request->get('account_holder_id')){
                $exists = Program::where('account_holder_id', $request->get('account_holder_id'))->first();
                if ($exists){
                    return response([ 'program' => $exists ]);
                }
            }

            $newProgram = $programService->create(
                $request->validated() +
                [
                    'organization_id' => $organization->id
                ]
            );
        } else {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        if ( ! $newProgram) {
            return response(['errors' => 'Program Creation failed'], 422);
        }

        ProgramCreated::dispatch($newProgram);

        return response(['program' => $newProgram]);
    }

    public function show(Organization $organization, Program $program)
    {

        if ($program) {
            if ( ! request()->get('only')) {
                $program->load(['domains', 'merchants', 'organization', 'address', 'status']);
            }
            // $program->getTemplate();
            return response($program);
        }

        return response([]);
    }

    public function update(
        ProgramRequest $request,
        Organization $organization,
        Program $program,
        ProgramService $programService
    ) {
        $program = $programService->update($program, $request->validated());
        return response(['program' => $program]);
    }

    public function move(ProgramMoveRequest $request, Organization $organization, Program $program)
    {
        // return $request->all();
        // return $request->validated();
        $program->update($request->validated());
        return response(['program' => $program]);
    }

    public function delete(Organization $organization, Program $program)
    {
        $program->delete();
        $program->update(['status' => 'deleted']);
        return response(['delete' => true]);
    }

    public function restore(Organization $organization, Program $program)
    {
        $program->restore();
        $program->update(['status' => 'active']);
        return response(['success' => true]);
    }

    public function getPayments(
        Organization $organization,
        Program $program,
        ProgramPaymentService $programPaymentService
    ) {
        $payments = $programPaymentService->getPayments($program);
        return response($payments);
    }

    public function submitPayments(
        ProgramPaymentRequest $request,
        Organization $organization,
        Program $program,
        ProgramPaymentService $programPaymentService
    ) {
        $result = $programPaymentService->submitPayments($program, $request->validated());
        return response($result);
    }

    public function reversePayment(
        ProgramPaymentReverseRequest $request,
        Organization $organization,
        Program $program,
        Invoice $invoice,
        ProgramPaymentService $programPaymentService
    ) {
        $result = $programPaymentService->reversePayment($program, $invoice, $request->validated());
        return response($result);
    }

    public function getTransferMonies(Organization $organization, Program $program, ProgramService $programService)
    {
        $result = $programService->getTransferMonies($program);
        return response($result);
    }

    public function submitTransferMonies(
        ProgramTransferMoniesRequest $request,
        Organization $organization,
        Program $program,
        ProgramService $programService
    ) {
        $result = $programService->submitTransferMonies($program, $request->validated());
        return response($result);
    }

    public function getBalance(Organization $organization, Program $program, AccountService $accountService)
    {   
        $balance = $accountService->readAvailableBalanceForProgram($program);
        return response($balance);
    }

    public function getBalanceInformation(Organization $organization, Program $program, AccountService $accountService)
    {
        $total_financial_balance = $accountService->readAvailableBalanceForProgram($program);
        $financial_detail = $accountService->readAvailableBalanceForOwner($program);
        return response(
            ["financial_detail" => $total_financial_balance, "total_financial_balance" => $total_financial_balance]
        );
    }

    public function prepareLiveMode(Organization $organization, Program $program, ProgramService $programService, AwardService $awardService)
    {
        try {
            $allPrograms = $program->descendantsAndSelf()->get();
            $allProgramIds = $allPrograms->pluck('id')->toArray();
            $allAccountHolderPrograms = $allPrograms->pluck('account_holder_id')->toArray();

            $socialWalls = SocialWallPost::getCountByPrograms($organization, $allProgramIds);
            $events = Event::getCountByPrograms($organization, $allProgramIds);
            $budget = ProgramBudget::getSumByPrograms($allProgramIds);
            $giftCodes = Giftcode::getCountByPrograms($allProgramIds);
            $invoices = Invoice::getByProgramId($program->id);
            $leaderboards = Leaderboard::getByProgramId($program->id);
            $users = User::getCountByPrograms($allProgramIds);
            $participants = User::getParticipantsByPrograms($allProgramIds);
            $awards = 0;
            foreach ($participants as $participant){
                $eventHistory = $awardService->readEventHistoryByParticipant(
                    $participant->account_holder_id, 99999999, 0
                );
                $awards += $eventHistory['total'];
            }

        } catch (\Exception $exception) {
            return response(['errors' => 'Live Mode failed', 'e' => $exception->getMessage()], 422);
        }

        return [
            'users' => $users,
            'socialWalls' => $socialWalls,
            'events' => $events,
            'budget' => $budget,
            'giftCodes' => $giftCodes,
            'programsAward' => $awards,
            'invoices' => count($invoices),
            'leaderboards' => count($leaderboards),
        ];
    }

    public function liveMode(Organization $organization, Program $program, ProgramService $programService)
    {
        DB::beginTransaction();
        $result = ['success' => false];
        try {
            $demoStart = date('Y-m-d', strtotime($program->created_at)) . ' 00:00:00';
            $allPrograms = $program->descendantsAndSelf()->get();
            $allProgramIds = $allPrograms->pluck('id')->toArray();
            $allAccountHolderPrograms = $allPrograms->pluck('account_holder_id')->toArray();

            $users = User::getAllByPrograms($allProgramIds);
            $userStatus = User::getStatusByName(User::STATUS_DELETED);
            foreach ($users as $item){
                $item->roles()->detach();
                $item->update(['user_status_id' => $userStatus->id]);
            }

            $socialWallsQuery = SocialWallPost::getAllByProgramsQuery($organization, $allProgramIds);
            $socialWallsQuery->delete();

            $budgetQuery = ProgramBudget::getAllByProgramsQuery($allProgramIds);
            $budgetQuery->delete();

            $leaderboards = Leaderboard::getByProgramId($program->id);
            foreach ($leaderboards as $leaderboard){
                /** @var Leaderboard $leaderboard */
                $leaderboardJournalEventQuery = $leaderboard->journal_events();
                Posting::whereIn('journal_event_id', $leaderboardJournalEventQuery->get()->pluck('id'))->delete();
                $leaderboard->journal_events()->delete();
                $leaderboard->leaderboard_journal_event()->delete();
            }
            Leaderboard::whereIn('id', $leaderboards->pluck('id'))->delete();

            $invoices = Invoice::getByProgramId($program->id);
            foreach ($invoices as $invoice){
                /** @var Invoice $invoice */
                $invoicesJournalEventQuery = $invoice->journal_events();
                Posting::whereIn('journal_event_id', $invoicesJournalEventQuery->get()->pluck('id'))->delete();
                $invoice->journal_events()->delete();
                $invoice->invoice_journal_event()->delete();
            }
            Invoice::whereIn('id', $invoices->pluck('id'))->delete();

            $reportHelper = new ReportHelper();
            $programsAward = $reportHelper->awardAuditDelete($allAccountHolderPrograms);
            $programService->deleteAwards($programsAward, $demoStart);

            $giftCodeQuery = Giftcode::getAllByProgramsQuery($allProgramIds);
            $giftCodeQuery->delete();
            $program->update(['is_demo' => 0]);
            DB::commit();
            $result['success'] = true;
        } catch (\Exception $exception){
            DB::rollBack();
            $result['data'] = $exception->getMessage().$exception->getTraceAsString();
        }

        return response($result);
    }

   /**
     * Get (cached) full hierarchy or tree of programs with minimal options
     *
     * @return array
     */

    public function hierarchy(Organization $organization, ProgramService $programService, Request $request)
    {
        if(request()->get('refresh'))
        {
            cache()->forget(Program::CACHE_FULL_HIERARCHY_NAME);
        }

        $result = cache()->remember(Program::CACHE_FULL_HIERARCHY_NAME, 3600, function () use($programService, $organization) {
            return $programService->getHierarchy($organization)->toArray();
        });

        return response($result);
    }
    public function downloadMoneyTranferTemplate(Organization $organization, Program $program, ProgramService $programService)
    {
        return response()->stream( ...($programService->getTransferTemplateCSV($program)) );
    }
    public function deposit(Organization $organization, Program $program, ProgramDepositRequest $request)
    {
        $result = (new \App\Services\Program\Deposit\DepositService)->deposit($program, $request->validated());
        return response( $result );
    }
    public function transferMoniesByTemplate(Organization $organization, Program $program)    {
        $transferMoniesService = app('App\Services\Program\TransferMoniesService');
        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        return $transferMoniesService->transferMoniesByCSVUpload($program, $supplied_constants);
    }
    public function getLedgerCodes(Organization $organization, Program $program, ProgramService $programService)    {
        return $programService->getLedgerCodes($program);
    }
}
