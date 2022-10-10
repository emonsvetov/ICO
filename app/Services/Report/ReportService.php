<?php
namespace App\Services\Report;

class ReportService {
	public static function GetReport($report_service, $params) {
		$report_handler = new $report_service ( $params );
		return $report_handler->getTable();
	}
}
