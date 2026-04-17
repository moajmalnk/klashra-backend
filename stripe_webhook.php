<?php
/**
 * Stripe Webhook Handler
 */

require_once 'db_connect.php';

// The library takes care of verifying the signature if we provide the secret
$endpoint_secret = $stripe_config['webhook_secret'];

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    if (!empty($endpoint_secret)) {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } else {
        // Fallback for testing WITHOUT signature verification (NOT recommended for production)
        $data = json_decode($payload, true);
        $event = \Stripe\Event::constructFrom($data);
    }
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
if ($event->type == 'checkout.session.completed') {
    $session = $event->data->object;

    // Extract booking info from metadata
    $booking_id = $session->metadata->booking_id ?? null;
    $type = $session->metadata->type ?? null;

    if ($booking_id && $type) {
        try {
            // Idempotency: Stripe may retry webhooks. Avoid duplicate payment inserts.
            $existingPaymentStmt = $pdo->prepare("SELECT id FROM payments WHERE transaction_id = :tid LIMIT 1");
            $existingPaymentStmt->execute([':tid' => $session->id]);
            $alreadyProcessed = (bool)$existingPaymentStmt->fetch();

            // Determine which table to update
            $table = '';
            if ($type === 'hotel') $table = 'hotel_bookings';
            else if ($type === 'holiday') $table = 'holiday_bookings';
            else if ($type === 'visa') $table = 'visa_bookings';

            if ($table) {
                // Update booking status (safe to run multiple times)
                $sql = "UPDATE $table SET status = 'Paid' WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $booking_id]);
                
                // --- USER ACCOUNT LOGIC ---
                $customerStmt = $pdo->prepare("SELECT email, phone, customerName, applicantName, mobile FROM $table WHERE id = :id");
                $customerStmt->execute([':id' => $booking_id]);
                $customerInfo = $customerStmt->fetch();
                
                $email = $customerInfo['email'] ?? '';
                $phone = $customerInfo['phone'] ?? $customerInfo['mobile'] ?? '';
                $name = $customerInfo['customerName'] ?? $customerInfo['applicantName'] ?? 'Traveler';

                if ($email) {
                    // Check if user exists
                    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
                    $userStmt->execute([':email' => $email]);
                    $user = $userStmt->fetch();

                    $userId = null;
                    if ($user) {
                        $userId = $user['id'];
                    } else {
                        // Create new user (Formula: first 3 of email + last 4 of phone)
                        $cleanEmail = preg_replace('/[^a-zA-Z]/', '', explode('@', $email)[0]);
                        $prefix = substr($cleanEmail, 0, 3);
                        $suffix = substr(preg_replace('/[^0-9]/', '', $phone), -4);
                        $generatedPassword = strtolower($prefix) . $suffix;
                        
                        $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);
                        
                        $createUserStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (:name, :email, :password, :phone)");
                        $createUserStmt->execute([
                            ':name' => $name,
                            ':email' => $email,
                            ':password' => $hashedPassword,
                            ':phone' => $phone
                        ]);
                        $userId = $pdo->lastInsertId();
                    }

                    // Link user to booking
                    $linkStmt = $pdo->prepare("UPDATE $table SET user_id = :uid WHERE id = :id");
                    $linkStmt->execute([':uid' => $userId, ':id' => $booking_id]);
                }

                // --- INSERT INTO PAYMENTS HISTORY (idempotent) ---
                if (!$alreadyProcessed) {
                    $paymentSql = "INSERT INTO payments (booking_id, booking_type, transaction_id, amount, currency, payment_method) 
                                   VALUES (:booking_id, :booking_type, :transaction_id, :amount, :currency, :method)";
                    $paymentStmt = $pdo->prepare($paymentSql);
                    $paymentStmt->execute([
                        ':booking_id' => $booking_id,
                        ':booking_type' => $type,
                        ':transaction_id' => $session->id,
                        ':amount' => ($session->amount_total / 100), // Stripe uses cents
                        ':currency' => strtoupper($session->currency),
                        ':method' => $session->payment_method_types[0] ?? 'stripe'
                    ]);
                }

                error_log("Payment successful and logged for $type booking #$booking_id");
            }
        } catch (PDOException $e) {
            error_log("Database error in webhook: " . $e->getMessage());
            http_response_code(500);
            exit();
        }
    }
}

http_response_code(200);
?>
