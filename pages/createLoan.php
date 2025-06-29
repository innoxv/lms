<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Enables error reporting for debugging purposes during development
error_reporting(E_ALL); // error_reporting() sets which PHP errors are reported; E_ALL includes all errors and warnings
ini_set('display_errors', 1); // ini_set() sets the value of a configuration option; displays errors on the page
ini_set('display_startup_errors', 1); // Enables display of errors during PHP startup

// Verifies if the user is logged in by checking for 'user_id' in the session
if (!isset($_SESSION['user_id'])) { // isset() checks if a variable is set and not null
    $_SESSION['loan_message'] = "You must be logged in to create a loan offer."; // Sets an error message in the session
    header("Location: lenderDashboard.php#createLoan"); // Redirects to the createLoan section of lenderDashboard.php
    exit(); // Terminates script execution after redirection
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Stores the user ID from the session for use in queries
$user_id = $_SESSION['user_id']; // $_SESSION is a superglobal array for session variables; $user_id holds the current user's ID

// Fetches the lender ID associated with the user from the lenders table
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$user_id'"; // SQL query to retrieve lender_id
$lenderResult = mysqli_query($myconn, $lenderQuery); // mysqli_query() executes the query on the database connection

// Checks if the user is a registered lender
if (mysqli_num_rows($lenderResult) === 0) { // mysqli_num_rows() returns the number of rows in the result set
    $_SESSION['loan_message'] = "You are not registered as a lender."; // Sets an error message in the session
    header("Location: lenderDashboard.php#createLoan"); // Redirects to the createLoan section
    exit(); // Terminates script execution after redirection
}

// Fetches lender data and stores the lender ID
$lender = mysqli_fetch_assoc($lenderResult); // mysqli_fetch_assoc() fetches a result row as an associative array
$lender_id = $lender['lender_id']; // Assigns the lender_id from the result

// Sanitizes and retrieves form data from POST request
$loan_type = mysqli_real_escape_string($myconn, $_POST['type']); // Escapes special characters in loan_type for SQL safety
$interest_rate = floatval($_POST['interestRate']); // Converts interestRate to float
$max_amount = floatval($_POST['maxAmount']); // Converts maxAmount to float
$max_duration = intval($_POST['maxDuration']); // Converts maxDuration to integer

// Checks if a loan offer of the same type already exists for the lender
$checkQuery = "SELECT offer_id FROM loan_offers 
              WHERE loan_type = '$loan_type' AND lender_id = '$lender_id'"; // SQL query to check for existing loan offer
$checkResult = mysqli_query($myconn, $checkQuery); // Executes the query

// Handles case where the loan type already exists
if (mysqli_num_rows($checkResult) > 0) { // Checks if any rows were returned
    $_SESSION['loan_message'] = "$loan_type already exists in your loan offers!"; // Sets error message with loan type
    header("Location: lenderDashboard.php#createLoan"); // Redirects to the createLoan section
    exit(); // Terminates script execution after redirection
}

// Inserts the new loan offer into the loan_offers table
$sql = "INSERT INTO loan_offers 
        (lender_id, loan_type, interest_rate, max_amount, max_duration)
        VALUES 
        ('$lender_id', '$loan_type', '$interest_rate', '$max_amount', '$max_duration')"; // SQL query to insert loan offer data

// Executes the insertion and handles the result
if (mysqli_query($myconn, $sql)) { // mysqli_query() executes the insertion query; checks if successful
    // Logs the loan offer creation activity
    $activity = "Created loan offer: $loan_type"; // Creates activity description with loan type
    $logSql = "INSERT INTO activity (user_id, activity, activity_time, activity_type)
              VALUES ('$user_id', '$activity', NOW(), 'loan offer creation')"; // SQL query to log activity
    mysqli_query($myconn, $logSql); // Executes the activity log query
    
    // Calculates the new average interest rate for the lender
    $avgQuery = "SELECT AVG(interest_rate) AS new_avg FROM loan_offers WHERE lender_id = '$lender_id'"; // SQL query to compute average interest rate
    $avgResult = mysqli_query($myconn, $avgQuery); // Executes the query
    $avgData = mysqli_fetch_assoc($avgResult); // Fetches the result as an associative array
    $newAverage = $avgData['new_avg']; // Stores the new average interest rate

    // Updates the lender's average interest rate in the lenders table
    $updateLender = "UPDATE lenders SET average_interest_rate = '$newAverage' WHERE lender_id = '$lender_id'"; // SQL query to update average_interest_rate
    mysqli_query($myconn, $updateLender); // Executes the update query

    $_SESSION['loan_message'] = "$loan_type created successfully!"; // Sets success message with loan type
} else {
    $_SESSION['loan_message'] = "Error creating loan offer: " . mysqli_error($myconn); // Sets error message with database error
}

// Closes the database connection
mysqli_close($myconn); // mysqli_close() terminates the database connection

// Redirects to the createLoan section of the lender dashboard
header("Location: lenderDashboard.php#createLoan"); // Sends HTTP header to redirect
exit(); // Terminates script execution after redirection
?>