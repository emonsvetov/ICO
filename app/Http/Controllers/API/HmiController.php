<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hmi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HmiController extends Controller
{
    /**
     * Retrieve all HMI configurations.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 100);
        $offset = $request->input('offset', 0);

        try {
            $items = Hmi::readList([], $limit, $offset);
            $total = Hmi::readListCount([]);

            return response()->json([
                'data' => $items,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to load HMI configurations: " . $e->getMessage());
            return response()->json(['error' => 'Failed to load HMI configurations'], 500);
        }
    }

    /**
     * Create a new HMI configuration.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function create(Request $request): JsonResponse
    {
        $this->validate($request, [
            'hmi_name' => 'required',
            'hmi_username' => 'required',
            'hmi_password' => 'required',
            'hmi_url' => 'required',
        ]);

        try {
            $data = $request->only([
                'hmi_name', 'hmi_username', 'hmi_password', 'hmi_url', 'hmi_is_test'
            ]);
            Hmi::createHmi($data);

            return response()->json(['message' => 'HMI configuration successfully created'], 201);
        } catch (\Exception $e) {
            Log::error("Failed to create HMI: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create HMI'], 500);
        }
    }

    /**
     * View a specific HMI configuration.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function view(int $id): JsonResponse
    {
        try {
            $hmi = Hmi::view($id);
            if (empty($hmi)) {
                return response()->json(['error' => 'HMI not found'], 404);
            }
            return response()->json($hmi);
        } catch (\Exception $e) {
            Log::error("Failed to load HMI: " . $e->getMessage());
            return response()->json(['error' => 'Failed to load HMI'], 500);
        }
    }

    /**
     * Update an existing HMI configuration.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->validate($request, [
            'hmi_name' => 'required',
            'hmi_username' => 'required',
            'hmi_url' => 'required',
        ]);

        try {
            $data = $request->only([
                'hmi_name', 'hmi_username', 'hmi_url', 'hmi_is_test'
            ]);
            Hmi::updateHmi($id, $data);

            return response()->json(['message' => 'HMI configuration successfully updated'], 200);
        } catch (\Exception $e) {
            Log::error("Failed to update HMI: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update HMI'], 500);
        }
    }

    /**
     * Delete a specific HMI configuration.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = Hmi::deleteConfiguration($id);
            if (!$deleted) {
                return response()->json(['error' => 'HMI not found or already deleted'], 404);
            }
            return response()->json(['message' => 'HMI configuration successfully deleted'], 200);
        } catch (\Exception $e) {
            Log::error("Failed to delete HMI: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete HMI'], 500);
        }
    }
}
