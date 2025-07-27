<?php
// Checks if a session is not already active before starting a new one
if (session_status() !== PHP_SESSION_ACTIVE) { // session_status() returns the current session status (none, active, disabled)
    session_start(); // Starts a new session or resumes the existing one
}

// require_once includes and evaluates the specified file during script execution, only once.
// Used here to enforce access control and ensure the user has the right permissions to view this page.
require_once 'check_access.php'; 

// include brings in the database configuration file, which sets up the $myconn variable for database connection.
include '../phpconfig/config.php'; 

// $_SESSION is a PHP superglobal array used to store information about the user's session.
// Checks if the user is logged in by verifying if 'user_id' exists in the session.
if (!isset($_SESSION['user_id'])) { // isset() checks if a variable is set and not null
    header("Location: signin.html"); // header() sends a raw HTTP header (here, a redirect)
    exit(); // exit() terminates script execution immediately
}

// Stores the user ID from the session in a variable for later use
$userId = $_SESSION['user_id']; // $userId holds the current user's ID

// Fetches the user's name from the users table using a prepared statement for security
$userQuery = "SELECT user_name FROM users WHERE user_id = ?"; // SQL query with a placeholder
$stmt = $myconn->prepare($userQuery); // prepare() creates a prepared statement object from the SQL query
$stmt->bind_param("i", $userId); // bind_param() binds variables to the prepared statement as parameters; "i" means integer
$stmt->execute(); // execute() runs the prepared statement
$userResult = $stmt->get_result(); // get_result() fetches the result set from the executed statement
$user = $userResult->fetch_assoc() ?? ['user_name' => "Guest"]; // fetch_assoc() fetches a result row as an associative array; ?? provides a default if null
$_SESSION['user_name'] = $user['user_name']; // Stores the user name in the session for later use

// Fetches the customer's profile details using another prepared statement
$customerQuery = "SELECT customer_id, name, email, phone, address, bank_account, 
                 DATE_FORMAT(dob, '%Y-%m-%d') as dob, 
                 DATE_FORMAT(registration_date, '%Y-%m-%d') as registration_date, 
                 national_id 
                 FROM customers WHERE user_id = ?"; // SQL query with a placeholder
$stmt = $myconn->prepare($customerQuery); // Prepares the SQL statement
$stmt->bind_param("i", $userId); // Binds the user ID as an integer parameter
$stmt->execute(); // Executes the statement
$customerResult = $stmt->get_result(); // Gets the result set
$customerProfile = $customerResult->fetch_assoc(); // Fetches the customer profile as an associative array

// If no customer profile is found, set a session message and redirect to the dashboard
if (!$customerProfile) { 
    $_SESSION['loan_message'] = "You are not registered as a customer."; // Sets a message in the session
    header("Location: customerDashboard.php"); // Redirects to the dashboard
    exit(); // Stops script execution
}

// Stores customer ID in session and variable for later use
$_SESSION['customer_id'] = $customerProfile['customer_id']; // Store customer ID in session
$customer_id = $_SESSION['customer_id']; // $customer_id holds the current customer's ID

// Determines the loan status filter from GET parameters, only allowing specific values
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['disbursed', 'pending', 'rejected', 'submitted']) 
    ? $_GET['status'] // Use status from GET if valid
    : ''; // Otherwise, no filter

// Builds the base SQL query to fetch loans for this customer, joining loan offers and lenders
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
WHERE loans.customer_id = ?"; // The ? is a placeholder for a prepared statement

// Initializes parameters and types for prepared statement
$params = [$customer_id]; // $params will hold all parameters for the query
$types = "i"; // $types is a string describing parameter types (i = integer, s = string, d = double)

// Applies status filter if provided
if ($statusFilter) { 
    $loansQuery .= " AND loans.status = ?"; // Add status filter to query
    $params[] = $statusFilter; // Add status to params
    $types .= "s"; // Add string type
}

// Applies loan type filter if provided
if (isset($_GET['loan_type']) && $_GET['loan_type']) { // Checks if 'loan_type' exists in the GET request and is not empty
    $loansQuery .= " AND loan_offers.loan_type = ?"; // Add loan type filter to query
    $params[] = $_GET['loan_type']; // Add loan type to params
    $types .= "s"; // Add string type
}

// Applies date range filter if provided
if (isset($_GET['date_range']) && $_GET['date_range']) { // Checks if 'date_range' exists in the GET request and is not empty
    switch ($_GET['date_range']) { // switch() selects code to execute based on value
        case 'today': 
            $loansQuery .= " AND DATE(loans.application_date) = CURDATE()"; // Today's loans
            break; 
        case 'week': 
            $loansQuery .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)"; // This week's loans
            break; 
        case 'month': 
            $loansQuery .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())"; // This month's loans
            break; 
        case 'year': 
            $loansQuery .= " AND YEAR(loans.application_date) = YEAR(CURDATE())"; // This year's loans
            break; 
    }
}

