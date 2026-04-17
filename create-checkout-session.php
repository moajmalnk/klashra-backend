<?php
/**
 * Create a Stripe Checkout Session for redirection
 * This endpoint supports:
 * - Stripe PHP SDK flow (if installed)
 * - cURL fallback flow (when vendor/autoload.php is missing in deployment)
 */

require_once 'db_connect.php';

if (!headers_sent()) {
    header("Content-Type: application/json; charset=UTF-8");
}

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if (!$data || !isset($data['booking_id']) || !isset($data['type']) || !isset($data['amount'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields: booking_id, type, amount"]);
    exit();
}

$booking_id = (string)$data['booking_id'];
$type = (string)$data['type'];
$amount = (float)$data['amount'];
$product_name = (string)($data['product_name'] ?? "Klashra Service Payment");
$amount_in_cents = (int)round($amount * 100);
$currency = 'aed'; // single-currency implementation
$base_frontend_url = (string)($data['frontend_url'] ?? "http://localhost:8080");
$success_url = $base_frontend_url . "/payment-success?session_id={CHECKOUT_SESSION_ID}";
$cancel_url = $base_frontend_url . "/payment-cancel";

if ($amount_in_cents <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid amount."]);
    exit();
}

// Stripe has minimum charge amounts per currency. For AED, Stripe requires at least 2.00 AED.
// Fail fast with a clear message instead of letting Stripe throw a less obvious error.
$min_amount_in_cents = 200; // 2.00 AED
if ($amount_in_cents < $min_amount_in_cents) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Minimum payment amount is 2 AED."
    ]);
    exit();
}

// Prefer shared config from db_connect.php; fallback to local key if missing.
$stripe_secret_key = $stripe_config['secret_key'] ?? '';
// #region agent log
@file_put_contents('/Users/moajmalp/Documents/Klashra/.cursor/debug-a321fe.log', json_encode([
    'sessionId' => 'a321fe',
    'runId' => 'pre-fix',
    'hypothesisId' => 'H8',
    'location' => 'create-checkout-session.php:41',
    'message' => 'checkout session key check',
    'data' => [
        'httpHost' => strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? ''))),
        'serverName' => strtolower(trim((string)($_SERVER['SERVER_NAME'] ?? ''))),
        'hasStripeSecretKey' => $stripe_secret_key !== '',
    ],
    'timestamp' => round(microtime(true) * 1000),
], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
// #endregion
if ($stripe_secret_key === '') {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Stripe secret key not configured."]);
    exit();
}

try {
    // Path 1: Stripe SDK available.
    if (class_exists('\Stripe\Checkout\Session')) {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $product_name,
                        'description' => "Booking ID: $booking_id ($type)",
                    ],
                    'unit_amount' => $amount_in_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => [
                'booking_id' => $booking_id,
                'type' => $type
            ],
        ]);

        echo json_encode([
            "status" => "success",
            "url" => $session->url
        ]);
        exit();
    }

    // Path 2: SDK missing -> direct Stripe API fallback using cURL.
    if (!function_exists('curl_init')) {
        throw new Exception("Stripe SDK unavailable and cURL extension missing.");
    }

    $payload = [
        'mode' => 'payment',
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
        'payment_method_types[0]' => 'card',
        'line_items[0][quantity]' => '1',
        'line_items[0][price_data][currency]' => $currency,
        'line_items[0][price_data][unit_amount]' => (string)$amount_in_cents,
        'line_items[0][price_data][product_data][name]' => $product_name,
        'line_items[0][price_data][product_data][description]' => "Booking ID: $booking_id ($type)",
        'metadata[booking_id]' => $booking_id,
        'metadata[type]' => $type,
    ];

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $stripe_secret_key,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $raw = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        throw new Exception("Stripe request failed: " . $curl_error);
    }

    $decoded = json_decode($raw, true);
    if ($http_code >= 400 || !is_array($decoded)) {
        $stripe_msg = $decoded['error']['message'] ?? "Stripe API returned HTTP " . $http_code;
        throw new Exception($stripe_msg);
    }

    $checkout_url = $decoded['url'] ?? null;
    if (!$checkout_url) {
        throw new Exception("Stripe session created without checkout URL.");
    }

    echo json_encode([
        "status" => "success",
        "url" => $checkout_url
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Checkout session error: " . $e->getMessage()]);
}
?>
