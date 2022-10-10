<?php
namespace App\Services\Report;

use App\Services\Report\ReportServiceInterface;
// use Illuminate\Database\Eloquent\Builder;
// use App\Models\Traits\Filterable;
// use App\Models\JournalEventType;
// use App\Models\PaymentMethod;
// use App\Models\JournalEvent;
// use App\Models\FinanceType;
// use App\Models\InvoiceType;
// use App\Models\MediumType;
// use App\Models\Currency;
// use App\Models\Program;
// use App\Models\Invoice;
// use App\Models\Account;
// use App\Models\Owner;
use DB;

abstract class ReportServiceAbstractBase implements ReportServiceInterface
{
	const DATE_BEGIN = 'dateBegin';

	const DATE_END = 'dateEnd';

	const ACCOUNT_HOLDER_IDS = "account_holder_ids";

	const PROGRAMS = 'program_ids';

	const MERCHANTS = 'merchant_ids';

	const ACCOUNT_TYPES = "account_types";

	const JOURNAL_EVENT_TYPES = "journal_event_types";

	const AWARD_LEVEL_IDS = "award_level_ids";

	const AWARD_LEVEL_NAMES = "award_level_names";

	const USERS = 'user_ids';

	const MONTH = 'month';

	const YEAR = 'year';

	const SQL_WHERE = 'where';

	const SQL_GROUP_BY = 'group';

	const SQL_ORDER_BY = 'order';

	const SQL_ORDER_BY_DIR = 'dir';

	const SQL_LIMIT = 'limit';

	const SQL_LIMIT_START = 'limitStart';

	const SQL_OFFSET = 'offset';

	const FIELD_COUNT = 'count';

	const FIELD_ID = "account_holder_id";
 // Main key used for organizing the return data. Change this in the sub class is different
	const FIELD_VALUE = "value";

	const FIELD_TOTAL = "total";

	const FIELD_ACCOUNT_TYPE = "account_type_name";

	const FIELD_JOURNAL_EVENT_TYPE = "journal_event_type";

	const FIELD_MONTH = "month";

	const FIELD_YEAR = "year";

	const PROGRAM_ACTIVE = 'program_active';

	const FIELD_REPORT_KEY = 'report_key';

	const MERCHANTS_ACTIVE = 'merchants_active';

	const RECLAIM_DETAILS = 'reclaim_details';

        protected $table;

	protected $params = array ();

	protected $account_holder_ids;

	protected $account_types;

	protected $journal_event_types;

	protected $userList;

	protected $merchantList;

	protected $programList;

	protected $awardLevelsList;

	protected $timestampFrom;

	protected $timestampTo;

	protected $order_by;

	protected $order_dir;

	protected $limit;

	protected $offset;

	protected $group_by;

	protected $month;

	protected $year;

	protected $report_key;

	protected $_ci;

