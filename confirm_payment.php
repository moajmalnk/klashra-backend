<?php
/**
 * Synchronous Payment Confirmation (Fallback for Local Testing or Webhook delays)
 */

header('Content-Type: application/json');
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

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

$session_id = $data['session_id'] ?? null;

file_put_contents('payment_debug.txt', "CONFIRM_PAYMENT STARTED with session: $session_id\n", FILE_APPEND);

if (!$session_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing session_id"]);
    exit();
}

try {
    // Retrieve the session from Stripe
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    file_put_contents('payment_debug.txt', "Session retrieved. Status: {$session->payment_status}\n", FILE_APPEND);

    if ($session->payment_status === 'paid') {
        $booking_id = $session->metadata->booking_id ?? null;
        $type = $session->metadata->type ?? null;
        file_put_contents('payment_debug.txt', "Metadata - ID: $booking_id, Type: $type\n", FILE_APPEND);

        if ($booking_id && $type) {
            // Check if already processed to avoid duplicates
            $checkStmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_id = :tid");
            $checkStmt->execute([':tid' => $session_id]);
            
            if (!$checkStmt->fetch()) {
                // Determine which table to update
                $table = '';
                if ($type === 'hotel') $table = 'hotel_bookings';
                else if ($type === 'holiday') $table = 'holiday_bookings';
                else if ($type === 'visa') $table = 'visa_bookings';
                
                file_put_contents('payment_debug.txt', "Updating table: $table for booking: $booking_id\n", FILE_APPEND);

                if ($table) {
                    // Update Booking Status
                    $sql = "UPDATE $table SET status = 'Paid' WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $booking_id]);
                    
                    // Get customer info to link/create user
                    if ($type === 'visa') {
                        $customerStmt = $pdo->prepare("SELECT email, mobile as phone, applicantName as name FROM visa_bookings WHERE id = :id");
                    } else {
                        $customerStmt = $pdo->prepare("SELECT email, phone, customerName as name FROM $table WHERE id = :id");
                    }
                    
                    $customerStmt->execute([':id' => $booking_id]);
                    $customerInfo = $customerStmt->fetch();
                    
                    if (!$customerInfo) {
                        file_put_contents('payment_debug.txt', "ERROR: Booking not found in database for ID: $booking_id in table: $table\n", FILE_APPEND);
                    }

                    $email = trim($customerInfo['email'] ?? '');
                    $phone = trim($customerInfo['phone'] ?? '');
                    $name = $customerInfo['name'] ?? 'Traveler';
                    $sessionUserId = $_SESSION['user_id'] ?? null;
                    $sessionUserEmail = trim($_SESSION['user_email'] ?? '');

                    file_put_contents('payment_debug.txt', "Processing payment for $email, phone: $phone, name: $name\n", FILE_APPEND);

                    $userId = null;
                    $generatedPassword = null;
                    $isNewUser = false;

                    if ($sessionUserId) {
                        $userId = (int)$sessionUserId;
                        file_put_contents('payment_debug.txt', "Using session user_id: $userId\n", FILE_APPEND);
                    } else if ($email) {
                        file_put_contents('payment_debug.txt', "Email exists, checking user...\n", FILE_APPEND);
                        // Check if user exists
                        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                        $userStmt->execute([':email' => $email]);
                        $user = $userStmt->fetch();

                        if ($user) {
                            $userId = $user['id'];
                            file_put_contents('payment_debug.txt', "User already exists with ID: $userId\n", FILE_APPEND);
                        } else {
                            // Create new user
                            $isNewUser = true;
                            // Formula: first 3 letters of first name + last 4 digits of phone.
                            // Example: CODO + 5678987678 => cod7678
                            $generatedPassword = generatePasswordFromNamePhone($name, $phone);
                            
                            $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);
                            
                            $createUserStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (:name, :email, :password, :phone)");
                            $createUserStmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':password' => $hashedPassword,
                                ':phone' => $phone
                            ]);
                            $userId = $pdo->lastInsertId();
                            file_put_contents('payment_debug.txt', "User created with ID: $userId, Password: $generatedPassword\n", FILE_APPEND);
                        }
                    } else if ($sessionUserEmail !== '') {
                        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                        $userStmt->execute([':email' => $sessionUserEmail]);
                        $user = $userStmt->fetch();
                        if ($user) {
                            $userId = $user['id'];
                            file_put_contents('payment_debug.txt', "Using session user by email with ID: $userId\n", FILE_APPEND);
                        }
                    }

                    if ($userId) {
                        // Link user to booking
                        $linkStmt = $pdo->prepare("UPDATE $table SET user_id = :uid WHERE id = :id");
                        $linkStmt->execute([':uid' => $userId, ':id' => $booking_id]);

                        // Auto-login resolved user after successful payment.
                        $sessionUserStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id LIMIT 1");
                        $sessionUserStmt->execute([':id' => $userId]);
                        $sessionUser = $sessionUserStmt->fetch();
                        if ($sessionUser) {
                            $_SESSION['user_id'] = $sessionUser['id'];
                            $_SESSION['user_name'] = $sessionUser['name'];
                            $_SESSION['user_email'] = $sessionUser['email'];
                            file_put_contents('payment_debug.txt', "Auto-login session set for user {$sessionUser['id']} ({$sessionUser['email']})\n", FILE_APPEND);
                        }
                    }

                    // Insert into Payments History
                    $paymentSql = "INSERT INTO payments (booking_id, booking_type, transaction_id, amount, currency, payment_method) 
                                   VALUES (:booking_id, :booking_type, :transaction_id, :amount, :currency, :method)";
                    $paymentStmt = $pdo->prepare($paymentSql);
                    $paymentStmt->execute([
                        ':booking_id' => $booking_id,
                        ':booking_type' => $type,
                        ':transaction_id' => $session->id,
                        ':amount' => ($session->amount_total / 100),
                        ':currency' => strtoupper($session->currency),
                        ':method' => $session->payment_method_types[0] ?? 'stripe'
                    ]);
                    
                    file_put_contents('payment_debug.txt', "Payment saved successfully.\n", FILE_APPEND);

                    echo json_encode([
                        "status" => "success", 
                        "message" => "Payment synchronized successfully",
                        "booking_id" => $booking_id,
                        "type" => $type,
                        "auto_logged_in" => isset($_SESSION['user_id']),
                        "account_created" => $isNewUser,
                        "credentials" => $isNewUser ? [
                            "email" => $email,
                            "password" => $generatedPassword
                        ] : null
                    ]);
                } else {
                    file_put_contents('payment_debug.txt', "ERROR: Invalid booking type: $type\n", FILE_APPEND);
                    echo json_encode(["status" => "error", "message" => "Invalid booking type"]);
                }
            } else {
                file_put_contents('payment_debug.txt', "Payment already synchronized for session: $session_id\n", FILE_APPEND);
                $sessionUserId = $_SESSION['user_id'] ?? null;
                if ($sessionUserId && $booking_id && $type) {
                    $table = '';
                    if ($type === 'hotel') $table = 'hotel_bookings';
                    else if ($type === 'holiday') $table = 'holiday_bookings';
                    else if ($type === 'visa') $table = 'visa_bookings';

                    if ($table) {
                        $linkStmt = $pdo->prepare("UPDATE $table SET user_id = :uid WHERE id = :id");
                        $linkStmt->execute([':uid' => $sessionUserId, ':id' => $booking_id]);
                        file_put_contents('payment_debug.txt', "Relinked existing booking $booking_id in $table to user $sessionUserId\n", FILE_APPEND);
                    }
                }
                if (!$sessionUserId && $booking_id && $type) {
                    $table = '';
                    if ($type === 'hotel') $table = 'hotel_bookings';
                    else if ($type === 'holiday') $table = 'holiday_bookings';
                    else if ($type === 'visa') $table = 'visa_bookings';

                    if ($table) {
                        if ($type === 'visa') {
                            $customerStmt = $pdo->prepare("SELECT email, applicantName as name FROM visa_bookings WHERE id = :id LIMIT 1");
                        } else {
                            $customerStmt = $pdo->prepare("SELECT email, customerName as name FROM $table WHERE id = :id LIMIT 1");
                        }
                        $customerStmt->execute([':id' => $booking_id]);
                        $customerInfo = $customerStmt->fetch();
                        $email = trim($customerInfo['email'] ?? '');
                        if ($email !== '') {
                            $userStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
                            $userStmt->execute([':email' => $email]);
                            $sessionUser = $userStmt->fetch();
                            if ($sessionUser) {
                                $_SESSION['user_id'] = $sessionUser['id'];
                                $_SESSION['user_name'] = $sessionUser['name'];
                                $_SESSION['user_email'] = $sessionUser['email'];
                                file_put_contents('payment_debug.txt', "Session restored from synced booking for user {$sessionUser['id']} ({$sessionUser['email']})\n", FILE_APPEND);
                            }
                        }
                    }
                }
                echo json_encode([
                    "status" => "success",
                    "message" => "Payment already synchronized",
                    "auto_logged_in" => isset($_SESSION['user_id'])
                ]);
            }
        } else {
            file_put_contents('payment_debug.txt', "ERROR: Booking metadata missing (ID: $booking_id, Type: $type)\n", FILE_APPEND);
            echo json_encode(["status" => "error", "message" => "Booking metadata not found in session"]);
        }
    } else {
        file_put_contents('payment_debug.txt', "Payment STATUS not paid: {$session->payment_status}\n", FILE_APPEND);
        echo json_encode(["status" => "pending", "message" => "Payment not yet confirmed by Stripe"]);
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    file_put_contents('payment_debug.txt', "STRIPE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Stripe error: " . $e->getMessage()]);
} catch (Exception $e) {
    file_put_contents('payment_debug.txt', "SERVER ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>
