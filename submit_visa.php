<?php
/**
 * Public API to submit a visa booking inquiry
 */

require_once 'db_connect.php';
session_start();

function generatePasswordFromNamePhone($name, $phone) {
    $name = trim((string)$name);
    $firstToken = $name !== '' ? explode(' ', $name)[0] : '';
    $cleanName = strtolower(preg_replace('/[^a-zA-Z]/', '', $firstToken));
    if ($cleanName === '') {
        $cleanName = 'usr';
    }
    $prefix = substr(str_pad($cleanName, 3, 'x'), 0, 3);

    $digits = preg_replace('/[^0-9]/', '', (string)$phone);
    $suffix = strlen($digits) >= 4 ? substr($digits, -4) : str_pad($digits, 4, '0');

    return $prefix . $suffix;
}

// Get the raw POST data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Basic validation
if (empty($data['applicantName']) || empty($data['mobile']) || empty($data['visaType'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Name, Mobile, and Visa Type are required"
    ]);
    exit();
}

try {
    $sessionUserId = $_SESSION['user_id'] ?? null;
    $applicantName = trim($data['applicantName'] ?? '');
    $email = trim($data['email'] ?? '');
    $mobile = trim($data['mobile'] ?? '');
    $resolvedUserId = $sessionUserId;
    $generatedPassword = null;
    $accountCreated = false;

    if (!$resolvedUserId && $email !== '') {
        $userStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
        $userStmt->execute([':email' => $email]);
        $existingUser = $userStmt->fetch();

        if ($existingUser) {
            $resolvedUserId = (int)$existingUser['id'];
        } else {
            // Auto-create account for visa enquiry users using same password rule.
            $generatedPassword = generatePasswordFromNamePhone($applicantName, $mobile);
            $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

            $createUserStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (:name, :email, :password, :phone)");
            $createUserStmt->execute([
                ':name' => $applicantName !== '' ? $applicantName : 'Traveler',
                ':email' => $email,
                ':password' => $hashedPassword,
                ':phone' => $mobile,
            ]);
            $resolvedUserId = (int)$pdo->lastInsertId();
            $accountCreated = true;
        }
    }

    if ($resolvedUserId) {
        $sessionUserStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id LIMIT 1");
        $sessionUserStmt->execute([':id' => $resolvedUserId]);
        $sessionUser = $sessionUserStmt->fetch();
        if ($sessionUser) {
            $_SESSION['user_id'] = $sessionUser['id'];
            $_SESSION['user_name'] = $sessionUser['name'];
            $_SESSION['user_email'] = $sessionUser['email'];
        }
    }

    $sql = "INSERT INTO visa_bookings (
                user_id,
                applicantName, 
                email, 
                mobile, 
                passportNumber, 
                visaType, 
                destinationCountry, 
                submissionDate,
                status
            ) VALUES (
                :user_id,
                :applicantName, 
                :email, 
                :mobile, 
                :passportNumber, 
                :visaType, 
                :destinationCountry, 
                CURDATE(),
                'Processing'
            )";

    $stmt = $pdo->prepare($sql);
    
    $stmt->bindValue(':user_id', $resolvedUserId, $resolvedUserId ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':applicantName', $applicantName);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':mobile', $mobile);
    $stmt->bindValue(':passportNumber', $data['passportNumber'] ?? '');
    $stmt->bindValue(':visaType', $data['visaType']);
    $stmt->bindValue(':destinationCountry', $data['destinationCountry'] ?? '');

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Your visa inquiry has been submitted successfully. Our team will contact you soon.",
            "auto_logged_in" => isset($_SESSION['user_id']),
            "account_created" => $accountCreated,
            "credentials" => $accountCreated ? [
                "email" => $email,
                "password" => $generatedPassword
            ] : null
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to submit visa enquiry"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
