<?php
namespace App\Services\reports;

use Illuminate\Support\Facades\DB;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;

class ReportServiceSumBudget extends ReportServiceAbstractBase {

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		// $this->table = 0;
		$this->table = $this->calcByDateRange ( $this->getParams () );
		// if (is_array ( $data ) && count ( $data ) > 0) {
		// 	$this->table = ( int ) $data [0]->{self::FIELD_VALUE};
		// }
	
	}

	/** basic sql without any filters */
    protected function getBaseSql(): String
    {
		$sql = "
                SELECT 
                COALESCE(SUM(budget), 0) as " . self::FIELD_VALUE . "
                FROM
                program_budget
                    ";
		return $sql;
	
	}

	/** get sql where filter
	 * 
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		$where [] = "program_budget.program_id IN (" . implode ( ',', $this->params [self::PROGRAMS] ) . ")";
		if (isset ( $this->params [self::MONTH] ) && ( int ) $this->params [self::MONTH] > 0) {
			$where [] = "program_budget.month = " . $this->params [self::MONTH];
		}
		if (isset ( $this->params [self::YEAR] ) && ( int ) $this->params [self::YEAR] > 0) {
			$where [] = "program_budget.year = " . $this->params [self::YEAR];
		}
		return $where;
	
	}
}