<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Read and execute equipment installments migration
    $sql = file_get_contents(__DIR__ . '/migrations/create_equipment_installments_table.sql');
    $pdo->exec($sql);
    echo "Equipment installments table created successfully\n";
    
    // Read and execute clinic installments migration
    $sql = file_get_contents(__DIR__ . '/migrations/create_clinic_installments_table.sql');
    $pdo->exec($sql);
    echo "Clinic installments table created successfully\n";
    
    // Add installment dates columns
    $sql = file_get_contents(__DIR__ . '/migrations/alter_tables_add_installment_dates.sql');
    $pdo->exec($sql);
    echo "Added installment date columns successfully\n";
    
} catch (PDOException $e) {
    die("Error executing migrations: " . $e->getMessage() . "\n");
}
