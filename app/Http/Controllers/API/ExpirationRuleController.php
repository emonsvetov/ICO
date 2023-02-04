<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExpirationRule;

class ExpirationRuleController extends Controller
{
    public function index()
    {
        $expiration_rules = ExpirationRule::get();
        if ( $expiration_rules->isNotEmpty() )
        {
            return response( $expiration_rules );
        }
        return response( [] );
    }
}