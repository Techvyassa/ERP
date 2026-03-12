<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreGateEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_id' => 'required|integer|exists:tenant.purchase_orders,id',
            'asn_id' => 'nullable|integer|exists:tenant.asn_headers,id',
            'vendor_id' => 'required|integer|exists:tenant.vendor_master,id',
            'vehicle_number' => 'required|string|max:20',
            'transporter_name' => 'nullable|string|max:100',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:15',
            'challan_number' => 'nullable|string|max:50',
            'vendor_invoice_number' => 'nullable|string|max:50',
            'eway_bill_number' => 'nullable|string|max:30',
            'eway_bill_expiry' => 'nullable|date',
            'material_type' => 'required|in:RAW_MATERIAL,PACKAGING,CONSUMABLE,CAPITAL_GOODS,SPARE_PARTS',
            'gross_weight_kg' => 'nullable|numeric|min:0',
            'arrived_at' => 'required|date_format:Y-m-d H:i:s',
            'remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'po_id.required' => 'Purchase Order is required',
            'po_id.exists' => 'Purchase Order not found',
            'vendor_id.required' => 'Vendor is required',
            'vendor_id.exists' => 'Vendor not found',
            'vehicle_number.required' => 'Vehicle number is required',
            'material_type.required' => 'Material type is required',
            'arrived_at.required' => 'Arrival time is required',
        ];
    }
}
