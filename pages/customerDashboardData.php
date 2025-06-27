<?php
// Checks if a session is not already active before starting a new one
if (session_status() !== PHP_SESSION_ACTIVE) { 
    session_start(); 
}

// Includes the check_access.php file to enforce admin access restrictions
require_once 'check_access.php'; 

// Includes the database configuration file to establish database connection
include '../phpconfig/config.php'; 

// Verifies if the user is logged in by checking for user_id in session
if (!isset($_SESSION['user_id'])) { 
    header("Location: signin.html"); 
    exit(); 
}

// Stores the user ID from the session in a variable
$userId = $_SESSION['user_id']; 

// Fetches basic user information from the database
$userQuery = "SELECT user_name FROM users WHERE user_id = ?"; 
$stmt = $myconn->prepare($userQuery); 
$stmt->bind_param("i", $userId); 
$stmt->execute(); 
$userResult = $stmt->get_result(); 
$user = $userResult->fetch_assoc() ?? ['user_name' => "Guest"]; 
$_SESSION['user_name'] = $user['user_name']; 

// Fetches customer profile details from the database
$customerQuery = "SELECT customer_id, name, email, phone, address, bank_account, 
                 DATE_FORMAT(dob, '%Y-%m-%d') as dob, 
                 DATE_FORMAT(registration_date, '%Y-%m-%d') as registration_date, 
                 national_id 
                 FROM customers WHERE user_id = ?"; 
$stmt = $myconn->prepare($customerQuery); 
$stmt->bind_param("i", $userId); 
$stmt->execute(); 
$customerResult = $stmt->get_result(); 
$customerProfile = $customerResult->fetch_assoc(); 

// Redirects if no customer profile is found
if (!$customerProfile) { 
    $_SESSION['loan_message'] = "You are not registered as a customer."; 
    header("Location: customerDashboard.php"); 
    exit(); 
}

// Stores customer ID in session and variable
$_SESSION['customer_id'] = $customerProfile['customer_id']; 
$customer_id = $_SESSION['customer_id']; 

// Fetches loan data with filters
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['disbursed', 'pending', 'rejected']) 
    ? $_GET['status'] 
    : ''; 

$loansQuery = "SELECT 
    loans.loan_id,
    loans.amount,
    loans.interest_rate,
    loans.duration,
    loans.installments,
    loans.collateral_value,
    loans.collateral_description,
    loans.status AS loan_status,
    loans.application_date,
    loan_offers.loan_type,
    lenders.name AS lender_name
FROM loans
JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
JOIN lenders ON loans.lender_id = lenders.lender_id
WHERE loans.customer_id = ?"; 

// Initializes parameters and types for prepared statement
$params = [$customer_id]; 
$types = "i"; 

// Applies status filter if provided
if ($statusFilter) { 
    $loansQuery .= " AND loans.status = ?"; 
    $params[] = $statusFilter; 
    $types .= "s"; 
}

// Applies loan type filter if provided
if (isset($_GET['loan_type']) && $_GET['loan_type']) { 
    $loansQuery .= " AND loan_offers.loan_type = ?"; 
    $params[] = $_GET['loan_type']; 
    $types .= "s"; 
}

// Applies date range filter if provided
if (isset($_GET['date_range']) && $_GET['date_range']) { 
    switch ($_GET['date_range']) { 
        case 'today': 
            $loansQuery .= " AND DATE(loans.application_date) = CURDATE()"; 
            break; 
        case 'week': 
            $loansQuery .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)"; 
            break; 
        case 'month': 
            $loansQuery .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())"; 
            break; 
        case 'year': 
            $loansQuery .= " AND YEAR(loans.application_date) = YEAR(CURDATE())"; 
            break; 
    }
}

// Applies amount range filter if provided
if (isset($_GET['amount_range']) && $_GET['amount_range']) { // isset() checks if 'amount_range' exists in the GET request and is not null/empty
    // str_replace() replaces '+' with '-' to standardize the delimiter, then explode() splits the string into min and max
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $_GET['amount_range'])); 
    $loansQuery .= " AND loans.amount >= ?"; // Adds a minimum amount filter to the SQL query
    $params[] = $minAmount; // Adds the minimum amount to the parameters array for the prepared statement
    $types .= "d"; // Appends 'd' (double/float) to the types string for parameter binding
    
    // Checks if the maximum amount is a valid number
    if (is_numeric($maxAmount)) { // is_numeric() checks if $maxAmount is a number
        $loansQuery .= " AND loans.amount <= ?"; // Adds a maximum amount filter to the SQL query
        $params[] = $maxAmount; // Adds the maximum amount to the parameters array
        $types .= "d"; // Appends 'd' for double type
    }
}

