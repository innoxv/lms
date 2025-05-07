<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Restrictions
require_once 'check_access.php';

// Redirect to login if user_id is not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['loan_message'] = "Please log in to access the dashboard.";
    header("Location: signin.html");
    exit();
}

// Initialize arrays
$payments = [];
$paymentMethods = [];

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    $_SESSION['loan_message'] = "Database connection error. Please try again later.";
} else {
    // Sanitize user_id
    $userId = mysqli_real_escape_string($myconn, $_SESSION['user_id']);

    // Fetch lender_id
    $lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'";
    $lenderResult = mysqli_query($myconn, $lenderQuery);

    if (mysqli_num_rows($lenderResult) > 0) {
        $lender = mysqli_fetch_assoc($lenderResult);
        $lender_id = $lender['lender_id'];
        $_SESSION['lender_id'] = $lender_id; // Store in session for other scripts
    } else {
        error_log("Warning: No lender record found for user_id: " . ($_SESSION['user_id'] ?? 'unknown'));
        $_SESSION['loan_message'] = "You are not registered as a lender.";
        header("Location: lenderDashboard.php");
        exit();
    }

    // Payment Review Filters
    $paymentTypeFilter = $_GET['payment_type'] ?? '';
    $paymentMethodFilter = $_GET['payment_method'] ?? '';
    $dateRangeFilter = $_GET['date_range'] ?? '';
    $amountRangeFilter = $_GET['amount_range'] ?? '';
    $balanceRangeFilter = $_GET['balance_range'] ?? '';

    // Reset filters on initial load (no GET parameters)
    if (empty($_GET)) {
        $paymentTypeFilter = '';
        $paymentMethodFilter = '';
        $dateRangeFilter = '';
        $amountRangeFilter = '';
        $balanceRangeFilter = '';
    }

    // Validate filters
    $validPaymentTypes = ['principal', 'interest', 'penalty'];
    if (!empty($paymentTypeFilter) && !in_array($paymentTypeFilter, $validPaymentTypes)) {
        $paymentTypeFilter = '';
    }
    if (!empty($paymentMethodFilter)) {
        $paymentMethodFilter = mysqli_real_escape_string($myconn, $paymentMethodFilter);
    }

    // Payment records query
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
    AND payments.payment_type != 'unpaid'";

    // Payment type filter
    if (!empty($paymentTypeFilter)) {
        $paymentsQuery .= " AND payments.payment_type = '$paymentTypeFilter'";
    }

    // Payment method filter
    if (!empty($paymentMethodFilter)) {
        $paymentsQuery .= " AND payments.payment_method = '$paymentMethodFilter'";
    }

    // Date range filter
    if (!empty($dateRangeFilter)) {
        switch ($dateRangeFilter) {
            case 'today':
                $paymentsQuery .= " AND DATE(payments.payment_date) = CURDATE()";
                break;
            case 'week':
                $paymentsQuery .= " AND YEARWEEK(payments.payment_date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $paymentsQuery .= " AND MONTH(payments.payment_date) = MONTH(CURDATE()) AND YEAR(payments.payment_date) = YEAR(CURDATE())";
                break;
            case 'year':
                $paymentsQuery .= " AND YEAR(payments.payment_date) = YEAR(CURDATE())";
                break;
        }
    }

    // Amount range filter
    if (!empty($amountRangeFilter)) {
        list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $amountRangeFilter));
        $minAmount = (float)$minAmount;
        $paymentsQuery .= " AND payments.amount >= $minAmount";
        if (is_numeric($maxAmount)) {
            $paymentsQuery .= " AND payments.amount <= " . (float)$maxAmount;
        }
    }

    // Remaining balance filter
    if (!empty($balanceRangeFilter)) {
        list($minBalance, $maxBalance) = explode('-', str_replace('+', '-', $balanceRangeFilter));
        $minBalance = (float)$minBalance;
        $paymentsQuery .= " AND payments.remaining_balance >= $minBalance";
        if (is_numeric($maxBalance)) {
            $paymentsQuery .= " AND payments.remaining_balance <= " . (float)$maxBalance;
        }
    }

    $paymentsQuery .= " ORDER BY payments.payment_date DESC";

    // Debug query
    error_log("Payments Query: $paymentsQuery");

    // Execute query
    $paymentsResult = mysqli_query($myconn, $paymentsQuery);
    if (!$paymentsResult) {
        error_log("Query failed: " . mysqli_error($myconn));
        $_SESSION['loan_message'] = "Error fetching payment records.";
    } else {
        $payments = mysqli_fetch_all($paymentsResult, MYSQLI_ASSOC);
        error_log("Payments fetched: " . count($payments) . " records");
    }

    // Get available payment methods for filter
    $paymentMethodsQuery = "SELECT DISTINCT payment_method FROM payments WHERE lender_id = '$lender_id'";
    $paymentMethodsResult = mysqli_query($myconn, $paymentMethodsQuery);
    if (!$paymentMethodsResult) {
        error_log("Payment methods query failed: " . mysqli_error($myconn));
    } else {
        while ($row = mysqli_fetch_assoc($paymentMethodsResult)) {
            $paymentMethods[] = $row['payment_method'];
        }
        error_log("Payment methods fetched: " . count($paymentMethods) . " methods");
    }

    // Close database connection
    // mysqli_close($myconn);   // do not close this (bug alert)
}
?>