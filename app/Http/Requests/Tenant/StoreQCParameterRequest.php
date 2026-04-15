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
        $isProduct = $this->input('product_id') && !$this->input('material_id');
        $scopeId = $isProduct ? $this->input('product_id') : $this->input('material_id');
        $scopeField = $isProduct ? 'product_id' : 'material_id';

        return [
            'material_id' => 'nullable|integer|exists:tenant.material_master,id',
            'product_id'  => 'nullable|integer|exists:tenant.product_master,id',
            'test_type_id' => 'nullable|integer|exists:tenant.qc_test_types,id',
            'parameter_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tenant.qc_parameters_master', 'parameter_code')
                    ->where(fn ($query) => $query->where($scopeField, $scopeId))
                    ->ignore($parameterId),
            ],
            'parameter_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant.qc_parameters_master', 'parameter_name')
                    ->where(fn ($query) => $query->where($scopeField, $scopeId))
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

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $hasMaterial = (bool) $this->input('material_id');
            $hasProduct  = (bool) $this->input('product_id');
            if (!$hasMaterial && !$hasProduct) {
                $v->errors()->add('material_id', 'Either a material or a product must be selected.');
            }
            if ($hasMaterial && $hasProduct) {
                $v->errors()->add('product_id', 'A parameter cannot be linked to both a material and a product.');
            }
        });
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
