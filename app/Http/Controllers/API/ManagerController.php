<?php

namespace App\Http\Controllers\API;

// use Illuminate\Support\Facades\Validator;
// use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Services\PostingService;
use App\Models\Organization;
use App\Models\Program;

class ManagerController extends Controller
{
    public function getMoniesAvailablePostings(Organization $organization, Program $program) {
        try{
            return response((new PostingService)->getMoniesAvailablePostings( $program ));
        } catch( \Exception $e )   {
            return response(['errors' => $e->getMessage()], 422);
        }
    }
}
