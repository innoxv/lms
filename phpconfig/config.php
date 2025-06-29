<?php
// Specifies the path to the .env file in the current directory
$envFile = __DIR__ . '/.env'; // __DIR__ provides the directory of the current script; concatenates with '/.env'

// Checks if the .env file exists
if (!file_exists($envFile)) { // file_exists() checks if the specified file path exists
    die('Error: .env file not found.'); // die() terminates script execution with an error message
}

// Parses the .env file to load environment variables
$env = parse_ini_file($envFile); // parse_ini_file() reads and parses .env file into an associative array
if ($env === false) { // Checks if parsing failed by comparing to boolean false
    die('Error: Failed to parse .env file.'); // Terminates script with error message if parsing fails
}

// Defines database connection parameters as constants if not already defined
if (!defined('DB_HOST')) define('DB_HOST', $env['DB_HOST']); // Defines DB_HOST constant from $env array
if (!defined('DB_USER')) define('DB_USER', $env['DB_USER']); // Defines DB_USER constant from $env array
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $env['DB_PASSWORD']); // Defines DB_PASSWORD constant from $env array
if (!defined('DB_NAME')) define('DB_NAME', $env['DB_NAME']); // Defines DB_NAME constant from $env array

// Sets up database connection only if it does not already exist
if (!isset($myconn)) { // isset() checks if $myconn variable is not set
    // Establishes a MySQLi connection using defined constants
    $myconn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME); // mysqli_connect() connects to database using host, user, password, and database name
    
    // Verifies if the connection was successful
    if (!$myconn) { // Checks if $myconn is false, indicating connection failure
        die("Connection failed: " . mysqli_connect_error()); // Terminates script with error message and mysqli_connect_error() details
    }
}
?>