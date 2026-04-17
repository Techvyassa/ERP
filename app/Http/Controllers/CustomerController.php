<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Active filter
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $customers = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'payment_terms' => 'nullable|string|max:255',
            'credit_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => [
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $data = $validator->validated();
        
        // Auto-generate customer code based on customer name and contact person
        $data['customer_code'] = Customer::generateCode(
            $data['customer_name'],
            $data['contact_person'] ?? null
        );
        $data['created_by'] = Auth::id();

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => ['customer' => $customer]
        ], 201);
    }

    /**
     * Display the specified customer
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => ['customer' => $customer]
        ]);
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'payment_terms' => 'nullable|string|max:255',
            'credit_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => [
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $customer->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => ['customer' => $customer]
        ]);
    }

    /**
     * Deactivate the specified customer (soft delete)
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Deactivate instead of delete
        $customer->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Customer deactivated successfully'
        ]);
    }

    /**
     * Download CSV template for import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer_import_template.csv"',
        ];

        $columns = [
            'customer_code',
            'customer_name',
            'contact_person',
            'phone',
            'email',
            'billing_address',
            'shipping_address',
            'gstin',
            'payment_terms',
            'credit_days',
            'is_active'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add sample row
            fputcsv($file, [
                '', // customer_code - BLANK (auto-generated from customer_name + contact_person)
                'Acme Global Industries',
                'John Doe',
                '9876543210',
                'john@acme.com',
                '123 Main St, City',
                '456 Shipping St, City',
                '27AABCU9603R1ZX',
                'Net 30',
                '30',
                'true'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import customers from CSV
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file',
                'error' => ['details' => $validator->errors()]
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        $headers = array_shift($data);
        $imported = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header and 0-index
            
            if (count($row) < count($headers)) {
                $errors[] = "Row {$rowNumber}: Incomplete data";
                continue;
            }

            $rowData = array_combine($headers, $row);
            
            // Validate required fields
            if (empty($rowData['customer_name'])) {
                $errors[] = "Row {$rowNumber}: customer_name is required";
                continue;
            }

            try {
                // Auto-generate customer code based on customer name and contact person
                $customerCode = Customer::generateCode(
                    $rowData['customer_name'],
                    $rowData['contact_person'] ?? null
                );
                
                Customer::create([
                    'customer_code' => $customerCode,
                    'customer_name' => $rowData['customer_name'],
                    'contact_person' => $rowData['contact_person'] ?? null,
                    'phone' => $rowData['phone'] ?? null,
                    'email' => $rowData['email'] ?? null,
                    'billing_address' => $rowData['billing_address'] ?? null,
                    'shipping_address' => $rowData['shipping_address'] ?? null,
                    'gstin' => $rowData['gstin'] ?? null,
                    'payment_terms' => $rowData['payment_terms'] ?? null,
                    'credit_days' => !empty($rowData['credit_days']) ? (int)$rowData['credit_days'] : null,
                    'is_active' => isset($rowData['is_active']) ? filter_var($rowData['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
                    'created_by' => Auth::id()
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Imported {$imported} customers successfully",
            'data' => [
                'imported' => $imported,
                'errors' => $errors
            ]
        ]);
    }
}
