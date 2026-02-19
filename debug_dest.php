<?php
require_once 'db_connect.php';
$stmt = $pdo->query("SELECT * FROM destinations");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_PRETTY_PRINT);
?>
