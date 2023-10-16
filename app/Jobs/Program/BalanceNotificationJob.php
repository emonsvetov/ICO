<?php

namespace App\Jobs\Program;

use App\Mail\templates\BalanceNotificationEmail;
use App\Models\CsvImport;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\Program;
use App\Models\TangoOrdersApi;
use App\Services\AccountService;
use App\Services\AwardService;
use App\Services\CSVimportService;
use Aws\S3\S3Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Event;
use App\Notifications\CSVImportNotification;

use DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use function Ramsey\Uuid\v1;

class BalanceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $errors = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CSVimportService $csvImportService, AwardService $awardService)
    {
        echo PHP_EOL . "Balance Notification cron Started on " . date('Y-m-d h:i:s') . PHP_EOL;

        $rootPrograms = Program::getAllRoot();
        $accountService = new AccountService;

        foreach ($rootPrograms as $rootProgram) {
            $allPrograms = $rootProgram->descendantsAndSelf()->get();

            $lowBalanceEmail = $rootProgram->low_balance_email;
            $data = $managersWithDetails = [];

            foreach ($allPrograms as $subProgram) {
                /** @var $subProgram Program */
                if ($subProgram->balance_threshold <= 0 || ! $subProgram->send_balance_threshold_notification) {
                    continue;
                }

                $currentBalance = $accountService->readAvailableBalanceForProgram($subProgram);
                if ($currentBalance >= $subProgram->balance_threshold) {
                    continue;
                }

                $data = $this->prepareData($data, $subProgram, $lowBalanceEmail, $currentBalance);
                $managersWithDetails = $this->prepareManagersData($managersWithDetails, $currentBalance, $subProgram);
            }

            if ($data) {
                foreach ($data as $dataBalanceEmail => $dataProgramDetails) {
                    $programContent = "";
                    foreach ($dataProgramDetails as $programDetails) {
                        $programContent .= '<p> &nbsp;&nbsp;&nbsp;' . $programDetails['program_name'] . ": $" . number_format($programDetails['balance'],
                                2) . " </p>";
                    }
                    $content = $this->prepareContent($dataBalanceEmail, $programContent);
                    $message = new BalanceNotificationEmail($content);
                    Mail::to($dataBalanceEmail)->send($message);
                }
            } elseif ( ! empty($managersWithDetails)) {
                foreach ($managersWithDetails as $managerWithDetails) {
                    $programContent = "";
                    foreach ($managerWithDetails['balanceDetails'] as $program_details) {
                        $programContent .= '<p> &nbsp;&nbsp;&nbsp;' . $program_details['program_name'] . ": $" . number_format($program_details['balance'],
                                2) . " </p>";
                    }
                    $content = $this->prepareContent($managerWithDetails['first_name'], $programContent);

                    $message = new BalanceNotificationEmail($content);
                    Mail::to($managerWithDetails['email'])->send($message);
                }
            }
        }
        echo PHP_EOL . "END on " . date('Y-m-d h:i:s') . PHP_EOL;
    }

    /**
     * @return array
     */
    private function prepareData($data, $subProgram, $lowBalanceEmail, $currentBalance)
    {
        $balanceEmail = $subProgram->low_balance_email && ($lowBalanceEmail != $subProgram->low_balance_email) ?
            $subProgram->low_balance_email : $lowBalanceEmail;
        if ( ! empty(trim($balanceEmail))) {
            $data[$balanceEmail][] = [
                'balance' => $currentBalance,
                'program_id' => $subProgram->account_holder_id,
                'program_name' => $subProgram->name
            ];
        }
        return $data;
    }

    /**
     * @return array
     */
    private function prepareManagersData($managersWithDetails, $currentBalance, $subProgram)
    {
        $managerData = $subProgram->getActiveManagers();
        if ( ! empty($managerData)) {
            $subProgramAccountHolderId = (int)$subProgram->account_holder_id;
            foreach ($managerData as $manager) {
                if ( ! isset($managersWithDetails[$manager->account_holder_id])) {
                    $managersWithDetails[$manager->account_holder_id] = array(
                        'email' => $manager->email,
                        'first_name' => $manager->first_name,
                        'last_name' => $manager->last_name,
                        'balanceDetails' => []
                    );
                }
                $managersWithDetails[$manager->account_holder_id]['balanceDetails'][$subProgramAccountHolderId] = [
                    'balance' => $currentBalance,
                    'program_id' => $subProgramAccountHolderId,
                    'program_name' => $subProgram->name
                ];
            }
        }
        return $managersWithDetails;
    }

    /**
     * @return string
     */
    private function prepareContent($userName, $programContent)
    {
        return "<p>Dear {$userName},</p>
            <p>You are receiving this message because the program funding balances are lower than the minimum threshold that is designated for your rewards program.</p>
            {$programContent}
            <p>If you have any questions, please complete</p>
            <p>Sincerely,</p>
            <p>Rewards Program Administrator</p>";
    }

}
