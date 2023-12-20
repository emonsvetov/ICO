<?php
namespace App\Services\Report;

use Illuminate\Support\Facades\DB;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;

class ReportServiceCountParticipantsByUserState extends ReportServiceAbstractBase {

	const USER_STATES = "user_states";

	protected $user_states = array ();

	public function __construct($params) {
		parent::__construct ( $params );
		if (isset ( $params [self::USER_STATES] )) {
			// turn user states into an array if it isn't already
			// and set our private variable for it
			$this->user_states = is_array ( $params [self::USER_STATES] ) ? $params [self::USER_STATES] : array (
					$params [self::USER_STATES] 
			);
		}
	
	}

	/** setup default parameters */
	protected function setDefaultParams() {
		parent::setDefaultParams ();
		$this->params [self::USER_STATES] = isset ( $this->user_states ) ? $this->user_states : array (
				"Active" 
		);
		$this->params [self::SQL_GROUP_BY] = array (
				ROLES . ".owner_id" 
		);
	
	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$report = $this->calcByDateRange ( $this->getParams () );
		$this->table = array ();
		// prime the report with zero data
		foreach ( $this->params [self::PROGRAMS] as $index => $program_id ) {
			$this->table [$program_id] = 0;
		}
		// transform the report results
		foreach ( $report as $row ) {
			$this->table [$row->program_id] = $row->count;
		}
	
	}

	/** basic sql without any filters */
	protected function getBaseSql() {
		$sql = "
    		SELECT
					roles.owner_id as program_id
    				, COUNT(DISTINCT.account_holder_id) as count
				FROM
					users
					LEFT JOIN roles ON roles.id = " . ROLES_HAS_USERS . ".roles_id
    		        LEFT JOIN " . ROLE_TYPES . " ON " . ROLE_TYPES . ".id = roles.role_type_id
    	";
		return $sql;
	
	}

	/** get sql where filter
	 * 
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		$where [] = ROLE_TYPES . ".type = '" . ROLE_PARTICIPANT . "'";
		if (isset ( $this->params [self::USER_STATES] ) && count ( $this->params [self::USER_STATES] ) > 0) {
			$where [] = STATE_TYPES_TBL . ".state in ('" . implode ( "','", $this->params [self::USER_STATES] ) . "')";
		}
		if (isset ( $this->params [self::PROGRAMS] ) && count ( $this->params [self::PROGRAMS] ) > 0) {
			// $where[]= ROLES.".owner_id = ".PROGRAMS.".account_holder_id";
			$where [] = ROLES . ".owner_id in (" . implode ( ",", $this->params [self::PROGRAMS] ) . ")";
			// $where[] = "a.account_holder_id IN ('". implode("','", $this->params[self::PROGRAMS]) . "')";
		}
		if (isset ( $this->params [self::DATE_BEGIN] )) {
			$where [] = USERS . ".created  >= '{$this->params[self::DATE_BEGIN]}'";
		}
		if (isset ( $this->params [self::DATE_END] )) {
			$where [] = USERS . ".created  <= '{$this->params[self::DATE_END]}'";
		}
		return $where;
	
	}
}