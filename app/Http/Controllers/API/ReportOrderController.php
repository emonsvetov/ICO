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
        $order = MediumInfo::query()
            ->leftJoin('merchants', 'medium_info.merchant_id', '=', 'merchants.id')
            ->leftJoin('users', 'medium_info.redeemed_user_id', '=', 'users.id')
            ->leftJoin('programs', 'medium_info.redeemed_program_id', '=', 'programs.id')
            ->where('medium_info.id', $orderID)
            ->select('medium_info.*', 'merchants.name as merchant_name', DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"), 'programs.name as program_name')
            ->first();

        if ($order) {
            return response(['order' => $order]);
        } else {
            return response(['error' => 'Order not found'], 404);
        }
    }
}

