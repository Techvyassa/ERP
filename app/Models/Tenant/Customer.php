<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'customers';

    protected $fillable = [
        'customer_code', 'customer_name', 'contact_person',
        'phone', 'email', 'billing_address', 'shipping_address',
        'gstin', 'payment_terms', 'credit_days', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_days' => 'integer',
    ];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function generateCode(string $customerName, ?string $contactPerson = null): string
    {
        // Extract initials from customer name (first letter of each word)
        $words = preg_split('/\s+/', trim($customerName));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        // Format contact person name (remove spaces, capitalize first letter of each word)
        $contactFormatted = '';
        if (!empty($contactPerson)) {
            $contactWords = preg_split('/\s+/', trim($contactPerson));
            foreach ($contactWords as $word) {
                if (!empty($word)) {
                    $contactFormatted .= ucfirst(strtolower($word));
                }
            }
        }
        
        // Build base code pattern
        $basePattern = $initials;
        if (!empty($contactFormatted)) {
            $basePattern .= '-' . $contactFormatted;
        }
        
        // Find the highest increment number for this pattern
        // Get all customers with codes starting with this pattern
        $existingCustomers = self::where('customer_code', 'like', $basePattern . '-%')
            ->orderBy('customer_code', 'desc')
            ->get();
        
        $maxIncrement = 0;
        
        // Loop through all matching customers to find the highest increment
        foreach ($existingCustomers as $customer) {
            // Extract the last number from the code
            if (preg_match('/-(\d+)$/', $customer->customer_code, $matches)) {
                $currentIncrement = (int) $matches[1];
                if ($currentIncrement > $maxIncrement) {
                    $maxIncrement = $currentIncrement;
                }
            }
        }
        
        // Increment by 1
        $increment = $maxIncrement + 1;
        
        // Format: AGI-JohnDoe-01, AGI-JohnDoe-02, etc.
        return $basePattern . '-' . str_pad($increment, 2, '0', STR_PAD_LEFT);
    }
}
