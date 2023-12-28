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
        return response(['order' => $order]);
    }

}
