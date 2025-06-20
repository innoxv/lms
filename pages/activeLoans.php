<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lender_id'])) {
    die("Lender ID not set in session.");
}

$lender_id = $_SESSION['lender_id'];

// Database config file
include '../phpconfig/config.php';

// Get filter parameters
$activeStatusFilter = $_GET['active_status'] ?? '';
$activeLoanTypeFilter = $_GET['active_loan_type'] ?? '';
$activeDueStatusFilter = $_GET['active_due_status'] ?? ''; 
$activeDateRange = $_GET['active_date_range'] ?? '';
$activeAmountRange = $_GET['active_amount_range'] ?? '';
$activeDurationRange = $_GET['active_duration_range'] ?? '';
$activeCollateralRange = $_GET['active_collateral_range'] ?? '';

// Initialize query parameters
$params = [$lender_id];
$types = "i";

// Active loans query without table aliases
$activeLoansQuery = "
    SELECT 
        loans.loan_id,
        loans.amount,
        loans.interest_rate,
        loans.duration,
        loans.collateral_value,
        loans.collateral_description,
        loans.status,
        loans.application_date,
        loans.due_date,
        loans.isDue,
        customers.name,
        loan_offers.loan_type,
        latest_payment.remaining_balance
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    JOIN customers ON loans.customer_id = customers.customer_id
    JOIN (
        SELECT loan_id, remaining_balance, payment_type
        FROM payments
        WHERE (loan_id, payment_date) IN (
            SELECT loan_id, MAX(payment_date)
            FROM payments
            GROUP BY loan_id
        )
    ) latest_payment ON loans.loan_id = latest_payment.loan_id
    WHERE loans.lender_id = ?
    AND loans.status = 'disbursed'
    AND latest_payment.remaining_balance > 0
    AND latest_payment.payment_type != 'full'";

// Apply filters
if (!empty($activeStatusFilter) && in_array($activeStatusFilter, ['disbursed'])) {
    $activeLoansQuery .= " AND loans.status = ?";
    $params[] = $activeStatusFilter;
    $types .= "s";
}

if (!empty($activeLoanTypeFilter)) {
    $activeLoansQuery .= " AND loan_offers.loan_type = ?";
    $params[] = $activeLoanTypeFilter;
    $types .= "s";
}

if (!empty($activeDueStatusFilter)) {
    $activeLoansQuery .= " AND loans.isDue = ?";
    $params[] = ($activeDueStatusFilter === 'due') ? 1 : 0;
    $types .= "i";
}

if (!empty($activeDateRange)) {
    switch ($activeDateRange) {
        case 'today':
            $activeLoansQuery .= " AND DATE(loans.application_date) = CURDATE()";
            break;
        case 'week':
            $activeLoansQuery .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $activeLoansQuery .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())";
            break;
        case 'year':
            $activeLoansQuery .= " AND YEAR(loans.application_date) = YEAR(CURDATE())";
            break;
    }
}

if (!empty($activeAmountRange)) {
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $activeAmountRange));
    $activeLoansQuery .= " AND loans.amount >= ?";
    $params[] = $minAmount;
    $types .= "d";
    if (is_numeric($maxAmount)) {
        $activeLoansQuery .= " AND loans.amount <= ?";
        $params[] = $maxAmount;
        $types .= "d";
    }
}

if (!empty($activeDurationRange)) {
    list($minDuration, $maxDuration) = explode('-', str_replace('+', '-', $activeDurationRange));
    $activeLoansQuery .= " AND loans.duration >= ?";
    $params[] = $minDuration;
    $types .= "i";
    if (is_numeric($maxDuration)) {
        $activeLoansQuery .= " AND loans.duration <= ?";
        $params[] = $maxDuration;
        $types .= "i";
    }
}

if (!empty($activeCollateralRange)) {
    list($minCollateral, $maxCollateral) = explode('-', str_replace('+', '-', $activeCollateralRange));
    $activeLoansQuery .= " AND loans.collateral_value >= ?";
    $params[] = $minCollateral;
    $types .= "d";
    if (is_numeric($maxCollateral)) {
        $activeLoansQuery .= " AND loans.collateral_value <= ?";
        $params[] = $maxCollateral;
        $types .= "d";
    }
}

$activeLoansQuery .= " ORDER BY loans.application_date DESC";

// Prepare and execute query
$stmt = $myconn->prepare($activeLoansQuery);
if (!$stmt) {
    die("Prepare failed: " . $myconn->error);
}

if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $lender_id);
}

$stmt->execute();
$activeLoansResult = $stmt->get_result();
$activeLoanData = $activeLoansResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Define all loan types
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

// Return data
return [
    'activeLoanData' => $activeLoanData,
    'filters' => [
        'status' => $activeStatusFilter,
        'loan_type' => $activeLoanTypeFilter,
        'due_status' => $activeDueStatusFilter,
        'date_range' => $activeDateRange,
        'amount_range' => $activeAmountRange,
        'duration_range' => $activeDurationRange,
        'collateral_range' => $activeCollateralRange
    ],
    'allLoanTypes' => $allLoanTypes
];
?>