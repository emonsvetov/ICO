<?php

namespace App\Services\reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class ReportServiceAbstract
{
    const DATE_FROM = 'dateFrom';
    const DATE_TO = 'dateTo';
    const DATE_BEGIN = self::DATE_FROM;
    const DATE_END = self::DATE_TO;

    const SQL_LIMIT = 'limit';
    const SQL_OFFSET = 'offset';
    const SQL_GROUP_BY = 'group';
    const SQL_ORDER_BY = 'order';
    const FIELD_ID = "account_holder_id";
    const FIELD_VALUE = "value";
    const FIELD_MONTH = "month";
    const FIELD_JOURNAL_EVENT_TYPE = "journal_event_type";

    const PROGRAM_ID = 'programId';
    const CREATED_ONLY = 'createdOnly';
    const PROGRAMS = 'program_account_holder_ids';
    const PROGRAM_ACCOUNT_HOLDER_IDS = 'program_account_holder_ids';
    const AWARD_LEVEL_NAMES = "award_level_names";
    const EXPORT_CSV = 'exportToCsv';
    const MERCHANTS = 'merchants';
    const MERCHANTS_ACTIVE = 'active';

    const FIELD_REPORT_KEY = 'reportKey';

    const JOURNAL_EVENT_TYPES = "journal_event_types";
    const FIELD_ACCOUNT_TYPE = "account_type_name";
    const ACCOUNT_HOLDER_IDS = "account_holder_ids";
    const ACCOUNT_TYPES = "account_types";

    const SQL_WHERE = 'where';
    const SQL_ORDER_BY_DIR = 'dir';

    protected array $params;
    protected array $table = [];
    protected bool $isExport = false;
    protected $query = null;
    const PAGINATE = 'paginate';

    /**
     * @var ReportHelper|null
     */
    protected $reportHelper;

    public function __construct(array $params = [])
    {
        $this->params[self::DATE_FROM] = $this->convertDate($params[self::DATE_FROM] ?? '');
        $this->params[self::DATE_TO] = $this->convertDate($params[self::DATE_TO] ?? '', false);
        $this->params[self::PROGRAMS] = isset($params[self::PROGRAMS]) && is_array($params[self::PROGRAMS]) ? $params[self::PROGRAMS] : [];
        $this->params[self::SQL_LIMIT] = $params[self::SQL_LIMIT] ?? null;
        $this->params[self::SQL_OFFSET] = $params[self::SQL_OFFSET] ?? null;
        $this->params[self::EXPORT_CSV] = $params[self::EXPORT_CSV] ?? null;
        $this->params[self::MERCHANTS] = isset($params[self::MERCHANTS]) && is_array($params[self::MERCHANTS]) ? $params[self::MERCHANTS] : [];
        $this->params[self::MERCHANTS_ACTIVE] = $params[self::MERCHANTS_ACTIVE] ?? null;
        $this->params[self::FIELD_REPORT_KEY] = $params[self::FIELD_REPORT_KEY] ?? null;
        $this->params[self::PROGRAM_ID] = $params[self::PROGRAM_ID] ?? null;
        $this->params[self::CREATED_ONLY] = $params[self::CREATED_ONLY] ?? null;
        $this->params[self::SQL_GROUP_BY] = $params[self::SQL_GROUP_BY] ?? null;
        $this->params[self::SQL_ORDER_BY] = $params[self::SQL_ORDER_BY] ?? null;
        $this->params[self::PAGINATE] = $params[self::PAGINATE] ?? null;

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
        // pr($this->params[self::PAGINATE]);
        if( $this->params[self::PAGINATE] )
        {
            return [
                'data' => $this->table,
                'total' => $this->query instanceof Builder ? $this->query->count() : count($this->table),
            ];
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
                // $this->table['total'] = $query->count();
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
            $this->table = $query;
        }
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
            $query->orderBy($this->params[self::SQL_ORDER_BY]);
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
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    protected function getCsvHeaders(): array
    {
        return [];
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
            return DB::select( DB::raw($sql), []);
        }

        DB::table( '' );
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
}
