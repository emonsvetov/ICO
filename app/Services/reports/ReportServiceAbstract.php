<?php

namespace App\Services\reports;

use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class ReportServiceAbstract
{
    const DATE_FROM = 'dateFrom';
    const DATE_TO = 'dateTo';
    const YEAR = 'year';
    const MONTH = 'month';
    const CODES = 'codes';
    const INVENTORY_TYPE = 'inventoryType';
    const ORDER_STATUS = 'orderStatus';
    const PURCHASE_BY_V2 = 'purchaseByV2';
    const KEYWORD = 'keyword';
    const DATE_BEGIN = 'from';
    const DATE_END = "to";
    const SQL_LIMIT = 'limit';
    const SQL_OFFSET = 'offset';
    const SQL_GROUP_BY = 'group';
    const SQL_ORDER_BY = 'order';
    const FIELD_ID = "account_holder_id";
    const FIELD_VALUE = "value";
    const FIELD_MONTH = "month";
    const FIELD_JOURNAL_EVENT_TYPE = "journal_event_type";
    const FIELD_COUNT = "count";
    const PROGRAM_ID = 'programId';
    const PROGRAM_ACCOUNT_HOLDER_ID = 'program_account_holder_id';
    const CREATED_ONLY = 'createdOnly';
    const PROGRAMS = 'programs';
    const PROGRAM_IDS = 'program_ids';
    const PROGRAM_ACCOUNT_HOLDER_IDS = 'program_account_holder_ids';
    const AWARD_LEVEL_NAMES = "award_level_names";
    const EXPORT_CSV = 'exportToCsv';
    const MERCHANTS = 'merchants';
    const MERCHANTS_ACTIVE = 'active';

    const FIELD_REPORT_KEY = 'reportKey';

    const JOURNAL_EVENT_TYPES = "journal_event_types";
    const FIELD_ACCOUNT_TYPE = "account_type_name";
    const ACCOUNT_HOLDER_IDS = "account_holder_ids";
    const USER_ACCOUNT_HOLDER_ID = "user_account_holder_id";
    const USER_ID = "user_id";
    const ACCOUNT_TYPES = "account_types";
    const SERVER = "server";
    const SQL_WHERE = 'where';
    const SQL_ORDER_BY_DIR = 'dir';

    const FIELD_TOTAL = "total";
    const ACCOUNT_TYPE_MONIES_DUE_TO_OWNER = "Monies Due to Owner";
    const ACCOUNT_TYPE_MONIES_AVAILABLE = "Monies Available";
    const ACCOUNT_TYPE_POINTS_REDEEMED = "Points Redeemed";
    const ACCOUNT_TYPE_MONIES_REDEEMED = "Monies Redeemed";
    const ACCOUNT_TYPE_MONIES_EXPIRED = 'Monies Expired';
    const ACCOUNT_TYPE_POINTS_EXPIRED = "Points Expired";

    const JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT = "Award points to recipient";
    const JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT = "Award monies to recipient";
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS = 'Program pays for points';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING = "Program pays for monies pending";
    const JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES = "Redeem points for gift codes";
    const JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING = "Redeem points for international shopping";
    const JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES = "Redeem monies for gift codes";
    const JOURNAL_EVENT_TYPES_EXPIRE_POINTS = "Expire points";
    const JOURNAL_EVENT_TYPES_EXPIRE_MONIES = "Expire monies";
    const JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS = "Deactivate points";
    const JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES = "Deactivate monies";
    const JOURNAL_EVENT_TYPES_RECLAIM_POINTS = "Reclaim points";
    const JOURNAL_EVENT_TYPES_RECLAIM_MONIES = "Reclaim monies";
    const JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS = "Reversal program pays for points";
    const JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING = "Reversal program pays for monies pending";
    const ACCOUNT_TYPE_POINTS_AWARDED = "Points Awarded";
    protected array $params;
    protected array $table = [];
    protected bool $isExport = false;
    protected $query = null;
    const PAGINATE = 'paginate';
    const IS_CREDIT = "is_credit";

    /**
     * @var ReportHelper|null
     */
    protected $reportHelper;

    public function __construct(array $params = [])
    {
        DB::statement("SET SQL_MODE=''"); //TODO - To be removed, correct query instead

        $this->params[self::DATE_FROM] = $this->convertDate($params[self::DATE_FROM] ?? '');
        $this->params[self::DATE_BEGIN] =   $this->convertDate($params[self::DATE_BEGIN] ?? '');
        $this->params[self::DATE_TO] = $this->convertDate($params[self::DATE_TO] ?? '', false);
        $this->params[self::DATE_END] =   $this->convertDate($params[self::DATE_END] ?? '', false);
        $this->params[self::SQL_LIMIT] = $params[self::SQL_LIMIT] ?? null;
        $this->params[self::SQL_OFFSET] = $params[self::SQL_OFFSET] ?? null;
        $this->params[self::EXPORT_CSV] = $params[self::EXPORT_CSV] ?? null;
        $this->params[self::MERCHANTS] = isset($params[self::MERCHANTS]) && is_array($params[self::MERCHANTS]) ? $params[self::MERCHANTS] : [];
        $this->params[self::MERCHANTS_ACTIVE] = $params[self::MERCHANTS_ACTIVE] ?? null;
        $this->params[self::FIELD_REPORT_KEY] = $params[self::FIELD_REPORT_KEY] ?? null;
        $this->params[self::USER_ID] = $params[self::USER_ID] ?? null;
        $this->params[self::PROGRAM_ID] = $params[self::PROGRAM_ID] ?? null;
        $this->params[self::PROGRAM_ACCOUNT_HOLDER_ID] = $params[self::PROGRAM_ACCOUNT_HOLDER_ID] ?? null;
        $this->params[self::USER_ACCOUNT_HOLDER_ID] = $params[self::USER_ACCOUNT_HOLDER_ID] ?? null;
        $this->params[self::CREATED_ONLY] = $params[self::CREATED_ONLY] ?? null;
        $this->params[self::SQL_GROUP_BY] = $params[self::SQL_GROUP_BY] ?? null;
        $this->params[self::SQL_ORDER_BY_DIR] = $params[self::SQL_ORDER_BY_DIR] ?? null;
        $this->params[self::SQL_ORDER_BY] = $params[self::SQL_ORDER_BY] ?? null;
        $this->params[self::PAGINATE] = $params[self::PAGINATE] ?? null;
        $this->params[self::PROGRAMS] = isset($params[self::PROGRAMS]) && is_array($params[self::PROGRAMS]) ? $params[self::PROGRAMS] : [];
        $this->params[self::PROGRAM_ACCOUNT_HOLDER_IDS] =  $this->params[self::PROGRAMS];
        $this->params[self::PROGRAM_IDS] = $this->params[self::PROGRAMS] ? Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get()->pluck('id')->toArray() : [];
        $this->params[self::SERVER] = $params[self::SERVER] ?? null;
        $this->params[self::YEAR] = $params[self::YEAR] ?? null;
        $this->params[self::MONTH] = $params[self::MONTH] ?? null;
        $this->params[self::CODES] = $params[self::CODES] ?? null;
        if (isset($params[self::ACCOUNT_TYPES])) {
            $temp = array();
            foreach( $params[self::ACCOUNT_TYPES] as $param) {
                array_push($temp, $param[0]);
            }
            $this->params[self::ACCOUNT_TYPES] = $temp;
        }
        else {
            $this->params[self::ACCOUNT_TYPES] = null;
        }
        // $this->params[self::ACCOUNT_TYPES] = isset($params[self::ACCOUNT_TYPES]) ? (
        //     is_array ( $params[self::ACCOUNT_TYPES] ) ?
        //     foreach( $params[self::ACCOUNT_TYPES] as $param) {
        //         array_push($temp, $param[0]);
        //     }
        //     :
        //     array (
        //         $params[self::ACCOUNT_TYPES]
        //     )
        // ) : null;
        if (isset($params[self::JOURNAL_EVENT_TYPES])) {
            $temp = array();
            foreach( $params[self::JOURNAL_EVENT_TYPES] as $param) {
                array_push($temp, $param[0]);
            }
            $this->params[self::JOURNAL_EVENT_TYPES] = $temp;
        }
        else {
            $this->params[self::JOURNAL_EVENT_TYPES] = null;
        }
        // $this->params[self::JOURNAL_EVENT_TYPES] = isset($params[self::JOURNAL_EVENT_TYPES]) ? (
        //     is_array ( $params[self::JOURNAL_EVENT_TYPES] ) ? $params[self::JOURNAL_EVENT_TYPES] : array (
        //         $params[self::JOURNAL_EVENT_TYPES]
        //     )
        // ) : null;

        $this->params[self::INVENTORY_TYPE] = $params[self::INVENTORY_TYPE] ?? null;
        $this->params[self::KEYWORD] = $params[self::KEYWORD] ?? null;
        $this->params[self::ORDER_STATUS] = $params[self::ORDER_STATUS] ?? null;
        $this->params[self::PURCHASE_BY_V2] = $params[self::PURCHASE_BY_V2] ?? null;
        $this->reportHelper = new ReportHelper() ?? null;
    }

    protected function setDefaultParams() {
    }

    /**
     * Set parameters for base query
     *
     * @param array $args
     */
    public function setParams(array $args): void
    {
        $this->params = $args;
    }

    private function convertDate(string $date, bool $from = true): string
    {
        $format = $from ? 'Y-m-d 00:00:00' : 'Y-m-d 23:59:59';
        return $date ? date($format, strtotime($date)) : '';
    }

    public function getReport()
    {
        if ($this->params[self::EXPORT_CSV]) {
            return $this->getReportForCSV();
        } else {
            return $this->getTable();
        }
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();
        $data['data'] = $data;
        $data['total'] = count($data);
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    protected function getCsvHeaders(): array
    {
        return [];
    }

    /**
     * Data table
     *
     * @return array data table
     */
    public function getTable(): array
    {
        if (empty($this->table)) {
            $this->calc();
        }

        if( $this->params[self::PAGINATE] )
        {
            if( isset($this->table['data']) && isset($this->table['total']))    {
                return $this->table; //Already paginated in child class
            }   else {
                return [
                    'data' => $this->table,
                    'total' => $this->query instanceof Builder ? $this->query->count() : count($this->table),
                ];
            }
        }
        return $this->table;
    }

    /**
     * Calculate full data
     *
     * @return array
     */
    protected function calc()
    {
        $this->table = [];
        $this->getDataDateRange();
    }

    /** Calculate data by date range (timestampFrom|To) */
    protected function getDataDateRange() {
        $data = $this->calcByDateRange ( $this->getParams() );
        // pr($data);
        if (count ( $data ) > 0) {
			foreach ( $data as $row ) {
				foreach ( $row as $key => $val ) {
                    if( isset($row->{self::FIELD_ID}) )
                    {
                        $this->table [$row->{self::FIELD_ID}] [$key] = $val;
                    }
				}
			}
		}
    }

	protected function calcByDateRange( $params = [] )
    {
        $this->table = [];
        $query = $this->getBaseQuery();
        if($query instanceof Builder)
        {
            $this->query = $query;
            $query = $this->setWhereFilters($query);
            $query = $this->setGroupBy($query);
            try {
                // pr($query->count());
                $query = $this->setOrderBy($query);
                $query = $this->setLimit($query);
                $this->table = $query->get()->toArray();
            } catch (\Exception $exception) {
               print_r($exception->getMessage());
               die;
            }
        }
        else if( is_array($query))
        {
            // $this->table['total'] = count($query);
            // $this->table['data'] = $query;
            // pr($query);
            $this->table = $query;
        }
        // pr(get_class($this));
        // if(get_class($this) == 'ReportServiceSumProgramCostOfGiftCodesRedeemed')
        // {
        //     pr($this->table);
        // }

        return $this->table;
	}

    /**
     * Set Where Filters
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Set Group By condition
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setGroupBy(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Set Order By condition
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy(Builder $query): Builder
    {
        if ($this->params[self::SQL_ORDER_BY]){
            $query->orderBy($this->params[self::SQL_ORDER_BY], $this->params[self::SQL_ORDER_BY_DIR]);
        }
        return $query;
    }

    /**
     * Set Limit & Offset condition
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setLimit(Builder $query): Builder
    {
        if ($this->params[self::SQL_LIMIT] !== null) {
            $query->limit($this->params[self::SQL_LIMIT]);
            if ($this->params[self::SQL_OFFSET] !== null) {
                $query->offset($this->params[self::SQL_OFFSET]);
            }
        }
        return $query;
    }

    /**
     * Get basic sql without any filters
     *
     * @return string
     */
    protected function getBaseSql(): string
    {
        return '';
    }

    /**
     * Get basic query without any filters
     *
     * @return Builder
     */
    protected function getBaseQuery(): mixed
    {
        $sql = $this->getBaseSql();
        if( $sql != "")
        {
            $sql = $this->addSqlFilters($sql);
            // pr($sql);
            return DB::select( DB::raw($sql), []);
        }

        return DB::table( '' );
    }

	public function getParams() {
		$this->setDefaultParams ();
		return $this->params;
	}

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

	/** get sql where filter
	 *
	 * @return array */
	protected function getWhereFilters() {
		return array ();
	}

    public function amountFormat($value){
        return number_format((float)$value, 2, '.', '');
    }
}
