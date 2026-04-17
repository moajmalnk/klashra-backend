<?php
/**
 * User Authentication Controller (Login, Session Check, Profile)
 */

session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

$action = $_GET['action'] ?? 'check';

if ($action === 'login') {
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(["status" => "error", "message" => "Email and password required"]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']); // Safety
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    }
} 
else if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, avatar_url, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        echo json_encode([
            "status" => "success",
            "authenticated" => true,
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "authenticated" => false
        ]);
    }
}
else if ($action === 'logout') {
    session_destroy();
    echo json_encode(["status" => "success", "message" => "Logged out"]);
}
else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}
?>
