<?php

namespace App\Services\reports;

use App\Models\CsvImport;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use stdClass;

class ReportFileImportService extends ReportServiceAbstract
{


    // /**
    //  * @inheritDoc
    //  */
    // protected function getBaseQuery(): Builder
    // {
    //     $selectedPrograms = $this->params[self::PROGRAM_IDS];
    //     $query = DB::table('csv_imports');
    //     $query->addSelect([
    //         'name',
    //         'created_at',
    //     ]);
    //     return $query;
    // }

    // /**
    //  * @inheritDoc
    //  */
    // protected function setWhereFilters(Builder $query): Builder
    // {
    //     $query->whereIn('program_id', $this->params[self::PROGRAMS]);

    //     return $query;
    // }

    public function getTable(): array
    {
        if (empty($this->table)) {
            $this->calc();
        }
        if ($this->params[self::PAGINATE]) {
            return [
                'data' => $this->table,
                'total' => $this->query instanceof Builder ? $this->query->count('csv_imports.id') : count($this->table),
            ];
        }
        return $this->table;
    }

    protected function calc(): array
    {
        $query = DB::table('csv_imports');
        $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get()->pluck('id')->toArray();
        $query->whereBetween('created_at', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);
        $query->selectRaw("
            name,
            path,
            created_at
        ");
        $query->orderBy('created_at', 'DESC');
        $table = $query->get();
        $this->table['data'] = $table;

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Id',
                'key' => 'program_id'
            ],
            [
                'label' => 'Name',
                'key' => 'name'
            ],
            [
                'label' => 'Last Name',
                'key' => 'recipient_last_name'
            ],
            [
                'label' => 'Path',
                'key' => 'path'
            ],
            [
                'label' => 'Created',
                'key' => 'created_at'
            ],
        ];
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

}
