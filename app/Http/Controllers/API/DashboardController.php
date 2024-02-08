<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainRequest;
use App\Models\Program;
use App\Services\DomainService;
use App\Models\Organization;
use App\Services\ProgramService;
use App\Services\reports\ReportHelper;
use App\Services\reports\ReportParticipantLoginService;
use App\Services\reports\ReportProgramStatusService;
use App\Services\reports\ReportSumProgramsSupplierRedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Domain;
use Exception;

class DashboardController extends Controller
{
    public function index(
        Organization $organization,
        Program $program,
        ProgramService $programService,
        Request $request
    ) {
        $programAccountHolderIds = [$program->account_holder_id];
        $dateFrom = date("Y-m-d");
        $dateTo = date("Y-m-d");

        $params = [
            'program_account_holder_ids' => $programAccountHolderIds,
            'from' => $dateFrom,
            'to' => $dateTo,
            'limit' => null,
            'offset' => null,
            'programs' => $programAccountHolderIds,
        ];

        $report = new ReportProgramStatusService($params);
        $report = $report->getTable();
        $awardsToday = $report['data']['report'][0] ?? [];

        $tmp = [];
        $tmp['title'] = "Today's Awards";
        $tmp['today']['value'] = $awardsToday ? (float)$awardsToday->awards_value * $program->factor_valuation : 0;
        $tmp['today']['amount'] = $awardsToday ? (float)$awardsToday->awards_value : 0;
        $tmp['mtd']['value'] = $awardsToday ? (float)$awardsToday->mtd_awards_value * $program->factor_valuation : 0;
        $tmp['mtd']['amount'] = $awardsToday ? (float)$awardsToday->mtd_awards_value : 0;
        $tmp['ytd']['value'] = $awardsToday ? (float)$awardsToday->ytd_awards_value * $program->factor_valuation : 0;
        $tmp['ytd']['amount'] = $awardsToday ? (float)$awardsToday->ytd_awards_value : 0;
        $data['awardsToday'] = (object)$tmp;

        // DB::enableQueryLog();


        $report = new ReportSumProgramsSupplierRedemptionService($params);
        $report = $report->getTable();
        // pr(toSql(DB::getQueryLog()));
        // pr($report);
        // exit;
        $redemptionToday = $report[0] ?? [];
        $redemptionToday = $redemptionToday ? (float)$redemptionToday->total_dollar_value_redeemed : 0;

        $month = date('m', strtotime($dateTo));
        $dateBeginMonth = date('Y', strtotime($dateTo)) . '-' . $month . '-01 00:00:00';
        $params = [
            'programs' => $programAccountHolderIds,
            'dateFrom' => $dateBeginMonth,
            'dateTo' => $dateTo,
            'limit' => null,
            'offset' => null,
        ];
        $report = new ReportSumProgramsSupplierRedemptionService($params);
        $report = $report->getTable();
        $redemptionMTD = $report[0] ?? [];
        $mtd = $redemptionMTD ? (float)$redemptionMTD->total_dollar_value_redeemed : 0;

        $dateBeginYear = date('Y', strtotime($dateTo)) . '-01-01 00:00:00';
        $params = [
            'programs' => $programAccountHolderIds,
            'dateFrom' => $dateBeginYear,
            'dateTo' => $dateTo,
            'limit' => null,
            'offset' => null,
            'paginate'=>false
        ];
        $report = new ReportSumProgramsSupplierRedemptionService($params);
        $report = $report->getTable();
        $redemptionYTD = $report[0] ?? [];
        $ytd = $redemptionYTD ? (float)$redemptionYTD->total_dollar_value_redeemed : 0;

        $tmp = [];
        $tmp['title'] = "Today's Redemptions";
        $tmp['today']['value'] = $redemptionToday * $program->factor_valuation;
        $tmp['today']['amount'] = $redemptionToday;
        $tmp['mtd']['value'] = $mtd * $program->factor_valuation;
        $tmp['mtd']['amount'] = $mtd;
        $tmp['ytd']['value'] = $ytd * $program->factor_valuation;
        $tmp['ytd']['amount'] = $ytd;
        $data['redemptionToday'] = (object)$tmp;


        /** Today's Active Participants */
        $params = [
            'programId' => $program->account_holder_id,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'limit' => null,
            'offset' => null,
        ];
        $report = new ReportParticipantLoginService($params);
        $report = $report->getTable();
        $participantToday = $report['data'] ?? 0;

        $params = [
            'programId' => $program->account_holder_id,
            'dateFrom' => $dateBeginMonth,
            'dateTo' => $dateTo,
            'limit' => null,
            'offset' => null,
        ];
        $report = new ReportParticipantLoginService($params);
        $report = $report->getTable();
        $participantMTD = $report['data'] ?? 0;

        $params = [
            'programId' => $program->account_holder_id,
            'dateFrom' => $dateBeginYear,
            'dateTo' => $dateTo,
            'limit' => null,
            'offset' => null,
        ];
        $report = new ReportParticipantLoginService($params);
        $report = $report->getTable();
        $participantYTD = $report['data'] ?? 0;

        $tmp = [];
        $tmp['title'] = "Today's Active Participants";
        $tmp['type'] = "users";
        $tmp['today']['value'] = $participantToday;
        $tmp['today']['amount'] = null;
        $tmp['mtd']['value'] = $participantMTD;
        $tmp['mtd']['amount'] = 0;
        $tmp['ytd']['value'] = $participantYTD;
        $tmp['ytd']['amount'] = 0;
        $data['participantToday'] = (object)$tmp;

        return response($data);
    }