	/** Generate data table to Portfolio Status Report
	 * 
	 * @param array $propertyList
	 *        list of properties
	 * @param string $timestampFrom
	 *        timestamp from date
	 * @param string $timestampTo
	 *        timestamp to date */
	public function __construct($params = array()) {
		// Attempt to load all of the usual params that could be passed in
		if (isset ( $params [self::DATE_BEGIN] )) {
			$timestampFrom = date ( 'Y-m-d H:i:s', strtotime ( $params [self::DATE_BEGIN] ) );
			$this->timestampFrom = $timestampFrom;
		}
		if (isset ( $params [self::DATE_END] )) {
			$timestampTo = date ( 'Y-m-d H:i:s', strtotime ( $params [self::DATE_END] ) );
			$this->timestampTo = $timestampTo;
		}
		if (isset ( $params [self::PROGRAMS] )) {
			$this->programList = is_array ( $params [self::PROGRAMS] ) ? $params [self::PROGRAMS] : array (
					$params [self::PROGRAMS] 
			);
		}
		if (isset ( $params [self::MERCHANTS] )) {
			$this->merchantList = is_array ( $params [self::MERCHANTS] ) ? $params [self::MERCHANTS] : array (
					$params [self::MERCHANTS] 
			);
		}
		if (isset ( $params [self::USERS] )) {
			$this->userList = is_array ( $params [self::USERS] ) ? $params [self::USERS] : array (
					$params [self::USERS] 
			);
		}
		if (isset ( $params [self::AWARD_LEVEL_IDS] )) {
			$this->awardLevelsList = is_array ( $params [self::AWARD_LEVEL_IDS] ) ? $params [self::AWARD_LEVEL_IDS] : array (
					$params [self::AWARD_LEVEL_IDS] 
			);
		}
		if (isset ( $params [self::AWARD_LEVEL_NAMES] )) {
			$this->awardLevelsNamesList = is_array ( $params [self::AWARD_LEVEL_NAMES] ) ? $params [self::AWARD_LEVEL_NAMES] : array (
					$params [self::AWARD_LEVEL_NAMES] 
			);
		}
		if (isset ( $params [self::SQL_LIMIT] )) {
			$this->limit = $params [self::SQL_LIMIT];
		}
		if (isset ( $params [self::MONTH] )) {
			$this->month = $params [self::MONTH];
		}
		if (isset ( $params [self::YEAR] )) {
			$this->year = $params [self::YEAR];
		}
		if (isset ( $params [self::SQL_OFFSET] )) {
			$this->offset = $params [self::SQL_OFFSET];
		}
		if (isset ( $params [self::SQL_ORDER_BY] )) {
			$this->order_by = is_array ( $params [self::SQL_ORDER_BY] ) ? $params [self::SQL_ORDER_BY] : array (
					$params [self::SQL_ORDER_BY] 
			);
		}
		if (isset ( $params [self::SQL_ORDER_BY_DIR] )) {
			$this->order_dir = $params [self::SQL_ORDER_BY_DIR];
		}
		if (isset ( $params [self::SQL_GROUP_BY] )) {
			$this->group_by = is_array ( $params [self::SQL_GROUP_BY] ) ? $params [self::SQL_GROUP_BY] : array (
					$params [self::SQL_GROUP_BY] 
			);
		}
		if (isset ( $params [self::ACCOUNT_HOLDER_IDS] )) {
			$this->account_holder_ids = is_array ( $params [self::ACCOUNT_HOLDER_IDS] ) ? $params [self::ACCOUNT_HOLDER_IDS] : array (
					$params [self::ACCOUNT_HOLDER_IDS] 
			);
		}
		if (isset ( $params [self::ACCOUNT_TYPES] )) {
			$this->account_types = is_array ( $params [self::ACCOUNT_TYPES] ) ? $params [self::ACCOUNT_TYPES] : array (
					$params [self::ACCOUNT_TYPES] 
			);
		}
		if (isset ( $params [self::JOURNAL_EVENT_TYPES] )) {
			$this->journal_event_types = is_array ( $params [self::JOURNAL_EVENT_TYPES] ) ? $params [self::JOURNAL_EVENT_TYPES] : array (
					$params [self::JOURNAL_EVENT_TYPES] 
			);
		}
		if (isset ( $params [self::FIELD_REPORT_KEY] )) {
			$this->report_key = $params [self::FIELD_REPORT_KEY];
		}
		if (isset ( $params [self::MERCHANTS_ACTIVE] )) {
			$this->merchants_active = $params [self::MERCHANTS_ACTIVE];
		}
		if (isset ( $params [self::RECLAIM_DETAILS] )) {
			$this->reclaim_details = $params [self::RECLAIM_DETAILS];
		}
	}
	
	/** set parameters for base query
	 * 
	 * @param array $args         */
	public function setParams(Array $args) {
		$this->params = $args;
	
	}

	/** get base query parameters
	 * if parameters is not specify then using default parameters
	 * 
	 * @return array */
	public function getParams() {
		if ($this->params == null) {
			$this->setDefaultParams ();
		}
		return $this->params;
	
	}

	/** Data table
	 * 
	 * @return array data table */
	public function getTable() {
		if (is_null ( $this->table )) {
			$this->calc ();
		}
		return $this->table;
	
	}

	public function getTimestampFrom() {
		return $this->timestampFrom;
	
	}

	public function getTimestampTo() {
		return $this->timestampTo;
	
	}

