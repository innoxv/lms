<?php
// Enable error reporting (optional, remove in production)
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
$customerId = $_SESSION['customer_id'] ?? null;

// Validate customer_id
if (!$customerId) {
    $_SESSION['payment_message'] = "Customer profile not found. Please log in again.";
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

// Database connection
$conn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$conn) {
    $_SESSION['payment_message'] = "Connection failed: " . mysqli_connect_error();
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

// Initialize filters
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

// Build query for active loans
$query = "SELECT 
    loans.loan_id,
    loan_offers.loan_type,
    loans.amount,
    loans.interest_rate,
    loans.duration,
    loans.status AS loan_status,
    lenders.name AS lender_name,
    loans.created_at,
    COALESCE(p.amount, 0) AS amount_paid,
    p.remaining_balance
FROM loans
JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
JOIN lenders ON loans.lender_id = lenders.lender_id
LEFT JOIN payments p ON loans.loan_id = p.loan_id
WHERE loans.customer_id = ?
AND loans.status IN ('approved', 'disbursed', 'active')";

$params = [$customerId];
$types = "i";

// Loan type filter
if (!empty($filters['loan_type'])) {
    $query .= " AND loan_offers.loan_type = ?";
    $params[] = $filters['loan_type'];
    $types .= "s";
}

// Amount range filter
if (!empty($filters['amount_range'])) {
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $filters['amount_range']));
    $query .= " AND loans.amount >= ?";
    $params[] = $minAmount;
    $types .= "d";
    
    if (is_numeric($maxAmount)) {
        $query .= " AND loans.amount <= ?";
        $params[] = $maxAmount;
        $types .= "d";
    }
}

// Date range filter
if (!empty($filters['date_range'])) {
    switch ($filters['date_range']) {
        case 'today':
            $query .= " AND DATE(loans.created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND YEARWEEK(loans.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $query .= " AND MONTH(loans.created_at) = MONTH(CURDATE()) AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $query .= " AND YEAR(loans.created_at) = YEAR(CURDATE())";
            break;
    }
}

// Complete the query
$query .= " ORDER BY loans.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['payment_message'] = "Query preparation failed: " . $conn->error;
    $_SESSION['payment_message_type'] = 'error';
    header("Location: customerDashboard.php#paymentTracking");
    exit();
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$activeLoans = [];

// Process results and apply payment_status filter in PHP
while ($row = $result->fetch_assoc()) {
    // Calculate total amount due with simple interest
    $principal = $row['amount'];
    $interestRate = $row['interest_rate'] / 100; // Convert percentage to decimal
    $durationYears = $row['duration'] / 12; // Convert months to years
    $totalAmountDue = $principal + ($principal * $interestRate * $durationYears);
    
    // Calculate remaining balance
    $amountPaid = $row['amount_paid'];
    $remainingBalance = $row['remaining_balance'] ?? $totalAmountDue;

    // Determine payment status
    $paymentStatus = 'unpaid';
    if ($remainingBalance <= 0) {
        $paymentStatus = 'fully_paid';
    } elseif ($amountPaid > 0) {
        $paymentStatus = 'partially_paid';
    }

    // Apply payment_status filter
    if (empty($filters['payment_status']) || $filters['payment_status'] === $paymentStatus) {
        $activeLoans[] = [
            'loan_id' => $row['loan_id'],
            'loan_type' => $row['loan_type'],
            'amount' => $row['amount'],
            'interest_rate' => $row['interest_rate'],
            'loan_status' => $row['loan_status'],
            'lender_name' => $row['lender_name'],
            'created_at' => $row['created_at'],
            'amount_paid' => $amountPaid,
            'remaining_balance' => $remainingBalance,
            'total_amount_due' => $totalAmountDue,
            'payment_status' => $paymentStatus
        ];
    }
}

// Store results in session
$_SESSION['active_loans'] = $activeLoans;
$_SESSION['payment_filters'] = $filters;

