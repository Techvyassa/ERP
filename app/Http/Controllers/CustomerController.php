<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::orderBy('customer_name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json(['success' => true, 'data' => $customer]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name'   => 'required|string|max:200',
            'contact_person'  => 'nullable|string|max:100',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address'=> 'nullable|string|max:500',
            'gstin'           => 'nullable|string|max:20',
            'payment_terms'   => 'nullable|string|max:30',
            'credit_days'     => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customer = Customer::create(array_merge(
            $validator->validated(),
            [
                'customer_code' => Customer::generateCode(),
                'created_by'    => $request->input('auth_user_id'),
            ]
        ));

        return response()->json(['success' => true, 'data' => $customer], 201);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_name'   => 'sometimes|string|max:200',
            'contact_person'  => 'nullable|string|max:100',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address'=> 'nullable|string|max:500',
            'gstin'           => 'nullable|string|max:20',
            'payment_terms'   => 'nullable|string|max:30',
            'credit_days'     => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customer->update($validator->validated());

        return response()->json(['success' => true, 'data' => $customer]);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'Customer deactivated.']);
    }
}
