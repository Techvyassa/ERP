<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StorePutawayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grn_id' => 'required|integer|exists:tenant.grn_headers,id',
            'material_id' => 'required|integer|exists:tenant.material_master,id',
            'source_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'destination_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'quantity' => 'required|numeric|gt:0',
            'strategy' => 'nullable|in:MANUAL,FIXED,EMPTY,FIFO',
        ];
    }

    public function messages(): array
    {
        return [
            'grn_id.required' => 'GRN is required',
            'grn_id.exists' => 'GRN not found',
            'material_id.required' => 'Material is required',
            'material_id.exists' => 'Material not found',
            'quantity.required' => 'Quantity is required',
            'quantity.gt' => 'Quantity must be greater than 0',
            'strategy.in' => 'Strategy must be MANUAL, FIXED, EMPTY, or FIFO',
        ];
    }
}
