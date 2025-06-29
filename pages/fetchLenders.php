<?php
// Starts the session if it has not been started already
if (session_status() === PHP_SESSION_NONE) { // Checks if the session is not already active
    session_start(); // Starts a new session
}

// Ensures the user is authenticated before proceeding
if (!isset($_SESSION['user_id'])) { // Checks if user ID is set in session
    $_SESSION['loan_message'] = "Unauthorized access"; // Sets an error message for unauthorized access
    $_SESSION['message_type'] = "error"; // Indicates the error type
    header("Location: /lms/pages/signin.html"); // Redirects to the sign-in page
    exit(); // Exits the script to prevent further execution
}

// Includes the database configuration file
include '../phpconfig/config.php'; // Provides access to database connection settings

// Verifies the database connection
if ($myconn->connect_error) { // Checks if there is a connection error
    $_SESSION['loan_message'] = "Database connection failed"; // Sets a failure message
    $_SESSION['message_type'] = "error"; // Indicates the error type
    header("Location: /lms/pages/customerDashboard.php#applyLoan"); // Redirects to the dashboard
    exit(); // Exits the script
}

// Handles a request to reset filters
if (isset($_GET['reset_filters']) && $_GET['reset_filters'] === 'true') { // Checks if reset_filters is set to 'true'
    unset($_SESSION['filtered_lenders']); // Clears filtered lenders from the session
    unset($_SESSION['current_filters']); // Clears current filters from the session
    unset($_SESSION['filters_applied']); // Clears the applied filters flag
    unset($_SESSION['search_query']); // Clears the search query
    header("Location: customerDashboard.php#applyLoan"); // Redirects back to the dashboard
    exit(); // Exits the script
}

// Initializes an array to store filters
$filters = [
    'loan_types' => [], // Stores loan types
    'interest_ranges' => [], // Stores interest rate ranges
    'lender_name' => '' // Stores lender name filter
];

// Processes the search query if provided
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) { // Checks if a search query is provided
    $_SESSION['search_query'] = $_GET['search_query']; // Stores the search query in the session
} else if (!isset($_GET['reset_filters'])) { // Preserves the search query if reset filters is not requested
    $_SESSION['search_query'] = $_SESSION['search_query'] ?? ''; // Keeps the existing search query
}

// Processes the loan type filter
if (isset($_GET['loan_type']) && is_array($_GET['loan_type'])) { // Checks if loan type filter is provided as an array
    $filters['loan_types'] = array_map(function($type) use ($myconn) { // Escapes each loan type
        return $myconn->real_escape_string($type); // Escapes special characters for SQL
    }, $_GET['loan_type']);
}

// Processes the lender name filter
if (isset($_GET['lender_name']) && !empty($_GET['lender_name'])) { // Checks if a lender name is provided
    $filters['lender_name'] = $myconn->real_escape_string($_GET['lender_name']); // Escapes special characters
}

// Processes the amount range filters
$amountConditions = []; // Initializes an array for amount-related conditions
if (isset($_GET['min_amount']) && is_numeric($_GET['min_amount'])) { // Checks if a minimum amount is specified
    $filters['min_amount'] = max(0, (int)$_GET['min_amount']); // Ensures minimum amount is not negative
    $amountConditions[] = "loan_offers.max_amount >= ?"; // Adds a condition for the minimum amount
}

if (isset($_GET['max_amount']) && is_numeric($_GET['max_amount'])) { // Checks if a maximum amount is specified
    $filters['max_amount'] = (int)$_GET['max_amount']; // Sets the maximum amount
    $amountConditions[] = "loan_offers.max_amount <= ?"; // Adds a condition for the maximum amount
}

// Processes the interest rate filter
if (isset($_GET['interest_range']) && is_array($_GET['interest_range'])) { // Checks if interest range is provided as an array
    $filters['interest_ranges'] = array_filter($_GET['interest_range'], function($range) { // Filters valid ranges
        return in_array($range, ['0-5', '5-10', '10+']); // Accepts only predefined ranges
    });
}

// Builds the base query for fetching loan offers
$query = "SELECT 
            loan_offers.*, 
            lenders.name AS lender_name
          FROM loan_offers
          JOIN lenders ON loan_offers.lender_id = lenders.lender_id"; // Joins lenders with loan offers

// Adds conditions to the query based on filters
$whereConditions = []; // Initializes an array for WHERE clause conditions
$params = []; // Initializes an array for parameters
$paramTypes = ''; // Initializes a string for parameter types

// Adds conditions for the amount range filter
if (!empty($amountConditions)) {
    $whereConditions = array_merge($whereConditions, $amountConditions); // Adds amount conditions
}

// Adds conditions for the loan type filter
if (!empty($filters['loan_types'])) { // Checks if loan types are provided
    $placeholders = implode(',', array_fill(0, count($filters['loan_types']), '?')); // Creates placeholders for loan types
    $whereConditions[] = "loan_offers.loan_type IN ($placeholders)"; // Adds the condition
}

