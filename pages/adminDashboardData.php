<?php
// Checks if a session is not already active before starting a new one
if (session_status() !== PHP_SESSION_ACTIVE) { // session_status() returns the current session status (none, active, disabled)
    session_start(); // Starts a new session or resumes the existing one
}

// Enables error reporting for debugging purposes during development
error_reporting(E_ALL); // error_reporting() sets which PHP errors are reported; E_ALL includes all errors and warnings
ini_set('display_errors', 1); // ini_set() sets the value of a configuration option; displays errors on the page
ini_set('display_startup_errors', 1); // Enables display of errors during PHP startup

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

// Counts the total number of users in the database
$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users"; // SQL query to count all users
$totalUsersResult = mysqli_query($myconn, $totalUsersQuery); // Executes the query
$totalUsersCount = 0; // Initializes the total users count
if ($totalUsersResult && mysqli_num_rows($totalUsersResult) > 0) { // Checks if the query was successful
    $countData = mysqli_fetch_assoc($totalUsersResult); // Fetches the result as an associative array
    $totalUsersCount = $countData['total_users']; // Stores the total user count
}

// Counts the number of active users (status = 'active')
$activeUsersQuery = "SELECT COUNT(*) as active_users FROM users where status='active'"; // SQL query to count active users
$activeUsersResult = mysqli_query($myconn, $activeUsersQuery); // Executes the query
$activeUsersCount = 0; // Initializes the active users count
if ($activeUsersResult && mysqli_num_rows($activeUsersResult) > 0) { // Checks if the query was successful
    $countData = mysqli_fetch_assoc($activeUsersResult); // Fetches the result
    $activeUsersCount = $countData['active_users']; // Stores the active users count
}

// Counts the number of blocked users (status = 'inactive')
$blockedUsersQuery = "SELECT COUNT(*) as blocked_users FROM users WHERE status='inactive'"; // SQL query to count inactive users
$blockedUsersResult = mysqli_query($myconn, $blockedUsersQuery); // Executes the query
$blockedUsersCount = 0; // Initializes the blocked users count
if ($blockedUsersResult && mysqli_num_rows($blockedUsersResult) > 0) { // Checks if the query was successful
    $countData = mysqli_fetch_assoc($blockedUsersResult); // Fetches the result
    $blockedUsersCount = $countData['blocked_users']; // Stores the blocked users count
}

// Counts the total number of customers
$customersQuery = "SELECT COUNT(*) as total_customers FROM customers"; // SQL query to count all customers
$customersResult = mysqli_query($myconn, $customersQuery); // Executes the query
$totalCustomers = 0; // Initializes the total customers count
if ($customersResult && mysqli_num_rows($customersResult) > 0) { // Checks if the query was successful
    $countData = mysqli_fetch_assoc($customersResult); // Fetches the result
    $totalCustomers = $countData['total_customers']; // Stores the total customers count
}

// Counts the total number of lenders
$lendersQuery = "SELECT COUNT(*) as total_lenders FROM lenders"; // SQL query to count all lenders
$lendersResult = mysqli_query($myconn, $lendersQuery); // Executes the query
$totalLenders = 0; // Initializes the total lenders count
if ($lendersResult && mysqli_num_rows($lendersResult) > 0) { // Checks if the query was successful
    $countData = mysqli_fetch_assoc($lendersResult); // Fetches the result
    $totalLenders = $countData['total_lenders']; // Stores the total lenders count
}

// Counts the total number of admins
$adminsQuery = "SELECT COUNT(*) as total_admins FROM users WHERE role='Admin'"; // SQL query to count users with Admin role
$adminsResult = mysqli_query($myconn, $adminsQuery); // Executes the query
$totalAdmins = 0; // Initializes the total admins count
if ($adminsResult && mysqli_num_rows($adminsResult) > 0) { // Checks if the query was successful
    $countData = mysqli_fetch_assoc($adminsResult); // Fetches the result
    $totalAdmins = $countData['total_admins']; // Stores the total admins count
}

