<?php
// Checks if a session is not already active before starting a new one
if (session_status() === PHP_SESSION_NONE) { // session_status() returns the current session status; PHP_SESSION_NONE indicates no active session
    session_start(); // Starts a new session or resumes an existing one
}

// Verifies if the user is logged in by checking for 'user_id' in the session
if (!isset($_SESSION['user_id'])) { // isset() checks if a variable is set and not null
    header("Location: signin.html"); // header() sends a raw HTTP header to redirect to the sign-in page
    exit(); // exit() terminates script execution immediately
}

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // include brings in the specified file for database connectivity

// Stores the user ID from the session for use in queries
$userId = $_SESSION['user_id']; // $_SESSION is a superglobal array for session variables; $userId holds the current user's ID

// Fetches the user's role from the users table using a prepared statement for security
$query = "SELECT role FROM users WHERE user_id = ?"; // SQL query with a placeholder for user_id
$stmt = mysqli_prepare($myconn, $query); // mysqli_prepare() creates a prepared statement object
mysqli_stmt_bind_param($stmt, "i", $userId); // mysqli_stmt_bind_param() binds the user ID as an integer
mysqli_stmt_execute($stmt); // mysqli_stmt_execute() runs the prepared statement
$result = mysqli_stmt_get_result($stmt); // mysqli_stmt_get_result() fetches the result set

// Checks if the user exists and processes their role and status
if ($result && mysqli_num_rows($result) > 0) { // mysqli_num_rows() returns the number of rows in the result set
    $user = mysqli_fetch_assoc($result); // mysqli_fetch_assoc() fetches a result row as an associative array
    $role = $user['role']; // Stores the user's role (e.g., Admin, Lender, Customer)
    
    // Determines the status query based on the user's role
    if ($role === 'Lender') {
        $statusQuery = "SELECT status FROM lenders WHERE user_id = ?"; // Query to fetch lender status
        $restrictedValue = 'restricted_create'; // Sets restriction value for lenders
    } elseif ($role === 'Customer') {
        $statusQuery = "SELECT status FROM customers WHERE user_id = ?"; // Query to fetch customer status
        $restrictedValue = 'restricted_apply'; // Sets restriction value for customers
    } else {
        // Admin or other roles are assumed to have no restrictions
        $status = 'active'; // Sets default status for non-Lender/Customer roles
    }
    
    // Fetches status for Lender or Customer roles using a prepared statement
    if (isset($statusQuery)) { // Checks if a status query was defined
        $stmt = mysqli_prepare($myconn, $statusQuery); // Prepares the status query
        mysqli_stmt_bind_param($stmt, "i", $userId); // Binds the user ID as an integer
        mysqli_stmt_execute($stmt); // Executes the statement
        $statusResult = mysqli_stmt_get_result($stmt); // Gets the result set
        $statusRow = mysqli_fetch_assoc($statusResult); // Fetches the status row
        $status = $statusRow['status'] ?? 'inactive'; // Sets status or defaults to 'inactive' using null coalescing
    }

    // Checks if the user is blocked and terminates their session if so
    if ($status === 'inactive') { // Compares status to 'inactive'
        session_destroy(); // session_destroy() ends the current session and clears all session data
        header("Location: blocked.html"); // Redirects to the blocked page
        exit(); // Stops script execution
    }
    
    // Retrieves current URL and page information for access control
    $currentUrl = $_SERVER['REQUEST_URI']; // $_SERVER['REQUEST_URI'] contains the current URL path and query string
    $currentPage = basename($_SERVER['PHP_SELF']); // basename() extracts the filename from $_SERVER['PHP_SELF']
    
    // Handles restrictions for Lenders
    if ($role === 'Lender' && $status === 'restricted_create') { // Checks if the user is a restricted Lender
        $isCreateLoanPage = (strpos($currentPage, 'createLoan.php') !== false); // strpos() checks if the page is createLoan.php
        $isCreateLoanAnchor = ($currentPage === 'lenderDashboard.php' && 
                             (isset($_GET['createLoan']) || strpos($currentUrl, '#createLoan') !== false)); // Checks for createLoan action or anchor
        
        if ($isCreateLoanPage || $isCreateLoanAnchor) { // If attempting to access restricted functionality
            header("Location: restricted.php"); // Redirects to the restricted page
            exit(); // Stops script execution
        }
    }
    
    // Handles restrictions for Customers
    if ($role === 'Customer' && $status === 'restricted_apply') { // Checks if the user is a restricted Customer
        $isApplyLoanPage = (strpos($currentPage, 'applyLoan.php') !== false); // Checks if the page is applyLoan.php
        $isApplyLoanAnchor = ($currentPage === 'customerDashboard.php' && 
                             (isset($_GET['applyLoan']) || strpos($currentUrl, '#applyLoan') !== false)); // Checks for applyLoan action or anchor
        
        if ($isApplyLoanPage || $isApplyLoanAnchor) { // If attempting to access restricted functionality
            header("Location: restricted.php"); // Redirects to the restricted page
            exit(); // Stops script execution
        }
    }
    
    // Enforces role-based page access control
    if ($role === 'Customer' && $currentPage === 'lenderDashboard.php') { // Prevents Customers from accessing lender dashboard
        header("Location: unauthorized.php"); // Redirects to the unauthorized page
        exit(); // Stops script execution
    }
    
    if ($role === 'Lender' && $currentPage === 'customerDashboard.php') { // Prevents Lenders from accessing customer dashboard
        header("Location: unauthorized.php"); // Redirects to the unauthorized page
        exit(); // Stops script execution
    }
} else {
    // Handles case where user is not found in the database
    session_destroy(); // Ends the session and clears all session data
    header("Location: signin.html"); // Redirects to the sign-in page
    exit(); // Stops script execution
}

?>