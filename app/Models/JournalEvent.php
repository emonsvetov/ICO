<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class JournalEvent extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = true;

	public static function read_sum_postings_by_account_and_journal_events($account_holder_id, $account_type_name, $journal_event_types, $is_credit) {
		$sql = "
			select
				count(0) as count
				, round(sum(posts.posting_amount * posts.qty),2) as total
	
			from
				accounts a 
				join account_types at on (at.id = a.account_type_id)
				join postings posts on (posts.account_id = a.id)
				join journal_events je on (je.id = posts.journal_event_id)
				join journal_event_types jet on (jet.id = je.journal_event_type_id)
	
			where
				a.account_holder_id = $account_holder_id
				and at.name = '$account_type_name'
				and posts.is_credit = '{$is_credit}'
		";
		if (is_array ( $journal_event_types )) {
			// this not empty check is nested on purpose, otherwise the else gets executed if the array is empty because an empty array is != ""
			if (! empty ( $journal_event_types )) {
				$sql = $sql . " and jet.type in ('" . implode ( "','", $journal_event_types ) . "')";
			}
		} else if ($journal_event_types != "") {
			$sql = $sql . " and jet.type = '{$journal_event_types}'";
		}
		// throw new RuntimeException($sql);
		try {
			$results = DB::select( DB::raw($sql), array(
				//'account_holder_id' => $account_holder_id,
				//'account_type_name' => $account_type_name
			));
		} catch (Exception $e) {
			throw new RuntimeException ( 'Could not get information in  Journal:read_sum_postings_by_account_and_journal_events. DB query failed.', 500 );
		}
		$row  = sizeof($results) ? current($results) : null;
		if (! $row) {
			throw new RuntimeException ( 'Zero row in Journal:read_sum_postings_by_account_and_journal_events', 400 );
		}
		return $row;
	}
}