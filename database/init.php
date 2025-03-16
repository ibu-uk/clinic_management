<?php
require_once __DIR__ . '/../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    // Read and execute SQL files
    $sql_files = [
        'monthly_installments.sql',
        'maintenance.sql',
        'notifications.sql'
    ];

    foreach ($sql_files as $file) {
        $sql = file_get_contents(__DIR__ . '/' . $file);
        $db->exec($sql);
    }

    echo "Database tables created successfully\n";

} catch (PDOException $e) {
    echo "Error creating database tables: " . $e->getMessage() . "\n";
}
