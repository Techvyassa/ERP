<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class MakeUsageDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|in:ACCEPTED,REJECTED,CONDITIONALLY_ACCEPTED,REWORK_REQUIRED',
            'accepted_qty' => 'required_if:decision,ACCEPTED|nullable|numeric|gt:0',
            'rejected_qty' => 'required_if:decision,REJECTED|nullable|numeric|gt:0',
            'override_approved_by' => 'nullable|integer|exists:users,id',
            'override_reason' => 'nullable|string|max:500',
            'coa_file_path' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Usage decision is required',
            'accepted_qty.required_if' => 'Accepted quantity is required for ACCEPTED decision',
            'rejected_qty.required_if' => 'Rejected quantity is required for REJECTED decision',
        ];
    }
}