// Applies amount range filter if provided
if (isset($_GET['amount_range']) && $_GET['amount_range']) { // isset() checks if 'amount_range' exists in the GET request and is not null/empty
    // str_replace() replaces '+' with '-' to standardize the delimiter, then explode() splits the string into min and max
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $_GET['amount_range'])); // list() assigns array values to variables
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
    list($minRate, $maxRate) = explode('-', str_replace('+', '-', $_GET['interest_rate'])); // list() assigns array values to variables
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
$loansQuery .= " ORDER BY loans.application_date DESC"; // Orders the results by application date, most recent first

// Prepares and executes the loans query using a prepared statement for security and performance
$stmt = $myconn->prepare($loansQuery); // prepare() prepares the SQL query for execution and returns a statement object
$stmt->bind_param($types, ...$params); // bind_param() binds the parameters in $params to the SQL statement using the types in $types
$stmt->execute(); // Executes the prepared statement
$loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // get_result() fetches the result set; fetch_all(MYSQLI_ASSOC) returns all rows as an array of associative arrays

// Handles loan details retrieval if loan_id is provided in the GET request
if (isset($_GET['loan_id'])) { // Checks if 'loan_id' exists in the GET request
    $loanId = $_GET['loan_id']; // Assigns the loan ID from the GET request to $loanId
    
    // Verifies the loan belongs to the current customer
    $verifyQuery = "SELECT customer_id FROM loans WHERE loan_id = ?"; // SQL query to get the customer_id for the given loan_id
    $stmt = $myconn->prepare($verifyQuery); // Prepares the SQL statement
    $stmt->bind_param("i", $loanId); // Binds $loanId as an integer parameter
    $stmt->execute(); // Executes the statement
    $result = $stmt->get_result(); // Gets the result set
    
    if ($result->num_rows === 0) { // num_rows returns the number of rows in the result set
        $_SESSION['loan_message'] = "Loan not found"; // Sets an error message in the session
        header("Location: customerDashboard.php#loanHistory"); // Redirects to the dashboard
        exit(); // Stops script execution
    }
    
    $loanData = $result->fetch_assoc(); // Fetches the row as an associative array
    if ($loanData['customer_id'] != $customer_id) { // Checks if the loan belongs to the current customer
        $_SESSION['loan_message'] = "You don't have permission to view this loan"; // Sets an error message
        header("Location: customerDashboard.php#loanHistory"); // Redirects to the dashboard
        exit(); // Stops script execution
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
    WHERE loans.loan_id = ?"; // Query for full loan details
    
    $stmt = $myconn->prepare($loanDetailsQuery); // Prepare statement
    $stmt->bind_param("i", $loanId); // Bind loan ID
    $stmt->execute(); // Execute
    $loanDetails = $stmt->get_result()->fetch_assoc(); // Fetch details
    
    if ($loanDetails) { // If found
        $_SESSION['loan_details'] = $loanDetails; // Store in session
        header("Location: customerDashboard.php#loanHistory"); // Redirect
        exit(); // Stop script
    } else { // If not found
        $_SESSION['loan_message'] = "Failed to load loan details"; // Set error
        header("Location: customerDashboard.php#loanHistory"); // Redirect
        exit(); // Stop script
    }
}

// Fetches payment tracking data
require_once 'fetchActiveLoans.php'; // Includes function for fetching active loans
$filters = $_SESSION['payment_filters'] ?? [ // Get filters from session or set defaults using null coalescing operator (??)
    'payment_status' => '', 
    'loan_type' => '', 
    'amount_range' => '', 
    'date_range' => '', 
    'due_status' => '' 
];
$_SESSION['active_loans'] = fetchActiveLoans($myconn, $customer_id, $filters); // Store active loans in session

// Fetches transaction history
require_once 'paymentHistory.php'; // Includes function for payment history
$historyFilters = $_SESSION['history_filters'] ?? [ // Get filters from session or set defaults
    'payment_type' => '', 
    'payment_method' => '', 
    'amount_range' => '', 
    'date_range' => '' 
];
$_SESSION['payment_history'] = fetchPaymentHistory($myconn, $customer_id, $historyFilters); // Store payment history in session

// Fetches count and sum of disbursed loans
$disbursedQuery = "SELECT COUNT(*) as total_disbursed, SUM(amount) as total_borrowed 
                 FROM loans 
                 WHERE customer_id = ? 
                 AND status = 'disbursed'"; // Query for disbursed loans
$stmt = $myconn->prepare($disbursedQuery); // Prepare statement
$stmt->bind_param("i", $customer_id); // Bind customer ID
$stmt->execute(); // Execute
$disbursedResult = $stmt->get_result(); // Get result
$disbursedData = $disbursedResult->fetch_assoc(); // Fetch data
$totalDisbursedLoans = (int)($disbursedData['total_disbursed'] ?? 0); // Total disbursed loans
$totalBorrowed = (int)($disbursedData['total_borrowed'] ?? 0); // Total borrowed amount

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
    AND latest_payment.remaining_balance > 0"; // Query for active loans
$stmt = $myconn->prepare($activeQuery); // Prepare statement
$stmt->bind_param("i", $customer_id); // Bind customer ID
$stmt->execute(); // Execute
$activeResult = $stmt->get_result(); // Get result
$activeLoansCount = (int)$activeResult->fetch_row()[0] ?? 0; // Number of active loans

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
    AND loans.status = 'disbursed'"; // Query for outstanding balance
$stmt = $myconn->prepare($balanceQuery); // Prepare statement
$stmt->bind_param("i", $customer_id); // Bind customer ID
$stmt->execute(); // Execute
$balanceResult = $stmt->get_result(); // Get result
$outstandingBalance = (float)$balanceResult->fetch_row()[0] ?? 0; // Outstanding balance

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
    )"; // Query for next payment due date
