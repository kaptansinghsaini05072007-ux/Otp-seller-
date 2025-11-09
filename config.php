<?php
// Database configuration (for future use)
define('DB_HOST', 'localhost');
define('DB_NAME', 'otp_service');
define('DB_USER', 'root');
define('DB_PASS', '');

// Price settings
$PRICE_SETTINGS = [
    'markup_multiplier' => 1.75,
    'currency' => 'INR',
    'usd_to_inr_rate' => 83
];

// Service settings
$SERVICE_SETTINGS = [
    'poll_interval' => 5, // seconds
    'max_orders_per_user' => 5,
    'auto_cancel_time' => 600 // 10 minutes
];
?>
