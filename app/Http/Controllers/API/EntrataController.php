<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Entrata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EntrataController extends Controller
{
    /**
     * Retrieves a list of Entrata configurations with optional pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $extra_args = $request->except(['limit', 'offset']);
        $limit = (int) $request->get('limit', 10);
        $offset = (int) $request->get('offset', 0);

        try {
            $entrata = Entrata::readList($extra_args, $limit, $offset);
            $totalCount = Entrata::readListCount($extra_args);

            return response()->json([
                'data' => $entrata,
                'totalCount' => $totalCount,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch list: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieves a specific Entrata configuration by its ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
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

    /**
     * Creates a new Entrata configuration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
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

    /**
     * Updates an existing Entrata configuration by ID.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
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

    /**
     * Deletes a specific Entrata configuration by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
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

    /**
     * Verifies the connection to Entrata.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $entrataObject = $request->all();
        $url = $entrataObject['url'].'customers';
        $username = $entrataObject['username'];
        $password = $entrataObject['password'];
        $entrata_property_id = $entrataObject['entrata_property_id'];

        $response = Http::withBasicAuth($username, $password)
            ->post($url, ['propertyId' => $entrata_property_id]);

        if ($response->successful()) {
            $data = $response->json();
            $msg = isset($data['Customers']['Customer']) ? 'Entrata connection works' : 'Entrata connection works. No data found.';
            return response()->json(['success' => true, 'msg' => $msg], 200);
        } else {
            return response()->json(['success' => false, 'msg' => 'Entrata connection failed'], 500);
        }
    }
}
