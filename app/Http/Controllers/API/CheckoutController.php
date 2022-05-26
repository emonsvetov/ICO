<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CheckoutRequest;
use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Country;
use App\Models\State;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkoutService, Organization $organization, Program $program )
    {

        $cart = $request->validated();
        try {
            $response = $checkoutService->processOrder( $cart, $program );
            if( !empty($response['errors']) )   {
                return response(['errors' => $response['errors']], 422);
            }
            return $response;
        }   catch (Exception $e)    {
            return response(
                [
                    'errors' => sprintf('Error while processing checkout. Line: %d, Error: %s', $e->getLine, $e->getMessage()),
                ],
                422
            );
        }
    }
}

