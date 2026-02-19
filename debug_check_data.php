<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

$output = [];

try {
    // Check Destinations
    $stmt = $pdo->query("SELECT * FROM destinations");
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output['destinations_count'] = count($destinations);
    $output['destinations_data'] = $destinations;

    // Check Holidays
    $stmt = $pdo->query("SELECT * FROM holidays");
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output['holidays_count'] = count($holidays);
    
} catch (Exception $e) {
    $output['error'] = $e->getMessage();
}

file_put_contents('debug_output.json', json_encode($output, JSON_PRETTY_PRINT));
echo "Debug info written to debug_output.json";
?>
