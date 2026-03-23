<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreQCTestTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeId = $this->route('id');

        return [
            'type_code'   => 'required|string|max:20|unique:tenant.qc_test_types,type_code' . ($typeId ? ",{$typeId}" : ''),
            'type_name'   => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type_code.required' => 'Type code is required',
            'type_code.unique'   => 'A QC test type with this code already exists',
            'type_name.required' => 'Type name is required',
        ];
    }
}
