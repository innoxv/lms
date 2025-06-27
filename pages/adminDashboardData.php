<?php
// Start the session
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);



// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: signin.html");
    exit();
}

// Database config file
include '../phpconfig/config.php';

// Fetch current user data from the database
$userId = $_SESSION['user_id'];  

$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name'];
} else {
    $_SESSION['user_name'] = "Guest";
}

// Count total users 
$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users";
$totalUsersResult = mysqli_query($myconn, $totalUsersQuery);
$totalUsersCount = 0;
if ($totalUsersResult && mysqli_num_rows($totalUsersResult) > 0) {
    $countData = mysqli_fetch_assoc($totalUsersResult);
    $totalUsersCount = $countData['total_users'];
}
// Count active users 
$activeUsersQuery = "SELECT COUNT(*) as active_users FROM users where status='active'";
$activeUsersResult = mysqli_query($myconn, $activeUsersQuery);
$activeUsersCount = 0;
if ($activeUsersResult && mysqli_num_rows($activeUsersResult) > 0) {
    $countData = mysqli_fetch_assoc($activeUsersResult);
    $activeUsersCount = $countData['active_users'];
}
// Count blocked users 
$blockedUsersQuery = "SELECT COUNT(*) as blocked_users FROM users WHERE status='inactive'";
$blockedUsersResult = mysqli_query($myconn, $blockedUsersQuery);
$blockedUsersCount = 0;
if ($blockedUsersResult && mysqli_num_rows($blockedUsersResult) > 0) {
    $countData = mysqli_fetch_assoc($blockedUsersResult);
    $blockedUsersCount = $countData['blocked_users'];
}
// Count total customers
$customersQuery = "SELECT COUNT(*) as total_customers FROM customers";
$customersResult = mysqli_query($myconn, $customersQuery);
$totalCustomers = 0;
if ($customersResult && mysqli_num_rows($customersResult) > 0) {
    $countData = mysqli_fetch_assoc($customersResult);
    $totalCustomers = $countData['total_customers'];
}
// Count total lenders
$lendersQuery = "SELECT COUNT(*) as total_lenders FROM lenders";
$lendersResult = mysqli_query($myconn, $lendersQuery);
$totalLenders = 0;
if ($lendersResult && mysqli_num_rows($lendersResult) > 0) {
    $countData = mysqli_fetch_assoc($lendersResult);
    $totalLenders = $countData['total_lenders'];
}

// Count total admins
$adminsQuery = "SELECT COUNT(*) as total_admins FROM users WHERE role='Admin'";
$adminsResult = mysqli_query($myconn, $adminsQuery);
$totalAdmins = 0;
if ($adminsResult && mysqli_num_rows($adminsResult) > 0) {
    $countData = mysqli_fetch_assoc($adminsResult);
    $totalAdmins = $countData['total_admins'];
}


// Fetch all users from the database for the View Users section
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';    // role
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';  // status

// Base query
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
               LEFT JOIN lenders ON users.user_id = lenders.user_id AND users.role = 'Lender'";

// Build WHERE conditions
$whereConditions = [];

// Role filter
if (!empty($roleFilter) && in_array($roleFilter, ['Admin', 'Lender', 'Customer'])) {
    $whereConditions[] = "users.role = '$roleFilter'";
}

// Status filter
if (!empty($statusFilter)) {
    if ($statusFilter === 'active') {
        $whereConditions[] = "(customers.status = 'active' OR lenders.status = 'active' OR users.role = 'Admin')";
    } 
    elseif ($statusFilter === 'restricted') {
        $whereConditions[] = "(customers.status LIKE '%restricted%' OR lenders.status LIKE '%restricted%')";
    } 
    elseif ($statusFilter === 'blocked') {
        $whereConditions[] = "(customers.status = 'inactive' OR lenders.status = 'inactive')";
    }
}

// Combine WHERE conditions
if (!empty($whereConditions)) {
    $usersQuery .= " WHERE " . implode(' AND ', $whereConditions);
}

