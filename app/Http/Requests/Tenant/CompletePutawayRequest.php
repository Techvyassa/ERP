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
            'bin_scan_confirmed' => 'required|string|max:30',
            'item_scan_confirmed' => 'required|string|max:100',
            'remarks' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'bin_scan_confirmed.required' => 'Bin scan confirmation is required',
            'item_scan_confirmed.required' => 'Item scan confirmation is required',
        ];
    }
}
