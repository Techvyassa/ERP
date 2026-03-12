<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePutawayTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_bin_id' => 'nullable|integer|exists:tenant.bin_locations,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'remarks' => 'nullable|string',
        ];
    }
}
