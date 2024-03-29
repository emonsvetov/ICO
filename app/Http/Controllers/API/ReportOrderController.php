<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MediumInfo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReportOrderController extends Controller
{
    /**
     * @param $organization
     * @param $orderID
     * @return Response
     */
    public function show($organization, $orderID): Response
    {
        $order = MediumInfo::getByID($orderID);

        if ($order) {
            $merchantName = $this->fetchMerchantName($order->merchant_id);
            $userName = $this->fetchUserName($order->redeemed_user_id);
            $programName = $this->fetchProgramName($order->redeemed_program_id);

            $order->merchant_name = $merchantName;
            $order->user_name = $userName;
            $order->program_name = $programName;

            return response(['order' => $order]);
        } else {
            return response(['error' => 'Order not found'], 404);
        }
    }

    /**
     * Fetch the merchant's name by ID
     *
     * @param int $merchantID
     * @return string|null
     */
    private function fetchMerchantName(int $merchantID): ?string
    {
        $merchant = DB::table('merchants')
            ->where('id', $merchantID)
            ->select('name')
            ->first();

        return $merchant ? $merchant->name : null;
    }

    /**
     * Fetch the user's name by ID
     *
     * @param int $userID
     * @return string|null
     */
    private function fetchUserName(int $userID): ?string
    {
        $user = DB::table('users')
            ->where('id', $userID)
            ->select(DB::raw("CONCAT(first_name, ' ', last_name) as name"))
            ->first();

        return $user ? $user->name : null;
    }

    /**
     * Fetch the program's name by ID
     *
     * @param int $programID
     * @return string|null
     */
    private function fetchProgramName(int $programID): ?string
    {
        $program = DB::table('programs')
            ->where('id', $programID)
            ->select('name')
            ->first();

        return $program ? $program->name : null;
    }
}

