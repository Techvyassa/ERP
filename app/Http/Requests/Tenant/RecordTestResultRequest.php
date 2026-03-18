<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class RecordTestResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parameter_id' => 'nullable|integer',
            'parameter_code' => 'nullable|string|max:50',
            'parameter_name' => 'required|string|max:100',
            'standard_min' => 'nullable|string|max:50',
            'standard_max' => 'nullable|string|max:50',
            'standard_value' => 'nullable|string|max:100',
            'observed_value' => 'required|numeric',
            'unit_of_measurement' => 'nullable|string|max:20',
            'remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'parameter_name.required' => 'Parameter name is required',
            'observed_value.required' => 'Observed value is required for test result',
            'observed_value.numeric' => 'Observed value must be a number',
        ];
    }
}
