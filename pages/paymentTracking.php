<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['payment_message'] = "Please log in to access payment tracking.";
    $_SESSION['payment_message_type'] = 'error';
    header("Location: signin.html");
    exit();
}

$userId = $_SESSION['user_id'];
$customer_id = $_SESSION['customer_id'] ?? null;

// Validate customer_id
if (!$customer_id) {
    $_SESSION['payment_message'] = "Customer profile not found. Please log in again.";
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    $_SESSION['payment_message'] = "Connection failed: " . mysqli_connect_error();
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

// Function to fetch active loans with filters
function fetchActiveLoans($myconn, $customer_id, $filters = []) {
    $baseQuery = "SELECT 
        loans.loan_id,
        loan_offers.loan_type,
        loans.amount,
        loans.interest_rate,
        loans.status AS loan_status,
        lenders.name AS lender_name,
        loans.created_at,
        COALESCE(SUM(payments.amount), 0) AS amount_paid,
        (loans.amount - COALESCE(SUM(payments.amount), 0)) AS remaining_balance,
        CASE 
            WHEN (loans.amount - COALESCE(SUM(payments.amount), 0)) <= 0 THEN 'fully_paid'
            WHEN COALESCE(SUM(payments.amount), 0) > 0 THEN 'partially_paid'
            ELSE 'unpaid'
        END AS payment_status
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    JOIN lenders ON loans.lender_id = lenders.lender_id
    LEFT JOIN payments ON loans.loan_id = payments.loan_id
    WHERE loans.customer_id = ?
    AND loans.status IN ('approved', 'disbursed', 'active')";

    $params = [$customer_id];
    $types = "i";
    $havingClause = "";

    // Status filter
    if (!empty($filters['payment_status']) && in_array($filters['payment_status'], ['fully_paid', 'partially_paid', 'unpaid'])) {
        $havingClause = " HAVING payment_status COLLATE utf8mb4_unicode_ci = ?";
        $params[] = $filters['payment_status'];
        $types .= "s";
    }

    // Loan type filter
    if (!empty($filters['loan_type'])) {
        $baseQuery .= " AND loan_offers.loan_type COLLATE utf8mb4_unicode_ci = ?";
        $params[] = $filters['loan_type'];
        $types .= "s";
    }

    // Amount range filter
    if (!empty($filters['amount_range'])) {
        list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $filters['amount_range']));
        $baseQuery .= " AND loans.amount >= ?";
        $params[] = $minAmount;
        $types .= "d";
        
        if (is_numeric($maxAmount)) {
            $baseQuery .= " AND loans.amount <= ?";
            $params[] = $maxAmount;
            $types .= "d";
        }
    }

    // Date range filter
    if (!empty($filters['date_range'])) {
        switch ($filters['date_range']) {
            case 'today':
                $baseQuery .= " AND DATE(loans.created_at) = CURDATE()";
                break;
            case 'week':
                $baseQuery .= " AND YEARWEEK(loans.created_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $baseQuery .= " AND MONTH(loans.created_at) = MONTH(CURDATE()) AND YEAR(loans.created_at) = YEAR(CURDATE())";
                break;
            case 'year':
                $baseQuery .= " AND YEAR(loans.created_at) = YEAR(CURDATE())";
                break;
        }
    }

    // Complete the query
    $baseQuery .= " GROUP BY loans.loan_id";
    if ($havingClause) {
        $baseQuery .= $havingClause;
    }
    $baseQuery .= " ORDER BY loans.created_at DESC";

    // Prepare and execute
    $stmt = $myconn->prepare($baseQuery);
    if (!$stmt) {
        $_SESSION['payment_message'] = "Query preparation failed: " . $myconn->error;
        $_SESSION['payment_message_type'] = 'error';
        return false;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Initial fetch of active loans
$filters = [
    'payment_status' => $_GET['payment_status'] ?? '',
    'loan_type' => $_GET['loan_type'] ?? '',
    'amount_range' => $_GET['amount_range'] ?? '',
    'date_range' => $_GET['date_range'] ?? ''
];

// Handle reset
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    unset($_SESSION['active_loans']);
    unset($_SESSION['payment_filters']);
    $filters = [
        'payment_status' => '',
        'loan_type' => '',
        'amount_range' => '',
        'date_range' => ''
    ];
}

$activeLoans = fetchActiveLoans($myconn, $customer_id, $filters);
if ($activeLoans === false) {
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

// Store initial results in session
$_SESSION['active_loans'] = $activeLoans;

// Process payment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit'])) {
    $loan_id = $_POST['loan_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $remaining_balance = $_POST['remaining_balance'];

    // Verify loan belongs to customer
    $verifyQuery = "SELECT customer_id FROM loans WHERE loan_id = ?";
    $stmt = $myconn->prepare($verifyQuery);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0 || $result->fetch_assoc()['customer_id'] != $customer_id) {
        $_SESSION['payment_message'] = "Invalid loan selected for payment";
        $_SESSION['payment_message_type'] = 'error';
    } else {
        // Calculate new remaining balance
        $new_balance = $remaining_balance - $amount;
        $payment_type = ($new_balance <= 0) ? 'full' : 'partial';

        // Insert payment
        $insertQuery = "INSERT INTO payments (
            loan_id, customer_id, lender_id, amount, 
            payment_method, payment_type, remaining_balance
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        // Get lender_id for this loan
        $lenderQuery = "SELECT lender_id FROM loans WHERE loan_id = ?";
        $stmt = $myconn->prepare($lenderQuery);
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $lender_id = $stmt->get_result()->fetch_row()[0];

        $stmt = $myconn->prepare($insertQuery);
        $stmt->bind_param(
            "iiidssd", 
            $loan_id, 
            $customer_id, 
            $lender_id, 
            $amount,
            $payment_method, 
            $payment_type, 
            $new_balance
        );

        if ($stmt->execute()) {
            $_SESSION['payment_message'] = "Payment of KES " . number_format($amount) . " processed successfully!";
            $_SESSION['payment_message_type'] = 'success';

            // Log activity
            $activity = "Processed payment of $amount for loan ID $loan_id";
            $activityQuery = "INSERT INTO activity (user_id, activity, activity_time, activity_type) VALUES (?, ?, NOW(), 'payment')";
            $stmt = $myconn->prepare($activityQuery);
            $stmt->bind_param("is", $userId, $activity);
            $stmt->execute();

            // Refresh active loans with current filters
            $activeLoans = fetchActiveLoans($myconn, $customer_id, $filters);
            if ($activeLoans !== false) {
                $_SESSION['active_loans'] = $activeLoans;
            }
        } else {
            $_SESSION['payment_message'] = "Error processing payment: " . $myconn->error;
            $_SESSION['payment_message_type'] = 'error';
        }
    }
}

// Store filter values in session
$_SESSION['payment_filters'] = [
    'payment_status' => $filters['payment_status'],
    'loan_type' => $filters['loan_type'],
    'amount_range' => $filters['amount_range'],
    'date_range' => $filters['date_range']
];

// Redirect back to customerDashboard.php
header("Location: customerDashboard.php#paymentTracking");
exit();
?>