<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CompletePutawayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'putaway_lines' => 'nullable|array',
            'putaway_lines.*.line_number' => 'nullable|integer',
            'putaway_lines.*.batch_number' => 'nullable|string|max:50',
            'putaway_lines.*.quantity' => 'nullable|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'destination_bin_id.exists' => 'Destination bin not found',
            'putaway_lines.*.quantity.gt' => 'Line quantity must be greater than 0',
        ];
    }
}