// Fetches total loan applications with 'submitted' status (admin view )
$pendingLoansQuery = "SELECT COUNT(*) FROM loans WHERE status = 'submitted'"; // Counts all submitted loans system-wide
$pendingLoansResult = mysqli_query($myconn, $pendingLoansQuery); // Executes the query
$pendingLoansRow = mysqli_fetch_row($pendingLoansResult); // Fetches result as numeric array (index 0 holds count)
$pendingLoans = isset($pendingLoansRow[0]) ? (int)$pendingLoansRow[0] : 0; // Safely converts count to integer (0 if empty)

// Retrieves filter parameters for the View Users section from the URL
$roleFilter = isset($_GET['role']) ? $_GET['role'] : ''; // Gets role filter or defaults to empty string
$statusFilter = isset($_GET['status']) ? $_GET['status'] : ''; // Gets status filter or defaults to empty string

// Builds the base SQL query to fetch user details with role and status
$usersQuery = "SELECT 
               users.user_id, 
               users.user_name, 
               users.email, 
               users.phone, 
               users.role,
               CASE 
                   WHEN users.role = 'Customer' THEN customers.status
                   WHEN users.role = 'Lender' THEN lenders.status
                   ELSE 'active'
               END as status
               FROM users
               LEFT JOIN customers ON users.user_id = customers.user_id AND users.role = 'Customer'
               LEFT JOIN lenders ON users.user_id = lenders.user_id AND users.role = 'Lender'"; // Base query with LEFT JOINs to get user status

// Initializes an array to store WHERE conditions
$whereConditions = []; // Empty array to collect filter conditions

// Applies role filter if provided and valid
if (!empty($roleFilter) && in_array($roleFilter, ['Admin', 'Lender', 'Customer'])) { // in_array() checks if the role is valid
    $whereConditions[] = "users.role = '$roleFilter'"; // Adds role filter to conditions
}

// Applies status filter if provided
if (!empty($statusFilter)) { // Checks if status filter is not empty
    if ($statusFilter === 'active') {
        $whereConditions[] = "(customers.status = 'active' OR lenders.status = 'active' OR users.role = 'Admin')"; // Filters for active users
    } elseif ($statusFilter === 'restricted') {
        $whereConditions[] = "(customers.status LIKE '%restricted%' OR lenders.status LIKE '%restricted%')"; // Filters for restricted users
    } elseif ($statusFilter === 'blocked') {
        $whereConditions[] = "(customers.status = 'inactive' OR lenders.status = 'inactive')"; // Filters for blocked users
    }
}

// Combines WHERE conditions into the query if any exist
if (!empty($whereConditions)) { // Checks if there are any conditions
    $usersQuery .= " WHERE " . implode(' AND ', $whereConditions); // Joins conditions with AND
}

// Adds sorting to the query
$usersQuery .= " ORDER BY users.user_id DESC"; // Orders results by user_id in descending order

// Executes the users query
$usersResult = mysqli_query($myconn, $usersQuery); // Runs the query

// Initializes an array to store user data
$users = []; // Empty array for user records
if ($usersResult && mysqli_num_rows($usersResult) > 0) { // Checks if the query was successful and has results
    while ($row = mysqli_fetch_assoc($usersResult)) { // Loops through each result row
        $users[] = $row; // Adds each user record to the array
    }
}

// Retrieves filter parameters for activity logs
$activityFilter = isset($_GET['activity_type']) ? $_GET['activity_type'] : ''; // Gets activity type filter or defaults to empty
$dateFilter = isset($_GET['date_range']) ? $_GET['date_range'] : ''; // Gets date range filter or defaults to empty

// Builds the base query to fetch activity logs
$activityQuery = "SELECT 
    activity.log_id, 
    users.user_name, 
    users.email,
    activity.activity, 
    activity.activity_time, 
    activity.activity_type
FROM activity
JOIN users ON activity.user_id = users.user_id"; // Base query with JOIN to get user details

// Initializes an array for activity log WHERE conditions
$whereConditions = []; // Empty array for filter conditions

// Applies activity type filter if provided
if (!empty($activityFilter)) { // Checks if activity type filter is not empty
    $whereConditions[] = "activity.activity_type = '$activityFilter'"; // Adds activity type filter
}

