<?php
// Load environment variables from .env file
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die('Error: .env file not found.');
}
$env = parse_ini_file($envFile);
if ($env === false) {
    die('Error: Failed to parse .env file.');
}

// Define database connection parameters if not already defined
if (!defined('DB_HOST')) define('DB_HOST', $env['DB_HOST']);
if (!defined('DB_USER')) define('DB_USER', $env['DB_USER']);
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $env['DB_PASSWORD']); 
if (!defined('DB_NAME')) define('DB_NAME', $env['DB_NAME']);

// Database connection setup:
// Only create connection if it doesn't exist
if (!isset($myconn)) {
    $myconn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if (!$myconn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}
?>