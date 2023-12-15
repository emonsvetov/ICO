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

        // Get IDs of programs that are assigned to a domain
        $assignedProgramIds = DomainProgram::distinct()->pluck('program_id')->all();

        // Get programs that are not assigned to any domain
        $unassignedPrograms = Program::whereNotIn('programs.account_holder_id', $assignedProgramIds)
            ->leftJoin('programs as parent', 'programs.parent_id', '=', 'parent.id')
            ->select(
                'programs.account_holder_id',
                'programs.name',
                'programs.parent_id',
                'parent.name as parent_name'
            )
            ->get();

        foreach ($unassignedPrograms as $program) {
            if (!isset($table[$program->account_holder_id])) {
                $table[$program->account_holder_id] = new stdClass();
                $table[$program->account_holder_id]->name = $program->name;
                $table[$program->account_holder_id]->root_id = $program->parent_id;
                $table[$program->account_holder_id]->root_name = $program->parent_name;
            }
        }

        // Prepare data for output
        $arr = [];
        foreach ($table as $accountHolderId => $programData) {
            $arr[] = [
                'id' => $accountHolderId,
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
            ['label' => 'Account Holder ID', 'key' => 'id'],
            ['label' => 'Program Name', 'key' => 'name'],
            ['label' => 'Root Program ID', 'key' => 'root_id'],
            ['label' => 'Root Program Name', 'key' => 'root_name'],
        ];
    }

    private function prepareForExport($programs): array
    {
        return array_map(function ($program) {
            return [
                'account_holder_id' => $program['id'],
                'program_name' => $program['name'],
                'root_id' => $program['root_id'],
                'root_name' => $program['root_name'],
            ];
        }, $programs);
    }
}
