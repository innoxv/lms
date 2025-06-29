<?php
// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Initiates or resumes a session to manage user state
if (session_status() == PHP_SESSION_NONE) { // session_status() checks if a session is active; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Validates that the user is logged in and has a customer ID
if (!isset($_SESSION['user_id']) || !isset($_SESSION['customer_id'])) { // isset() checks if user_id and customer_id are set in the session
    $_SESSION['loan_message'] = "Please login to view loan details"; // Sets an error message in the session
    header("Location: /lms/pages/signin.html"); // Redirects to the sign-in page
    exit; // Terminates script execution after redirection
}

// Validates the loan ID from the GET request
if (!isset($_GET['loan_id']) || !is_numeric($_GET['loan_id'])) { // Checks if loan_id is set and numeric
    $_SESSION['loan_message'] = "Invalid loan ID"; // Sets an error message for invalid or missing loan ID
    header("Location: /lms/pages/customerDashboard.php#loanHistory"); // Redirects to the loanHistory section of customerDashboard.php
    exit; // Terminates script execution after redirection
}

// Sanitizes input data to ensure correct types
$loan_id = (int)$_GET['loan_id']; // Converts loan_id to integer
$customer_id = (int)$_SESSION['customer_id']; // Converts customer_id from session to integer

// Builds the SQL query to fetch loan details
$query = "SELECT 
            loans.loan_id,
            loans.offer_id,
            loans.lender_id,
            loans.customer_id,
            loans.amount,
            loans.interest_rate,
            loans.duration,
            loans.installments,
            loans.collateral_value,
            loans.collateral_description,
            loans.status,
            loans.application_date,
            loan_offers.loan_type,
            lenders.name AS lender_name
          FROM loans
          JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
          JOIN lenders ON loans.lender_id = lenders.lender_id
          WHERE loans.loan_id = ? AND loans.customer_id = ?"; // Query joins tables and filters by loan_id and customer_id

// Prepares the SQL query for secure execution
$stmt = $myconn->prepare($query); // prepare() creates a prepared statement
if (!$stmt) { // Checks if statement preparation failed
    $_SESSION['loan_message'] = "Database error"; // Sets a generic error message
    header("Location: /lms/pages/customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
    exit; // Terminates script execution after redirection
}

// Binds parameters to the prepared statement
$stmt->bind_param("ii", $loan_id, $customer_id); // bind_param() binds loan_id and customer_id as integers

// Executes the query and checks for failure
if (!$stmt->execute()) { // Checks if the statement execution failed
    $_SESSION['loan_message'] = "Error fetching loan details"; // Sets error message for query failure
    header("Location: /lms/pages/customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
    exit; // Terminates script execution after redirection
}

// Fetches the query results
$result = $stmt->get_result(); // Gets the result set

// Checks if the loan exists and the customer has access
if ($result->num_rows === 0) { // num_rows returns the number of rows in the result set
    $_SESSION['loan_message'] = "Loan not found or access denied"; // Sets error message for invalid loan or permissions
    header("Location: /lms/pages/customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
    exit; // Terminates script execution after redirection
}

// Fetches loan data
$loan = $result->fetch_assoc(); // fetch_assoc() fetches the result row as an associative array

// Formats loan details for display
$loan_details = [
    'loan_id' => $loan['loan_id'], // Stores loan ID
    'loan_type' => htmlspecialchars($loan['loan_type']), // Escapes loan type for safe display
    'lender_name' => htmlspecialchars($loan['lender_name']), // Escapes lender name for safe display
    'amount' => number_format($loan['amount']), // Formats amount with thousands separators
    'interest_rate' => $loan['interest_rate'], // Stores interest rate as is
    'duration' => $loan['duration'], // Stores duration in months
    'installments' => number_format($loan['installments'], 2), // Formats installments to 2 decimal places
    'collateral_value' => number_format($loan['collateral_value']), // Formats collateral value with thousands separators
    'collateral_description' => htmlspecialchars($loan['collateral_description']), // Escapes collateral description for safe display
    'status' => $loan['status'], // Stores loan status
    'created_date' => date('j M Y', strtotime($loan['application_date'])) // Formats application date as day, month, year
];

// Stores loan details in session for display on the dashboard
$_SESSION['loan_details'] = $loan_details; // Assigns formatted loan details to session variable

// Closes the prepared statement
$stmt->close(); // Frees resources associated with the statement

// Redirects to the loanHistory section of the customer dashboard
header("Location: /lms/pages/customerDashboard.php#loanHistory"); // Sends HTTP header to redirect
exit; // Terminates script execution after redirection
?>