<?php

namespace App\Http\Controllers;

use App\Models\ServerIpsTarget;
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
        $limit = $request->input('limit', 0);
        $offset = $request->input('offset', 0);
        $all = $request->input('all', false);

        if (!is_int($offset) || $offset < 0) {
            throw new InvalidArgumentException('Invalid "offset" passed, integer expected and not less than 0', 400);
        }
        if (!is_int($limit) || $limit < 1) {
            throw new InvalidArgumentException('Invalid "limit" passed, integer expected and not less than 0', 400);
        }

        $targets = ServerIpsTarget::readList($limit, $offset, $all);

        return response()->json($targets);
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
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        try {
            $id = ServerIpsTarget::createTarget($data);
            return response()->json(['id' => $id], 201);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
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
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        try {
            $success = ServerIpsTarget::updateTarget($id, $data);
            return response()->json(['success' => $success]);
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
