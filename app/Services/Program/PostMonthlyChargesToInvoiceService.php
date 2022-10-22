<?php
namespace App\Services\Program;

use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\CronInvoice;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Owner;

use App\Services\ProgramService;

class PostMonthlyChargesToInvoiceService
{
    public function __construct(
        ProgramService $programService
    ) {
        $this->programService = $programService;
    }

    public function post()
    {
        $cronInvoices = CronInvoice::getProgramsToPostCharges();

        $last_month = strtotime(date('Y-m') . " -1 month" );
        $date_start = date('Y-m-01', $last_month); 
        $date_end = date('Y-m-t', $last_month);

        foreach($cronInvoices as $cronInvoice)   
        {
            if($cronInvoice->program->is_demo)
            {
                continue;
            }
            // pr($cronInvoice->toArray());
            \Log::info("posting monthly charges to program '{$cronInvoice->program->name}'");
            $this->postMonthlyCharges($cronInvoice->program, $date_start, $date_end);
            $this->markProgramInvoiceProcessed($cronInvoice);
        }
    }

    private function postMonthlyCharges($program, $date_start, $date_end)   
    {
        // \DB::enableQueryLog();
        $countParticipants = $program->getBillableParticipants(true);
        // pr($countParticipants);
        // pr(toSql(\DB::getQueryLog()));
        // exit;
        $countNewParticipants = $program->getBillableParticipants(true, $date_start);
        // pr($countNewParticipants);
        // \DB::enableQueryLog();
        $countManagers = $program->getManagers(true);
        // // pr(toSql(\DB::getQueryLog()));
        // // pr($program->toArray());

        $admin_fee_amount = $program->administrative_fee;
		$admin_fee_quantity = 0;
		$admin_fee_calc = $program->administrative_fee_calculation;
		$fixed_fee = $program->fixed_fee;
		$currency_type = Currency::getIdByType(config('global.default_currency'), true);
        // echo $currency_type;
        // dump($program);
        // dd($admin_fee_calc);
        switch ($admin_fee_calc) 
        {
			case ADMIN_FEE_CALC_PARTICIPANTS :
				$admin_fee_quantity = $countParticipants;
				break;
			case ADMIN_FEE_CALC_UNITS :
				$admin_fee_quantity = count($program->unit_numbers); // get the number of units
				break;
			case ADMIN_FEE_CALC_CUSTOM :
				$admin_fee_quantity = ( float ) $program->administrative_fee_factor;
				$custom_admin_fee = $admin_fee_amount * $program->administrative_fee_factor;
		}
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $usage_fee = $program->monthly_usage_fee / 100.0;
        // dd($usage_fee);
        if ($usage_fee > 0) 
        {
            $posts = JournalEvent::read_sum_postings_by_account_and_journal_events_between (
                $program->account_holder_id, 
                "Monies Fees", 
                array (
                    JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONTHLY_USAGE_FEE 
                ),
                1, //is_credit
                $date_start,
                $date_end . " 23:59:59" 
            );
            if ($posts->count == 0) 
            {
                $gross_awards = JournalEvent::read_sum_postings_by_account_and_journal_events_between(
                    $program->account_holder_id, 
                    "Points Available",
                    array (
                        JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT 
                    ), 
                    1, //is_credit
                    $date_start,
                    $date_end . " 23:59:59" 
                );

                if (isset ( $gross_awards->total ) && (float) $gross_awards->total > 0) 
                {
                    echo "Charging monthly usage fee to " . $program->name . PHP_EOL;
                    $this->programService->chargeForMonthlyUsageFee($program, $gross_awards->total, $usage_fee);
                }
            }
            else
            {
                echo "Monthly usage fee already applied to " . $program->name . PHP_EOL;
            }
        }
        if ($admin_fee_amount > 0 && $admin_fee_quantity > 0) 
        {
            // dd($admin_fee_quantity);
            $posts = JournalEvent::read_sum_postings_by_account_and_journal_events_between (
                $program->account_holder_id, 
                "Monies Fees", 
                array (
                    JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_ADMIN_FEE 
                ),
                1, //is_credit
                $date_start,
                $date_end . " 23:59:59" 
            );
            if ($posts->count == 0) 
            {
                echo "Charging admin fee to " . $program->name . PHP_EOL;
                $this->programService->chargeForAdminFee($program, $admin_fee_amount, $admin_fee_quantity);
            }
        }
        if ($fixed_fee > 0) 
        {
            // dd($admin_fee_quantity);
            $posts = JournalEvent::read_sum_postings_by_account_and_journal_events_between (
                $program->account_holder_id, 
                "Monies Fees", 
                array (
                    JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_FIXED_FEE 
                ),
                1, //is_credit
                $date_start,
                $date_end . " 23:59:59" 
            );
            if ($posts->count == 0) 
            {
                echo "Charging fixed fee to " . $program->name . PHP_EOL;
                $this->programService->chargeForFixedFee($program, $fixed_fee, 1);
            }
        }
    }

    private function markProgramInvoiceProcessed($cronInvoice) 
    {
        $cronInvoice->update(['charges_posted_date' => now()]);
    }
}