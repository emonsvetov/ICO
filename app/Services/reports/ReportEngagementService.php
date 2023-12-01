<?php

namespace App\Services\reports;

use App\Models\CsvImport;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use stdClass;

class ReportEngagementService extends ReportServiceAbstract
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
                'total' => $this->query instanceof Builder ? $this->query->count('program_user.id') : count($this->table),
            ];
        }
        return $this->table;
    }

    protected function calc(): array
    {
        $query = DB::table('program_user');
        $query->join('programs', 'programs.id', '=', 'program_user.program_id');
        $query->join('users', 'users.id', '=', 'program_user.user_id');
       
        $query->whereBetween('program_user.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        $query->selectRaw("
        program_user.created_at as created,
        programs.name as program,
        CONCAT(users.first_name, users.last_name) as referrer,
        users.email as referrer_email
        ");
        $query->orderBy('program_user.created_at', 'DESC');
        $table = $query->get();
        $this->table['data'] = $table;

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Created',
                'key' => 'created'
            ],
            [
                'label' => 'Program',
                'key' => 'program'
            ],
            [
                'label' => 'Referrer',
                'key' => 'referrer'
            ],
            [
                'label' => 'Referrer Email',
                'key' => 'referrer_email'
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
