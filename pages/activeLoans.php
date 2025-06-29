<?php
// Checks if a session is not already active before starting a new one
if (session_status() === PHP_SESSION_NONE) { // session_status() returns the current session status; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Verifies if the lender ID is set in the session
if (!isset($_SESSION['lender_id'])) { // isset() checks if a variable is set and not null
    die("Lender ID not set in session."); // die() terminates script execution with an error message
}

// Stores the lender ID from the session for use in queries
$lender_id = $_SESSION['lender_id']; // $_SESSION is a superglobal array for session variables; $lender_id holds the current lender's ID

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // include brings in the specified file for database connectivity

// Retrieves filter parameters from the URL with defaults
$activeStatusFilter = $_GET['active_status'] ?? ''; // Null coalescing operator (??) provides a default empty string if not set
$activeLoanTypeFilter = $_GET['active_loan_type'] ?? ''; // Gets loan type filter or defaults to empty string
$activeDueStatusFilter = $_GET['active_due_status'] ?? ''; // Gets due status filter or defaults to empty string
$activeDateRange = $_GET['active_date_range'] ?? ''; // Gets date range filter or defaults to empty string
$activeAmountRange = $_GET['active_amount_range'] ?? ''; // Gets amount range filter or defaults to empty string
$activeDurationRange = $_GET['active_duration_range'] ?? ''; // Gets duration range filter or defaults to empty string
$activeCollateralRange = $_GET['active_collateral_range'] ?? ''; // Gets collateral range filter or defaults to empty string

// Initializes parameters and types for prepared statement
$params = [$lender_id]; // Initializes array with lender ID as the first parameter
$types = "i"; // Initializes types string with 'i' for integer (lender_id)

// Builds the base SQL query to fetch active loans with remaining balances
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
    AND latest_payment.payment_type != 'full'"; // Query fetches active loans with positive balances, excluding fully paid loans

// Applies status filter if provided and valid
if (!empty($activeStatusFilter) && in_array($activeStatusFilter, ['disbursed'])) { // in_array() checks if the status is valid
    $activeLoansQuery .= " AND loans.status = ?"; // Adds status filter to the query
    $params[] = $activeStatusFilter; // Adds status to parameters array
    $types .= "s"; // Appends 's' for string type
}

// Applies loan type filter if provided
if (!empty($activeLoanTypeFilter)) { // Checks if loan type filter is not empty
    $activeLoansQuery .= " AND loan_offers.loan_type = ?"; // Adds loan type filter
    $params[] = $activeLoanTypeFilter; // Adds loan type to parameters
    $types .= "s"; // Appends 's' for string type
}

// Applies due status filter if provided
if (!empty($activeDueStatusFilter)) { // Checks if due status filter is not empty
    $activeLoansQuery .= " AND loans.isDue = ?"; // Adds due status filter
    $params[] = ($activeDueStatusFilter === 'due') ? 1 : 0; // Converts due status to 1 or 0
    $types .= "i"; // Appends 'i' for integer type
}

// Applies date range filter if provided
if (!empty($activeDateRange)) { // Checks if date range filter is not empty
    switch ($activeDateRange) { // switch() selects code based on the date range value
        case 'today':
            $activeLoansQuery .= " AND DATE(loans.application_date) = CURDATE()"; // Filters for today's loans
            break;
        case 'week':
            $activeLoansQuery .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)"; // Filters for this week's loans
            break;
        case 'month':
            $activeLoansQuery .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())"; // Filters for this month's loans
            break;
        case 'year':
            $activeLoansQuery .= " AND YEAR(loans.application_date) = YEAR(CURDATE())"; // Filters for this year's loans
            break;
    }
}

