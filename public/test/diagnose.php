<?php
/**
 * Diagnostic script for 503 errors
 * Place in public/test/diagnose.php
 * Access: https://ZAPERP.techvyassa.com/test/diagnose.php
 * DELETE after use!
 */

// header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>ERP Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .check { padding: 10px; margin: 5px 0; border-left: 4px solid #ccc; background: #f9f9f9; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .delete-warning { background: #dc3545; color: white; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="delete-warning">
        ⚠️ SECURITY WARNING: Delete this file immediately after diagnosis!
    </div>

    <h1>🔍 ERP Application Diagnostic Tool</h1>
    <p><strong>Timestamp:</strong> <?php 
    // echo date('Y-m-d H:i:s'); 
    ?></p>

    <?php
    // $results = [];

    // Check 1: PHP Version
    // $phpVersion = PHP_VERSION;
    // $phpOk = version_compare($phpVersion, '8.1.0', '>=');
    // $results['PHP Version'] = [
        // 'status' => $phpOk ? 'success' : 'error',
        // 'message' => $phpOk ? "PHP {$phpVersion} (OK)" : "PHP {$phpVersion} (Need 8.1+)",
        // 'details' => "PHP Version: {$phpVersion}"
    // ];

    // Check 2: Required Extensions
    // $requiredExtensions = ['pdo_mysql', 'mbstring', 'xml', 'curl', 'zip', 'openssl', 'json', 'bcmath'];
    // $missingExtensions = [];
    // foreach ($requiredExtensions as $ext) {
    //     if (!extension_loaded($ext)) {
    //         $missingExtensions[] = $ext;
    //     }
    // }
    // $results['Required Extensions'] = [
    //     'status' => empty($missingExtensions) ? 'success' : 'error',
    //     'message' => empty($missingExtensions) ? 'All extensions loaded' : 'Missing: ' . implode(', ', $missingExtensions),
    //     'details' => 'Required: ' . implode(', ', $requiredExtensions)
    // ];

    // Check 3: File Permissions
    // $pathsToCheck = [
    //     'storage' => __DIR__ . '/../../storage',
    //     'storage/framework' => __DIR__ . '/../../storage/framework',
    //     'storage/logs' => __DIR__ . '/../../storage/logs',
    //     'bootstrap/cache' => __DIR__ . '/../../bootstrap/cache',
    // ];
    
    // $permissionIssues = [];
    // foreach ($pathsToCheck as $name => $path) {
    //     if (!is_dir($path)) {
    //         $permissionIssues[] = "{$name}: Directory missing";
    //     } elseif (!is_writable($path)) {
    //         $permissionIssues[] = "{$name}: Not writable";
    //     }
    // }
    // $results['File Permissions'] = [
    //     'status' => empty($permissionIssues) ? 'success' : 'error',
    //     'message' => empty($permissionIssues) ? 'All directories writable' : implode('; ', $permissionIssues),
    //     'details' => implode("\n", $permissionIssues)
    // ];

    // Check 4: Environment File
    // $envFile = __DIR__ . '/../../.env';
    // $envExists = file_exists($envFile);
    // $envReadable = is_readable($envFile);
    // $results['.env File'] = [
    //     'status' => ($envExists && $envReadable) ? 'success' : 'error',
    //     'message' => ($envExists && $envReadable) ? 'Environment file exists and readable' : 'Environment file missing or unreadable',
    //     'details' => "Path: {$envFile}\nExists: " . ($envExists ? 'Yes' : 'No') . "\nReadable: " . ($envReadable ? 'Yes' : 'No')
    // ];

    // Check 5: Maintenance Mode
    // $downFile = __DIR__ . '/../../storage/framework/down';
    // $inMaintenance = file_exists($downFile);
    // $results['Maintenance Mode'] = [
    //     'status' => $inMaintenance ? 'error' : 'success',
    //     'message' => $inMaintenance ? 'Application IS in maintenance mode' : 'Application is NOT in maintenance mode',
    //     'details' => "Down file: " . ($inMaintenance ? 'EXISTS' : 'Not found')
    // ];

    // Check 6: Laravel Bootstrap
    // try {
    //     $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    //     if (file_exists($autoloadPath)) {
    //         require_once $autoloadPath;
    //         $results['Autoload'] = [
    //             'status' => 'success',
    //             'message' => 'Composer autoload loaded',
    //             'details' => 'Autoload file found and loaded'
    //         ];

    //         // Try to bootstrap Laravel
    //         $appPath = __DIR__ . '/../../bootstrap/app.php';
    //         if (file_exists($appPath)) {
    //             $results['Laravel Bootstrap'] = [
    //                 'status' => 'success',
    //                 'message' => 'bootstrap/app.php exists',
    //                 'details' => 'Laravel bootstrap file found'
    //             ];
    //         } else {
    //             $results['Laravel Bootstrap'] = [
    //                 'status' => 'error',
    //                 'message' => 'bootstrap/app.php missing',
    //                 'details' => 'Critical Laravel file not found'
    //             ];
    //         }
    //     } else {
    //         $results['Autoload'] = [
    //             'status' => 'error',
    //             'message' => 'vendor/autoload.php not found',
    //             'details' => 'Run: composer install'
    //         ];
    //     }
    // } catch (Exception $e) {
    //     $results['Bootstrap Error'] = [
    //         'status' => 'error',
    //         'message' => 'Exception during bootstrap',
    //         'details' => $e->getMessage()
    //     ];
    // }

    // Check 7: Disk Space
    // $diskFree = @disk_free_space(__DIR__);
    // $diskTotal = @disk_total_space(__DIR__);
    // if ($diskFree !== false && $diskTotal !== false) {
    //     $diskUsed = $diskTotal - $diskFree;
    //     $diskPercent = round(($diskUsed / $diskTotal) * 100, 2);
    //     $diskStatus = $diskPercent > 90 ? 'error' : ($diskPercent > 80 ? 'warning' : 'success');
    //     $results['Disk Space'] = [
    //         'status' => $diskStatus,
    //         'message' => "Disk usage: {$diskPercent}%",
    //         'details' => sprintf("Total: %.2f GB | Used: %.2f GB | Free: %.2f GB", 
    //             $diskTotal / 1073741824, 
    //             $diskUsed / 1073741824, 
    //             $diskFree / 1073741824)
    //     ];
    // } else {
    //     $results['Disk Space'] = [
    //         'status' => 'warning',
    //         'message' => 'Unable to check disk space',
    //         'details' => 'disk_free_space() function may be disabled'
    //     ];
    // }

    // Check 8: Memory Limit
    // $memoryLimit = ini_get('memory_limit');
    // $memoryOK = $memoryLimit !== '-1' && intval($memoryLimit) >= 128;
    // $results['Memory Limit'] = [
    //     'status' => $memoryOK ? 'success' : 'warning',
    //     'message' => "Memory limit: {$memoryLimit}",
    //     'details' => "Recommended: 256M or higher"
    // ];

    // Check 9: Error Logging
    // $errorLog = ini_get('error_log');
    // $logErrors = ini_get('log_errors');
    // $results['Error Logging'] = [
    //     'status' => $logErrors ? 'success' : 'warning',
    //     'message' => $logErrors ? "Errors logged to: {$errorLog}" : 'Error logging is disabled',
    //     'details' => "log_errors: " . ($logErrors ? 'On' : 'Off')
    // ];

    // Check 10: Database Connection (if possible)
    // if (file_exists($envFile)) {
    //     $envContent = file_get_contents($envFile);
    //     preg_match('/DB_HOST=(.*)/', $envContent, $dbHost);
    //     preg_match('/DB_DATABASE=(.*)/', $envContent, $dbName);
        
    //     if (!empty($dbHost[1]) && !empty($dbName[1])) {
    //         try {
    //             $pdo = new PDO(
    //                 "mysql:host={$dbHost[1]};dbname={$dbName[1]}",
    //                 trim(explode('=', preg_match('/DB_USERNAME=(.*)/', $envContent, $u)[1] ?? '')[0]),
    //                 trim(explode('=', preg_match('/DB_PASSWORD=(.*)/', $envContent, $p)[1] ?? '')[0]),
    //                 [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    //             );
    //             $results['Database Connection'] = [
    //                 'status' => 'success',
    //                 'message' => "Connected to {$dbName[1]}",
    //                 'details' => "Host: {$dbHost[1]}\nDatabase: {$dbName[1]}"
    //             ];
    //         } catch (Exception $e) {
    //             $results['Database Connection'] = [
    //                 'status' => 'error',
    //                 'message' => 'Database connection failed',
    //                 'details' => $e->getMessage()
    //             ];
    //         }
    //     }
    // }
    ?>

    <!-- <h2>Diagnostic Results</h2>
    <table>
        <tr>
            <th>Check</th>
            <th>Status</th>
            <th>Message</th>
            <th>Details</th>
        </tr> -->
        <?php 
        // foreach ($results as $check => $result): 
        ?>
        <tr>
            <td><strong><?php 
            // echo htmlspecialchars($check); 
            ?></strong></td>
            <td>
                <?php

                // $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️'];
                // echo $icons[$result['status']] ?? 'ℹ️';

                ?>
            </td>
            <td><?php 
            // echo htmlspecialchars($result['message']); ?></td>
            <td><pre><?php 
            // echo htmlspecialchars($result['details']); ?></pre></td>
        </tr>
        <?php // endforeach; ?>
    </table>

    <!-- <h2>Next Steps</h2>
    <div class="check info">
        <h3>Common Solutions for 503 Errors:</h3>
        <ol>
            <li><strong>Check error logs:</strong> Look in <code>storage/logs/laravel.log</code></li>
            <li><strong>Clear cache:</strong> If possible, delete <code>bootstrap/cache/*.php</code></li>
            <li><strong>Check PHP-FPM:</strong> Restart PHP-FPM service via hosting panel</li>
            <li><strong>Increase resources:</strong> Check if you've hit memory/CPU limits</li>
            <li><strong>Verify .env:</strong> Ensure all required environment variables are set</li>
        </ol>
    </div> -->

    <!-- <div class="check warning">
        <h3>🔒 Security Reminder</h3>
        <p><strong>DELETE THIS FILE IMMEDIATELY</strong> after diagnosis to prevent unauthorized access to your system information!</p>
    </div> -->
</div>
</body>
</html>
