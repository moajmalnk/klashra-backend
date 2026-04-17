<?php
/**
 * ONE-CLICK MIGRATION SCRIPT
 * This script will safely update your database schema to support the new User Dashboard.
 * Run this on your Hostinger server once.
 */

require_once 'db_connect.php';

try {
    // 1. Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        avatar_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Users table created or already exists.<br>";

    // 2. Add user_id to Hotel Bookings
    $checkHotel = $pdo->query("SHOW COLUMNS FROM hotel_bookings LIKE 'user_id'");
    if (!$checkHotel->fetch()) {
        $pdo->exec("ALTER TABLE hotel_bookings ADD COLUMN user_id INT, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;");
        echo "✅ user_id added to hotel_bookings.<br>";
    }

    // 3. Add user_id to Holiday Bookings
    $checkHoliday = $pdo->query("SHOW COLUMNS FROM holiday_bookings LIKE 'user_id'");
    if (!$checkHoliday->fetch()) {
        $pdo->exec("ALTER TABLE holiday_bookings ADD COLUMN user_id INT, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;");
        echo "✅ user_id added to holiday_bookings.<br>";
    }

    // 4. Add user_id to Visa Bookings
    $checkVisa = $pdo->query("SHOW COLUMNS FROM visa_bookings LIKE 'user_id'");
    if (!$checkVisa->fetch()) {
        $pdo->exec("ALTER TABLE visa_bookings ADD COLUMN user_id INT, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;");
        echo "✅ user_id added to visa_bookings.<br>";
    }

    echo "<h3>🎉 Migration Complete! Your database is now compatible with the User Dashboard.</h3>";

} catch (PDOException $e) {
    die("❌ Migration failed: " . $e->getMessage());
}
?>
