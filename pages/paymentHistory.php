<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database config file
include '../phpconfig/config.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: signin.html");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch payment history function
function fetchPaymentHistory($myconn, $customer_id, $filters = []) {
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
    AND payments.payment_type != 'unpaid'";
    
    $params = [$customer_id];
    $types = "i";

    // Initialize filters: prioritize $_GET, then passed $filters, then session, then defaults
    $appliedFilters = [
        'payment_type' => isset($_GET['payment_type']) ? $_GET['payment_type'] : ($filters['payment_type'] ?? ($_SESSION['history_filters']['payment_type'] ?? '')),
        'payment_method' => isset($_GET['payment_method']) ? $_GET['payment_method'] : ($filters['payment_method'] ?? ($_SESSION['history_filters']['payment_method'] ?? '')),
        'amount_range' => isset($_GET['amount_range']) ? $_GET['amount_range'] : ($filters['amount_range'] ?? ($_SESSION['history_filters']['amount_range'] ?? '')),
        'date_range' => isset($_GET['date_range']) ? $_GET['date_range'] : ($filters['date_range'] ?? ($_SESSION['history_filters']['date_range'] ?? ''))
    ];

    // Payment type filter
    if ($appliedFilters['payment_type']) {
        $query .= " AND payments.payment_type = ?";
        $params[] = $appliedFilters['payment_type'];
        $types .= "s";
    }

    // Payment method filter
    if ($appliedFilters['payment_method']) {
        $query .= " AND payments.payment_method = ?";
        $params[] = $appliedFilters['payment_method'];
        $types .= "s";
    }

    // Amount range filter
    if ($appliedFilters['amount_range']) {
        $rangeParts = explode('-', str_replace('+', '-', $appliedFilters['amount_range']));
        if (count($rangeParts) >= 1 && is_numeric($rangeParts[0])) {
            $minAmount = $rangeParts[0];
            $query .= " AND payments.amount >= ?";
            $params[] = $minAmount;
            $types .= "d";
            if (isset($rangeParts[1]) && is_numeric($rangeParts[1])) {
                $maxAmount = $rangeParts[1];
                $query .= " AND payments.amount <= ?";
                $params[] = $maxAmount;
                $types .= "d";
            }
        }
    }

    // Date range filter
    if ($appliedFilters['date_range']) {
        switch ($appliedFilters['date_range']) {
            case 'today':
                $query .= " AND DATE(payments.payment_date) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(payments.payment_date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $query .= " AND MONTH(payments.payment_date) = MONTH(CURDATE()) AND YEAR(payments.payment_date) = YEAR(CURDATE())";
                break;
            case 'year':
                $query .= " AND YEAR(payments.payment_date) = YEAR(CURDATE())";
                break;
        }
    }

    $query .= " ORDER BY payments.payment_date DESC";

    $stmt = $myconn->prepare($query);
    if (!$stmt) {
        error_log("Error preparing fetchPaymentHistory query: " . $myconn->error);
        $_SESSION['trans_error_message'] = "Error preparing query.";
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Error executing fetchPaymentHistory query: " . $stmt->error);
        $_SESSION['trans_error_message'] = "Error executing query.";
        return [];
    }

    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Update session filters only if new filters were applied via $_GET
    if (!empty(array_filter($_GET, fn($key) => in_array($key, ['payment_type', 'payment_method', 'amount_range', 'date_range']), ARRAY_FILTER_USE_KEY))) {
        $_SESSION['history_filters'] = $appliedFilters;
    }

    // Store results in session
    $_SESSION['payment_history'] = $payments;

    return $payments;
}

// Handle payment details request
if (isset($_GET['payment_id'])) {
    $paymentId = filter_var($_GET['payment_id'], FILTER_VALIDATE_INT);
    if (!$paymentId) {
        $_SESSION['trans_error_message'] = "Invalid payment ID";
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    }
    
    // Verify payment belongs to customer
    $verifyQuery = "SELECT customer_id FROM payments WHERE payment_id = ?";
    $stmt = $myconn->prepare($verifyQuery);
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['trans_error_message'] = "Payment not found";
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    }
    
    $paymentData = $result->fetch_assoc();
    if ($paymentData['customer_id'] != $customer_id) {
        $_SESSION['trans_error_message'] = "You don't have permission to view this payment";
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    }

    // Fetch payment details
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
    WHERE payments.payment_id = ?";
    
    $stmt = $myconn->prepare($paymentDetailsQuery);
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $paymentDetails = $stmt->get_result()->fetch_assoc();
    
    if ($paymentDetails) {
        $_SESSION['payment_details'] = $paymentDetails;
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    } else {
        $_SESSION['trans_error_message'] = "Failed to load payment details";
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    }
}

// Handle reset filters
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    error_log("Reset triggered in paymentHistory.php");
    unset($_SESSION['payment_history']);
    unset($_SESSION['history_filters']);
    header("Location: customerDashboard.php#transactionHistory");
    exit();
}

// Prevent direct access but allow inclusion
if (basename($_SERVER['PHP_SELF']) === 'paymentHistory.php' && !isset($_GET['payment_id']) && !isset($_GET['reset'])) {
    header("Location: customerDashboard.php#transactionHistory");
    exit();
}
?>