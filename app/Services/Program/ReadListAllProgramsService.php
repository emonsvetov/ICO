<?php
namespace App\Services\Program;

use App\Models\Program;
use App\Models\Domain;

class ReadListAllProgramsService
{
    public function get( $extraArgs = [])
    {
        $domainIds = Domain::where([])->pluck('id');
        $query = Program::whereHas('domains', function($query) use ($domainIds) {
            $query->whereIn('domains.id', $domainIds);
        })->with(['children' => function($query){
            return $query->select('id', 'name', 'parent_id');
        }])->select('id', 'name', 'parent_id');

        if( isset($extraArgs['create_invoices']) )
        {
            $query->where('create_invoices', $extraArgs['create_invoices']);
        }

        if( isset($extraArgs['program_is_demo']) )
        {
            $query->where('program_is_demo', $extraArgs['program_is_demo']);
        }

        $programs = $query->get();

        $programs = _flatten($programs);
        return $programs;
    }
}