// Process payment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit'])) {
    $loanId = intval($_POST['loan_id']);
    $amount = floatval($_POST['amount']);
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $submittedRemainingBalance = floatval($_POST['remaining_balance']);

    // Verify loan belongs to customer and fetch payment record
    $verifyQuery = "SELECT 
        l.customer_id, 
        l.amount, 
        l.interest_rate, 
        l.duration, 
        p.payment_id, 
        p.amount AS amount_paid, 
        p.remaining_balance 
    FROM loans l 
    LEFT JOIN payments p ON l.loan_id = p.loan_id 
    WHERE l.loan_id = ?";
    $stmt = $conn->prepare($verifyQuery);
    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0 || $result->fetch_assoc()['customer_id'] != $customerId) {
        $_SESSION['payment_message'] = "Invalid loan selected for payment";
        $_SESSION['payment_message_type'] = 'error';
    } else {
        // Reset result pointer and fetch loan and payment details
        $result->data_seek(0);
        $loanDetails = $result->fetch_assoc();
        $paymentId = $loanDetails['payment_id'];

        if (!$paymentId) {
            $_SESSION['payment_message'] = "No payment record found for this loan. Contact support.";
            $_SESSION['payment_message_type'] = 'error';
        } else {
            // Recalculate total amount due
            $principal = $loanDetails['amount'];
            $interestRate = $loanDetails['interest_rate'] / 100;
            $durationYears = $loanDetails['duration'] / 12;
            $totalAmountDue = $principal + ($principal * $interestRate * $durationYears);

            // Get current payment details
            $currentAmountPaid = $loanDetails['amount_paid'];
            $currentRemainingBalance = $loanDetails['remaining_balance'];

            // Validate payment amount
            if ($amount <= 0 || $amount > $currentRemainingBalance) {
                $_SESSION['payment_message'] = "Invalid payment amount. Must be greater than 0 and not exceed remaining balance.";
                $_SESSION['payment_message_type'] = 'error';
            } else {
                // Calculate new values
                $newAmountPaid = $currentAmountPaid + $amount;
                $newRemainingBalance = $currentRemainingBalance - $amount;
                $paymentType = ($newRemainingBalance <= 0) ? 'full' : 'partial';

                // Update existing payment record
                $updateQuery = "UPDATE payments SET 
                    amount = ?, 
                    payment_method = ?, 
                    payment_type = ?, 
                    remaining_balance = ?
                WHERE payment_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param(
                    "dssdi",
                    $newAmountPaid,
                    $paymentMethod,
                    $paymentType,
                    $newRemainingBalance,
                    $paymentId
                );

                if ($stmt->execute()) {
                    $_SESSION['payment_message'] = "Payment of KES " . number_format($amount, 2) . " processed successfully!";
                    $_SESSION['payment_message_type'] = 'success';

                    // Log activity
                    $activity = "Processed payment of $amount for loan ID $loanId";
                    $activityQuery = "INSERT INTO activity (user_id, activity, activity_time, activity_type) VALUES (?, ?, NOW(), 'payment')";
                    $stmt = $conn->prepare($activityQuery);
                    $stmt->bind_param("is", $userId, $activity);
                    $stmt->execute();

                    // Refresh active loans with current filters
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        $_SESSION['payment_message'] = "Query preparation failed: " . $conn->error;
                        $_SESSION['payment_message_type'] = 'error';
                        header("Location: customerDashboard.php#paymentTracking");
                        exit();
                    }

                    if ($params) {
                        $stmt->bind_param($types, ...$params);
                    }

                    $stmt->execute();
                    $result = $stmt->get_result();
                    $activeLoans = [];

                    while ($row = $result->fetch_assoc()) {
                        $principal = $row['amount'];
                        $interestRate = $row['interest_rate'] / 100;
                        $durationYears = $row['duration'] / 12;
                        $totalAmountDue = $principal + ($principal * $interestRate * $durationYears);
                        
                        $amountPaid = $row['amount_paid'];
                        $remainingBalance = $row['remaining_balance'] ?? $totalAmountDue;

                        $paymentStatus = 'unpaid';
                        if ($remainingBalance <= 0) {
                            $paymentStatus = 'fully_paid';
                        } elseif ($amountPaid > 0) {
                            $paymentStatus = 'partially_paid';
                        }

                        if (empty($filters['payment_status']) || $filters['payment_status'] === $paymentStatus) {
                            $activeLoans[] = [
                                'loan_id' => $row['loan_id'],
                                'loan_type' => $row['loan_type'],
                                'amount' => $row['amount'],
                                'interest_rate' => $row['interest_rate'],
                                'loan_status' => $row['loan_status'],
                                'lender_name' => $row['lender_name'],
                                'created_at' => $row['created_at'],
                                'amount_paid' => $amountPaid,
                                'remaining_balance' => $remainingBalance,
                                'total_amount_due' => $totalAmountDue,
                                'payment_status' => $paymentStatus
                            ];
                        }
                    }

                    $_SESSION['active_loans'] = $activeLoans;
                } else {
                    $_SESSION['payment_message'] = "Error processing payment: " . $conn->error;
                    $_SESSION['payment_message_type'] = 'error';
                }
            }
        }
    }
}

// Close connection
mysqli_close($conn);

// Redirect back to customerDashboard.php
header("Location: customerDashboard.php#paymentTracking");
exit();
?>