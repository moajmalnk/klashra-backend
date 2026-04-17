<?php
require_once 'db_connect.php';
$stmt = $pdo->query("SELECT id, name, email FROM users");
$users = $stmt->fetchAll();
file_put_contents('user_dump.txt', print_r($users, true));
?>
