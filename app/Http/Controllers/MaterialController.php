<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Material;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Material::with(['uom', 'hsnCode']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('material_code', 'like', "%{$search}%")
                      ->orWhere('material_name', 'like', "%{$search}%");
                });
            }

            if ($request->has('material_type')) {
                $query->where('material_type', $request->material_type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $perPage = $request->get('per_page', 15);
            $materials = $query->orderBy('material_code')->paginate($perPage);

            return ResponseFormatter::success($materials, 'Material list retrieved successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $material = Material::with(['uom', 'hsnCode', 'vendorMaps'])->findOrFail($id);
            return ResponseFormatter::success($material, 'Material retrieved successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, 'Material not found', 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'material_code' => 'required|string|max:50|unique:material_master,material_code',
            'material_name' => 'required|string|max:200',
            'material_description' => 'nullable|string',
            'material_type' => 'required|string|max:50',
            'uom_id' => 'required|exists:uom_master,id',
            'hsn_code_id' => 'nullable|exists:hsn_codes,id',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'standard_cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $material = Material::create(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            ));

            return ResponseFormatter::success($material->load(['uom', 'hsnCode']), 'Material created successfully', 201);
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'material_code' => 'required|string|max:50|unique:material_master,material_code,' . $id,
            'material_name' => 'required|string|max:200',
            'material_description' => 'nullable|string',
            'material_type' => 'required|string|max:50',
            'uom_id' => 'required|exists:uom_master,id',
            'hsn_code_id' => 'nullable|exists:hsn_codes,id',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'standard_cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $material = Material::findOrFail($id);
            $material->update(array_merge(
                $request->all(),
                ['updated_by' => auth()->id()]
            ));

            return ResponseFormatter::success($material->load(['uom', 'hsnCode']), 'Material updated successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $material = Material::findOrFail($id);
            $material->delete();

            return ResponseFormatter::success(null, 'Material deleted successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }
}
