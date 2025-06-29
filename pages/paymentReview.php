<?php
// Initiates or resumes a session to manage user state
if (session_status() === PHP_SESSION_NONE) { // session_status() checks if a session is active; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Includes access restriction checks
require_once 'check_access.php'; // Imports access control logic from check_access.php

// Redirects to login if user_id is not set
if (!isset($_SESSION['user_id'])) { // isset() checks if user_id is set in the session
    $_SESSION['loan_message'] = "Please log in to access the dashboard."; // Sets an error message in the session
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Terminates script execution after redirection
}

// Initializes arrays for payments and payment methods
$payments = []; // Empty array to store payment records
$paymentMethods = []; // Empty array to store available payment methods

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Checks if database connection was successful
if (!$myconn) { // Checks if $myconn is null or false
    error_log("Database connection failed: " . mysqli_connect_error()); // Logs connection error to server log
    $_SESSION['loan_message'] = "Database connection error. Please try again later."; // Sets error message in session
} else {
    // Sanitizes user_id to prevent SQL injection
    $userId = mysqli_real_escape_string($myconn, $_SESSION['user_id']); // Escapes special characters in user_id

    // Fetches lender_id for the logged-in user
    $lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'"; // SQL query to get lender_id
    $lenderResult = mysqli_query($myconn, $lenderQuery); // Executes the query

    // Checks if lender record exists
    if (mysqli_num_rows($lenderResult) > 0) { // mysqli_num_rows() returns the number of rows in the result set
        $lender = mysqli_fetch_assoc($lenderResult); // Fetches lender data as an associative array
        $lender_id = $lender['lender_id']; // Stores lender_id
        $_SESSION['lender_id'] = $lender_id; // Stores lender_id in session for other scripts
    } else {
        error_log("Warning: No lender record found for user_id: " . ($_SESSION['user_id'] ?? 'unknown')); // Logs warning to server log
        $_SESSION['loan_message'] = "You are not registered as a lender."; // Sets error message
        header("Location: lenderDashboard.php"); // Redirects to lender dashboard
        exit(); // Terminates script execution after redirection
    }

    // Retrieves filter parameters from GET request
    $paymentTypeFilter = $_GET['payment_type'] ?? ''; // Gets payment_type filter, defaults to empty string
    $paymentMethodFilter = $_GET['payment_method'] ?? ''; // Gets payment_method filter, defaults to empty string
    $dateRangeFilter = $_GET['date_range'] ?? ''; // Gets date_range filter, defaults to empty string
    $amountRangeFilter = $_GET['amount_range'] ?? ''; // Gets amount_range filter, defaults to empty string
    $balanceRangeFilter = $_GET['balance_range'] ?? ''; // Gets balance_range filter, defaults to empty string

    // Resets filters on initial load if no GET parameters are present
    if (empty($_GET)) { // Checks if GET array is empty
        $paymentTypeFilter = ''; // Resets payment type filter
        $paymentMethodFilter = ''; // Resets payment method filter
        $dateRangeFilter = ''; // Resets date range filter
        $amountRangeFilter = ''; // Resets amount range filter
        $balanceRangeFilter = ''; // Resets balance range filter
    }

    // Validates payment type filter
    $validPaymentTypes = ['principal', 'interest', 'penalty']; // Defines valid payment types
    if (!empty($paymentTypeFilter) && !in_array($paymentTypeFilter, $validPaymentTypes)) { // Checks if payment type is invalid
        $paymentTypeFilter = ''; // Resets invalid payment type filter
    }
    if (!empty($paymentMethodFilter)) { // Checks if payment method filter is set
        $paymentMethodFilter = mysqli_real_escape_string($myconn, $paymentMethodFilter); // Escapes payment method for SQL safety
    }

    // Builds the query to fetch payment records
    $paymentsQuery = "SELECT 
        payments.payment_id,
        payments.loan_id,
        payments.amount,
        payments.payment_method,
        payments.payment_type,
        payments.remaining_balance,
        payments.payment_date,
        customers.name AS customer_name,
        loan_offers.loan_type
    FROM payments
    JOIN customers ON payments.customer_id = customers.customer_id
    JOIN loans ON payments.loan_id = loans.loan_id
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    WHERE payments.lender_id = '$lender_id'
        -- to prevent showing payments with payment_type unpaid (this is default in the database when a lender approves request)
    AND payments.payment_type != 'unpaid'"; // Base query joins tables and excludes unpaid payments

    // Applies payment type filter
    if (!empty($paymentTypeFilter)) { // Checks if payment type filter is set
        $paymentsQuery .= " AND payments.payment_type = '$paymentTypeFilter'"; // Adds payment type condition
    }

    // Applies payment method filter
    if (!empty($paymentMethodFilter)) { // Checks if payment method filter is set
        $paymentsQuery .= " AND payments.payment_method = '$paymentMethodFilter'"; // Adds payment method condition
    }

    // Applies date range filter
    if (!empty($dateRangeFilter)) { // Checks if date range filter is set
        switch ($dateRangeFilter) { // switch() selects code based on date range value
            case 'today':
                $paymentsQuery .= " AND DATE(payments.payment_date) = CURDATE()"; // Filters for payments today
                break;
            case 'week':
                $paymentsQuery .= " AND YEARWEEK(payments.payment_date, 1) = YEARWEEK(CURDATE(), 1)"; // Filters for payments this week
                break;
            case 'month':
                $paymentsQuery .= " AND MONTH(payments.payment_date) = MONTH(CURDATE()) AND YEAR(payments.payment_date) = YEAR(CURDATE())"; // Filters for payments this month
                break;
            case 'year':
                $paymentsQuery .= " AND YEAR(payments.payment_date) = YEAR(CURDATE())"; // Filters for payments this year
                break;
        }
    }

    // Applies amount range filter
    if (!empty($amountRangeFilter)) { // Checks if amount range filter is set
        list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $amountRangeFilter)); // Splits range into min and max
        $minAmount = (float)$minAmount; // Converts min amount to float
        $paymentsQuery .= " AND payments.amount >= $minAmount"; // Adds minimum amount condition
        if (is_numeric($maxAmount)) { // Checks if max amount is numeric
            $paymentsQuery .= " AND payments.amount <= " . (float)$maxAmount; // Adds maximum amount condition
        }
    }

    // Applies remaining balance filter
    if (!empty($balanceRangeFilter)) { // Checks if balance range filter is set
        list($minBalance, $maxBalance) = explode('-', str_replace('+', '-', $balanceRangeFilter)); // Splits range into min and max
        $minBalance = (float)$minBalance; // Converts min balance to float
        $paymentsQuery .= " AND payments.remaining_balance >= $minBalance"; // Adds minimum balance condition
        if (is_numeric($maxBalance)) { // Checks if max balance is numeric
            $paymentsQuery .= " AND payments.remaining_balance <= " . (float)$maxBalance; // Adds maximum balance condition
        }
    }

    // Orders results by payment date in descending order
    $paymentsQuery .= " ORDER BY payments.payment_date DESC"; // Sorts payments by newest first

    // Logs the query for debugging
    error_log("Payments Query: $paymentsQuery"); // Logs the constructed query to server log

    // Executes the payment records query
    $paymentsResult = mysqli_query($myconn, $paymentsQuery); // Executes the query
    if (!$paymentsResult) { // Checks if query execution failed
        error_log("Query failed: " . mysqli_error($myconn)); // Logs error to server log
        $_SESSION['loan_message'] = "Error fetching payment records."; // Sets error message in session
    } else {
        $payments = mysqli_fetch_all($paymentsResult, MYSQLI_ASSOC); // Fetches all payment records as associative array
        error_log("Payments fetched: " . count($payments) . " records"); // Logs number of fetched records
    }

    // Fetches available payment methods for filter options
    $paymentMethodsQuery = "SELECT DISTINCT payment_method FROM payments WHERE lender_id = '$lender_id'"; // Query to get unique payment methods
    $paymentMethodsResult = mysqli_query($myconn, $paymentMethodsQuery); // Executes the query
    if (!$paymentMethodsResult) { // Checks if query execution failed
        error_log("Payment methods query failed: " . mysqli_error($myconn)); // Logs error to server log
    } else {
        while ($row = mysqli_fetch_assoc($paymentMethodsResult)) { // Fetches each payment method
            $paymentMethods[] = $row['payment_method']; // Adds payment method to array
        }
        error_log("Payment methods fetched: " . count($paymentMethods) . " methods"); // Logs number of fetched methods
    }


}
?>