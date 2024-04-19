<?php

namespace App\Services\reports;

class CONSTANT extends ReportServiceAbstract {}

class ReportFactory
{
    public function build(string $title = '', array $params = [])
    {
        $program_account_holder_ids = [];
        if(!empty($params['programs']))  {
            $program_account_holder_ids = $params['programs'];
        }   else if( !empty($params['program_account_holder_ids']) )  {
            $program_account_holder_ids = $params['program_account_holder_ids'];
        }   else if( !empty($params['account_holder_ids']) )  {
            $program_account_holder_ids = $params['account_holder_ids'];
        }

        $programs = isset($params['programs']) ? $params['programs'] : null;
        if( is_string( $programs ) && $programs )    {
            $programs = $programs ? explode(',', $programs) : [];
        }
        $merchants = isset($params['merchants']) ? $params['merchants'] : null;
        $merchants = $merchants ? explode(',', $merchants) : [];
        $dateFrom = isset($params[CONSTANT::DATE_FROM]) ? date('Y-m-d 00:00:00', strtotime($params[CONSTANT::DATE_FROM])) : '';
        $dateBegin = isset($params[CONSTANT::DATE_BEGIN]) ? date('Y-m-d 00:00:00', strtotime($params[CONSTANT::DATE_BEGIN])) : '';
        $dateTo = isset($params[CONSTANT::DATE_TO]) ? date('Y-m-d 23:59:59', strtotime($params[CONSTANT::DATE_TO])) : '';
        $dateEnd = isset($params[CONSTANT::DATE_END]) ? date('Y-m-d 00:00:00', strtotime($params[CONSTANT::DATE_END])) : '';
        $paramPage = isset($params['page']) ? (int)$params['page'] : null;
        $paramLimit = isset($params['limit']) ? (int)$params['limit'] : null;
        $exportToCsv = $params['exportToCsv'] ?? 0;
        $active = isset($params['active']) && $params['active'] != 'false' ? 1 : 0;
        $reportKey = $params['reportKey'] ?? 0;
        $programId = $params['programId'] ?? null;
        $createdOnly = $params['createdOnly'] ?? null;
        $rootOnly = $params['rootOnly'] ?? null;
        $group = $params['group'] ?? null;
        $order = $params['order'] ?? null;
        $order = $order ?? $params['sortby'] ?? null;
        $dir = $params['direction'] ?? null;
        $paginate = false;
        $server = $params['server'] ?? null;
        $year = $params['year'] ?? null;
        $targetParticipant = $params['targetParticipant'] ?? null;
        $month = $params['month'] ?? null;
        $codes = $params['codes'] ?? null;
        $inventoryType = $params['inventoryType'] ?? null;
        $keyword = $params['keyword'] ?? null;
        $orderStatus = $params['orderStatus'] ?? null;
        $purchaseByV2 = $params['purchaseByV2'] ?? null;
        if ($paramPage && $paramLimit) {
            $paginate = true;
            $offset = ($paramPage - 1) * $paramLimit;
            $limit = $paramLimit;
        }

        $finalParams = [
            'merchants' => $merchants,
            'program_account_holder_ids' => $program_account_holder_ids,
            CONSTANT::DATE_FROM => $dateFrom,
            CONSTANT::DATE_BEGIN => $dateBegin,
            CONSTANT::DATE_TO => $dateTo,
            CONSTANT::DATE_END => $dateEnd,
            'limit' => $limit ?? null,
            'offset' => $offset ?? null,
            'exportToCsv' => $exportToCsv,
            'active' => $active,
            'reportKey' => $reportKey,
            'programId' => $programId,
            'createdOnly' => $createdOnly,
            'rootOnly' => $rootOnly,
            'group' => $group,
            'order' => $order,
            'dir' => $dir,
            'paginate' => $paginate,
            'server' => $server,
            'year' => $year,
            'targetParticipant' => $targetParticipant,
            'month' => $month,
            'codes' => $codes,
            'inventoryType' => $inventoryType,
            'keyword' => $keyword,
            'orderStatus' => $orderStatus,
            'purchaseByV2' => $purchaseByV2,
            'programs'=> $programs
        ];

        // pr($finalParams);

        if( !empty( $params[CONSTANT::ACCOUNT_TYPES] )) {

            $finalParams[CONSTANT::ACCOUNT_TYPES] = $params[CONSTANT::ACCOUNT_TYPES];
        }

        if( !empty( $params[CONSTANT::JOURNAL_EVENT_TYPES] )) {
            $finalParams[CONSTANT::JOURNAL_EVENT_TYPES] = $params[CONSTANT::JOURNAL_EVENT_TYPES];
        }

        if( isset( $params[CONSTANT::IS_CREDIT] )) {
            $finalParams[CONSTANT::IS_CREDIT] = $params[CONSTANT::IS_CREDIT];
        }

        if (empty($title)) {
            throw new \InvalidArgumentException('Invalid Report Title.');
        } else {
            $resultTitle = '';
            $tmpTitle = explode('-', $title);
            foreach ($tmpTitle as $item){
                $resultTitle .= ucfirst($item);
            }

            $resultTitle = $resultTitle ?: ucfirst($title);

            $className = 'App\Services\reports\Report' . ucfirst($resultTitle).'Service';

            if (class_exists($className)) {
                return new $className($finalParams);
            } else {
                throw new \RuntimeException('Report not found.');
            }
        }
    }
}
