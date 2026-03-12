<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ge_id' => 'required|integer|exists:tenant.gate_entries,id',
            'po_id' => 'required|integer|exists:tenant.purchase_orders,id',
            'vendor_id' => 'required|integer|exists:tenant.vendor_master,id',
            'unloading_started_at' => 'nullable|date_format:Y-m-d H:i:s',
            'remarks' => 'nullable|string',
            
            // Line items
            'line_items' => 'required|array|min:1',
            'line_items.*.po_line_id' => 'required|integer|exists:tenant.po_line_items,id',
            'line_items.*.material_id' => 'required|integer|exists:tenant.material_master,id',
            'line_items.*.received_qty' => 'required|numeric|gt:0',
            'line_items.*.uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'line_items.*.rejected_on_arrival' => 'nullable|numeric|gte:0',
            'line_items.*.batch_number' => 'nullable|string|max:50',
            'line_items.*.manufacturing_date' => 'nullable|date',
            'line_items.*.expiry_date' => 'nullable|date|after_or_equal:line_items.*.manufacturing_date',
            'line_items.*.provisional_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'line_items.*.damage_found' => 'nullable|boolean',
            'line_items.*.damage_remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'ge_id.required' => 'Gate Entry is required',
            'ge_id.exists' => 'Gate Entry not found',
            'po_id.required' => 'Purchase Order is required',
            'po_id.exists' => 'Purchase Order not found',
            'vendor_id.required' => 'Vendor is required',
            'vendor_id.exists' => 'Vendor not found',
            'line_items.required' => 'At least one line item is required',
            'line_items.min' => 'At least one line item is required',
        ];
    }
}
