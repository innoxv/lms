<?php
// Initiates or resumes a session to manage user state
if (session_status() === PHP_SESSION_NONE) { // session_status() checks if a session is active; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Validates that the user is logged in as a customer
if (!isset($_SESSION['customer_id'])) { // isset() checks if customer_id is set in the session
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Retrieves the customer ID from the session
$customer_id = $_SESSION['customer_id']; // Stores customer_id from session for use in queries

// Defines a function to fetch payment history for a customer with optional filters
function fetchPaymentHistory($myconn, $customer_id, $filters = []) { // Takes database connection, customer ID, and optional filters array as parameters
    // Builds the base SQL query to fetch payment history
    $query = "SELECT 
        payments.payment_id,
        payments.loan_id,
        payments.amount,
        payments.payment_method,
        payments.payment_type,
        payments.remaining_balance,
        DATE_FORMAT(payments.payment_date, '%Y-%m-%d %H:%i:%s') as payment_date,
        COALESCE(lenders_direct.name, lenders_via_loans.name, 'Unknown') AS lender_name
    FROM payments
    LEFT JOIN lenders AS lenders_direct ON payments.lender_id = lenders_direct.lender_id
    LEFT JOIN loans ON payments.loan_id = loans.loan_id
    LEFT JOIN lenders AS lenders_via_loans ON loans.lender_id = lenders_via_loans.lender_id
    WHERE payments.customer_id = ?
    AND payments.payment_type != 'unpaid'"; // Base query joins tables and excludes unpaid records

    // Initializes parameters and types for the prepared statement
    $params = [$customer_id]; // Starts with customer_id as the first parameter
    $types = "i"; // Initializes types string with 'i' for integer (customer_id)

    // Initializes filters, prioritizing GET, then function filters, then session, then defaults
    $appliedFilters = [
        'payment_type' => isset($_GET['payment_type']) ? $_GET['payment_type'] : ($filters['payment_type'] ?? ($_SESSION['history_filters']['payment_type'] ?? '')), // Resolves payment_type filter
        'payment_method' => isset($_GET['payment_method']) ? $_GET['payment_method'] : ($filters['payment_method'] ?? ($_SESSION['history_filters']['payment_method'] ?? '')), // Resolves payment_method filter
        'amount_range' => isset($_GET['amount_range']) ? $_GET['amount_range'] : ($filters['amount_range'] ?? ($_SESSION['history_filters']['amount_range'] ?? '')), // Resolves amount_range filter
        'date_range' => isset($_GET['date_range']) ? $_GET['date_range'] : ($filters['date_range'] ?? ($_SESSION['history_filters']['date_range'] ?? '')) // Resolves date_range filter
    ];

    // Applies payment type filter if provided
    if ($appliedFilters['payment_type']) { // Checks if payment_type filter is not empty
        $query .= " AND payments.payment_type = ?"; // Adds payment type condition
        $params[] = $appliedFilters['payment_type']; // Adds payment type to parameters
        $types .= "s"; // Appends 's' for string type
    }

    // Applies payment method filter if provided
    if ($appliedFilters['payment_method']) { // Checks if payment_method filter is not empty
        $query .= " AND payments.payment_method = ?"; // Adds payment method condition
        $params[] = $appliedFilters['payment_method']; // Adds payment method to parameters
        $types .= "s"; // Appends 's' for string type
    }

    // Applies amount range filter if provided
    if ($appliedFilters['amount_range']) { // Checks if amount_range filter is not empty
        $rangeParts = explode('-', str_replace('+', '-', $appliedFilters['amount_range'])); // Splits range into parts, replacing '+' with '-'
        if (count($rangeParts) >= 1 && is_numeric($rangeParts[0])) { // Checks if at least min amount is numeric
            $minAmount = $rangeParts[0]; // Stores minimum amount
            $query .= " AND payments.amount >= ?"; // Adds minimum amount condition
            $params[] = $minAmount; // Adds minimum amount to parameters
            $types .= "d"; // Appends 'd' for double type
            if (isset($rangeParts[1]) && is_numeric($rangeParts[1])) { // Checks if max amount is provided and numeric
                $maxAmount = $rangeParts[1]; // Stores maximum amount
                $query .= " AND payments.amount <= ?"; // Adds maximum amount condition
                $params[] = $maxAmount; // Adds maximum amount to parameters
                $types .= "d"; // Appends 'd' for double type
            }
        }
    }

    // Applies date range filter if provided
    if ($appliedFilters['date_range']) { // Checks if date_range filter is not empty
        switch ($appliedFilters['date_range']) { // switch() selects code based on date range value
            case 'today':
                $query .= " AND DATE(payments.payment_date) = CURDATE()"; // Filters for payments made today
                break;
            case 'week':
                $query .= " AND YEARWEEK(payments.payment_date, 1) = YEARWEEK(CURDATE(), 1)"; // Filters for payments this week
                break;
            case 'month':
                $query .= " AND MONTH(payments.payment_date) = MONTH(CURDATE()) AND YEAR(payments.payment_date) = YEAR(CURDATE())"; // Filters for payments this month
                break;
            case 'year':
                $query .= " AND YEAR(payments.payment_date) = YEAR(CURDATE())"; // Filters for payments this year
                break;
        }
    }

    // Orders results by payment date in descending order
    $query .= " ORDER BY payments.payment_date DESC"; // Sorts payments by newest first

    // Prepares the SQL query for secure execution
    $stmt = $myconn->prepare($query); // prepare() creates a prepared statement
    if (!$stmt) { // Checks if statement preparation failed
        error_log("Error preparing fetchPaymentHistory query: " . $myconn->error); // Logs error to server log using error_log()
        $_SESSION['trans_error_message'] = "Error preparing query."; // Sets error message in session
        return []; // Returns empty array on failure
    }

    // Binds parameters to the prepared statement if any exist
    if (!empty($params)) { // Checks if there are parameters to bind
        $stmt->bind_param($types, ...$params); // bind_param() binds parameters using the types string
    }

    // Executes the query and checks for failure
    if (!$stmt->execute()) { // Checks if the statement execution failed
        error_log("Error executing fetchPaymentHistory query: " . $stmt->error); // Logs error to server log
        $_SESSION['trans_error_message'] = "Error executing query."; // Sets error message in session
        return []; // Returns empty array on failure
    }

    // Fetches and returns the results
    $result = $stmt->get_result(); // Gets the result set
    $payments = $result->fetch_all(MYSQLI_ASSOC); // Fetches all rows as an associative array
    $stmt->close(); // Closes the prepared statement

    // Updates session filters if new filters were applied via GET
    if (!empty(array_filter($_GET, fn($key) => in_array($key, ['payment_type', 'payment_method', 'amount_range', 'date_range']), ARRAY_FILTER_USE_KEY))) { // Checks for filter keys in GET
        $_SESSION['history_filters'] = $appliedFilters; // Stores applied filters in session
    }

    // Stores payment history in session
    $_SESSION['payment_history'] = $payments; // Assigns fetched payments to session variable

    // Returns the payment history
    return $payments; // Returns the processed payment data
}

// Handles request for specific payment details
if (isset($_GET['payment_id'])) { // Checks if payment_id is provided in GET request
    $paymentId = filter_var($_GET['payment_id'], FILTER_VALIDATE_INT); // Validates payment_id as an integer
    if (!$paymentId) { // Checks if payment_id is invalid
        $_SESSION['trans_error_message'] = "Invalid payment ID"; // Sets error message for invalid payment ID
        header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
        exit(); // Terminates script execution after redirection
    }
    
    // Verifies that the payment belongs to the customer
    $verifyQuery = "SELECT customer_id FROM payments WHERE payment_id = ?"; // SQL query to check payment ownership
    $stmt = $myconn->prepare($verifyQuery); // Prepares the verification query
    $stmt->bind_param("i", $paymentId); // Binds payment_id as an integer
    $stmt->execute(); // Executes the prepared statement
    $result = $stmt->get_result(); // Gets the result set
    
    if ($result->num_rows === 0) { // Checks if no payment was found
        $_SESSION['trans_error_message'] = "Payment not found"; // Sets error message for missing payment
        header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
        exit(); // Terminates script execution after redirection
    }
    
    $paymentData = $result->fetch_assoc(); // Fetches payment data as an associative array
    if ($paymentData['customer_id'] != $customer_id) { // Checks if payment belongs to the customer
        $_SESSION['trans_error_message'] = "You don't have permission to view this payment"; // Sets error message for unauthorized access
        header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
        exit(); // Terminates script execution after redirection
    }

    // Fetches detailed payment information
    $paymentDetailsQuery = "SELECT 
        payments.payment_id,
        payments.loan_id,
        payments.amount,
        payments.payment_method,
        payments.payment_type,
        payments.remaining_balance,
        DATE_FORMAT(payments.payment_date, '%Y-%m-%d %H:%i:%s') as payment_date,
        COALESCE(lenders_direct.name, lenders_via_loans.name, 'Unknown') AS lender_name
    FROM payments
    LEFT JOIN lenders AS lenders_direct ON payments.lender_id = lenders_direct.lender_id
    LEFT JOIN loans ON payments.loan_id = loans.loan_id
    LEFT JOIN lenders AS lenders_via_loans ON loans.lender_id = lenders_via_loans.lender_id
    WHERE payments.payment_id = ?"; // Query to fetch payment details
    
    $stmt = $myconn->prepare($paymentDetailsQuery); // Prepares the payment details query
    $stmt->bind_param("i", $paymentId); // Binds payment_id as an integer
    $stmt->execute(); // Executes the prepared statement
    $paymentDetails = $stmt->get_result()->fetch_assoc(); // Fetches the payment details as an associative array
    
    if ($paymentDetails) { // Checks if payment details were found
        $_SESSION['payment_details'] = $paymentDetails; // Stores payment details in session
        header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
        exit(); // Terminates script execution after redirection
    } else {
        $_SESSION['trans_error_message'] = "Failed to load payment details"; // Sets error message for failed query
        header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
        exit(); // Terminates script execution after redirection
    }
}

// Handles reset filters request
if (isset($_GET['reset']) && $_GET['reset'] === 'true') { // Checks if reset flag is set to true
    error_log("Reset triggered in paymentHistory.php"); // Logs reset action to server log
    unset($_SESSION['payment_history']); // Removes payment_history from session
    unset($_SESSION['history_filters']); // Removes history_filters from session
    header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
    exit(); // Terminates script execution after redirection
}

// Prevents direct access to the script unless specific actions are requested
if (basename($_SERVER['PHP_SELF']) === 'paymentHistory.php' && !isset($_GET['payment_id']) && !isset($_GET['reset'])) { // Checks if script is accessed directly
    header("Location: customerDashboard.php#transactionHistory"); // Redirects to the transactionHistory section
    exit(); // Terminates script execution after redirection
}
?>