// Add sorting in descending order based on the user_id
$usersQuery .= " ORDER BY users.user_id DESC";

$usersResult = mysqli_query($myconn, $usersQuery);


// Initialize users array
$users = [];
if ($usersResult && mysqli_num_rows($usersResult) > 0) {
    while ($row = mysqli_fetch_assoc($usersResult)) {
        $users[] = $row;
    }
}


// Fetch activity logs 
// Get activity type filter
$activityFilter = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';
$dateFilter = isset($_GET['date_range']) ? $_GET['date_range'] : '';

// Activity log query
$activityQuery = "SELECT 
    activity.log_id, 
    users.user_name, 
    users.email,
    activity.activity, 
    activity.activity_time, 
    activity.activity_type
FROM activity
JOIN users ON activity.user_id = users.user_id";

// Build WHERE conditions
$whereConditions = [];

// Activity type filter
if (!empty($activityFilter)) {
    $whereConditions[] = "activity.activity_type = '$activityFilter'";
}

// Date range filter
if (!empty($dateFilter)) {
    switch ($dateFilter) {
        case 'today':
            $today = date('Y-m-d');
            $whereConditions[] = "DATE(activity_time) = '$today'";
            break;
        case 'week':
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            $whereConditions[] = "DATE(activity_time) BETWEEN '$weekStart' AND '$weekEnd'";
            break;
        case 'month':
            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-t');
            $whereConditions[] = "DATE(activity_time) BETWEEN '$monthStart' AND '$monthEnd'";
            break;
        case 'year':
            $year = date('Y');
            $whereConditions[] = "YEAR(activity_time) = '$year'";
            break;
    }
}

// Combine WHERE conditions if any exist
if (!empty($whereConditions)) {
    $activityQuery .= " WHERE " . implode(' AND ', $whereConditions);
}

$activityQuery .= " ORDER BY activity.activity_time DESC";

$activityResult = mysqli_query($myconn, $activityQuery);

// Initialize activity logs array
$activityLogs = [];
if ($activityResult && mysqli_num_rows($activityResult) > 0) {
    while ($row = mysqli_fetch_assoc($activityResult)) {
        $activityLogs[] = $row;
    }
}


// Recent Activity Logs - for the Dashboard

$recentActivityQuery = "SELECT 
    activity.log_id, 
    users.email,
    activity.activity_time, 
    activity.activity_type
FROM activity
JOIN users ON activity.user_id = users.user_id
ORDER BY activity.activity_time DESC
LIMIT 10"; // Limit to 10 most recent logs

$recentActivityResult = mysqli_query($myconn, $recentActivityQuery);

// Initialize activity logs array
$recentActivityLogs = [];
if ($recentActivityResult && mysqli_num_rows($recentActivityResult) > 0) {
    while ($row = mysqli_fetch_assoc($recentActivityResult)) {
        $recentActivityLogs[] = $row;
    }
}


// Pie chart data
$roleQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$roleResult = mysqli_query($myconn, $roleQuery);

$roleData = [];
$totalUsers = 0;
while ($row = mysqli_fetch_assoc($roleResult)) {
    $roleData[$row['role']] = (int)$row['count'];
    $totalUsers += (int)$row['count'];
}

// Calculate percentages for pie chart
$pieData = [
    'Admin' => isset($roleData['Admin']) ? ($roleData['Admin'] / $totalUsers * 100) : 0,
    'Customer' => isset($roleData['Customer']) ? ($roleData['Customer'] / $totalUsers * 100) : 0,
    'Lender' => isset($roleData['Lender']) ? ($roleData['Lender'] / $totalUsers * 100) : 0
];



// Fetch admin profile data
$adminProfileQuery = "SELECT * FROM users WHERE user_id = '$userId'";
$adminProfileResult = mysqli_query($myconn, $adminProfileQuery);
$adminProfile = mysqli_fetch_assoc($adminProfileResult);

?>