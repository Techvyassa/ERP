<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grn_id' => 'required|integer|exists:tenant.grn_headers,id',
            'grn_line_id' => 'required|integer|exists:tenant.grn_line_items,id',
            'material_id' => 'required|integer|exists:tenant.material_master,id',
            'lot_qty' => 'required|numeric|gt:0',
            'sample_size' => 'required|numeric|gt:0|lte:lot_qty',
            'sampling_method' => 'required|in:AQL,100PCT,SKIP',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'due_by' => 'nullable|date|after:now',
            'remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'grn_id.required' => 'GRN ID is required',
            'grn_line_id.required' => 'GRN line item ID is required',
            'sample_size.lte' => 'Sample size cannot exceed lot quantity',
        ];
    }
}
