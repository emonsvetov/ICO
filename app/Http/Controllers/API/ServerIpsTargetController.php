<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServerIpsTarget;
use AWS\CRT\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class ServerIpsTargetController extends Controller
{
    /**
     * Retrieve a list of server IP targets with optional pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function readList(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:0',
            'offset' => 'integer|min:0',
            'all' => 'boolean',
        ]);

        $limit = $request->input('limit', 0);
        $offset = $request->input('offset', 0);
        $all = $request->input('all', false);

        try {
            $targets = ServerIpsTarget::readList($limit, $offset, $all);
            return response()->json(['data' => $targets]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal query failed, please contact the API administrator',
                'message' => $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    /**
     * Retrieve the count of server IP targets.
     *
     * @return JsonResponse
     */
    public function count(): JsonResponse
    {
        $count = ServerIpsTarget::countAll();

        return response()->json(['count' => $count]);
    }

    /**
     * Create a new server IP target.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    /**
     * Create a new server IP target.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:server_ips_target',
        ]);

        try {
            $data = $request->only(['name']);
            $id = ServerIpsTarget::createTarget($data);
            return response()->json(['message' => 'Server IP target successfully created', 'id' => $id], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to create Server IP: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create Target Name'], $e->getCode());
        }
    }


    /**
     * Retrieve a specific server IP target by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function readById(int $id): JsonResponse
    {
        try {
            $target = ServerIpsTarget::readById($id);
            return response()->json($target);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }

    /**
     * Update an existing server IP target by ID.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:server_ips_targets',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        try {
            $success = ServerIpsTarget::updateTarget($id, $data);
            return response()->json(['success' => $success], 200);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }

    /**
     * Delete a specific server IP target by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $success = ServerIpsTarget::deleteById($id);
            return response()->json(['success' => $success]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }
}
