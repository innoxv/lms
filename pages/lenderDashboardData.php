<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();


// Access Restrictions from Admin Functionality
require_once 'check_access.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

// Database config file
include '../phpconfig/config.php';

$userId = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name'];
} else {
    $_SESSION['user_name'] = "Guest";
}

// Fetch lender_id
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'";
$lenderResult = mysqli_query($myconn, $lenderQuery);

if (mysqli_num_rows($lenderResult) > 0) {
    $lender = mysqli_fetch_assoc($lenderResult);
    $_SESSION['lender_id'] = $lender['lender_id'];
} else {
    $_SESSION['loan_message'] = "You are not registered as a lender.";
    header("Location: lenderDashboard.php");
    exit();
}

// Include paymentReview.php 
require_once 'paymentReview.php';

// Include activeLoans.php
$activeLoansData = require_once 'activeLoans.php';
$activeLoanData = $activeLoansData['activeLoanData'];
$activeFilters = $activeLoansData['filters'];
$allLoanTypes = $activeLoansData['allLoanTypes'];

// Define all loan types
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

// Get loan offers count
$totalOffersQuery = "SELECT COUNT(*) FROM loan_offers WHERE lender_id = '$lender_id'";
$totalOffersResult = mysqli_query($myconn, $totalOffersQuery);
$totalOffers = (int)mysqli_fetch_row($totalOffersResult)[0];

// Get average interest rate
$avgInterestQuery = "SELECT AVG(interest_rate) FROM loan_offers WHERE lender_id = '$lender_id'";
$avgInterestResult = mysqli_query($myconn, $avgInterestQuery);
$avgInterestRate = number_format((float)mysqli_fetch_row($avgInterestResult)[0], 2);

// Get total loan amount owed
// subquery checks last payment state to determine state
$owedQuery = "
    SELECT COALESCE(SUM(latest_payment.remaining_balance), 0)
    FROM loans
    JOIN (
        SELECT loan_id, remaining_balance
        FROM payments
        WHERE (loan_id, payment_date) IN (
            SELECT loan_id, MAX(payment_date)
            FROM payments
            GROUP BY loan_id
        )
    ) latest_payment ON loans.loan_id = latest_payment.loan_id
    WHERE loans.lender_id = ?
    AND loans.status = 'disbursed'";

$stmt = $myconn->prepare($owedQuery);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$owedResult = $stmt->get_result();
$owedData = $owedResult->fetch_row();
$owedCapacity = $owedData[0] ? number_format((float)$owedData[0], 0) : '0';

// Get total disbursed loans count
$disbursedLoansQuery = "SELECT COUNT(*) FROM loans WHERE lender_id = '$lender_id' AND status = 'disbursed'";
$disbursedLoansResult = mysqli_query($myconn, $disbursedLoansQuery);
$disbursedLoans = (int)mysqli_fetch_row($disbursedLoansResult)[0];

// Get active loans count (disbursed loans with remaining balance)
// subquery checks last payment state to determine state

$activeLoansQuery = "
    SELECT COUNT(DISTINCT loans.loan_id)
    FROM loans
    JOIN (
        SELECT loan_id, remaining_balance
        FROM payments
        WHERE (loan_id, payment_date) IN (
            SELECT loan_id, MAX(payment_date)
            FROM payments
            GROUP BY loan_id
        )
    ) latest_payment ON loans.loan_id = latest_payment.loan_id
    WHERE loans.lender_id = ?
    AND loans.status = 'disbursed'
    AND latest_payment.remaining_balance > 0";

$stmt = $myconn->prepare($activeLoansQuery);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$activeLoansResult = $stmt->get_result();
$activeLoans = (int)$activeLoansResult->fetch_row()[0] ?? 0;

// Get total amount disbursed
$disbursedAmountQuery = "SELECT SUM(amount) FROM loans WHERE lender_id = '$lender_id' AND status IN ('disbursed')";
$disbursedAmountResult = mysqli_query($myconn, $disbursedAmountQuery);
$disbursedAmountData = mysqli_fetch_row($disbursedAmountResult);
$totalDisbursedAmount = $disbursedAmountData[0] ? number_format((float)$disbursedAmountData[0]) : 0;

// Get loan offers with their disbursed loans count
$loanOffersQuery = "SELECT 
                      loan_offers.offer_id,
                      loan_offers.loan_type,
                      loan_offers.interest_rate,
                      loan_offers.max_amount,
                      loan_offers.max_duration,
                      COUNT(loans.loan_id) as disbursed_count
                    FROM loan_offers
                    LEFT JOIN loans ON loan_offers.offer_id = loans.offer_id
                      AND loans.lender_id = '$lender_id'
                      AND loans.status = 'disbursed'
                    WHERE loan_offers.lender_id = '$lender_id'
                    GROUP BY loan_offers.offer_id, loan_offers.loan_type, loan_offers.interest_rate, 
                             loan_offers.max_amount, loan_offers.max_duration";

$loanOffersResult = mysqli_query($myconn, $loanOffersQuery);

// Initialize loan counts
$loanCounts = array_fill_keys($allLoanTypes, 0);
$offersData = [];

