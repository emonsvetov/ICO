<?php

namespace App\Services\reports;

use App\Models\DomainProgram;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;

class ReportUnassignedProgramDomainsService extends ReportServiceAbstract
{
    protected function calc(): array
    {
        $table = [];
        $this->table = [];

        // Get programs that are not assigned to any domain
        $unassignedPrograms = DB::table('programs as p')
            ->leftJoin('programs as parent', 'p.parent_id', '=', 'parent.id')
            ->leftJoin('domain_program as dp', 'p.id', '=', 'dp.program_id')
            ->select(
                'p.id',
                'p.name',
                'p.parent_id',
                'parent.name as parent_name'
            )
            ->whereNull('dp.program_id')
            ->get();

        foreach ($unassignedPrograms as $program) {
            if (!isset($table[$program->id])) {
                $table[$program->id] = new stdClass();
                $table[$program->id]->name = $program->name;
                $table[$program->id]->root_id = $program->parent_id;
                $table[$program->id]->root_name = $program->parent_name;
            }
        }

        // Prepare data for output
        $arr = [];
        foreach ($table as $programId => $programData) {
            $arr[] = [
                'id' => $programId,
                'name' => $programData->name,
                'root_id' => $programData->root_id,
                'root_name' => $programData->root_name
            ];
        }

        $this->table['data'] = $arr;
        $this->table['total'] = count($arr);
        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            ['label' => 'Program ID', 'key' => 'id'],
            ['label' => 'Program Name', 'key' => 'name'],
            ['label' => 'Root Program ID', 'key' => 'root_id'],
            ['label' => 'Root Program Name', 'key' => 'root_name'],
        ];
    }

    private function prepareForExport($programs): array
    {
        return array_map(function ($program) {
            return [
                'id' => $program['id'],
                'program_name' => $program['name'],
                'root_id' => $program['root_id'],
                'root_name' => $program['root_name'],
            ];
        }, $programs);
    }
}
