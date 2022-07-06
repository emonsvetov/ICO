<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProgramMoveRequest;
use App\Http\Requests\ProgramRequest;
use App\Http\Controllers\Controller;
use App\Services\ProgramService;
use App\Events\ProgramCreated;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\Program;
use DB;

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
        return $programService->getDescendents( $program, $includeSelf );
    }
}
