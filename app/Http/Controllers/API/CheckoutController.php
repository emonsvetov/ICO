<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CheckoutRequest;
use App\Http\Controllers\Controller;
use App\Models\Giftcode;
use App\Services\CheckoutService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkoutService, Organization $organization, Program $program )
    {
        $cart = $request->validated();
        try {
            $response = $checkoutService->processOrder($cart, $program);
            if( !empty($response['errors']) )   {
                return response(['errors' => $response['errors']], 422);
            }

            return $response;
        }   catch (\Exception $e)    {
            return response(
                [
                    'errors' => sprintf('Error while processing checkout. Line: %d, Error: %s', $e->getLine(), $e->getMessage()),
                ],
                422
            );
        }
    }

    public function hmiStore($account_holder_id, CheckoutRequest $request, CheckoutService $checkoutService)
    {
        $user = User::where('account_holder_id', $account_holder_id)->with('programs')->first();
        $program = Program::where('id', $user->programs[0]->id)->first();
        $cart = $request->validated();
        try {
            $response = $checkoutService->processOrder( $cart, $program, $user );
            if( !empty($response['errors']) )   {
                return response(['errors' => $response['errors']], 422);
            }

            return $response;
        }   catch (\Exception $e)    {
            return response(
                [
                    'errors' => sprintf('Error while processing checkout. Line: %d, Error: %s', $e->getLine(), $e->getMessage()),
                ],
                422
            );
        }
    }
}

