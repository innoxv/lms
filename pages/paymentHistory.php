<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: signin.html");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Handle payment details request
if (isset($_GET['payment_id'])) {
    $paymentId = $_GET['payment_id'];
    
    // Verify the payment belongs to the customer
    $verifyQuery = "SELECT customer_id FROM payments WHERE payment_id = ?";
    $stmt = $myconn->prepare($verifyQuery);
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['payment_details_message'] = "Payment not found";
        $_SESSION['payment_details_message_type'] = "error";
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    }
    
    $paymentData = $result->fetch_assoc();
    if ($paymentData['customer_id'] != $customer_id) {
        $_SESSION['payment_details_message'] = "You don't have permission to view this payment";
        $_SESSION['payment_details_message_type'] = "error";
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
        lenders.name AS lender_name
    FROM payments
    JOIN loans ON payments.loan_id = loans.loan_id
    JOIN lenders ON payments.lender_id = lenders.lender_id
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
        $_SESSION['payment_details_message'] = "Failed to load payment details";
        $_SESSION['payment_details_message_type'] = "error";
        header("Location: customerDashboard.php#transactionHistory");
        exit();
    }
}

// Fetch payment history
function fetchPaymentHistory($myconn, $customer_id) {
    $query = "SELECT 
        payments.payment_id,
        payments.loan_id,
        payments.amount,
        payments.payment_method,
        payments.payment_type,
        payments.remaining_balance,
        DATE_FORMAT(payments.payment_date, '%Y-%m-%d %H:%i:%s') as payment_date
    FROM payments
    WHERE payments.customer_id = ?";
    
    $params = [$customer_id];
    $types = "i";

    // Apply filters
    $filters = [
        'payment_type' => isset($_GET['payment_type']) ? $_GET['payment_type'] : '',
        'payment_method' => isset($_GET['payment_method']) ? $_GET['payment_method'] : '',
        'amount_range' => isset($_GET['amount_range']) ? $_GET['amount_range'] : '',
        'date_range' => isset($_GET['date_range']) ? $_GET['date_range'] : ''
    ];

    // Payment type filter
    if ($filters['payment_type']) {
        $query .= " AND payments.payment_type = ?";
        $params[] = $filters['payment_type'];
        $types .= "s";
    }

    // Payment method filter
    if ($filters['payment_method']) {
        $query .= " AND payments.payment_method = ?";
        $params[] = $filters['payment_method'];
        $types .= "s";
    }

    // Amount range filter
    if ($filters['amount_range']) {
        list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $filters['amount_range']));
        $query .= " AND payments.amount >= ?";
        $params[] = $minAmount;
        $types .= "d";
        if (is_numeric($maxAmount)) {
            $query .= " AND payments.amount <= ?";
            $params[] = $maxAmount;
            $types .= "d";
        }
    }

    // Date range filter
    if ($filters['date_range']) {
        switch ($filters['date_range']) {
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
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);

    // Store results and filters in session
    $_SESSION['payment_history'] = $payments;
    $_SESSION['history_filters'] = $filters;

    return $payments;
}

// Handle reset filters
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    unset($_SESSION['payment_history']);
    unset($_SESSION['history_filters']);
    header("Location: customerDashboard.php#transactionHistory");
    exit();
}

// Fetch payment history if not a reset or payment details request
$paymentHistory = fetchPaymentHistory($myconn, $customer_id);

?>