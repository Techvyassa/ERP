<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreASNRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // RBAC middleware handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Header
            'po_id' => 'required|integer|exists:tenant.purchase_orders,id',
            'vendor_id' => 'required|integer|exists:tenant.vendor_master,id',
            'warehouse_id' => 'required|integer|exists:tenant.warehouse_master,id',
            'ship_date' => 'required|date',
            'eta' => 'required|date|after_or_equal:ship_date',
            'actual_arrival' => 'nullable|date',
            
            // Transport
            'carrier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:20',
            'container_id' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            
            // Addresses
            'ship_from_address' => 'nullable|string|max:500',
            'ship_to_address' => 'nullable|string|max:500',
            'customer_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            
            // Line Items
            'line_items' => 'required|array|min:1',
            'line_items.*.po_line_id' => 'required|integer|exists:tenant.po_line_items,id',
            'line_items.*.material_id' => 'required|integer|exists:tenant.material_master,id',
            'line_items.*.material_description' => 'nullable|string|max:300',
            'line_items.*.shipped_qty' => 'required|numeric|gt:0',
            'line_items.*.uom_id' => 'required|integer|exists:tenant.uom_master,id',
            
            // Traceability
            'line_items.*.batch_number' => 'nullable|string|max:50',
            'line_items.*.lot_number' => 'nullable|string|max:50',
            'line_items.*.manufacturing_date' => 'nullable|date',
            'line_items.*.expiry_date' => 'nullable|date|after_or_equal:line_items.*.manufacturing_date',
            
            // Packaging
            'line_items.*.pallet_id' => 'nullable|string|max:50',
            'line_items.*.sscc' => 'nullable|string|max:50',
            'line_items.*.gross_weight' => 'nullable|numeric|min:0',
            'line_items.*.net_weight' => 'nullable|numeric|min:0',
            'line_items.*.weight_uom' => 'nullable|string|max:10',
            
            // Dimensions
            'line_items.*.length' => 'nullable|numeric|min:0',
            'line_items.*.width' => 'nullable|numeric|min:0',
            'line_items.*.height' => 'nullable|numeric|min:0',
            'line_items.*.dimension_uom' => 'nullable|string|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'po_id.required' => 'Purchase Order is required',
            'po_id.exists' => 'Purchase Order not found',
            'vendor_id.required' => 'Vendor is required',
            'vendor_id.exists' => 'Vendor not found',
            'warehouse_id.required' => 'Warehouse is required',
            'warehouse_id.exists' => 'Warehouse not found',
            'ship_date.required' => 'Shipment date is required',
            'eta.required' => 'Estimated arrival is required',
            'eta.after_or_equal' => 'Estimated arrival must be after or equal to shipment date',
            'line_items.required' => 'At least one line item is required',
            'line_items.min' => 'At least one line item is required',
        ];
    }
}