	/** setup default parameters */
	protected function setDefaultParams() {
		if (isset ( $this->timestampFrom ) && $this->timestampFrom != '') {
			$this->params [self::DATE_BEGIN] = date ( 'Y-m-d 00:00:00', strtotime ( $this->timestampFrom ) );
		} else {
			$this->params [self::DATE_BEGIN] = '';
		}
		if (isset ( $this->timestampTo ) && $this->timestampTo != '') {
			$this->params [self::DATE_END] = date ( 'Y-m-d 23:59:59', strtotime ( $this->timestampTo ) );
		} else {
			$this->params [self::DATE_END] = '';
		}
		$this->params [self::PROGRAMS] = isset ( $this->programList ) ? $this->programList : array ();
		$this->params [self::MERCHANTS] = isset ( $this->merchantList ) ? $this->merchantList : array ();
		$this->params [self::USERS] = isset ( $this->userList ) ? $this->userList : array ();
		$this->params [self::ACCOUNT_HOLDER_IDS] = isset ( $this->account_holder_ids ) ? $this->account_holder_ids : array ();
		$this->params [self::AWARD_LEVEL_IDS] = isset ( $this->awardLevelsList ) ? $this->awardLevelsList : array ();
		$this->params [self::AWARD_LEVEL_NAMES] = isset ( $this->awardLevelsNamesList ) ? $this->awardLevelsNamesList : array ();
		$this->params [self::JOURNAL_EVENT_TYPES] = isset ( $this->journal_event_types ) ? $this->journal_event_types : array ();
		$this->params [self::ACCOUNT_TYPES] = isset ( $this->account_types ) ? $this->account_types : array ();
		$this->params [self::SQL_GROUP_BY] = isset ( $this->group_by ) ? $this->group_by : array ();
		$this->params [self::SQL_ORDER_BY] = isset ( $this->order_by ) ? $this->order_by : array ();
		$this->params [self::SQL_ORDER_BY_DIR] = isset ( $this->order_dir ) ? $this->order_dir : 'ASC';
		$this->params [self::SQL_LIMIT] = isset ( $this->limit ) ? $this->limit : '';
		$this->params [self::SQL_OFFSET] = isset ( $this->offset ) ? $this->offset : '';
		$this->params [self::MONTH] = isset ( $this->month ) ? $this->month : '';
		$this->params [self::YEAR] = isset ( $this->year ) ? $this->year : '';
		$this->params [self::FIELD_REPORT_KEY] = isset ( $this->report_key ) ? $this->report_key : '';
		$this->params [self::MERCHANTS_ACTIVE] = isset ( $this->merchants_active ) ? $this->merchants_active : false;
		$this->params [self::RECLAIM_DETAILS] = isset ( $this->reclaim_details ) ? $this->reclaim_details : true;

	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$data = $this->calcByDateRange ( $this->getParams () );
		if (count ( $data ) > 0) {
			foreach ( $data as $row ) {
				foreach ( $row as $key => $val ) {
					$this->table [$row->{$this::FIELD_ID}] [$key] = $val;
				}
			}
		}
	}

	/** basic sql without any filters
	 * 
	 * @throws AppException */
	protected function getBaseSql() {
		throw new \RuntimeException ( get_class ( $this ) . ' > getBaseSql is not implemented' );
	
	}

	/** get sql where filter
	 * 
	 * @return array */
	protected function getWhereFilters() {
		return array ();
	}

	/** Calculate full data */
	protected function calc() {
		$this->table = array ();
		$this->getDataDateRange ();
	
	}

	/** Calculate simple data using query and filters
	 * 
	 * @param array $params
	 *        arguments to query
	 * @return array */
	protected function calcByDateRange(Array $params) {
		$sql = $this->getBaseSql ();
		$sql = $this->addSqlFilters ( $sql );
		$res = $this->doSql ( $sql );
		return $res;
	
	}

	protected function selectQuery($sql) {
		// pr($sql);
		// exit;
		try {
				$result = DB::select( DB::raw($sql), [
			]);
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get information in  ReportServiceAbstractBase:selectQuery. DB query failed.', 500 );
		}
		return $result;
	}

	/** execute sql query
	 * 
	 * @param string $sql        
	 * @return array
	 * @throws AppException when error in sql query */
	protected function doSql($sql) {
		$res = $this->selectQuery ( $sql );
		if ($res === false) {
			throw new RuntimeException ( get_class ( $this ) . '::doSql > Error in SQL' );
		}
		return $res;
	
	}

	/** add sql filters to sql string
	 * 
	 * @param string $sql
	 *        sql-string
	 * @return string */
	protected function addSqlFilters($sql) {
		$this->params [self::SQL_WHERE] = $this->getWhereFilters ();
		if (isset ( $this->params [self::SQL_WHERE] ) && (count ( $this->params [self::SQL_WHERE] ))) {
			$sql .= ' WHERE ' . implode ( ' AND ', $this->params [self::SQL_WHERE] );
		}
		if (isset ( $this->params [self::SQL_GROUP_BY] ) && (count ( $this->params [self::SQL_GROUP_BY] ))) {
			$sql .= ' GROUP BY ' . implode ( ',', $this->params [self::SQL_GROUP_BY] );
		}
		if (isset ( $this->params [self::SQL_ORDER_BY] ) && (count ( $this->params [self::SQL_ORDER_BY] ))) {
			$sql .= ' ORDER BY ' . implode ( ',', $this->params [self::SQL_ORDER_BY] ) . ' ' . $this->params [self::SQL_ORDER_BY_DIR];
		}
		if (isset ( $this->params [self::SQL_LIMIT] ) && (( int ) $this->params [self::SQL_LIMIT]) > 0) {
			$sql .= ' LIMIT ';
			if (isset ( $this->params [self::SQL_OFFSET] ) && (( int ) $this->params [self::SQL_OFFSET]) > 0) {
				$sql .= ' ' . $this->params [self::SQL_OFFSET] . ', ';
			}
			$sql .= $this->params [self::SQL_LIMIT];
		}
		return $sql;
	
	}
}
