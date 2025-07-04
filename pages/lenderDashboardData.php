<?php
// Checks if a session is not already active before starting a new one
if (session_status() !== PHP_SESSION_ACTIVE) { // session_status() returns the current session status (none, active, disabled)
    session_start(); // Starts a new session or resumes the existing one
}

// Requires the access control file to enforce user permissions for this page
require_once 'check_access.php'; // require_once includes and evaluates the specified file only once

// Verifies if the user is logged in by checking for 'user_id' in the session
if (!isset($_SESSION['user_id'])) { // isset() checks if a variable is set and not null
    header("Location: signin.html"); // header() sends a raw HTTP header to redirect to the sign-in page
    exit(); // exit() terminates script execution immediately
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // include brings in the specified file for database connectivity

// Stores the user ID from the session for use in queries
$userId = $_SESSION['user_id']; // $_SESSION is a superglobal array for session variables; $userId holds the current user's ID

// Fetches the user's name from the users table using a direct SQL query
$query = "SELECT user_name FROM users WHERE user_id = '$userId'"; // SQL query to retrieve user_name
$result = mysqli_query($myconn, $query); // mysqli_query() executes the query on the database connection

// Checks if the query was successful and has results, then stores the user name
if ($result && mysqli_num_rows($result) > 0) { // mysqli_num_rows() returns the number of rows in the result set
    $user = mysqli_fetch_assoc($result); // mysqli_fetch_assoc() fetches a result row as an associative array
    $_SESSION['user_name'] = $user['user_name']; // Stores the user name in the session for later use
} else {
    $_SESSION['user_name'] = "Guest"; // Sets a default user name if no user is found
}

// Fetches the lender ID associated with the user
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'"; // SQL query to get lender_id
$lenderResult = mysqli_query($myconn, $lenderQuery); // Executes the query to fetch lender data

// Verifies if the user is a registered lender
if (mysqli_num_rows($lenderResult) > 0) { // Checks if the query returned any rows
    $lender = mysqli_fetch_assoc($lenderResult); // Fetches the lender data as an associative array
    $_SESSION['lender_id'] = $lender['lender_id']; // Stores the lender ID in the session
} else {
    $_SESSION['loan_message'] = "You are not registered as a lender."; // Sets an error message in the session
    header("Location: lenderDashboard.php"); // Redirects to the lender dashboard
    exit(); // Stops script execution
}

// Stores the lender ID in a variable for use in queries
$lender_id = $_SESSION['lender_id']; // $lender_id holds the current lender's ID

// Includes the payment review logic for handling payment-related data
require_once 'paymentReview.php'; // require_once includes the file for payment review functionality

// Includes the active loans data and extracts relevant arrays
$activeLoansData = require_once 'activeLoans.php'; // Includes and evaluates the activeLoans.php file, which returns an array
$activeLoanData = $activeLoansData['activeLoanData']; // Extracts the active loans data array
$activeFilters = $activeLoansData['filters']; // Extracts the filters array for active loans
$allLoanTypes = $activeLoansData['allLoanTypes']; // Extracts the array of all loan types

// Defines an array of all possible loan types for consistency
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
]; // Array listing all supported loan types

// Fetches the total count of loan offers made by the lender
$totalOffersQuery = "SELECT COUNT(*) FROM loan_offers WHERE lender_id = '$lender_id'"; // SQL query to count loan offers
$totalOffersResult = mysqli_query($myconn, $totalOffersQuery); // Executes the query
$totalOffers = (int)mysqli_fetch_row($totalOffersResult)[0]; // Fetches the count and casts to integer

// Fetches the average interest rate of the lender's loan offers
$avgInterestQuery = "SELECT AVG(interest_rate) FROM loan_offers WHERE lender_id = '$lender_id'"; // SQL query to calculate average interest rate
$avgInterestResult = mysqli_query($myconn, $avgInterestQuery); // Executes the query
$avgInterestRate = number_format((float)mysqli_fetch_row($avgInterestResult)[0], 2); // Fetches the average and formats to 2 decimal places

// Fetches the total outstanding balance for disbursed loans using a prepared statement
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
    AND loans.status = 'disbursed'"; // Query to sum remaining balances for disbursed loans
$stmt = $myconn->prepare($owedQuery); // prepare() creates a prepared statement for secure execution
$stmt->bind_param("i", $lender_id); // bind_param() binds the lender ID as an integer
$stmt->execute(); // Executes the prepared statement
$owedResult = $stmt->get_result(); // Gets the result set
$owedData = $owedResult->fetch_row(); // Fetches the result row
$owedCapacity = $owedData[0] ? number_format((float)$owedData[0], 0) : '0'; // Formats the sum or defaults to '0'