    public function topMerchants(
        Organization $organization,
        Program $program,
        ProgramService $programService,
        string $duration,
        int $unit
    ) {
        $dateFrom = date("Y-m-d");
        $dateTo = date("Y-m-d");
        switch ($duration):
            case 'day':
                $dateFrom = date("Y-m-d");
                break;
            case 'month':
                $month = date('m', strtotime($dateTo));
                $dateFrom = date('Y', strtotime($dateTo)) . '-' . $month . '-01 00:00:00';
                break;
            case 'year':
                $dateFrom = date('Y', strtotime($dateTo)) . '-01-01 00:00:00';
                break;
        endswitch;

        $programAccountHolderIds = [$program->account_holder_id];
        $params = [
            'programs' => $programAccountHolderIds,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'limit' => 6,
            'offset' => 0,
            'order' => $unit === 0 ? 'total_dollar_value_redeemed' : 'count',
            'paginate' => false,
            'group' => ['merchants.id', 'merchants.name'],
        ];
        $report = new ReportSumProgramsSupplierRedemptionService($params);
        $report = $report->getTable();
        $data = $report ?? [];
        foreach ($data as $key => $item) {
            $item->total_dollar_value_redeemed = (float)$item->total_dollar_value_redeemed;
            $item->total_dollar_value_rebated = (float)$item->total_dollar_value_rebated;
        }

        return response($data);
    }

    public function topAwards(
        Organization $organization,
        Program $program,
        ProgramService $programService,
        string $duration,
        int $unit
    ) {
        $dateFrom = date("Y-m-d");
        $dateTo = date("Y-m-d");
        switch ($duration):
            case 'day':
                $dateFrom = date("Y-m-d 00:00:01");
                $dateTo = date("Y-m-d 23:59:59");
                break;
            case 'month':
                $month = date('m', strtotime($dateTo));
                $dateFrom = date('Y', strtotime($dateTo)) . '-' . $month . '-01 00:00:00';
                break;
            case 'year':
                $dateFrom = date('Y', strtotime($dateTo)) . '-01-01 00:00:00';
                break;
        endswitch;

        $reporthelper = new ReportHelper();
        $args['group'] = 'event_name';
        $args['order'] = $unit === 0 ? 'total' : 'count';
        $args['limit'] = 6;
        $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
        $data = [];
        foreach ($awardsAudit as $item) {
            $tmp = [];
            $tmp['total'] = (float)$item->total;
            $tmp['event_name'] = $item->event_name;
            $tmp['count'] = (int)$item->count;
            $data[] = $tmp;
        }

        return response($data);
    }

