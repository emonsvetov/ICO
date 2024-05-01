<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\Transaction;
use App\Models\Subscription;
use Carbon\Carbon;

class PayPalController extends Controller
{
    protected $provider;
    public function __construct()
    {
        $this->provider = new PayPalClient;
        $accessToken = $this->provider->getAccessToken();
    }

    public function getPlans()
    {
        try {
            $plans = $this->provider->listPlans();

            return response()->json($plans['plans']);
        } catch (\Exception $e) {
            // Handle API error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function processPayment(Request $request)
    {
        $subscriptionExists = Subscription::where('user_id', $request->user_id)->exists();
        if ($request->is_recurring == true) {
            // Handle subscription
            $response = $this->createSubscription($request, $subscriptionExists);
        } else {
            // Handle one-time payment
            $response = $this->createOneTimePayment($request);
        }

        if (isset($response['id'])) {
            if ($request->is_recurring) {
                Subscription::create([
                    'user_id' => $request->user_id,
                    'subscription_id' => $response['id'],
                    'plan_id' => $request->plan_id,
                    'start_date' => Carbon::now(),
                    'end_date' => $this->calculateEndDate($request->billing_interval),
                    'billing_interval' => $request->billing_interval,
                    'trial_period' => $subscriptionExists ? 30 : 0,
                ]);
            } else {
                Transaction::create([
                    'user_id' => $request->user_id,
                    'transaction_id' => $response['id'],
                    'payment_status' => 'Completed',
                    'amount' => $request->price,
                    'currency' => 'USD',
                    'payment_method' => 'one_time',
                ]);
            }
            return response()->json(['success' => true, 'data' => $response]);
        } else {
            return response()->json(['success' => false, 'error' => 'Payment processing failed.'], 500);
        }
    }

    public function cancelSubscription(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|string',
        ]);

        try {
            $this->provider->cancelSubscription($request->subscription_id, 'Canceling the subscription');

            $subscription = Subscription::where('subscription_id', $request->subscription_id)->first();
            if ($subscription) {
                $subscription->cancellation_date = now();
                $subscription->is_active = false;
                $subscription->save();
            }

            return response()->json(['success' => true, 'message' => 'Subscription canceled successfully.']);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifySubscription(Request $request) {
        $response = $this->provider->showSubscriptionDetails($request->subscription_id);

        if ($response['status'] == 'ACTIVE' || $response['status'] == 'APPROVED') {
            $subscription = Subscription::where('subscription_id', $request->subscription_id)->first();
            if ($subscription) {
                $subscription->is_active = true;
                $subscription->save();
            }

            return response()->json(['success' => true, 'message' => 'Subscription verified.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Subscription not verified.']);
        }
    }

    private function createSubscription(Request $request, $subscriptionExists)
    {
        $user = User::where('id', $request->user_id)->first();
        // Example subscription data. Adjust according to your needs.
        $subscriptionData = [
            'plan_id' => $request->plan_id,
            'start_time' => $subscriptionExists ? Carbon::now()->addDay(30)->toIso8601String() : Carbon::now()->addDay(1)->toIso8601String(),
            'subscriber' => [
                'name' => [
                    'given_name' => $user->first_name,
                    'surname' => $user->last_name,
                ],
                'email_address' => $user->email,
            ],
            'application_context' => [
                'brand_name' => 'Example Company Inc.',
                'locale' => 'en-US',
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
                ],
                'return_url' => url('/api/paypal/success'),
                'cancel_url' => url('/api/paypal/cancel'),
            ],
        ];

        try {
            $response = $this->provider->createSubscription($subscriptionData);

            return $response;
        } catch (\Exception $e) {

            return ['error' => $e->getMessage()];
        }
    }

    private function createOneTimePayment(Request $request)
    {
        $paymentData = [
            'intent' => 'sale',
            'payer' => [
                'payment_method' => 'paypal',
            ],
            'transactions' => [
                [
                    'amount' => [
                        'total' => $request->price,
                        'currency' => 'USD',
                        'details' => [
                            'subtotal' => $request->price,

                        ],
                    ],
                    'description' => 'Payment for services rendered',
                    'invoice_number' => uniqid(),
                ],
            ],
            'redirect_urls' => [
                'return_url' => url('/api/paypal/success'),
                'cancel_url' => url('/api/paypal/cancel'),
            ],
        ];

        try {
            $response = $this->provider->createPayment($paymentData);

            return $response;
        } catch (\Exception $e) {

            return ['error' => $e->getMessage()];
        }
    }


    private function calculateEndDate($interval)
    {
        if ($interval === 'monthly') {
            return Carbon::now()->addMonth();
        } else if ($interval === 'yearly') {
            return Carbon::now()->addYear();
        }
    }
}