// Applies interest rate filter if provided
if (isset($_GET['interest_rate']) && $_GET['interest_rate']) { // Checks if 'interest_rate' exists in the GET request and is not empty
    // str_replace() replaces '+' with '-' to standardize the delimiter, then explode() splits the string into min and max
    list($minRate, $maxRate) = explode('-', str_replace('+', '-', $_GET['interest_rate'])); 
    $loansQuery .= " AND loans.interest_rate >= ?"; // Adds a minimum interest rate filter to the SQL query
    $params[] = $minRate; // Adds the minimum interest rate to the parameters array
    $types .= "d"; // Appends 'd' for double type
    
    // Checks if the maximum rate is a valid number
    if (is_numeric($maxRate)) { // is_numeric() checks if $maxRate is a number
        $loansQuery .= " AND loans.interest_rate <= ?"; // Adds a maximum interest rate filter to the SQL query
        $params[] = $maxRate; // Adds the maximum interest rate to the parameters array
        $types .= "d"; // Appends 'd' for double type
    }
}

// Adds sorting to the query
$loansQuery .= " ORDER BY loans.application_date DESC"; 

// Prepares and executes the loans query
$stmt = $myconn->prepare($loansQuery); // $myconn is the database connection object; prepare() prepares the SQL query for execution
$stmt->bind_param($types, ...$params); // bind_param() binds the parameters in $params to the SQL statement using the types in $types
$stmt->execute(); // Executes the prepared statement
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // get_result() fetches the result set; fetch_all(MYSQLI_ASSOC) returns all rows as an array of associative arrays

// Handles loan details retrieval if loan_id is provided
if (isset($_GET['loan_id'])) { // Checks if 'loan_id' exists in the GET request
    $loanId = $_GET['loan_id']; // Assigns the loan ID from the GET request to $loanId
    
    // Verifies the loan belongs to the current customer
    $verifyQuery = "SELECT customer_id FROM loans WHERE loan_id = ?"; 
    $stmt = $myconn->prepare($verifyQuery); 
    $stmt->bind_param("i", $loanId); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    
    if ($result->num_rows === 0) { 
        $_SESSION['loan_message'] = "Loan not found"; 
        header("Location: customerDashboard.php#loanHistory"); 
        exit(); 
    }
    
    $loanData = $result->fetch_assoc(); 
    if ($loanData['customer_id'] != $customer_id) { 
        $_SESSION['loan_message'] = "You don't have permission to view this loan"; 
        header("Location: customerDashboard.php#loanHistory"); 
        exit(); 
    }

    // Fetches full loan details with joins
    $loanDetailsQuery = "SELECT 
        loans.*,
        loan_offers.loan_type,
        lenders.name AS lender_name,
        DATE_FORMAT(loans.application_date, '%Y-%m-%d') as created_date
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    JOIN lenders ON loans.lender_id = lenders.lender_id
    WHERE loans.loan_id = ?"; 
    
    $stmt = $myconn->prepare($loanDetailsQuery); 
    $stmt->bind_param("i", $loanId); 
    $stmt->execute(); 
    $loanDetails = $stmt->get_result()->fetch_assoc(); 
    
    if ($loanDetails) { 
        $_SESSION['loan_details'] = $loanDetails; 
        header("Location: customerDashboard.php#loanHistory"); 
        exit(); 
    } else { 
        $_SESSION['loan_message'] = "Failed to load loan details"; 
        header("Location: customerDashboard.php#loanHistory"); 
        exit(); 
    }
}

// Fetches payment tracking data
require_once 'fetchActiveLoans.php'; 
$filters = $_SESSION['payment_filters'] ?? [ 
    'payment_status' => '', 
    'loan_type' => '', 
    'amount_range' => '', 
    'date_range' => '', 
    'due_status' => '' 
];
$_SESSION['active_loans'] = fetchActiveLoans($myconn, $customer_id, $filters); 

// Fetches transaction history
require_once 'paymentHistory.php'; 
$historyFilters = $_SESSION['history_filters'] ?? [ 
    'payment_type' => '', 
    'payment_method' => '', 
    'amount_range' => '', 
    'date_range' => '' 
];
$_SESSION['payment_history'] = fetchPaymentHistory($myconn, $customer_id, $historyFilters); 

// Fetches count and sum of disbursed loans
$disbursedQuery = "SELECT COUNT(*) as total_disbursed, SUM(amount) as total_borrowed 
                 FROM loans 
                 WHERE customer_id = ? 
                 AND status = 'disbursed'"; 
$stmt = $myconn->prepare($disbursedQuery); 
$stmt->bind_param("i", $customer_id); 
$stmt->execute(); 
$disbursedResult = $stmt->get_result(); 
$disbursedData = $disbursedResult->fetch_assoc(); 
$totalDisbursedLoans = (int)($disbursedData['total_disbursed'] ?? 0); 
$totalBorrowed = (int)($disbursedData['total_borrowed'] ?? 0); 

// Fetches count of active loans with positive remaining balance
$activeQuery = "
    SELECT COUNT(DISTINCT loans.loan_id) as active_loans
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
    WHERE loans.customer_id = ?
    AND loans.status = 'disbursed'
    AND latest_payment.remaining_balance > 0"; 
