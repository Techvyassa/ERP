<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // RBAC middleware handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Header
            'pr_number'  => 'nullable|string|max:50',
            'vendor_id' => 'required|integer|exists:tenant.vendor_master,id',
            'currency_id' => 'required|integer|exists:tenant.currency_master,id',
            'billing_address' => 'nullable|string|max:500',
            'ship_to_address' => 'nullable|string|max:500',
            'payment_terms' => 'nullable|string|max:30',
            'credit_days' => 'nullable|integer|min:0',
            'delivery_terms' => 'nullable|string|max:20',
            'discount_amount' => 'nullable|numeric|min:0',
            'freight_charges' => 'nullable|numeric|min:0',
            'po_date' => 'required|date',
            'expected_delivery' => 'nullable|date|after_or_equal:po_date',
            'valid_until' => 'nullable|date|after_or_equal:po_date',
            'terms_conditions' => 'nullable|string',
            'remarks' => 'nullable|string',

            // Line Items
            'line_items' => 'required|array|min:1',
            'line_items.*.material_id' => 'required|integer|exists:tenant.material_master,id',
            'line_items.*.material_description' => 'nullable|string|max:300',
            'line_items.*.ordered_qty' => 'required|numeric|gt:0',
            'line_items.*.uom_id' => 'required|integer|exists:tenant.uom_master,id',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'line_items.*.gst_tax_id' => 'nullable|integer|exists:tenant.gst_taxes,id',
            'line_items.*.scheduled_delivery' => 'nullable|date|after_or_equal:po_date',
            'line_items.*.under_delivery_tolerance' => 'nullable|numeric|min:0|max:100',
            'line_items.*.over_delivery_tolerance' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