if ($loanOffersResult) {
    while ($row = mysqli_fetch_assoc($loanOffersResult)) {
        $loanType = $row['loan_type'];
        $loanCounts[$loanType] = (int)$row['disbursed_count'];
        
        $offersData[] = [
            'offer_id' => $row['offer_id'],
            'loan_type' => $loanType,
            'interest_rate' => $row['interest_rate'],
            'max_amount' => $row['max_amount'],
            'max_duration' => $row['max_duration']
        ];
    }
    // Sort the $offersData array by offer_id in descending order using funtion usort
    usort($offersData, function($a, $b) {
        return $b['offer_id'] - $a['offer_id'];
    });
}

// Get loan status distribution
$statusQuery = "SELECT status, COUNT(*) as count FROM loans WHERE lender_id = '$lender_id' GROUP BY status";
$statusResult = mysqli_query($myconn, $statusQuery);
$statusData = mysqli_fetch_all($statusResult, MYSQLI_ASSOC);



// Get filter parameters from URL (add near top with other initializations)
$statusFilter = $_GET['status'] ?? '';
$loanTypeFilter = $_GET['loan_type'] ?? '';

// loan requests query to include both filters
$loanRequestsQuery = "SELECT 
    loans.loan_id,
    loans.amount,
    loans.interest_rate,
    loans.duration,
    loans.collateral_value,
    loans.collateral_description,
    loans.status,
    loans.application_date,
    customers.name,
    loan_offers.loan_type
FROM loans
JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
JOIN customers ON loans.customer_id = customers.customer_id
WHERE loans.lender_id = '$lender_id'  -- exclude loans with status submitted
AND loans.status != 'submitted'";

// Status filter
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'disbursed', 'rejected'])) {
    $loanRequestsQuery .= " AND loans.status = '$statusFilter'";
}

// Loan type filter
if (!empty($loanTypeFilter)) {
    $loanRequestsQuery .= " AND loan_offers.loan_type = '$loanTypeFilter'";
}

// Date range filter
if (isset($_GET['date_range']) && $_GET['date_range']) {
    switch ($_GET['date_range']) {
        case 'today':
            $loanRequestsQuery .= " AND DATE(loans.application_date) = CURDATE()";
            break;
        case 'week':
            $loanRequestsQuery .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $loanRequestsQuery .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())";
            break;
        case 'year':
            $loanRequestsQuery .= " AND YEAR(loans.application_date) = YEAR(CURDATE())";
            break;
    }
}

// Amount range filter
if (isset($_GET['amount_range']) && $_GET['amount_range']) {
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $_GET['amount_range']));
    $loanRequestsQuery .= " AND loans.amount >= $minAmount";
    if (is_numeric($maxAmount)) {
        $loanRequestsQuery .= " AND loans.amount <= $maxAmount";
    }
}

// Duration filter
if (isset($_GET['duration_range']) && $_GET['duration_range']) {
    list($minDuration, $maxDuration) = explode('-', str_replace('+', '-', $_GET['duration_range']));
    $loanRequestsQuery .= " AND loans.duration >= $minDuration";
    if (is_numeric($maxDuration)) {
        $loanRequestsQuery .= " AND loans.duration <= $maxDuration";
    }
}

// Collateral filter
if (isset($_GET['collateral_range']) && $_GET['collateral_range']) {
    list($minCollateral, $maxCollateral) = explode('-', str_replace('+', '-', $_GET['collateral_range']));
    $loanRequestsQuery .= " AND loans.collateral_value >= $minCollateral";
    if (is_numeric($maxCollateral)) {
        $loanRequestsQuery .= " AND loans.collateral_value <= $maxCollateral";
    }
}
 $loanRequestsQuery .= " ORDER BY loans.application_date DESC";


// Execute the query
$loanRequestsResult = mysqli_query($myconn, $loanRequestsQuery);
if (!$loanRequestsResult) {
    die("Query failed: " . mysqli_error($myconn));
}
$loanRequests = mysqli_fetch_all($loanRequestsResult, MYSQLI_ASSOC);


// Pie Chart
// Get loan status distribution for the current lender
$statusQuery = "SELECT status, COUNT(*) as count 
                FROM loans 
                WHERE lender_id = '$lender_id' 
                GROUP BY status";
$statusResult = mysqli_query($myconn, $statusQuery);
$statusData = [];
$totalLoans = 0;

while ($row = mysqli_fetch_assoc($statusResult)) {
    $statusData[$row['status']] = (int)$row['count'];
    // Only include "pending", "disbursed", and "rejected" in the total
    if (in_array($row['status'], ['pending', 'disbursed', 'rejected'])) {
        $totalLoans += (int)$row['count'];
    }
}

// Calculate percentages for each status, only for relevant statuses
$pieData = [
    'pending' => $totalLoans > 0 && isset($statusData['pending']) ? ($statusData['pending'] / $totalLoans * 100) : 0,
    'disbursed' => $totalLoans > 0 && isset($statusData['disbursed']) ? ($statusData['disbursed'] / $totalLoans * 100) : 0,
    'rejected' => $totalLoans > 0 && isset($statusData['rejected']) ? ($statusData['rejected'] / $totalLoans * 100) : 0
];

// Fetch lender profile data
$lenderProfileQuery = "SELECT * FROM lenders WHERE lender_id = '$lender_id'";
$lenderProfileResult = mysqli_query($myconn, $lenderProfileQuery);
$lenderProfile = mysqli_fetch_assoc($lenderProfileResult);



// Check for messages
if (isset($_SESSION['loan_message'])) {
    $loan_message = $_SESSION['loan_message'];
    unset($_SESSION['loan_message']);
} else {
    $loan_message = null;
}

?>