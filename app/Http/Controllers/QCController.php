<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreInspectionLotRequest;
use App\Http\Requests\Tenant\UpdateInspectionLotRequest;
use App\Http\Requests\Tenant\RecordTestResultRequest;
use App\Http\Requests\Tenant\MakeUsageDecisionRequest;
use App\Models\Tenant\InspectionLot;
use App\Services\QCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QCController extends Controller
{
    protected QCService $qcService;

    public function __construct(QCService $qcService)
    {
        $this->qcService = $qcService;
    }

    /**
     * List all inspection lots
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = InspectionLot::with(['grn', 'material', 'assignedTechnician', 'testResults', 'usageDecision']);
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by technician
            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }
            
            // Filter by date range
            if ($request->has('due_by_from')) {
                $query->where('due_by', '>=', $request->due_by_from);
            }
            if ($request->has('due_by_to')) {
                $query->where('due_by', '<=', $request->due_by_to);
            }
            
            $perPage = $request->input('per_page', 15);
            $lots = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $lots,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inspection lots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single inspection lot
     */
    public function show(int $id): JsonResponse
    {
        try {
            $lot = InspectionLot::with([
                'grn.lineItems',
                'grnLineItem.material',
                'material',
                'assignedTechnician',
                'testResults',
                'usageDecision'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $lot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Inspection lot not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create inspection lot
     */
    public function store(StoreInspectionLotRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $lot = $this->qcService->createInspectionLot($request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Inspection lot created successfully',
                'data' => $lot,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inspection lot: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update inspection lot
     */
    public function update(int $id, UpdateInspectionLotRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $lot = $this->qcService->updateInspectionLot($id, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Inspection lot updated successfully',
                'data' => $lot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inspection lot: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start inspection
     */
    public function startInspection(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $lot = $this->qcService->startInspection($id, $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Inspection started successfully',
                'data' => $lot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete inspection
     */
    public function completeInspection(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $lot = $this->qcService->completeInspection($id, $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Inspection completed successfully',
                'data' => $lot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record test result
     */
    public function recordTestResult(int $lotId, RecordTestResultRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $result = $this->qcService->recordTestResult($lotId, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Test result recorded successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record test result: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Make usage decision
     */
    public function makeDecision(int $id, MakeUsageDecisionRequest $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            $decision = $this->qcService->makeUsageDecision($id, $request->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Usage decision recorded successfully',
                'data' => $decision,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to make usage decision: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending inspection lots
     */
    public function pending(): JsonResponse
    {
        try {
            $lots = InspectionLot::with(['grn', 'material', 'assignedTechnician'])
                ->pending()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $lots,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending lots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get in-progress inspection lots
     */
    public function inProgress(): JsonResponse
    {
        try {
            $lots = InspectionLot::with(['grn', 'material', 'assignedTechnician', 'testResults'])
                ->inProgress()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $lots,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch in-progress lots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get completed inspection lots
     */
    public function completed(): JsonResponse
    {
        try {
            $lots = InspectionLot::with(['grn', 'material', 'assignedTechnician', 'testResults', 'usageDecision'])
                ->completed()
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $lots,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch completed lots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inspection lots by GRN
     */
    public function byGRN(int $grnId): JsonResponse
    {
        try {
            $lots = InspectionLot::with(['grn', 'material', 'testResults', 'usageDecision'])
                ->byGRN($grnId)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $lots,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inspection lots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QC parameters for a material
     */
    public function getParameters(int $materialId): JsonResponse
    {
        try {
            $parameters = QCParameter::where('material_id', $materialId)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $parameters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch QC parameters: ' . $e->getMessage(),
            ], 500);
        }
    }
}
