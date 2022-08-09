<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\InvoiceFilters;
use App\Models\Traits\Filterable;
use App\Models\JournalEventType;
use App\Models\PaymentMethod;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\InvoiceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\Owner;
use DB;

class InvoiceService 
{
    use Filterable, InvoiceFilters;

    public function index( $program, $paginate = true ) {
        $program = self::GetModelByMixed($program);
        if( !$program->exists() ) return;
        // $query = Invoice::query();
        // self::$query = Invoice::whereHas('roles', function (Builder $query) use($program) {
        //     $query->where('name', 'LIKE', config('roles.participant'))
        //     ->where('model_has_roles.program_id', $program->id);
        // });
        // pr(DB::enableQueryLog());
        $query = self::filterable(Invoice::class);
        $query = $query->where('program_id', $program->id);
        if( $paginate ) {
            $invoices = $query->paginate( self::$PARAMS['limit'] );
        }   else    {
            $invoices = $query->get();
        }
        // pr(DB::getQueryLog());
        return $invoices;
    }

	public function createOnDemand($data, $program) {
		// payment method
        $amount = $data['amount'];
		$payment_method_id = PaymentMethod::getPaymentMethodCheck(true);
		$deposit_fee = $program->deposit_fee / 100.0;
		$deposit_fee_amount = $deposit_fee * $amount;
		// create a new invoice
		$date_begin = $date_end = date ( 'Y-m-d' );
		$days_to_pay = isset($data['days_to_pay']) && $data['days_to_pay'] > 0 ? $data['days_to_pay'] : Invoice::DAYS_TO_PAY;
        $invoice_type_id = InvoiceType::getIdByTypeOnDemand(true);
        $program_id = $program->id;

        $user = auth()->user();

        $invoice_key = $program_id . date('ym', strtotime($date_end));

        $type_on_demand = InvoiceType::getIdByTypeOnDemand(true);
        $type_monthly = InvoiceType::getIdByTypeMonthly(true);
        $type_creditcard = InvoiceType::getIdByTypeCreditCard(true);
        $invoice = null;

        if( $invoice_type_id == $type_on_demand || $invoice_type_id == $type_creditcard )   
        {
            
        }   
        else 
        {
            $query = Invoice::where(['program_id' => $program_id, 'date_end' => $date_end, 'invoice_type_id' => $invoice_type_id]);
            if( $query->count() > 0)
            {
                $invoice = $query->select('id')->first();
            }
        }

        if( !$invoice )   {
            $count = Invoice::where(['program_id' => $program_id])
            ->where('created_at', '<=', now())
            ->count();
            $seq = $count + 1;
            // pr($days_to_pay);
            $date_due_strtotime = strtotime($date_end . " +{$days_to_pay} days");
            // pr($date_due_strtotime);

            $invoice = Invoice::create([
                'program_id' => $program_id,
                'key' => $invoice_key,
                'seq' => $seq,
                'invoice_type_id' => $invoice_type_id,
                'payment_method_id' => $payment_method_id,
                'date_begin' => $date_begin,
                'date_end' => $date_end,
                'date_due' => date ( 'Y-m-d', $date_due_strtotime )
            ]);
        }

        if( $invoice )  {
            $invoice = $this->chargeForMoniesPending($invoice, $user, $program, $amount );
            if ($deposit_fee > 0) {
            	$invoice = $this->chargeForDepositFee ($invoice, $user, $program, $deposit_fee_amount);
            }
        }

		return $invoice;
	}

    public function chargeForMoniesPending($invoice, $user, $program, $amount)    {
        $owner = Owner::first();
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $owner_account_holder_id = ( int ) $owner->account_holder_id;
        $program_account_holder_id = ( int ) $program->account_holder_id;
        $prime_account_holder_id = ( int ) $user->account_holder_id;
        $monies = MediumType::getIdByName('Monies', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Charge program for monies pending', true );
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'prime_account_holder_id' => $prime_account_holder_id,
			'created_at' => now()
		]);
        $postings = Account::postings(
			$program_account_holder_id,
			'Monies Due to Owner',
			$asset,
			$monies,
			$program_account_holder_id,
			'Monies Pending',
			$liability,
			$monies,
			$journal_event_id,
			$amount,
			1, //qty
			null, //medium_info
			null, // medium_info_id
			$currency_id
		);
        if( $postings )   {
            $invoice->journal_events()->sync( [ $journal_event_id ], false);
        }
        return $invoice;
    }

    public function chargeForDepositFee($invoice, $user, $program, $amount)    {
        $owner = Owner::first();
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $owner_account_holder_id = ( int ) $owner->account_holder_id;
        $program_account_holder_id = ( int ) $program->account_holder_id;
        $prime_account_holder_id = ( int ) $user->account_holder_id;
        $monies = MediumType::getIdByName('Monies', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Charge program for deposit fee', true );
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'prime_account_holder_id' => $prime_account_holder_id,
			'created_at' => now()
		]);
        $postings = Account::postings(
			$program_account_holder_id,
			'Monies Due to Owner',
			$asset,
			$monies,
			$program_account_holder_id,
			'Monies Fees',
			$liability,
			$monies,
			$journal_event_id,
			$amount,
			1, //qty
			null, //medium_info
			null, // medium_info_id
			$currency_id
		);
        if( $postings )   {
            $invoice->journal_events()->sync( [ $journal_event_id ], false);
        }
        return $invoice;
    }
}
