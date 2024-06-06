<?php

namespace App\Http\Controllers;

use App\Models\ServerIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ServerIpController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $limit = $request->input('limit', 0);
        $offset = $request->input('offset', 0);
        $all = $request->input('all', false);

        $serverIps = ServerIp::readList($limit, $offset, $all);

        return response()->json($serverIps);
    }

    public function count(): \Illuminate\Http\JsonResponse
    {
        $count = ServerIp::countAll();

        return response()->json(['count' => $count]);
    }

    /**
     * @throws ValidationException
     */
    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|string|max:255',
            'comment' => 'nullable|string',
            'target' => 'required|integer',
            'updated_by' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        try {
            $id = ServerIp::createIp($data);
            return response()->json(['id' => $id], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }

    public function readById($id): \Illuminate\Http\JsonResponse
    {
        try {
            $serverIp = ServerIp::readById($id);
            return response()->json($serverIp);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|string|max:255',
            'comment' => 'nullable|string',
            'target' => 'required|integer',
            'updated_by' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated();

        try {
            $success = ServerIp::updateIp($id, $data);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }

    public function delete($id, Request $request): \Illuminate\Http\JsonResponse
    {
        $updated_by = $request->input('updated_by');

        try {
            $success = ServerIp::deleteById($id, $updated_by);
            return response()->json(['success' => $success]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal query failed, please contact the API administrator'], 500);
        }
    }
}
