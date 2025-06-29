<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Validates that the user is logged in
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    $_SESSION['loan_message'] = "Please login"; // Sets an error message in the session
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: signin.html"); // Redirects to the sign-in page
    exit; // Terminates script execution after redirection
}

// Retrieves the user ID from the session
$userId = $_SESSION['user_id']; // Stores user_id from session for use in queries
$loanId = $_GET['loan_id'] ?? null; // Retrieves loan_id from GET request, defaults to null if not set
$statusFilter = $_GET['status'] ?? ''; // Retrieves status filter from GET request, defaults to empty string if not set

// Handles single loan details if loan_id is provided
if ($loanId) { // Checks if a specific loan_id is provided
    // Prepares SQL query to fetch details for a single loan
    $stmt = $myconn->prepare("SELECT loans.*, loan_offers.loan_type, lenders.name AS lender_name 
                         FROM loans 
                         JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
                         JOIN lenders ON loans.lender_id = lenders.lender_id
                         WHERE loans.loan_id = ? AND loans.customer_id IN 
                         (SELECT customer_id FROM customers WHERE user_id = ?)"); // Query ensures loan belongs to the user
    if (!$stmt) { // Checks if statement preparation failed
        $_SESSION['loan_message'] = "Database error"; // Sets a generic error message
        $_SESSION['message_type'] = "error"; // Sets the message type to error
        header("Location: customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
        exit; // Terminates script execution after redirection
    }

    // Binds parameters to the prepared statement
    $stmt->bind_param("ii", $loanId, $userId); // Binds loan_id and user_id as integers
    $stmt->execute(); // Executes the prepared statement
    $result = $stmt->get_result(); // Gets the result set

    // Checks if loan details were found
    if ($result->num_rows > 0) { // num_rows returns the number of rows in the result set
        $_SESSION['loan_details'] = $result->fetch_assoc(); // Stores loan details in session using fetch_assoc()
    } else {
        $_SESSION['loan_message'] = "Loan not found"; // Sets error message for invalid or inaccessible loan
        $_SESSION['message_type'] = "error"; // Sets the message type to error
    }

    // Closes the statement and redirects
    $stmt->close(); // Frees resources associated with the statement
    header("Location: customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
    exit; // Terminates script execution after redirection
}

// Builds query to fetch all loans for the user with optional status filter
$query = "SELECT loans.loan_id, loan_offers.loan_type, lenders.name AS lender_name,
          loans.amount, loans.interest_rate, loans.status, loans.application_date
          FROM loans
          JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
          JOIN lenders ON loans.lender_id = lenders.lender_id
          JOIN customers ON loans.customer_id = customers.customer_id
          WHERE customers.user_id = ?"; // Base query to fetch loans for the user

// Adds status filter if valid and specified
$validStatuses = ['disbursed', 'pending', 'rejected']; // Defines valid loan statuses
if (!empty($statusFilter) && in_array($statusFilter, $validStatuses)) { // Checks if status filter is valid
    $query .= " AND loans.status = ?"; // Adds status condition to the query
}

// Orders results by application date in descending order
$query .= " ORDER BY loans.application_date DESC"; // Sorts loans by newest first

// Prepares the SQL query for secure execution
$stmt = $myconn->prepare($query); // prepare() creates a prepared statement
if (!$stmt) { // Checks if statement preparation failed
    $_SESSION['loan_message'] = "Database error"; // Sets a generic error message
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#loanHistory"); // Redirects to the loanHistory section
    exit; // Terminates script execution after redirection
}

// Binds parameters to the prepared statement
if (!empty($statusFilter)) { // Checks if status filter is applied
    $stmt->bind_param("is", $userId, $statusFilter); // Binds user_id as integer and status as string
} else {
    $stmt->bind_param("i", $userId); // Binds only user_id as integer
}

// Executes the query and fetches results
$stmt->execute(); // Executes the prepared statement
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Fetches all rows as an associative array

// Stores loan history in session
$_SESSION['loan_history'] = $loans; // Assigns fetched loans to session variable

// Sets message if no loans are found
if (empty($loans)) { // Checks if the loans array is empty
    $message = 'No loan history found'; // Default message for no loans
    if (!empty($statusFilter)) { // Adjusts message if a status filter was applied
        $message = "No $statusFilter loans found"; // Specific message for filtered status
    }
    $_SESSION['loan_message'] = $message; // Sets message in session
    $_SESSION['message_type'] = "info"; // Sets the message type to info
}

// Closes the prepared statement
$stmt->close(); // Frees resources associated with the statement

// Redirects to the loanHistory section of the customer dashboard
header("Location: customerDashboard.php#loanHistory"); // Sends HTTP header to redirect
exit; // Terminates script execution after redirection
?>