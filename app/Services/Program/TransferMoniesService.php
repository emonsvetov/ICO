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
    const TRANSFER_CSV_FROM_HEADER = ["Transfer_from_program_id", "Transfer_from_program_external_id", "Transfer_from_program_name"];
    const TRANSFER_CSV_TO_HEADER = ["Transfer_to_program_id", "Transfer_to_program_external_id", "Transfer_to_program_name", "Amount"];

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
        $balance = (new \App\Services\AccountService)->readAvailableBalanceForProgram ( $program );
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
                $balance = (new \App\Services\AccountService)->readAvailableBalanceForProgram ( $program );
                if ($amount > $balance) {
                    throw new \RuntimeException ( "Account balance has insufficient funds to transfer $" . $amount, 400 );
                }
                $user_account_holder_id = auth()->user()->account_holder_id;
                $program_account_holder_id = $program->account_holder_id;
                $new_program_account_holder_id = $program->where('id', $programId)->first()->account_holder_id;
                $result[$programId] = $this->transferMonies($user_account_holder_id, $program_account_holder_id, $new_program_account_holder_id, $amount);
            }
            if( sizeof($data["amounts"]) == sizeof($result))    {
                $balance = (new \App\Services\AccountService)->readAvailableBalanceForProgram ( $program );
                return ['success'=>true, 'transferred' => $result, 'balance' => $balance];
            }
        }
    }
    public function getTransferTemplateCSV( Program $program )   {
        $transferData = (object) $this->getTransferMoniesByProgram($program);
        $csv = array ();

        // Add the section for the transfer from
        $csvTransferFromRow = self::TRANSFER_CSV_FROM_HEADER;
        $csv[] = $csvTransferFromRow; //csv header row

        $csv[] = [ $program->id, $program->external_id, $program->name, ""]; //

        $csvTransferToRow = self::TRANSFER_CSV_TO_HEADER;
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

    public function transferMoniesByCSVUpload(Program $program, $supplied_constants) {
        $validated = $this->validate_CSVUpload( $supplied_constants );
        if( empty($validated['from']) || empty($validated['to']))   {
            throw new \RuntimeException ( "Invalid monies transfer request. ", 400 );
        }
        if( $program->id !== (int) $validated['from']->id)  {
            throw new \RuntimeException ( "Invalid monies transfer request. Program mismatch", 400 );
        }
        $data = [];
        foreach( $validated['to'] as $toProgram)   {
            $data['amounts'][$toProgram->id] = $toProgram->Amount;
        }
        if( !empty( $data ) && !empty($data['amounts']) )    {
            return $this->submitTransferMonies($program, $data);
        }
    }

    private function validate_CSVUpload( $supplied_constants = [] )  {
         try {
            $csvImportRequest = app('App\Http\Requests\TransferMoniesCSVRequest');
            $validated = $csvImportRequest->validated();

            $file = $validated['upload-file'];
            $fromFields = self::TRANSFER_CSV_FROM_HEADER;
            $toFields = self::TRANSFER_CSV_TO_HEADER;
            if ( $file instanceof \Illuminate\Http\UploadedFile )
            {
                $fromProgram = [];
                $toProgram = [];
                $filepath = $file->getRealPath();
                $handle = fopen($filepath, 'r');
                $headersFrom = [];
                $headersTo = [];
                $line = 0;
                $errors = [];
                if ($handle)
                {
                    while ( ($filedata = fgetcsv($handle)) !== FALSE )
                    {
                        if ($line == 0)
                        {
                            foreach ($filedata as $key => $value)
                            {
                                $headersFrom[trim($value)] = $key;
                            }
                            $line++;
                            continue;
                        }

                        if ($line == 1)
                        {
                            // $fromRules = $csvImportRequest->fromRules(); //TODO, dont remove
                            foreach( $fromFields as $csvField )    {
                                $csvFieldValue = isset($headersFrom[$csvField]) ? trim($filedata[$headersFrom[$csvField]]) : NULL;
                                $fromProgram[str_replace('Transfer_from_program_', '', $csvField)] = $csvFieldValue;
                            }
                            $line++;
                            continue;
                        }

                        if ($line == 2)
                        {
                            foreach ($filedata as $key => $value)
                            {
                                $headersTo[trim($value)] = $key;
                            }
                            $line++;
                            continue;
                        }

                        if ($line > 2)
                        {
                            // $toRules = $csvImportRequest->toRules(); //TODO, dont remove
                            $toProgramRow = [];
                            foreach( $toFields as $csvField )    {
                                $csvFieldValue = isset($headersTo[$csvField]) ? trim($filedata[$headersTo[$csvField]]) : NULL;
                                $toProgramRow[str_replace('Transfer_to_program_', '', $csvField)] = $csvFieldValue;
                            }
                            $toProgram[] = (object) $toProgramRow;
                            $line++;
                            continue;
                        }
                    }
                }
                if($fromProgram &&  $toProgram) {
                    return ['from'=> (object) $fromProgram, 'to'=>$toProgram];
                }
            }
        }
        catch (\Throwable $e)
        {
            $errors = 'TransferMoniesService error: ' . $e->getMessage() . ' in line ' . $e->getLine();
            return response(['errors'=> 'Error Monies Tranfer via CSV Import' ,'e' => $errors], 422);
        }
    }
}
