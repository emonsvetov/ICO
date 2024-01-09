<?php
namespace App\Services\reports;

class ReportSumProgramAwardsMoniesService extends ReportSumProgramAwardsPointsService
{
	/** get sql where filter
	 *
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		$where [] = "(`atypes`.`name` = 'Monies Awarded')";
		$where [] = "(`jet`.`type` = 'Award monies to recipient')";
		$where [] = "`posts`.`is_credit` = 1";
		if (isset ( $this->params [self::DATE_BEGIN] ) && $this->params [self::DATE_BEGIN] != '') {
			$where [] = "posts.created_at >= '{$this->params[self::DATE_BEGIN]}'";
		}
		if (isset ( $this->params [self::DATE_END] ) && $this->params [self::DATE_END] != '') {
			$where [] = "posts.created_at <= '{$this->params[self::DATE_END]}'";
		}
		if (is_array ( $this->params [self::PROGRAMS] ) && count ( $this->params [self::PROGRAMS] ) > 0) {
			$where [] = "p.id IN (" . implode ( ',', $this->params [self::PROGRAMS] ) . ")";
		}
		return $where;
	}
}
