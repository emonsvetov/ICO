<?php

namespace App\Services\reports;

use Illuminate\Database\Query\Builder;

abstract class ReportServiceAbstract
{
    const DATE_FROM = 'dateFrom';
    const DATE_TO = 'dateTo';

    const SQL_LIMIT = 'limit';
    const SQL_OFFSET = 'offset';

    const PROGRAMS = 'program_account_holder_ids';
    const AWARD_LEVEL_NAMES = "award_level_names";
    const EXPORT_CSV = 'exportToCsv';

    /**
     * The main key used to organize the return data. Change this in the subclass if it's different
     */
    const FIELD_ID = "account_holder_id";

    protected array $params;
    protected array $table = [];

    public function __construct(array $params = [])
    {
        $this->params[self::DATE_FROM] = $this->convertDate($params[self::DATE_FROM] ?? '');
        $this->params[self::DATE_TO] = $this->convertDate($params[self::DATE_TO] ?? '', false);
        $this->params[self::PROGRAMS] = isset($params[self::PROGRAMS]) && is_array($params[self::PROGRAMS]) ? $params[self::PROGRAMS] : [];
        $this->params[self::SQL_LIMIT] = $params[self::SQL_LIMIT] ?? null;
        $this->params[self::SQL_OFFSET] = $params[self::SQL_OFFSET] ?? null;
        $this->params[self::EXPORT_CSV] = $params[self::EXPORT_CSV] ?? null;
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
        $query = $this->setOrderBy($query);
        $this->table['total'] = $query->count();
        $query = $this->setLimit($query);
        $this->table['data'] = $query->get()->toArray();
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
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    abstract protected function getCsvHeaders():array;

    /**
     * Get basic sql without any filters
     *
     * @return Builder
     */
    abstract protected function getBaseSql(): Builder;

}
