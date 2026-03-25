<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS erp_test_tenant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "✓ Database erp_test_tenant created successfully\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