// Applies date range filter if provided
if (!empty($dateFilter)) { // Checks if date range filter is not empty
    switch ($dateFilter) { // switch() selects code based on the date range value
        case 'today':
            $today = date('Y-m-d'); // Gets today's date
            $whereConditions[] = "DATE(activity_time) = '$today'"; // Filters for today's activities
            break;
        case 'week':
            $weekStart = date('Y-m-d', strtotime('monday this week')); // Gets week's start date
            $weekEnd = date('Y-m-d', strtotime('sunday this week')); // Gets week's end date
            $whereConditions[] = "DATE(activity_time) BETWEEN '$weekStart' AND '$weekEnd'"; // Filters for this week
            break;
        case 'month':
            $monthStart = date('Y-m-01'); // Gets month's start date
            $monthEnd = date('Y-m-t'); // Gets month's end date
            $whereConditions[] = "DATE(activity_time) BETWEEN '$monthStart' AND '$monthEnd'"; // Filters for this month
            break;
        case 'year':
            $year = date('Y'); // Gets current year
            $whereConditions[] = "YEAR(activity_time) = '$year'"; // Filters for this year
            break;
    }
}

// Combines activity log WHERE conditions if any exist
if (!empty($whereConditions)) { // Checks if there are any conditions
    $activityQuery .= " WHERE " . implode(' AND ', $whereConditions); // Joins conditions with AND
}

// Adds sorting to the activity query
$activityQuery .= " ORDER BY activity.activity_time DESC"; // Orders results by activity time, newest first

// Executes the activity logs query
$activityResult = mysqli_query($myconn, $activityQuery); // Runs the query

// Initializes an array to store activity logs
$activityLogs = []; // Empty array for activity records
if ($activityResult && mysqli_num_rows($activityResult) > 0) { // Checks if the query was successful
    while ($row = mysqli_fetch_assoc($activityResult)) { // Loops through each result row
        $activityLogs[] = $row; // Adds each activity record to the array
    }
}

// Fetches recent activity logs for the dashboard
$recentActivityQuery = "SELECT 
    activity.log_id, 
    users.email,
    activity.activity_time, 
    activity.activity_type
FROM activity
JOIN users ON activity.user_id = users.user_id
ORDER BY activity.activity_time DESC
LIMIT 10"; // Query to fetch the 10 most recent activity logs
$recentActivityResult = mysqli_query($myconn, $recentActivityQuery); // Executes the query

// Initializes an array for recent activity logs
$recentActivityLogs = []; // Empty array for recent activity records
if ($recentActivityResult && mysqli_num_rows($recentActivityResult) > 0) { // Checks if the query was successful
    while ($row = mysqli_fetch_assoc($recentActivityResult)) { // Loops through each result row
        $recentActivityLogs[] = $row; // Adds each recent activity record to the array
    }
}

// Fetches role distribution for pie chart
$roleQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role"; // Query to count users by role
$roleResult = mysqli_query($myconn, $roleQuery); // Executes the query

// Initializes arrays for role data and total users
$roleData = []; // Empty array for role counts
$totalUsers = 0; // Initializes total users count
while ($row = mysqli_fetch_assoc($roleResult)) { // Loops through result rows
    $roleData[$row['role']] = (int)$row['count']; // Stores count for each role
    $totalUsers += (int)$row['count']; // Adds to total users count
}

// Calculates percentages for pie chart data
$pieData = [
    'Admin' => $totalUsers > 0 && isset($roleData['Admin']) ? ($roleData['Admin'] / $totalUsers * 100) : 0, // Calculates Admin percentage
    'Customer' => $totalUsers > 0 && isset($roleData['Customer']) ? ($roleData['Customer'] / $totalUsers * 100) : 0, // Calculates Customer percentage
    'Lender' => $totalUsers > 0 && isset($roleData['Lender']) ? ($roleData['Lender'] / $totalUsers * 100) : 0 // Calculates Lender percentage
];

// Fetches admin profile data
$adminProfileQuery = "SELECT * FROM users WHERE user_id = '$userId'"; // Query to fetch admin user details
$adminProfileResult = mysqli_query($myconn, $adminProfileQuery); // Executes the query
$adminProfile = mysqli_fetch_assoc($adminProfileResult); // Fetches the admin profile as an associative array

?>