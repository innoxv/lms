<?php
// Specifies the path to the .env file in the current directory
$envFile = __DIR__ . '/.env'; // Concatenates current directory path with '/.env' to form full file path

// Checks if the .env file exists
if (!file_exists($envFile)) { // Uses file_exists() to verify if the .env file is present
    die('Error: .env file not found.'); // Terminates script with error message if file is missing
}

// Parses the .env file to load environment variables
$env = parse_ini_file($envFile); // Reads and parses .env file into an associative array
if ($env === false) { // Checks if parsing failed by comparing to boolean false
    die('Error: Failed to parse .env file.'); // Terminates script with error message if parsing fails
}

// Defines database connection parameters as constants if not already defined
if (!defined('DB_HOST')) define('DB_HOST', $env['DB_HOST']); // Defines DB_HOST constant with value from $env if not defined
if (!defined('DB_USER')) define('DB_USER', $env['DB_USER']); // Defines DB_USER constant with value from $env if not defined
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $env['DB_PASSWORD']); // Defines DB_PASSWORD constant with value from $env if not defined
if (!defined('DB_NAME')) define('DB_NAME', $env['DB_NAME']); // Defines DB_NAME constant with value from $env if not defined

// Sets up database connection only if it does not already exist
// $myconn is a variable that holds database connection
if (!isset($myconn)) { // Checks if $myconn variable is not set
    // Establishes a MySQLi connection using defined constants
    $myconn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME); //  mysqli_connect function connects to database with host, user, password, and database name
    
    // Verifies if the connection was successful
    if (!$myconn) { // Checks if $myconn is false, indicating connection failure
        die("Connection failed: " . mysqli_connect_error()); // Terminates script with error message and connection error details
    }
}
?>