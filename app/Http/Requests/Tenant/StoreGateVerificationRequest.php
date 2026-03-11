<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreGateVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challan_verified' => 'required|boolean',
            'invoice_verified' => 'required|boolean',
            'eway_bill_valid' => 'required|boolean',
            'po_status_valid' => 'required|boolean',
            'seal_number' => 'nullable|string|max:50',
            'seal_intact' => 'nullable|boolean',
            'external_damage' => 'required|boolean',
            'tare_weight_kg' => 'nullable|numeric|min:0',
            'net_weight_kg' => 'nullable|numeric|min:0',
            'weight_variance_flag' => 'required|boolean',
            'dock_assigned' => 'nullable|string|max:30',
            'approval_status' => 'required|in:PENDING,APPROVED,REJECTED',
            'rejection_reason' => 'required_if:approval_status,REJECTED|nullable|string',
            'security_remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'approval_status.required' => 'Approval status is required',
            'rejection_reason.required_if' => 'Rejection reason is required when status is REJECTED',
        ];
    }
}