    public function awardDetail(
        Organization $organization,
        Program $program,
        ProgramService $programService,
        string $duration,
        int $unit
    ) {
        $dateTo = date("Y-m-d");
        switch ($duration):
            case '7days':
                $dateFrom = date('Y-m-d', strtotime('-7 days'));
                $reporthelper = new ReportHelper();
                $args['group'] = 'date';
                $args['order'] = $unit === 0 ? 'total' : 'count';
                $args['limit'] = 7;
                $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
                $reportData = [];
                foreach ($awardsAudit as $item) {
                    $reportData[$item->date] = $item->toArray();
                }
                $months = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Aug',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec'
                ];
                $data = [];
                for ($i = 0; $i < 7; $i++) {
                    $value = [];
                    $value['total'] = 0;
                    $value['count'] = 0;
                    $dateStringMonth = $months[(int)date('m', strtotime('-' . $i . ' days'))] . '-' . date('d',
                            strtotime('-' . $i . ' days'));
                    if (isset($reportData[date('Y-m-d', strtotime('-' . $i . ' days'))])) {
                        $realValue = $reportData[date('Y-m-d', strtotime('-' . $i . ' days'))];
                        $value['total'] = (float)$realValue['total'];
                        $value['count'] = (int)$realValue['count'];
                    }
                    $data['labels'][] = $dateStringMonth;
                    $data['datasets'][0]['data'][] = $unit === 0 ? $value['total'] : $value['count'];
                }
                $data['datasets'][0]['borderColor'] = '#26CE83';
                $data['datasets'][0]['backgroundColor'] = 'rgba(255, 99, 132, 0.5)';
                $data['datasets'][0]['label'] = 'Dataset 1';
                break;
            case '30days':
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
                $reporthelper = new ReportHelper();
                $args['group'] = 'date';
                $args['order'] = $unit === 0 ? 'total' : 'count';
                $args['limit'] = 30;
                $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
                $reportData = [];
                foreach ($awardsAudit as $item) {
                    $reportData[$item->date] = $item->toArray();
                }
                $months = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Aug',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec'
                ];
                $data = [];
                for ($i = 0; $i < 30; $i++) {
                    $value = [];
                    $value['total'] = 0;
                    $value['count'] = 0;
                    $dateStringMonth = $months[(int)date('m', strtotime('-' . $i . ' days'))] . '-' . date('d',
                            strtotime('-' . $i . ' days'));
                    if (isset($reportData[date('Y-m-d', strtotime('-' . $i . ' days'))])) {
                        $realValue = $reportData[date('Y-m-d', strtotime('-' . $i . ' days'))];
                        $value['total'] = (float)$realValue['total'];
                        $value['count'] = (int)$realValue['count'];
                    }
                    $data['labels'][] = $dateStringMonth;
                    $data['datasets'][0]['data'][] = $unit === 0 ? $value['total'] : $value['count'];
                }
                $data['datasets'][0]['borderColor'] = '#26CE83';
                $data['datasets'][0]['backgroundColor'] = 'rgba(255, 99, 132, 0.5)';
                $data['datasets'][0]['label'] = 'Dataset 1';
                break;
            case '12month':
                $dateFrom = date('Y-m-d', strtotime('-12 month'));
                $reporthelper = new ReportHelper();
                $args['group'] = 'month';
                $args['order'] = $unit === 0 ? 'total' : 'count';
