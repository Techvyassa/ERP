<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sample_size' => 'nullable|numeric|gt:0',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'due_by' => 'nullable|date',
            'remarks' => 'nullable|string',
        ];
    }
}