$stmt = $myconn->prepare($activeQuery); 
$stmt->bind_param("i", $customer_id); 
$stmt->execute(); 
$activeResult = $stmt->get_result(); 
$activeLoansCount = (int)$activeResult->fetch_row()[0] ?? 0; 

// Fetches sum of outstanding balances for disbursed loans
$balanceQuery = "
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
    WHERE loans.customer_id = ?
    AND loans.status = 'disbursed'"; 
$stmt = $myconn->prepare($balanceQuery); 
$stmt->bind_param("i", $customer_id); 
$stmt->execute(); 
$balanceResult = $stmt->get_result(); 
$outstandingBalance = (float)$balanceResult->fetch_row()[0] ?? 0; 

// Fetches the next payment due date
$dateQuery = "
    SELECT MIN(due_date)
    FROM loans
    WHERE customer_id = ?
    AND status = 'disbursed'
    AND due_date IS NOT NULL
    AND EXISTS (
        SELECT 1
        FROM payments
        WHERE payments.loan_id = loans.loan_id
        AND payments.remaining_balance > 0
        AND payments.payment_date = (
            SELECT MAX(payment_date)
            FROM payments
            WHERE payments.loan_id = loans.loan_id
        )
    )"; 
$stmt = $myconn->prepare($dateQuery); 
$stmt->bind_param("i", $customer_id); 
$stmt->execute(); 
$dateResult = $stmt->get_result(); 
$nextPaymentDate = $dateResult->fetch_row()[0] ?? 'N/A'; 
$nextPaymentDate = ($nextPaymentDate !== 'N/A') 
    ? date('j M', strtotime($nextPaymentDate)) 
    : 'N/A'; 

// Defines all possible loan types
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
]; 

// Fetches loan counts by type
$loanTypesQuery = "SELECT 
    loan_offers.loan_type, 
    COUNT(*) as loan_count
FROM loans 
JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
WHERE loans.customer_id = ?
AND loans.status IN ('disbursed', 'disbursed', 'active')
GROUP BY loan_offers.loan_type"; 
$stmt = $myconn->prepare($loanTypesQuery); 
$stmt->bind_param("i", $customer_id); 
$stmt->execute(); 
$loanTypesResult = $stmt->get_result(); 

$loanCounts = array_fill_keys($allLoanTypes, 0); 
while ($row = $loanTypesResult->fetch_assoc()) { 
    if (array_key_exists($row['loan_type'], $loanCounts)) { 
        $loanCounts[$row['loan_type']] = (int)$row['loan_count']; 
    }
}

// Fetches loan status counts for pie chart
$statusQuery = "SELECT status, COUNT(*) as count 
                FROM loans 
                WHERE customer_id = ? 
                GROUP BY status"; 
$stmt = $myconn->prepare($statusQuery); 
$stmt->bind_param("i", $customer_id); 
$stmt->execute(); 
$statusResult = $stmt->get_result(); 

$statusData = []; 
$totalLoans = 0; 
while ($row = $statusResult->fetch_assoc()) { 
    $statusData[$row['status']] = (int)$row['count']; 
    if (in_array($row['status'], ['pending', 'disbursed', 'rejected'])) { 
        $totalLoans += (int)$row['count']; 
    }
}

// Calculates percentages for pie chart data
$pieData = [
    'pending' => $totalLoans > 0 && isset($statusData['pending']) ? ($statusData['pending'] / $totalLoans * 100) : 0, 
    'disbursed' => $totalLoans > 0 && isset($statusData['disbursed']) ? ($statusData['disbursed'] / $totalLoans * 100) : 0, 
    'rejected' => $totalLoans > 0 && isset($statusData['rejected']) ? ($statusData['rejected'] / $totalLoans * 100) : 0 
];

// Clears loan application message after display
if (isset($_SESSION['loan_application_message_shown'])) { 
    unset($_SESSION['loan_message']); 
    unset($_SESSION['loan_application_message_shown']); 
}

// Clears loan details message after display
if (isset($_SESSION['loan_details_message_shown'])) { 
    unset($_SESSION['loan_details_message']); 
    unset($_SESSION['loan_details_message_shown']); 
}

// Clears profile message after display
if (isset($_SESSION['profile_message_shown'])) { 
    unset($_SESSION['profile_message']); 
    unset($_SESSION['profile_message_type']); 
    unset($_SESSION['profile_message_shown']); 
}

// Sets default timezone and generates greeting based on time
date_default_timezone_set('Africa/Nairobi'); 
$currentTime = date("H"); 
$message = $currentTime < 12 ? "good morning," : ($currentTime < 18 ? "good afternoon," : "good evening,"); 
$currentYear = date("Y"); 

// Stores active loans and filters in session
$activeLoans = $_SESSION['active_loans'] ?? []; 
$filters = $_SESSION['payment_filters'] ?? [ 
    'payment_status' => '', 
    'loan_type' => '', 
    'amount_range' => '', 
    'date_range' => '', 
    'due_status' => '' 
];

?>