// Fetches the count of disbursed loans for the lender
$disbursedLoansQuery = "SELECT COUNT(*) FROM loans WHERE lender_id = '$lender_id' AND status = 'disbursed'"; // SQL query to count disbursed loans
$disbursedLoansResult = mysqli_query($myconn, $disbursedLoansQuery); // Executes the query
$disbursedLoans = (int)mysqli_fetch_row($disbursedLoansResult)[0]; // Fetches the count and casts to integer

// Fetches the count of active loans (disbursed with positive remaining balance)
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
    AND latest_payment.remaining_balance > 0"; // Query to count active loans
$stmt = $myconn->prepare($activeLoansQuery); // Prepares the statement
$stmt->bind_param("i", $lender_id); // Binds the lender ID
$stmt->execute(); // Executes the statement
$activeLoansResult = $stmt->get_result(); // Gets the result
$activeLoans = (int)$activeLoansResult->fetch_row()[0] ?? 0; // Fetches the count or defaults to 0

// Fetches the total amount disbursed by the lender
$disbursedAmountQuery = "SELECT SUM(amount) FROM loans WHERE lender_id = '$lender_id' AND status IN ('disbursed')"; // Query to sum disbursed loan amounts
$disbursedAmountResult = mysqli_query($myconn, $disbursedAmountQuery); // Executes the query
$disbursedAmountData = mysqli_fetch_row($disbursedAmountResult); // Fetches the result
$totalDisbursedAmount = $disbursedAmountData[0] ? number_format((float)$disbursedAmountData[0]) : 0; // Formats the sum or defaults to 0


// Fetches total pending loan requests for the lender
$loanRequestsCountQuery = "SELECT COUNT(*) FROM loans WHERE lender_id = '$lender_id' AND status = 'pending'"; // Query counts pending loans
$loanRequestsCountResult = mysqli_query($myconn, $loanRequestsCountQuery); // Executes query
$loanRequestsCountRow = mysqli_fetch_row($loanRequestsCountResult); // Gets result as numeric array
$loanRequestsCount = isset($loanRequestsCountRow[0]) ? (int)$loanRequestsCountRow[0] : 0; // Extracts count (0 if empty)

// Fetches loan offers with their disbursed loan counts
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
                             loan_offers.max_amount, loan_offers.max_duration"; // Query to fetch loan offers and their disbursed counts
$loanOffersResult = mysqli_query($myconn, $loanOffersQuery); // Executes the query

// Initializes arrays for loan counts and offer data
$loanCounts = array_fill_keys($allLoanTypes, 0); // array_fill_keys() creates an array with specified keys and default value 0
$offersData = []; // Initializes an empty array for offer data

// Processes loan offers and counts disbursed loans by type
if ($loanOffersResult) { // Checks if the query was successful
    while ($row = mysqli_fetch_assoc($loanOffersResult)) { // Loops through each result row
        $loanType = $row['loan_type']; // Extracts the loan type
        $loanCounts[$loanType] = (int)$row['disbursed_count']; // Updates the count for the loan type
        
        $offersData[] = [ // Adds offer details to the offersData array
            'offer_id' => $row['offer_id'],
            'loan_type' => $loanType,
            'interest_rate' => $row['interest_rate'],
            'max_amount' => $row['max_amount'],
            'max_duration' => $row['max_duration']
        ];
    }
    // Sorts the offersData array by offer_id in descending order
    usort($offersData, function($a, $b) { // usort() sorts an array using a user-defined comparison function
        return $b['offer_id'] - $a['offer_id']; // Compares offer IDs for descending order
    });
}

// Fetches loan status distribution for the lender
$statusQuery = "SELECT status, COUNT(*) as count FROM loans WHERE lender_id = '$lender_id' GROUP BY status"; // Query to count loans by status
$statusResult = mysqli_query($myconn, $statusQuery); // Executes the query
$statusData = mysqli_fetch_all($statusResult, MYSQLI_ASSOC); // Fetches all rows as an array of associative arrays

// Retrieves filter parameters from the URL with defaults
$statusFilter = $_GET['status'] ?? ''; // Null coalescing operator (??) provides a default empty string if not set
$loanTypeFilter = $_GET['loan_type'] ?? ''; // Gets loan type filter or defaults to empty string

// Builds the loan requests query with filters
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
WHERE loans.lender_id = '$lender_id'
AND loans.status != 'submitted'"; // Base query to fetch loan requests, excluding 'submitted' status

// Applies status filter if provided and valid
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'disbursed', 'rejected'])) { // in_array() checks if the status is valid
    $loanRequestsQuery .= " AND loans.status = '$statusFilter'"; // Adds status filter to the query
}

// Applies loan type filter if provided
if (!empty($loanTypeFilter)) { // Checks if the loan type filter is not empty
    $loanRequestsQuery .= " AND loan_offers.loan_type = '$loanTypeFilter'"; // Adds loan type filter
}

