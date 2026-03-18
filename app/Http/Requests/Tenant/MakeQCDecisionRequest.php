<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class MakeQCDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|in:ACCEPTED,REJECTED,CONDITIONAL',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Decision is required',
            'decision.in' => 'Decision must be ACCEPTED, REJECTED, or CONDITIONAL',
            'remarks.max' => 'Remarks cannot exceed 500 characters',
        ];
    }
}
