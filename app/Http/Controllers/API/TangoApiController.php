<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TeamRequest;
use App\Http\Traits\TeamUploadTrait;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Program;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\TangoOrdersApi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TangoApiController extends Controller
{
    public function index(Organization $organization, Program $program, Request $request )
    {
        return response(TangoOrdersApi::getActiveConfigurations());
    }

    /**
     * List configurations with optional pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listConfigurations(Organization $organization, Program $program, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);
        $configurations = (new TangoOrdersApi)->listConfigurations($limit, $offset);

        return response()->json($configurations);
    }


    /**
     * Count the total number of configurations.
     *
     * @return JsonResponse
     */
    public function countConfigurations(): JsonResponse
    {
        $count = (new TangoOrdersApi)->countConfigurations();
        return response()->json(['count' => $count]);
    }

    /**
     * Create a new configuration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createConfiguration(Request $request): JsonResponse
    {
        try {
            $allowedFields = [
                'name', 'platform_name', 'platform_url', 'platform_mode',
                'account_identifier', 'account_number', 'customer_number',
                'udid', 'etid', 'status', 'is_test'
            ];
            $newConfiguration = TangoOrdersApi::create($request->only($allowedFields));

            return response()->json(['newConfigurationId' => $newConfiguration->id], 201);
        } catch (\Exception $e) {
            Log::error("Failed to create configuration: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create configuration'], 500);
        }
    }

    /**
     * Update an existing configuration.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateConfiguration(Request $request, int $id): JsonResponse
    {
        try {
            $configuration = TangoOrdersApi::find($id);
            if (!$configuration) {
                return response()->json(['error' => 'Configuration not found'], 404);
            }

            $allowedFields = [
                'name', 'platform_name', 'platform_url', 'platform_mode',
                'account_identifier', 'account_number', 'customer_number',
                'udid', 'etid', 'status', 'is_test'
            ];
            $data = $request->only($allowedFields);
            $configuration->update($data);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Failed to update configuration: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update configuration'], 500);
        }
    }

    /**
     * View a specific configuration.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function viewConfiguration(int $id): JsonResponse
    {
        $configuration = (new TangoOrdersApi)->viewConfiguration($id);
        return response()->json($configuration);
    }

    /**
     * Delete a configuration.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteConfiguration(int $id): JsonResponse
    {
        $deleted = (new TangoOrdersApi)->deleteConfiguration($id);
        return response()->json(['deleted' => $deleted]);
    }

    /**
     * Retrieve the test configuration.
     *
     * @return JsonResponse
     */
    public function getTestConfiguration(): JsonResponse
    {
        $testConfiguration = TangoOrdersApi::getTestConfiguration();
        return response()->json($testConfiguration);
    }
}
