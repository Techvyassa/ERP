<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreGRNRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mr_id' => 'required|integer|exists:tenant.material_receipts,id',
            'grn_date' => 'required|date',
            'posting_date' => 'required|date',
            'remarks' => 'nullable|string',
            
            // Line items
            'line_items' => 'required|array|min:1',
            'line_items.*.mr_line_id' => 'required|integer|exists:tenant.mr_line_items,id',
            'line_items.*.material_id' => 'required|integer|exists:tenant.material_master,id',
            'line_items.*.accepted_qty' => 'required|numeric|gt:0',
            'line_items.*.uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'line_items.*.batch_number' => 'nullable|string|max:50',
            'line_items.*.manufacturing_date' => 'nullable|date',
            'line_items.*.expiry_date' => 'nullable|date|after_or_equal:line_items.*.manufacturing_date',
            'line_items.*.unit_price' => 'required|numeric|gte:0',
            'line_items.*.tax_rate' => 'nullable|numeric|gte:0|lte:100',
            'line_items.*.warehouse_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
        ];
    }

    public function messages(): array
    {
        return [
            'mr_id.required' => 'Material Receipt ID is required',
            'mr_id.exists' => 'Material Receipt not found',
            'line_items.required' => 'At least one line item is required',
            'line_items.*.accepted_qty.gt' => 'Accepted quantity must be greater than 0',
        ];
    }
}
