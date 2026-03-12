<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StorePutawayTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grn_line_id' => 'required|integer|exists:tenant.grn_line_items,id',
            'material_id' => 'required|integer|exists:tenant.material_master,id',
            'batch_number' => 'nullable|string|max:50',
            'quantity' => 'required|numeric|gt:0',
            'uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'source_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'destination_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'strategy' => 'required|in:MANUAL,FIXED_BIN,EMPTY_BIN,FIFO,FEFO',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'grn_line_id.required' => 'GRN line item ID is required',
            'quantity.gt' => 'Quantity must be greater than 0',
        ];
    }
}
