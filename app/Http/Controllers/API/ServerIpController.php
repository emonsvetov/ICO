<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServerIp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ServerIpController extends Controller
{
    /**
     * Retrieve a list of server IPs with optional pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function readList(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 100);
        $offset = $request->input('offset', 0);
        $all = filter_var($request->input('all', false), FILTER_VALIDATE_BOOLEAN);

        if (!is_numeric($offset) || $offset < 0) {
            return response()->json(['error' => 'Invalid "offset" passed, integer expected and not less than 0'], 400);
        }
        if (!is_numeric($limit) || $limit < 0) {
            return response()->json(['error' => 'Invalid "limit" passed, integer expected and not less than 0'], 400);
        }

        try {
            $items = ServerIp::readList($limit, $offset, $all);
            $total = ServerIp::countAll();

            return response()->json([
                'data' => $items,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to load server IPs: " . $e->getMessage());
            return response()->json(['error' => 'Failed to load server IPs'], 500);
        }
    }

    /**
     * Retrieve the count of all server IPs.
     *
     * @return JsonResponse
     */
    public function count(): JsonResponse
    {
        $count = ServerIp::countAll();
        return response()->json(['count' => $count]);
    }

    /**
     * Create a new server IP.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function create(Request $request): JsonResponse
    {
        $this->validate($request, [
            'ip' => 'required|string',
            'comment' => 'nullable|string',
            'target' => 'required|integer',
        ]);

        try {
            $data = $request->only(['ip', 'comment', 'target']);
            $id = ServerIp::createIp($data);
            return response()->json(['message' => 'Server IP successfully created', 'id' => $id], 201);
        } catch (\Exception $e) {
            Log::error("Failed to create Server IP: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create Server IP'], 500);
        }
    }

    /**
     * Retrieve a specific server IP by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function readById(int $id): JsonResponse
    {
        try {
            $serverIp = ServerIp::readById($id);
            return response()->json($serverIp);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }

    /**
     * Update a specific server IP by its ID.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->validate($request, [
            'ip' => 'required|string',
            'comment' => 'nullable|string',
            'target' => 'required|integer',
        ]);

        try {
            $data = $request->only(['ip', 'comment', 'target']);
            ServerIp::updateIp($id, $data);
            return response()->json(['message' => 'Server IP successfully updated'], 200);
        } catch (\Exception $e) {
            Log::error("Failed to update Server IP: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update Server IP'], 500);
        }
    }

    /**
     * Delete a specific server IP by its ID.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(int $id, Request $request): JsonResponse
    {
        $updated_by = Auth::id();

        try {
            $success = ServerIp::deleteById($id, $updated_by);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            Log::error("Failed to delete Server IP: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete Server IP'], 500);
        }
    }
}