// Adds conditions for the lender name filter
if (!empty($filters['lender_name'])) { // Checks if a lender name is provided
    $whereConditions[] = "lenders.name = ?"; // Adds the condition
}

// Adds conditions for the interest rate filter
if (!empty($filters['interest_ranges'])) { // Checks if interest ranges are provided
    $conditions = []; // Initializes an array for interest rate conditions
    foreach ($filters['interest_ranges'] as $range) { // Iterates through each range
        switch ($range) {
            case '0-5': 
                $conditions[] = "loan_offers.interest_rate BETWEEN 0 AND 5"; 
                break;
            case '5-10': 
                $conditions[] = "loan_offers.interest_rate BETWEEN 5 AND 10"; 
                break;
            case '10+': 
                $conditions[] = "loan_offers.interest_rate > 10"; 
                break;
        }
    }
    $whereConditions[] = "(" . implode(' OR ', $conditions) . ")"; // Adds the conditions with OR
}

// Combines all conditions into a WHERE clause
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(' AND ', $whereConditions); // Adds conditions to the query
}

// Sorts the results
$query .= " ORDER BY loan_offers.offer_id DESC"; // Orders results by offer ID in descending order

// Prepares the query for execution
$stmt = $myconn->prepare($query); // Prepares the query
if (!$stmt) { // Checks if query preparation failed
    $_SESSION['loan_message'] = "Failed to prepare query: " . $myconn->error; // Sets an error message
    $_SESSION['message_type'] = "error"; // Indicates the error type
    header("Location: customerDashboard.php#applyLoan"); // Redirects to the dashboard
    exit(); // Exits the script
}

// Binds parameters to the prepared statement
$params = []; // Resets the parameters array
$paramTypes = ''; // Resets the parameter types string

// Adds parameters for amount range filters
if (isset($filters['min_amount'])) {
    $params[] = $filters['min_amount']; // Adds the minimum amount
    $paramTypes .= 'i'; // Adds the parameter type
}

if (isset($filters['max_amount'])) {
    $params[] = $filters['max_amount']; // Adds the maximum amount
    $paramTypes .= 'i'; // Adds the parameter type
}

// Adds parameters for loan types
if (!empty($filters['loan_types'])) {
    $paramTypes .= str_repeat('s', count($filters['loan_types'])); // Appends types for each loan type
    $params = array_merge($params, $filters['loan_types']); // Merges loan types into parameters
}

// Adds the lender name parameter
if (!empty($filters['lender_name'])) {
    $paramTypes .= 's'; // Adds the type for the lender name
    $params[] = $filters['lender_name']; // Adds the lender name
}

// Binds the parameters to the prepared statement
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params); // Binds parameters using the types string
}

// Executes the query
if (!$stmt->execute()) { // Checks if query execution failed
    $_SESSION['loan_message'] = "Failed to execute query: " . $stmt->error; // Sets an error message
    $_SESSION['message_type'] = "error"; // Indicates the error type
    header("Location: customerDashboard.php#applyLoan"); // Redirects to the dashboard
    exit(); // Exits the script
}

// Fetches the results
$result = $stmt->get_result(); // Gets the result set
$lenders = $result->fetch_all(MYSQLI_ASSOC); // Fetches all rows as an associative array

// Clears previously filtered lenders from the session
unset($_SESSION['filtered_lenders']); // Removes old filtered lenders

// Processes and stores the fetched lenders
if (!empty($lenders)) { // Checks if there are any lenders
    $_SESSION['filtered_lenders'] = array_map(function($lender) { // Maps lenders into a structured format
        return [
            'offer_id' => (int)$lender['offer_id'], // Offer ID
            'lender_id' => (int)$lender['lender_id'], // Lender ID
            'name' => htmlspecialchars($lender['lender_name']), // Escaped lender name
            'type' => htmlspecialchars($lender['loan_type']), // Escaped loan type
            'rate' => $lender['interest_rate'], // Interest rate
            'duration' => (int)$lender['max_duration'], // Maximum duration
            'amount' => (int)$lender['max_amount'] // Maximum amount
        ];
    }, $lenders);
}

// Stores the filter state in the session if filters are applied
if (!empty($_GET['loan_type']) || isset($_GET['min_amount']) || 
    isset($_GET['max_amount']) || !empty($_GET['interest_range']) || !empty($_GET['lender_name'])) {
    $_SESSION['current_filters'] = $filters; // Saves current filters
    $_SESSION['filters_applied'] = true; // Flags that filters are applied
}

// Closes the statement and connection
$stmt->close(); // Frees the prepared statement
$myconn->close(); // Closes the database connection

// Redirects back to the loan application page
header("Location: customerDashboard.php#applyLoan"); // Redirects to the dashboard
exit(); // Exits the script
?>
