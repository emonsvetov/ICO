<?php

namespace App\Services\reports;

use App\Services\UserService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class ReportServiceAbstract
{
    const DATE_FROM = 'dateFrom';
    const DATE_TO = 'dateTo';

    const SQL_LIMIT = 'limit';
    const SQL_OFFSET = 'offset';
    const SQL_GROUP_BY = 'group';
    const SQL_ORDER_BY = 'order';

    const PROGRAM_ID = 'programId';
    const CREATED_ONLY = 'createdOnly';
    const PROGRAMS = 'program_account_holder_ids';
    const AWARD_LEVEL_NAMES = "award_level_names";
    const EXPORT_CSV = 'exportToCsv';
    const MERCHANTS = 'merchants';
    const MERCHANTS_ACTIVE = 'active';

    const FIELD_REPORT_KEY = 'reportKey';

    protected array $params;
    protected array $table = [];
    protected bool $isExport = false;

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

        $this->reportHelper = new ReportHelper() ?? null;
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
        return $this->table;
    }

    /**
     * Calculate full data
     *
     * @return array
     */
    protected function calc(): array
    {
        $this->table = [];
        $query = $this->getBaseSql();
        $query = $this->setWhereFilters($query);
        $query = $this->setGroupBy($query);
        try {
            $this->table['total'] = $query->count();
            $query = $this->setOrderBy($query);
            $query = $this->setLimit($query);
            $this->table['data'] = $query->get()->toArray();
        } catch (\Exception $exception){
//            print_r($exception->getMessage());
//            die;
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
     * @return Builder
     */
    protected function getBaseSql(): Builder
    {
        return DB::table('');
    }

}