$stmt = $myconn->prepare($dateQuery); // Prepare statement
$stmt->bind_param("i", $customer_id); // Bind customer ID
$stmt->execute(); // Execute
$dateResult = $stmt->get_result(); // Get result
$nextPaymentDate = $dateResult->fetch_row()[0] ?? 'N/A'; // Next payment date or N/A
$nextPaymentDate = ($nextPaymentDate !== 'N/A') 
    ? date('j M', strtotime($nextPaymentDate)) // Format date as "day Month"
    : 'N/A'; // If not available, set to N/A

// Defines all possible loan types in an array
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
]; // Array of all loan types

// Fetches loan counts by type
$loanTypesQuery = "SELECT 
    loan_offers.loan_type, 
    COUNT(*) as loan_count
FROM loans 
JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
WHERE loans.customer_id = ?
AND loans.status IN ('disbursed', 'disbursed', 'active')
GROUP BY loan_offers.loan_type"; // Query for loan counts by type
$stmt = $myconn->prepare($loanTypesQuery); // Prepare statement
$stmt->bind_param("i", $customer_id); // Bind customer ID
$stmt->execute(); // Execute
$loanTypesResult = $stmt->get_result(); // Get result

$loanCounts = array_fill_keys($allLoanTypes, 0); // Initialize counts for all types
while ($row = $loanTypesResult->fetch_assoc()) { // Loop through results
    if (array_key_exists($row['loan_type'], $loanCounts)) { // If type exists
        $loanCounts[$row['loan_type']] = (int)$row['loan_count']; // Set count
    }
}

// Fetches loan status counts for pie chart
$statusQuery = "SELECT status, COUNT(*) as count 
                FROM loans 
                WHERE customer_id = ? 
                GROUP BY status"; // Query for loan status counts
$stmt = $myconn->prepare($statusQuery); // Prepare statement
$stmt->bind_param("i", $customer_id); // Bind customer ID
$stmt->execute(); // Execute
$statusResult = $stmt->get_result(); // Get result

$statusData = []; // Initialize status data array
$totalLoans = 0; // Initialize total loans
while ($row = $statusResult->fetch_assoc()) { // Loop through results
    $statusData[$row['status']] = (int)$row['count']; // Store count by status
    if (in_array($row['status'], ['pending', 'disbursed', 'rejected'])) { // If status is one of these
        $totalLoans += (int)$row['count']; // Add to total
    }
}

// Calculates percentages for pie chart data
$pieData = [
    'pending' => $totalLoans > 0 && isset($statusData['pending']) ? ($statusData['pending'] / $totalLoans * 100) : 0, // Pending %
    'disbursed' => $totalLoans > 0 && isset($statusData['disbursed']) ? ($statusData['disbursed'] / $totalLoans * 100) : 0, // Disbursed %
    'rejected' => $totalLoans > 0 && isset($statusData['rejected']) ? ($statusData['rejected'] / $totalLoans * 100) : 0 // Rejected %
];

// Clears loan application message after display
if (isset($_SESSION['loan_application_message_shown'])) { // Checks if the loan application message was shown
    unset($_SESSION['loan_message']); // Removes the loan message from session
    unset($_SESSION['loan_application_message_shown']); // Removes the flag from session
}

// Clears loan details message after display
if (isset($_SESSION['loan_details_message_shown'])) { // Checks if the loan details message was shown
    unset($_SESSION['loan_details_message']); // Removes the loan details message from session
    unset($_SESSION['loan_details_message_shown']); // Removes the flag from session
}

// Clears profile message after display
if (isset($_SESSION['profile_message_shown'])) { // Checks if the profile message was shown
    unset($_SESSION['profile_message']); // Removes the profile message from session
    unset($_SESSION['profile_message_type']); // Removes the profile message type from session
    unset($_SESSION['profile_message_shown']); // Removes the flag from session
}

// Sets default timezone and generates greeting based on time
date_default_timezone_set('Africa/Nairobi'); // Sets the default timezone for date/time functions
$currentTime = date("H"); // Gets the current hour in 24-hour format
$message = $currentTime < 12 ? "good morning," : ($currentTime < 18 ? "good afternoon," : "good evening,"); // Sets greeting based on time
$currentYear = date("Y"); // Gets the current year

// Stores active loans and filters in session
$activeLoans = $_SESSION['active_loans'] ?? []; // Gets active loans from session or sets to empty array if not set
$filters = $_SESSION['payment_filters'] ?? [ // Gets payment filters from session or sets default values
    'payment_status' => '', 
    'loan_type' => '', 
    'amount_range' => '', 
    'date_range' => '', 
    'due_status' => '' 
];

?>