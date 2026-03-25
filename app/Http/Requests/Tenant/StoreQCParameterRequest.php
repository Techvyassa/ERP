<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQCParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $parameterId = $this->route('id');

        return [
            'material_id' => 'required|integer|exists:tenant.material_master,id',
            'test_type_id' => 'nullable|integer|exists:tenant.qc_test_types,id',
            'parameter_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tenant.qc_parameters_master', 'parameter_code')
                    ->where(fn ($query) => $query->where('material_id', $this->input('material_id')))
                    ->ignore($parameterId),
            ],
            'parameter_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant.qc_parameters_master', 'parameter_name')
                    ->where(fn ($query) => $query->where('material_id', $this->input('material_id')))
                    ->ignore($parameterId),
            ],
            'parameter_category' => 'nullable|string|max:50',
            'data_type' => 'required|in:NUMERIC,TEXT,BOOLEAN',
            'tolerance_type' => 'required|in:RANGE,MIN_ONLY,MAX_ONLY,EXACT',
            'standard_min' => 'nullable|string|max:50',
            'standard_max' => 'nullable|string|max:50',
            'standard_value' => 'nullable|string|max:100',
            'unit_of_measurement' => 'nullable|string|max:30',
            'test_method' => 'nullable|string|max:100',
            'is_critical' => 'sometimes|boolean',
            'display_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'material_id.required' => 'Material is required',
            'material_id.exists' => 'Selected material was not found',
            'parameter_code.required' => 'Parameter code is required',
            'parameter_code.unique' => 'This parameter code already exists for the selected material',
            'parameter_name.required' => 'Parameter name is required',
            'parameter_name.unique' => 'This parameter name already exists for the selected material',
            'data_type.required' => 'Data type is required',
            'tolerance_type.required' => 'Tolerance type is required',
        ];
    }
}
