<?php
$content = file_get_contents('routes/web.php');

// Find and replace the broken /create route
$pattern = "/            })->name\('purchase-orders'\);\s+Route::get\('\/create'.*?->name\('create'\);/s";
$replacement = "            })->name('purchase-orders');

            Route::get('/purchase-orders/create', function (\$orgSlug) use (\$getOrg) {
                extract(\$getOrg(\$orgSlug));
                return view('procurement.purchase-orders.create', [
                    'organization' => \$org,
                    'tenantType' => \$tenantType
                ]);
            })->name('purchase-orders.create');";

$new = preg_replace($pattern, $replacement, $content, 1, $count);
if ($count > 0) {
    file_put_contents('routes/web.php', $new);
    echo "Fixed: $count replacement(s) made.\n";
} else {
    echo "Pattern not found.\n";
}
