<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Entrata;
use App\Models\Hmi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EntrataController extends Controller
{
    public function index(Request $request)
    {
        $extra_args = $request->all();
        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        try {
            $entrata = Entrata::readList($extra_args, $limit, $offset);
            return response()->json($entrata, 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch list: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $entrata = Entrata::view($id);
            if ($entrata) {
                return response()->json($entrata, 200);
            } else {
                return response()->json(['error' => 'Entrata not found'], 404);
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch Entrata: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->all();

        try {
            $entrata = Entrata::createEntrata($data);
            return response()->json($entrata, 201);
        } catch (\Exception $e) {
            Log::error("Failed to create Entrata: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();

        try {
            $updated = Entrata::updateEntrata($id, $data);
            if ($updated) {
                return response()->json(['message' => 'Entrata updated successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed to update Entrata'], 500);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update Entrata: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = Entrata::deleteConfiguration($id);
            if ($deleted) {
                return response()->json(['message' => 'Entrata deleted successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed to delete Entrata'], 500);
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete Entrata: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
