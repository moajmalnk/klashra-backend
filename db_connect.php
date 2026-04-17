<?php
/**
 * Database Connection and Global Configuration
 */

// Environment detection
// Note: HTTP_HOST can include a port (e.g. localhost:8001), so normalize first.
$http_host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
$server_name = strtolower(trim((string)($_SERVER['SERVER_NAME'] ?? '')));
$normalized_host = preg_replace('/:\d+$/', '', $http_host);

$is_production_host = (
    $normalized_host === 'api.klashra.com' ||
    strpos($normalized_host, 'api.klashra.com') !== false
);
$is_local = !$is_production_host && (
    strpos($normalized_host, 'localhost') === 0 ||
    strpos($normalized_host, '127.0.0.1') === 0 ||
    strpos($normalized_host, '[::1]') === 0 ||
    $normalized_host === '::1'
);

// Enable verbose errors only on localhost.
$isLocalhost = $is_local || in_array($server_name, ['localhost', '127.0.0.1'], true);
if ($isLocalhost) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
}

// Database Configuration
// - Production API: use production credentials, but allow env vars to override.
// - Local: use config.local.php if present, else local defaults.
// - Other hosts: default to production credentials with env overrides.
if ($is_production_host) {
    $host = getenv('DB_HOST') ?: "localhost";
    $db_name = getenv('DB_NAME') ?: "u608599757_klashra";
    $username = getenv('DB_USER') ?: "u608599757_klashra";
    $password = getenv('DB_PASS') ?: "vF;5|adTO@1#";
} elseif (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
} elseif ($is_local) {
    $host = "localhost";
    $db_name = "klashra";
    $username = "root";
    $password = "";
} else {
    $host = getenv('DB_HOST') ?: "localhost";
    $db_name = getenv('DB_NAME') ?: "u608599757_klashra";
    $username = getenv('DB_USER') ?: "u608599757_klashra";
    $password = getenv('DB_PASS') ?: "vF;5|adTO@1#";
}

// --- CORS (single source: PHP only; .htaccess must NOT set CORS or headers will duplicate) ---
$allowed_origins = [
    'http://localhost:8080', 'http://localhost:8081', 'http://localhost:8082',
    'http://localhost:5173', 'http://localhost:3000',
    'http://127.0.0.1:8080', 'http://127.0.0.1:8081', 'http://127.0.0.1:8082',
    'http://127.0.0.1:5173', 'http://127.0.0.1:3000',
    'https://www.klashra.com', 'https://klashra.com',
    'https://klashra-lyart.vercel.app',
];
$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '' && in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Database Connection failed: " . $e->getMessage()]));
}

// --- Stripe Configuration ---
// IMPORTANT: Stripe keys must be available even when vendor/autoload.php is missing (shared-host deployments).
// This file therefore reads env / .env and builds $stripe_config unconditionally, and only loads the Stripe SDK if present.

// Load environment variables from a local .env file (if present).
// Many shared hosts ignore .htaccess SetEnv or do not pass env vars to PHP-FPM.
// This loader only fills missing vars; it never overwrites existing ones.
$loadDotEnv = function (string $envPath): void {
    if (!file_exists($envPath) || !is_readable($envPath)) return;
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) continue;
        $eqPos = strpos($trimmed, '=');
        if ($eqPos === false) continue;
        $key = trim(substr($trimmed, 0, $eqPos));
        if ($key === '') continue;
        $val = trim(substr($trimmed, $eqPos + 1));
        if ($val === '') continue;
        // Strip optional surrounding quotes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        // Do not overwrite existing values
        $existing = getenv($key);
        if ($existing !== false && trim((string)$existing) !== '') continue;
        if (isset($_SERVER[$key]) && trim((string)$_SERVER[$key]) !== '') continue;
        if (isset($_ENV[$key]) && trim((string)$_ENV[$key]) !== '') continue;

        @putenv($key . '=' . $val);
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
};
$loadDotEnv(__DIR__ . '/.env');

$readEnv = function (string $key): string {
    $fromGetEnv = getenv($key);
    if ($fromGetEnv !== false && $fromGetEnv !== null && trim((string)$fromGetEnv) !== '') {
        return trim((string)$fromGetEnv);
    }
    $fromRedirectGetEnv = getenv('REDIRECT_' . $key);
    if ($fromRedirectGetEnv !== false && $fromRedirectGetEnv !== null && trim((string)$fromRedirectGetEnv) !== '') {
        return trim((string)$fromRedirectGetEnv);
    }
    if (isset($_SERVER[$key]) && trim((string)$_SERVER[$key]) !== '') {
        return trim((string)$_SERVER[$key]);
    }
    if (isset($_SERVER['REDIRECT_' . $key]) && trim((string)$_SERVER['REDIRECT_' . $key]) !== '') {
        return trim((string)$_SERVER['REDIRECT_' . $key]);
    }
    if (isset($_ENV[$key]) && trim((string)$_ENV[$key]) !== '') {
        return trim((string)$_ENV[$key]);
    }
    if (isset($_ENV['REDIRECT_' . $key]) && trim((string)$_ENV['REDIRECT_' . $key]) !== '') {
        return trim((string)$_ENV['REDIRECT_' . $key]);
    }
    return '';
};

$env_publishable = $readEnv('STRIPE_PUBLISHABLE_KEY');
$env_secret = $readEnv('STRIPE_SECRET_KEY');
$env_webhook_secret = $readEnv('STRIPE_WEBHOOK_SECRET');

// Development fallback (localhost only). Production must use environment variables.
if ($isLocalhost) {
    if ($env_publishable === '') $env_publishable = 'pk_test_51MZVhpGL57V6KGVdvLELPaSu6hf5hmxfulAONkoc5sAv7PRjITe1AAPgAJnt4PfC2vhi6iew8EgugL8vgIPeTLWt00CqpL8X0G';
    if ($env_secret === '') $env_secret = 'sk_test_51MZVhpGL57V6KGVdSpFEOX6n0Pqy1q4silObAJaKjSG3LBDw39I8ZN2xjH17oOiQCfgOT4FnxAlIA6DGohoYGlyJ00HT3g4cne';
    // webhook secret intentionally not defaulted; set STRIPE_WEBHOOK_SECRET to test signature verification locally.
}

$stripe_config = [
    'publishable_key' => $env_publishable,
    'secret_key'      => $env_secret,
    'webhook_secret'  => $env_webhook_secret,
    'currency'        => 'AED'
];

// Do NOT hard-fail the entire API when Stripe keys are missing.
// Only Stripe-specific endpoints (checkout/webhook) should enforce required keys.

$vendor_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor_path)) {
    require_once $vendor_path;
    if (class_exists('\Stripe\Stripe')) {
        if (($stripe_config['secret_key'] ?? '') !== '') {
            \Stripe\Stripe::setApiKey($stripe_config['secret_key']);
        }
    }
}
?>
