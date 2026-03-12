<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StorePutawayTaskRequest;
use App\Http\Requests\Tenant\UpdatePutawayTaskRequest;
use App\Http\Requests\Tenant\CompletePutawayRequest;
use App\Models\Tenant\PutawayTask;
use App\Services\PutawayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PutawayController extends Controller
{
    protected PutawayService $putawayService;

    public function __construct(PutawayService $putawayService)
    {
        $this->putawayService = $putawayService;
    }

    /**
     * List all putaway tasks
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PutawayTask::with(['grnLineItem', 'material', 'sourceBin', 'destinationBin', 'assignedOperator']);
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by operator
            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }
            
            // Filter by material
            if ($request->has('material_id')) {
                $query->where('material_id', $request->material_id);
            }
            
            $perPage = $request->input('per_page', 15);
            $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch putaway tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single putaway task
     */
    public function show(int $id): JsonResponse
    {
        try {
            $task = PutawayTask::with([
                'grnLineItem.grn',
                'material',
                'uom',
                'sourceBin',
                'destinationBin',
                'assignedOperator',
                'completedByOperator'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Putaway task not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create putaway task
     */
    public function store(StorePutawayTaskRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $task = $this->putawayService->createPutawayTask($request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Putaway task created successfully',
                'data' => $task,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create putaway task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update putaway task
     */
    public function update(int $id, UpdatePutawayTaskRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $task = $this->putawayService->updatePutawayTask($id, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Putaway task updated successfully',
                'data' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update putaway task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start putaway
     */
    public function start(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $task = $this->putawayService->startPutaway($id, $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Putaway started successfully',
                'data' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start putaway: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete putaway
     */
    public function complete(int $id, CompletePutawayRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $task = $this->putawayService->completePutaway($id, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Putaway completed successfully',
                'data' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete putaway: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel putaway
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        try {
            $userId = $request->input('auth_user_id');
            $task = $this->putawayService->cancelPutaway($id, $request->reason, $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Putaway cancelled successfully',
                'data' => $task,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel putaway: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending putaway tasks
     */
    public function pending(): JsonResponse
    {
        try {
            $tasks = PutawayTask::with(['grnLineItem', 'material', 'destinationBin'])
                ->pending()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get in-progress putaway tasks
     */
    public function inProgress(): JsonResponse
    {
        try {
            $tasks = PutawayTask::with(['grnLineItem', 'material', 'destinationBin', 'assignedOperator'])
                ->inProgress()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch in-progress tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get completed putaway tasks
     */
    public function completed(): JsonResponse
    {
        try {
            $tasks = PutawayTask::with(['grnLineItem', 'material', 'destinationBin', 'completedByOperator'])
                ->completed()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch completed tasks: ' . $e->getMessage(),
            ], 500);
        }
    }
}
