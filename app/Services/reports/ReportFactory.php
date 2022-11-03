<?php

namespace App\Services\reports;

use http\Exception\InvalidArgumentException;
use http\Exception\RuntimeException;

class ReportFactory
{

    public static function build(string $title = '', array $params = [])
    {
        $programs = isset($params['programs']) ? $params['programs'] : null;
        $programs = $programs ? explode(',', $programs) : [];
        $dateFrom = isset($params['from']) ? date('Y-m-d 00:00:00', strtotime($params['from'])) : '';
        $dateTo = isset($params['to']) ? date('Y-m-d 23:59:59', strtotime($params['to'])) : '';
        $paramPage = isset($params['page']) ? (int)$params['page'] : null;
        $paramLimit = isset($params['limit']) ? (int)$params['limit'] : null;
        $exportToCsv = $params['exportToCsv'] ?? 0;

        if ($paramPage && $paramLimit) {
            $offset = ($paramPage - 1) * $paramLimit;
            $limit = $paramLimit;
        }

        $params = [
            'program_account_holder_ids' => $programs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'limit' => $limit ?? null,
            'offset' => $offset ?? null,
            'exportToCsv' => $exportToCsv
        ];

        if (empty($title)) {
            throw new InvalidArgumentException('Invalid Report Title.');
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
                throw new RuntimeException('Report not found.');
            }
        }
    }


}
