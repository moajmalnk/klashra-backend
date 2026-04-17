<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$email = $_GET['email'] ?? '';

if (!$email) {
    // LIST ALL USERS FOR DEBUGGING
    $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
    echo json_encode(["status" => "debug", "total_users" => count($users), "users" => $users]);
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, email, phone, created_at FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if ($user) {
    // Re-calculate what the password SHOULD be for debugging
    $email_val = $user['email'];
    $phone_val = $user['phone'] ?? '';
    
    $cleanEmail = preg_replace('/[^a-zA-Z]/', '', explode('@', $email_val)[0]);
    $prefix = substr($cleanEmail, 0, 3);
    $suffix = substr(preg_replace('/[^0-9]/', '', $phone_val), -4);
    $debugPassword = strtolower($prefix) . $suffix;

    echo json_encode([
        "status" => "success",
        "message" => "User found!",
        "user_details" => $user,
        "debug_calculation" => [
            "cleaned_email_prefix" => $prefix,
            "phone_last_4" => $suffix,
            "EXPECTED_PASSWORD" => $debugPassword
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No user found with this email. Please try doing a test booking first to auto-create the account!"
    ]);
}
?>