//                $args['limit'] = 12;
                $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
                $reportData = [];
                foreach ($awardsAudit as $item) {
                    $reportData[$item->month] = $item->toArray();
                }
                $months = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Aug',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec'
                ];
                $data = [];
                for ($i = 0; $i < 12; $i++) {
                    $value = [];
                    $value['total'] = 0;
                    $value['count'] = 0;
                    $dateStringMonth = $months[(int)date('m', strtotime('-' . $i . ' month'))];
                    if (isset($reportData[(int)date('m', strtotime('-' . $i . ' month'))])) {
                        $realValue = $reportData[(int)date('m', strtotime('-' . $i . ' month'))];
                        $value['total'] = (float)$realValue['total'];
                        $value['count'] = (int)$realValue['count'];
                    }
                    $data['labels'][] = $dateStringMonth;
                    $data['datasets'][0]['data'][] = $unit === 0 ? $value['total'] : $value['count'];
                }
                $data['datasets'][0]['borderColor'] = '#26CE83';
                $data['datasets'][0]['backgroundColor'] = 'rgba(255, 99, 132, 0.5)';
                $data['datasets'][0]['label'] = 'Dataset 1';
                break;
        endswitch;

        return response($data);
    }

    public function awardPeerDetail(
        Organization $organization,
        Program $program,
        ProgramService $programService,
        string $duration,
        int $unit
    ) {
        $dateTo = date("Y-m-d");
        switch ($duration):
            case '7days':
                $dateFrom = date('Y-m-d', strtotime('-7 days'));
                $reporthelper = new ReportHelper();
                $args['group'] = 'date';
                $args['p2p'] = true;
                $args['order'] = $unit === 0 ? 'total' : 'count';
                $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
                $reportData = [];
                foreach ($awardsAudit as $item) {
                    $reportData[$item->date] = $item->toArray();
                }
                $months = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Aug',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec'
                ];
                $data = [];
                for ($i = 0; $i < 7; $i++) {
                    $value = [];
                    $value['total'] = 0;
                    $value['count'] = 0;
                    $dateStringMonth = $months[(int)date('m', strtotime('-' . $i . ' days'))] . '-' . date('d',
                            strtotime('-' . $i . ' days'));
                    if (isset($reportData[date('Y-m-d', strtotime('-' . $i . ' days'))])) {
                        $realValue = $reportData[date('Y-m-d', strtotime('-' . $i . ' days'))];
                        $value['total'] = (float)$realValue['total'];
                        $value['count'] = (int)$realValue['count'];
                    }
                    $data['labels'][] = $dateStringMonth;
                    $data['datasets'][0]['data'][] = $unit === 0 ? $value['total'] : $value['count'];
                }

                break;
            case '30days':
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
                $reporthelper = new ReportHelper();
                $args['group'] = 'date';
                $args['p2p'] = true;
                $args['order'] = $unit === 0 ? 'total' : 'count';
                $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
                $reportData = [];
                foreach ($awardsAudit as $item) {
                    $reportData[$item->date] = $item->toArray();
                }
                $months = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Aug',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec'
                ];
                $data = [];
                for ($i = 0; $i < 30; $i++) {
                    $value = [];
                    $value['total'] = 0;
                    $value['count'] = 0;
                    $dateStringMonth = $months[(int)date('m', strtotime('-' . $i . ' days'))] . '-' . date('d',
                            strtotime('-' . $i . ' days'));
                    if (isset($reportData[date('Y-m-d', strtotime('-' . $i . ' days'))])) {
                        $realValue = $reportData[date('Y-m-d', strtotime('-' . $i . ' days'))];
                        $value['total'] = (float)$realValue['total'];
                        $value['count'] = (int)$realValue['count'];
                    }
                    $data['labels'][] = $dateStringMonth;
                    $data['datasets'][0]['data'][] = $unit === 0 ? $value['total'] : $value['count'];
                }

                break;
            case '12month':
                $dateFrom = date('Y-m-d', strtotime('-12 month'));
                $reporthelper = new ReportHelper();
                $args['group'] = 'month';
                $args['p2p'] = true;
                $args['order'] = $unit === 0 ? 'total' : 'count';
                $awardsAudit = $reporthelper->awardsAudit([$program->account_holder_id], $dateFrom, $dateTo, $args);
                $reportData = [];
                foreach ($awardsAudit as $item) {
                    $reportData[$item->month] = $item->toArray();
                }
                $months = [
                    1 => 'Jan',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'May',
                    6 => 'Jun',
                    7 => 'Jul',
                    8 => 'Aug',
                    9 => 'Sep',
                    10 => 'Oct',
                    11 => 'Nov',
                    12 => 'Dec'
                ];
                $data = [];
                for ($i = 0; $i < 12; $i++) {
                    $value = [];
                    $value['total'] = 0;
                    $value['count'] = 0;
                    $dateStringMonth = $months[(int)date('m', strtotime('-' . $i . ' month'))];
                    if (isset($reportData[(int)date('m', strtotime('-' . $i . ' month'))])) {
                        $realValue = $reportData[(int)date('m', strtotime('-' . $i . ' month'))];
                        $value['total'] = (float)$realValue['total'];
                        $value['count'] = (int)$realValue['count'];
                    }
                    $data['labels'][] = $dateStringMonth;
                    $data['datasets'][0]['data'][] = $unit === 0 ? $value['total'] : $value['count'];
                }

                break;
        endswitch;
        $data['datasets'][0]['borderColor'] = '#573BFF';
        $data['datasets'][0]['backgroundColor'] = 'rgba(255, 99, 132, 0.5)';
        $data['datasets'][0]['label'] = 'Dataset 2';

        return response($data);
    }


}
