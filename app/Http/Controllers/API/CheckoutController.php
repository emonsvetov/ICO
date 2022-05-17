<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CheckoutRequest;
use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Models\Organization;
use App\Models\Program;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkoutService, Organization $organization, Program $program )
    {
        $cart = $request->validated();
        try {
            return $checkoutService->processOrder( $cart, $program );
        }   catch (Exception $e)    {
            return response(
                [
                    'errors' => sprintf('Error while processing checkout. Line: %d, Error: %s', $e->getLine, $e->getMessage()),
                ]
            );
        }
    }
}

