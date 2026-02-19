<?php
require_once 'db_connect.php';
$stmt = $pdo->query("SELECT id, title, destinationId, slug FROM holidays ORDER BY id DESC LIMIT 5");
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Last 5 Holidays:\n";
print_r($holidays);

$stmt = $pdo->query("SELECT id, destination FROM destinations ORDER BY id DESC LIMIT 5");
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nLast 5 Destinations:\n";
print_r($destinations);
?>
