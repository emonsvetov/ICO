<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\v2migrate\MigrateProgramsService;
use Illuminate\Http\Request;

class V2DeprecatedProgramController extends Controller
{
    public function index(Request $request, MigrateProgramsService $migrateProgramsService)
    {
        $result = [
            'data' => [],
            'total' => 0
        ];
        $args = [];
        $params = $request->all();
        $paramPage = isset($params['page']) ? (int)$params['page'] : null;
        $paramLimit = isset($params['limit']) ? (int)$params['limit'] : null;
        if ($paramPage && $paramLimit) {
            $migrateProgramsService->offset = ($paramPage - 1) * $paramLimit;
            $migrateProgramsService->limit = $paramLimit;
        }
        $paramName = $params['keyword'] ?? null;
        if ($paramName) {
            $args['name'] = $paramName;
        }

        $v2RootPrograms = $migrateProgramsService->read_list_all_root_program_ids($args);
        $migrateProgramsService->offset = 0;
        $migrateProgramsService->limit = 999999999;
        $v2RootProgramsCount = count($migrateProgramsService->read_list_all_root_program_ids($args));

        if (!empty($v2RootPrograms)) {
            $result['data'] = $v2RootPrograms;
            $result['total'] = $v2RootProgramsCount;
            return response($result);
        }

        return response([]);
    }

    public function migrate(Request $request, $account_holder_id, MigrateProgramsService $migrateProgramsService)
    {
        $args = [];
        $args['program'] = $account_holder_id;
        $migrateProgramsService->migrate($args);

    }

}
