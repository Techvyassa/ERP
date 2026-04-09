<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * TestApiController
 *
 * A test controller demonstrating all HTTP methods: GET, POST, PUT, PATCH, DELETE.
 * Endpoint prefix: /api/v1/test
 */
class TestApiController extends Controller
{
    /**
     * GET /api/v1/test
     * List all test items.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'method'  => 'GET',
            'message' => 'GET method called successfully',
            'data'    => [
                ['id' => 1, 'name' => 'Item One',   'status' => 'active'],
                ['id' => 2, 'name' => 'Item Two',   'status' => 'inactive'],
                ['id' => 3, 'name' => 'Item Three', 'status' => 'active'],
            ],
        ]);
    }

    /**
     * GET /api/v1/test/{id}
     * Show a single test item by ID.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'method'  => 'GET',
            'message' => "GET method called successfully — item ID: {$id}",
            'data'    => [
                'id'     => $id,
                'name'   => "Item {$id}",
                'status' => 'active',
            ],
        ]);
    }

    /**
     * POST /api/v1/test
     * Create a new test item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        return response()->json([
            'success' => true,
            'method'  => 'POST',
            'message' => 'POST method called successfully — item created',
            'data'    => array_merge(['id' => rand(100, 999)], $validated),
        ], 201);
    }

    /**
     * PUT /api/v1/test/{id}
     * Full update of a test item (all fields required).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        return response()->json([
            'success' => true,
            'method'  => 'PUT',
            'message' => "PUT method called successfully — item {$id} fully updated",
            'data'    => array_merge(['id' => $id], $validated),
        ]);
    }

    /**
     * PATCH /api/v1/test/{id}
     * Partial update of a test item (only provided fields updated).
     */
    public function patch(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        return response()->json([
            'success' => true,
            'method'  => 'PATCH',
            'message' => "PATCH method called successfully — item {$id} partially updated",
            'data'    => array_merge(['id' => $id], $validated),
        ]);
    }

    /**
     * DELETE /api/v1/test/{id}
     * Delete a test item by ID.
     */
    public function destroy(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'method'  => 'DELETE',
            'message' => "DELETE method called successfully — item {$id} deleted",
            'data'    => ['id' => $id],
        ]);
    }
}
