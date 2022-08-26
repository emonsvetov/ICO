<?php
namespace App\Models;

use App\Services\Report\ReportServiceJournalDetail;
use App\Services\Report\ReportServiceAbstractBase;
use App\Services\Report\ReportService;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = null;
    protected $guarded = [];

    public function read_journal_entry_detail($program_ids = array(), $start_date, $end_date) {
		$params = array ();
		$params [ReportServiceAbstractBase::PROGRAMS] = $program_ids;
		$params [ReportServiceAbstractBase::DATE_BEGIN] = $start_date . ' 00:00:00';
		$params [ReportServiceAbstractBase::DATE_END] = $end_date . ' 23:59:59';
		$report = ReportService::GetReport ( ReportServiceJournalDetail::class, $params );
		return $report;
	}
}