// Applies date range filter if provided
if (isset($_GET['date_range']) && $_GET['date_range']) { // Checks if 'date_range' exists and is not empty
    switch ($_GET['date_range']) { // switch() selects code based on the date range value
        case 'today':
            $loanRequestsQuery .= " AND DATE(loans.application_date) = CURDATE()"; // Filters for today's loans
            break;
        case 'week':
            $loanRequestsQuery .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)"; // Filters for this week's loans
            break;
        case 'month':
            $loanRequestsQuery .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())"; // Filters for this month's loans
            break;
        case 'year':
            $loanRequestsQuery .= " AND YEAR(loans.application_date) = YEAR(CURDATE())"; // Filters for this year's loans
            break;
    }
}

// Applies amount range filter if provided
if (isset($_GET['amount_range']) && $_GET['amount_range']) { // Checks if 'amount_range' exists and is not empty
    list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $_GET['amount_range'])); // Splits the range into min and max
    $loanRequestsQuery .= " AND loans.amount >= $minAmount"; // Adds minimum amount filter
    if (is_numeric($maxAmount)) { // is_numeric() checks if maxAmount is a valid number
        $loanRequestsQuery .= " AND loans.amount <= $maxAmount"; // Adds maximum amount filter
    }
}

// Applies duration range filter if provided
if (isset($_GET['duration_range']) && $_GET['duration_range']) { // Checks if 'duration_range' exists and is not empty
    list($minDuration, $maxDuration) = explode('-', str_replace('+', '-', $_GET['duration_range'])); // Splits the range
    $loanRequestsQuery .= " AND loans.duration >= $minDuration"; // Adds minimum duration filter
    if (is_numeric($maxDuration)) { // Checks if maxDuration is valid
        $loanRequestsQuery .= " AND loans.duration <= $maxDuration"; // Adds maximum duration filter
    }
}

// Applies collateral value range filter if provided
if (isset($_GET['collateral_range']) && $_GET['collateral_range']) { // Checks if 'collateral_range' exists and is not empty
    list($minCollateral, $maxCollateral) = explode('-', str_replace('+', '-', $_GET['collateral_range'])); // Splits the range
    $loanRequestsQuery .= " AND loans.collateral_value >= $minCollateral"; // Adds minimum collateral filter
    if (is_numeric($maxCollateral)) { // Checks if maxCollateral is valid
        $loanRequestsQuery .= " AND loans.collateral_value <= $maxCollateral"; // Adds maximum collateral filter
    }
}

// Adds sorting to the query
$loanRequestsQuery .= " ORDER BY loans.application_date DESC"; // Orders results by application date, newest first

// Executes the loan requests query
$loanRequestsResult = mysqli_query($myconn, $loanRequestsQuery); // Runs the query
if (!$loanRequestsResult) { // Checks if the query failed
    die("Query failed: " . mysqli_error($myconn)); // die() stops execution and outputs the error
}
$loanRequests = mysqli_fetch_all($loanRequestsResult, MYSQLI_ASSOC); // Fetches all rows as an array of associative arrays

// Fetches loan status distribution for pie chart
$statusQuery = "SELECT status, COUNT(*) as count 
                FROM loans 
                WHERE lender_id = '$lender_id' 
                GROUP BY status"; // Query to count loans by status
$statusResult = mysqli_query($myconn, $statusQuery); // Executes the query
$statusData = []; // Initializes array for status data
$totalLoans = 0; // Initializes total loan count

// Processes status data and calculates total loans
while ($row = mysqli_fetch_assoc($statusResult)) { // Loops through result rows
    $statusData[$row['status']] = (int)$row['count']; // Stores count for each status
    if (in_array($row['status'], ['pending', 'disbursed', 'rejected'])) { // Checks for relevant statuses
        $totalLoans += (int)$row['count']; // Adds to total loan count
    }
}

// Calculates percentages for pie chart data
$pieData = [
    'pending' => $totalLoans > 0 && isset($statusData['pending']) ? ($statusData['pending'] / $totalLoans * 100) : 0, // Calculates pending percentage
    'disbursed' => $totalLoans > 0 && isset($statusData['disbursed']) ? ($statusData['disbursed'] / $totalLoans * 100) : 0, // Calculates disbursed percentage
    'rejected' => $totalLoans > 0 && isset($statusData['rejected']) ? ($statusData['rejected'] / $totalLoans * 100) : 0 // Calculates rejected percentage
];

// Fetches lender profile data
$lenderProfileQuery = "SELECT * FROM lenders WHERE lender_id = '$lender_id'"; // Query to fetch all lender details
$lenderProfileResult = mysqli_query($myconn, $lenderProfileQuery); // Executes the query
$lenderProfile = mysqli_fetch_assoc($lenderProfileResult); // Fetches the lender profile as an associative array

// Checks for and clears loan message after display
if (isset($_SESSION['loan_message'])) { // Checks if a loan message exists in the session
    $loan_message = $_SESSION['loan_message']; // Stores the message in a variable
    unset($_SESSION['loan_message']); // Removes the message from the session
} else {
    $loan_message = null; // Sets message to null if none exists
}

?>