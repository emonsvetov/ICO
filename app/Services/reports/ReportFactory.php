<?php

namespace App\Services\reports;

class ReportFactory
{
    public function build(string $title = '', array $params = [])
    {
        $programs = isset($params['programs']) ? $params['programs'] : null;
        if( is_string( $programs ) && $programs )    {
            $programs = $programs ? explode(',', $programs) : [];
        }
        $merchants = isset($params['merchants']) ? $params['merchants'] : null;
        $merchants = $merchants ? explode(',', $merchants) : [];
        $dateFrom = isset($params['from']) ? date('Y-m-d 00:00:00', strtotime($params['from'])) : '';
        $dateTo = isset($params['to']) ? date('Y-m-d 23:59:59', strtotime($params['to'])) : '';
        $paramPage = isset($params['page']) ? (int)$params['page'] : null;
        $paramLimit = isset($params['limit']) ? (int)$params['limit'] : null;
        $exportToCsv = $params['exportToCsv'] ?? 0;
        $active = isset($params['active']) && $params['active'] != 'false' ? 1 : 0;
        $reportKey = $params['reportKey'] ?? 0;
        $programId = $params['programId'] ?? null;
        $createdOnly = $params['createdOnly'] ?? null;
        $group = $params['group'] ?? null;
        $order = $params['order'] ?? null;
        $paginate = false;

        if ($paramPage && $paramLimit) {
            $paginate = true;
            $offset = ($paramPage - 1) * $paramLimit;
            $limit = $paramLimit;
        }

        $params = [
            'merchants' => $merchants,
            'program_account_holder_ids' => $programs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'limit' => $limit ?? null,
            'offset' => $offset ?? null,
            'exportToCsv' => $exportToCsv,
            'active' => $active,
            'reportKey' => $reportKey,
            'programId' => $programId,
            'createdOnly' => $createdOnly,
            'group' => $group,
            'order' => $order,
            'paginate' => $paginate,
        ];

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
                return new $className($params);
            } else {
                throw new \RuntimeException('Report not found.');
            }
        }
    }
}
