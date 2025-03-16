<?php
// Test creating equipment with one-time payment
$data = array(
    "equipment_name" => "Test Scanner",
    "equipment_model" => "TS2000",
    "company_name" => "TestCo",
    "contract_number" => "TEST123",
    "contract_type" => "new",
    "contract_start_date" => "2025-01-21",
    "contract_end_date" => "2026-01-21",
    "total_cost" => 5000,
    "payment_type" => "one_time",
    "maintenance" => ["3"]
);

$ch = curl_init('http://localhost/clinic_management/api/equipment/create.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
