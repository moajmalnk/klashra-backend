<?php
$host = "localhost";
$db_name = "klashar_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, title, destinationId, slug FROM holidays ORDER BY id DESC LIMIT 5");
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Last 5 Holidays:\n";
    foreach ($holidays as $h) {
        echo "ID: " . $h['id'] . ", Title: " . $h['title'] . ", DestID: " . $h['destinationId'] . "\n";
    }

    $stmt = $pdo->query("SELECT id, destination FROM destinations ORDER BY id DESC LIMIT 5");
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nLast 5 Destinations:\n";
    foreach ($destinations as $d) {
        echo "ID: " . $d['id'] . ", Name: " . $d['destination'] . "\n";
    }

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
