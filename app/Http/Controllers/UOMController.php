<?php

namespace App\Http\Controllers;

use App\Models\Tenant\UOM;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Validator;

class UOMController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = UOM::query();

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('uom_code', 'like', "%{$search}%")
                      ->orWhere('uom_name', 'like', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $uoms = $query->orderBy('uom_code')->paginate($perPage);

            return ResponseFormatter::success($uoms, 'UOM list retrieved successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $uom = UOM::findOrFail($id);
            return ResponseFormatter::success($uom, 'UOM retrieved successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, 'UOM not found', 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uom_code' => 'required|string|max:20|unique:uom_master,uom_code',
            'uom_name' => 'required|string|max:100',
            'uom_description' => 'nullable|string',
            'base_unit' => 'nullable|string|max:20',
            'conversion_factor' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $uom = UOM::create(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            ));

            return ResponseFormatter::success($uom, 'UOM created successfully', 201);
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'uom_code' => 'required|string|max:20|unique:uom_master,uom_code,' . $id,
            'uom_name' => 'required|string|max:100',
            'uom_description' => 'nullable|string',
            'base_unit' => 'nullable|string|max:20',
            'conversion_factor' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $uom = UOM::findOrFail($id);
            $uom->update(array_merge(
                $request->all(),
                ['updated_by' => auth()->id()]
            ));

            return ResponseFormatter::success($uom, 'UOM updated successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $uom = UOM::findOrFail($id);
            $uom->delete();

            return ResponseFormatter::success(null, 'UOM deleted successfully');
        } catch (\Exception $e) {
            return ResponseFormatter::error(null, $e->getMessage(), 500);
        }
    }
}
