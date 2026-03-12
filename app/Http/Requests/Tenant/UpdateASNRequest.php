<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateASNRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            // Header
            'ship_date' => 'sometimes|date',
            'eta' => 'sometimes|date|after_or_equal:ship_date',
            'actual_arrival' => 'nullable|date',
            
            // Transport
            'carrier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:20',
            'container_id' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            
            // Addresses
            'ship_from_address' => 'nullable|string|max:500',
            'ship_to_address' => 'nullable|string|max:500',
            'customer_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ];
    }
}
