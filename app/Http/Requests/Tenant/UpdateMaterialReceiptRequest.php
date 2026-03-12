<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unloading_completed_at' => 'nullable|date_format:Y-m-d H:i:s',
            'remarks' => 'nullable|string',
            
            // Line items (optional for updates)
            'line_items' => 'nullable|array',
            'line_items.*.id' => 'nullable|integer|exists:tenant.mr_line_items,id',
            'line_items.*.po_line_id' => 'nullable|integer|exists:tenant.po_line_items,id',
            'line_items.*.material_id' => 'nullable|integer|exists:tenant.material_master,id',
            'line_items.*.received_qty' => 'nullable|numeric|gt:0',
            'line_items.*.uom_id' => 'nullable|integer|exists:tenant.uom_master,id',
            'line_items.*.rejected_on_arrival' => 'nullable|numeric|gte:0',
            'line_items.*.batch_number' => 'nullable|string|max:50',
            'line_items.*.manufacturing_date' => 'nullable|date',
            'line_items.*.expiry_date' => 'nullable|date|after_or_equal:line_items.*.manufacturing_date',
            'line_items.*.provisional_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'line_items.*.damage_found' => 'nullable|boolean',
            'line_items.*.damage_remarks' => 'nullable|string',
        ];
    }
}
