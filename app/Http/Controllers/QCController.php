<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\StoreInspectionLotRequest;
use App\Http\Requests\Tenant\UpdateInspectionLotRequest;
use App\Http\Requests\Tenant\RecordTestResultRequest;
use App\Http\Requests\Tenant\MakeUsageDecisionRequest;
use App\Models\Tenant\InspectionLot;
use App\Models\Tenant\QCParameter;
use App\Services\QCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            $query = InspectionLot::with([
                'grn',
                'productionOrder.product',
                'material',
                'product',
                'assignedTechnician',
                'testResults',
                'usageDecision.decisionMaker'
            ])->excludeProvisionalGrn();
            
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
                'productionOrder.product',
                'material',
                'product',
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
    public function recordTestResult(int $lotId, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            
            // Get JSON input directly from the request
            $jsonData = $request->json()->all();
            
            // Validate manually
            $validator = \Validator::make($jsonData, [
                'parameter_id' => 'nullable|integer',
                'parameter_code' => 'nullable|string|max:50',
                'parameter_name' => 'required|string|max:100',
                'standard_min' => 'nullable|string|max:50',
                'standard_max' => 'nullable|string|max:50',
                'standard_value' => 'nullable|string|max:100',
                'observed_value' => 'required|numeric',
                'sample_size' => 'nullable|numeric|gte:0',
                'unit_of_measurement' => 'nullable|string|max:20',
                'remarks' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                throw new \App\Exceptions\ValidationException(
                    $validator->errors()->toArray(),
                    'Validation failed'
                );
            }
            
            $result = $this->qcService->recordTestResult($lotId, $validator->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Test result recorded successfully',
                'data' => $result,
            ], 201);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getCode(),
                    'details' => $e->getDetails(),
                ],
                'message' => $e->getMessage(),
            ], 422);
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
    public function makeDecision(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->input('auth_user_id');
            
            // Get JSON input directly from the request
            $jsonData = $request->json()->all();
            
            // Validate manually
            $validator = \Validator::make($jsonData, [
                'decision'           => 'required|in:ACCEPTED,REJECTED,CONDITIONALLY_ACCEPTED,REWORK_REQUIRED',
                'accepted_qty'       => 'nullable|numeric|gte:0',
                'rejected_qty'       => 'nullable|numeric|gte:0',
                'return_qty'         => 'nullable|numeric|gte:0',
                'scrap_qty'          => 'nullable|numeric|gte:0',
                'return_remarks'     => 'nullable|string|max:500',
                'scrap_remarks'      => 'nullable|string|max:500',
                'override_approved_by' => 'nullable|integer',
                'override_reason'    => 'nullable|string|max:500',
                'coa_file_path'      => 'nullable|string|max:500',
                'remarks'            => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                throw new \App\Exceptions\ValidationException(
                    $validator->errors()->toArray(),
                    'Validation failed'
                );
            }
            
            $decision = $this->qcService->makeDecision($id, $validator->validated(), $userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Usage decision recorded successfully',
                'data' => $decision,
            ]);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $e->getCode(),
                    'details' => $e->getDetails(),
                ],
                'message' => $e->getMessage(),
            ], 422);
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
            $lots = InspectionLot::with(['grn', 'productionOrder.product', 'material', 'product', 'assignedTechnician'])
                ->excludeProvisionalGrn()
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
            $lots = InspectionLot::with(['grn', 'productionOrder.product', 'material', 'product', 'assignedTechnician', 'testResults'])
                ->excludeProvisionalGrn()
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
            $lots = InspectionLot::with(['grn', 'productionOrder.product', 'material', 'product', 'assignedTechnician', 'testResults', 'usageDecision'])
                ->excludeProvisionalGrn()
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
            $lots = InspectionLot::with(['grn', 'productionOrder.product', 'material', 'product', 'testResults', 'usageDecision'])
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

    public function byProductionOrder(int $productionOrderId): JsonResponse
    {
        try {
            $lots = InspectionLot::with(['productionOrder.product', 'product', 'testResults', 'usageDecision'])
                ->byProductionOrder($productionOrderId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $lots,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch production inspection lots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QC parameters for a material
     */
    public function getParameters(Request $request, int $id): JsonResponse
    {
        try {
            // $id can be either a material_id or product_id depending on the lot source
            // The caller passes ?type=product to look up by product_id
            $type = $request->query('type', 'material'); // 'material' or 'product'

            $parameters = QCParameter::where('is_active', true)
                ->when($type === 'product', fn($q) => $q->where('product_id', $id))
                ->when($type !== 'product', fn($q) => $q->where('material_id', $id))
                ->orderBy('display_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $parameters,
                'type' => $type,
                'id' => $id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch QC parameters: ' . $e->getMessage(),
            ], 500);
        }
    }
}