// Applies amount range filter if provided
if (!empty($activeAmountRange)) { // Checks if amount range filter is not empty
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $activeAmountRange)); // Splits range into min and max
    $activeLoansQuery .= " AND loans.amount >= ?"; // Adds minimum amount filter
    $params[] = $minAmount; // Adds minimum amount to parameters
    $types .= "d"; // Appends 'd' for double type
    if (is_numeric($maxAmount)) { // is_numeric() checks if maxAmount is a valid number
        $activeLoansQuery .= " AND loans.amount <= ?"; // Adds maximum amount filter
        $params[] = $maxAmount; // Adds maximum amount to parameters
        $types .= "d"; // Appends 'd' for double type
    }
}

// Applies duration range filter if provided
if (!empty($activeDurationRange)) { // Checks if duration range filter is not empty
    list($minDuration, $maxDuration) = explode('-', str_replace('+', '-', $activeDurationRange)); // Splits range into min and max
    $activeLoansQuery .= " AND loans.duration >= ?"; // Adds minimum duration filter
    $params[] = $minDuration; // Adds minimum duration to parameters
    $types .= "i"; // Appends 'i' for integer type
    if (is_numeric($maxDuration)) { // Checks if maxDuration is valid
        $activeLoansQuery .= " AND loans.duration <= ?"; // Adds maximum duration filter
        $params[] = $maxDuration; // Adds maximum duration to parameters
        $types .= "i"; // Appends 'i' for integer type
    }
}

// Applies collateral range filter if provided
if (!empty($activeCollateralRange)) { // Checks if collateral range filter is not empty
    list($minCollateral, $maxCollateral) = explode('-', str_replace('+', '-', $activeCollateralRange)); // Splits range into min and max
    $activeLoansQuery .= " AND loans.collateral_value >= ?"; // Adds minimum collateral filter
    $params[] = $minCollateral; // Adds minimum collateral to parameters
    $types .= "d"; // Appends 'd' for double type
    if (is_numeric($maxCollateral)) { // Checks if maxCollateral is valid
        $activeLoansQuery .= " AND loans.collateral_value <= ?"; // Adds maximum collateral filter
        $params[] = $maxCollateral; // Adds maximum collateral to parameters
        $types .= "d"; // Appends 'd' for double type
    }
}

// Adds sorting to the query
$activeLoansQuery .= " ORDER BY loans.application_date DESC"; // Orders results by application date, newest first

// Prepares and executes the active loans query using a prepared statement
$stmt = $myconn->prepare($activeLoansQuery); // prepare() creates a prepared statement for secure execution
if (!$stmt) { // Checks if preparation failed
    die("Prepare failed: " . $myconn->error); // Terminates script with the database error
}

// Binds parameters to the prepared statement
if (count($params) > 1) { // Checks if there are additional parameters beyond lender_id
    $stmt->bind_param($types, ...$params); // bind_param() binds all parameters using the types string
} else {
    $stmt->bind_param($types, $lender_id); // Binds only the lender_id if no additional parameters
}

// Executes the query and fetches results
$stmt->execute(); // Executes the prepared statement
$activeLoansResult = $stmt->get_result(); // Gets the result set
$activeLoanData = $activeLoansResult->fetch_all(MYSQLI_ASSOC); // fetch_all(MYSQLI_ASSOC) returns all rows as an array of associative arrays
$stmt->close(); // Closes the prepared statement to free resources

// Defines an array of all possible loan types for consistency
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
]; // Array listing all supported loan types

// Returns the active loans data, filters, and loan types
return [
    'activeLoanData' => $activeLoanData, // Array of active loan records
    'filters' => [
        'status' => $activeStatusFilter, // Stores status filter
        'loan_type' => $activeLoanTypeFilter, // Stores loan type filter
        'due_status' => $activeDueStatusFilter, // Stores due status filter
        'date_range' => $activeDateRange, // Stores date range filter
        'amount_range' => $activeAmountRange, // Stores amount range filter
        'duration_range' => $activeDurationRange, // Stores duration range filter
        'collateral_range' => $activeCollateralRange // Stores collateral range filter
    ],
    'allLoanTypes' => $allLoanTypes // Array of all loan types
];
?>