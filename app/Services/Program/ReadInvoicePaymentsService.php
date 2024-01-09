<?php
namespace App\Services\Program;

use App\Models\Program;
use App\Models\Invoice;
use App\Models\Domain;
use DB;

class ReadInvoicePaymentsService
{
	public function get(Invoice $invoice) {
		$sql = "
		select
			invoices.id as invoice_id
			, a.account_holder_id as program_account_holder_id
			, account_types.name
			, jet.type as journal_event_type
			, je.created_at
			, je.notes
			, postings.is_credit
			, p.name as program_name
			, sum(postings.posting_amount * postings.qty) as amount
			, je.id as journal_event_id
		from
			invoices
			join invoice_journal_event ijet on (invoices.id = ijet.invoice_id)
			join journal_events je on (ijet.journal_event_id = je.id)
			join journal_event_types jet on (je.journal_event_type_id = jet.id)
			join postings on (postings.journal_event_id = je.id)
			join accounts a on (a.id = postings.account_id)
			join account_types on (a.account_type_id = account_types.id)
			join programs p on (p.account_holder_id = a.account_holder_id)
			left join journal_events reversals on (je.id = reversals.parent_journal_event_id)
		where
			invoices.id = :invoice_id
			and account_types.name = 'Monies Due to Owner'
			and postings.is_credit = 1
			and reversals.id is null
	    group by
	       je.id
    	";

        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07

        try {
			$result = DB::select( DB::raw($sql), array(
				'invoice_id' => $invoice->id,
			));
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get information in  InvoiceService:read_invoice_payments. DB query failed.', 500 );
		}
		return $result;
	}
}
