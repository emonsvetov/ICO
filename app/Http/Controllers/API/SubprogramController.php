<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ProgramService;
use App\Models\Organization;
use App\Models\Program;

class SubprogramController extends Controller
{
    public function index( Organization $organization, Program $program, ProgramService $programService)
    {
        $programs = $programService->getSubprograms( $organization, $program );

        if ( $programs->isNotEmpty() )
        {
            return response( $programs );
        }

        return response( [] );
    }

    public function available(Organization $organization, Program $program, ProgramService $programService, $action)  {
        $available = [];
        switch( $action ):
            case 'add':
                $available = $programService->getAvailableToAddAsSubprogram($organization, $program);
            break;
            case 'move':
                $available = $programService->getAvailableToMoveSubprogram($organization, $program);
            break;
        endswitch;

        return response($available);
    }

    public function unlink(Organization $organization, Program $program, ProgramService $programService)
    {
        $unlinkSubtree = request()->get('subtree');
        if( $unlinkSubtree )    {
            $programService->unlinkNodeWithSubtree($organization, $program);
        }   else {
            $programService->unlinkNode($organization, $program);
        }
        return response([ 'unlinked' => true ]);
    }

    public function getDescendents(Organization $organization, Program $program, ProgramService $programService)    {
        $includeSelf = request()->get('includeSelf') ? true : false;
        $flat = (bool)request()->get('flat');
        $result = $programService->getDescendents( $program, $includeSelf );
        $result = $flat ? _flatten($result) : $result;
        return $result;
    }
}
