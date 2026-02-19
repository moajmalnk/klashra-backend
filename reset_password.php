<?php
require_once 'db_connect.php';

$username = 'admin@agency.com';
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $sql = "UPDATE admins SET password = :password WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':password' => $hashed_password,
        ':username' => $username
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Password for $username has been reset to $new_password"]);
    } else {
        echo json_encode(["status" => "error", "message" => "User $username not found or password already set."]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
