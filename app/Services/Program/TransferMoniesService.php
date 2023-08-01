<?php
namespace App\Services\Program;

// use App\Services\Program\ProgramService;
use App\Services\AccountService;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Account;
use App\Models\Program;


class TransferMoniesService
{
    // private ProgramService $programService;
    private AccountService $accountService;

    public function __construct(
        // ProgramService $programService,
        AccountService $accountService,
    ) {
        // $transferData->programservice = $programService;
        $this->accountService = $accountService;
    }
    public function transferMonies($user_account_holder_id, $program_account_holder_id, $new_program_account_holder_id, $amount)    {
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;
        $journal_event_type_id = JournalEventType::getIdByType( 'Program transfers monies available', true );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);

        //create program postings
        $postings = Account::postings(
            $program_account_holder_id,
            'Monies Available',
            $asset,
            $monies,
            $new_program_account_holder_id,
            'Monies Available',
            $asset,
            $monies,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );
        if( isset($postings['success']) ) {
            return true;
        }
    }
    public function getTransferMoniesByProgram(Program $program)    {
        $topLevelProgram = $program->rootAncestor()->select(['id', 'name', 'external_id'])->first();
        if( !$topLevelProgram ) {
            $topLevelProgram = $program;
        }
        $programs = $topLevelProgram->descendantsAndSelf()->depthFirst()->whereNotIn('id', [$program->id])->select(['id', 'name', 'external_id'])->get();
        $balance = $this->accountService->readAvailableBalanceForProgram ( $program );
        return
            [
                'program' => $program,
                'programs' => $programs,
                'balance' => $balance,
            ]
        ;
    }
    public function submitTransferMonies( Program $program, $data) {
        if(sizeof($data["amounts"]) > 0)    {
            $result = [];
            foreach($data["amounts"] as $programId => $amount)  {
                $balance = $this->accountService->readAvailableBalanceForProgram ( $program );
                if ($amount > $balance) {
                    throw new \RuntimeException ( "Account balance has insufficient funds to transfer $" . $amount, 400 );
                }
                $user_account_holder_id = auth()->user()->account_holder_id;
                $program_account_holder_id = $program->account_holder_id;
                $new_program_account_holder_id = $program->where('id', $programId)->first()->account_holder_id;
                $result[$programId] = $this->transferMonies($user_account_holder_id, $program_account_holder_id, $new_program_account_holder_id, $amount);
            }
            if( sizeof($data["amounts"]) == sizeof($result))    {
                $balance = $this->accountService->readAvailableBalanceForProgram ( $program );
                return ['success'=>true, 'transferred' => $result, 'balance' => $balance];
            }
        }
    }
    public function getTransferTemplateCSV( Program $program )   {
        $transferData = (object) $this->getTransferMoniesByProgram($program);
        $csv = array ();

        // Add the section for the transfer from
        $csvTransferFromRow = ["Transfer_from_program_id", "Transfer_from_program_external_id", "Transfer_from_program_name"];
        $csv[] = $csvTransferFromRow; //csv header row

        $csv[] = [ $program->id, $program->external_id, $program->name, ""]; //

        $csvTransferToRow = ["Transfer_to_program_id", "Transfer_to_program_external_id", "Transfer_to_program_name", "Amount"];
        $csv [] = $csvTransferToRow;

        if( $transferData->programs->isNotEmpty() ) {
            foreach ( $transferData->programs as $_program ) {
                if ($_program->id == $program->id) { //in case
                    continue;
                }
                $programToRow = [ $_program->id, $_program->external_id, $_program->name, 0];
                $csv [] = $programToRow;
            }
        }
        // pr($csv);
        // exit;
        return $csv;
    }

    public function getTransferTemplateCSVStream(Program $program) {
        $csv = $this->getTransferTemplateCSV($program);
        $csvFilename = 'transfer-template-' . $program->id;

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFilename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($csv) {
            $file = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };
        return [$callback, 200, $headers];
    }
}
