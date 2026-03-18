<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreQCResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qc_parameter_id' => 'required|integer|exists:tenant.qc_parameters,id',
            'observed_value' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'qc_parameter_id.required' => 'QC Parameter is required',
            'qc_parameter_id.exists' => 'QC Parameter not found',
            'observed_value.required' => 'Observed value is required',
            'observed_value.numeric' => 'Observed value must be numeric',
        ];
    }
}
