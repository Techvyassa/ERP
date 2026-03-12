<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGRNRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grn_date' => 'nullable|date',
            'posting_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            
            // Line items (optional for updates)
            'line_items' => 'nullable|array',
            'line_items.*.id' => 'nullable|integer|exists:tenant.grn_line_items,id',
            'line_items.*.accepted_qty' => 'nullable|numeric|gt:0',
            'line_items.*.batch_number' => 'nullable|string|max:50',
            'line_items.*.manufacturing_date' => 'nullable|date',
            'line_items.*.expiry_date' => 'nullable|date',
            'line_items.*.warehouse_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
        ];
    }
}
