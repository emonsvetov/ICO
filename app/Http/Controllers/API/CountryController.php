<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;

class CountryController extends Controller
{
    public function listStates(Country $country) {
        if(!$country->states->isEmpty()) {
            return response($country->states);
        }
        return response([]);
    }
}
