<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $type = request()->get('type');
        $emailTemplates = EmailTemplate::where('type', $type)
                        ->get();

        if ( $emailTemplates->isNotEmpty() )
        {
            return response( $emailTemplates );
        }
        return response( [] );
    }

}
