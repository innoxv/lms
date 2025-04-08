<?php
session_start();

// Enable error reporting
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

$userId = $_POST['user_id'] ?? 0;
$action = $_POST['action'] ?? '';
$newStatus = $_POST['new_status'] ?? '';
$restrictionType = $_POST['restriction_type'] ?? '';
$role = $_POST['role'] ?? '';

if (empty($userId) || empty($action)) {
    $_SESSION['admin_message'] = "Invalid request";
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers");

    exit();
}

// Check if user exists and get role
$checkUser = mysqli_query($myconn, "SELECT role FROM users WHERE user_id = '$userId'");
if (mysqli_num_rows($checkUser) === 0) {
    $_SESSION['admin_message'] = "User not found";
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers");

    exit();
}

$userData = mysqli_fetch_assoc($checkUser);
$role = $userData['role'];

// Get current status from the appropriate table
if ($role === 'Customer') {
    $table = 'customers';
    $idField = 'customer_id';
} elseif ($role === 'Lender') {
    $table = 'lenders';
    $idField = 'lender_id';
} else {
    // Admin or other roles - no restrictions
    $_SESSION['admin_message'] = "Cannot modify admin permissions";
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers");

    exit();
}

// Get user's ID in their role table
$roleIdQuery = mysqli_query($myconn, "SELECT $idField, status FROM $table WHERE user_id = '$userId'");
if (mysqli_num_rows($roleIdQuery) === 0) {
    $_SESSION['admin_message'] = "User role record not found";
    header("Location: adminDashboard.php?role=$roleFilter#viewUsers");

    exit();
}

$roleData = mysqli_fetch_assoc($roleIdQuery);
$currentStatus = $roleData['status'];
$roleId = $roleData[$idField];

// Updating status to users, lenders (additional stats - restricted_create) and customers( additional stats -restricted_apply)
switch ($action) {
    case 'toggle_status':
        if ($newStatus === 'inactive') {
            $statusToSet = 'inactive';
        } elseif ($newStatus === 'active') {
            $statusToSet = 'active';
        } else {
            $_SESSION['admin_message'] = "Invalid status";
            header("Location: adminDashboard.php?role=$roleFilter#viewUsers");
            exit();
        }

        // Update users table
        $query = "UPDATE users SET status = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($myconn, $query);
        mysqli_stmt_bind_param($stmt, "si", $statusToSet, $userId);
        mysqli_stmt_execute($stmt);

        // Update role-specific table
        if ($role === 'Customer') {
            $table = 'customers';
            $idField = 'customer_id';
        } elseif ($role === 'Lender') {
            $table = 'lenders';
            $idField = 'lender_id';
        } else {
            // Admin or other roles - no restrictions
            $_SESSION['admin_message'] = "Cannot modify admin permissions";
            header("Location: adminDashboard.php?role=$roleFilter#viewUsers");
            exit();
        }

        $roleIdQuery = mysqli_query($myconn, "SELECT $idField FROM $table WHERE user_id = '$userId'");
        if (mysqli_num_rows($roleIdQuery) === 0) {
            $_SESSION['admin_message'] = "User role record not found";
            header("Location: adminDashboard.php?role=$roleFilter#viewUsers");
            exit();
        }

        $roleData = mysqli_fetch_assoc($roleIdQuery);
        $roleId = $roleData[$idField];

        $query = "UPDATE $table SET status = ? WHERE $idField = ?";
        $stmt = mysqli_prepare($myconn, $query);
        mysqli_stmt_bind_param($stmt, "si", $statusToSet, $roleId);
        mysqli_stmt_execute($stmt);

        $_SESSION['admin_message'] = ($statusToSet === 'active') ? "User is Unblocked" : "User is Blocked";
        break;

    case 'toggle_restriction':
        if ($role === 'Customer' && $restrictionType === 'apply_loan') {
            $newStatus = ($currentStatus === 'restricted_apply') ? 'active' : 'restricted_apply';
            $query = "UPDATE customers SET status = ? WHERE customer_id = ?";
            $stmt = mysqli_prepare($myconn, $query);
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $roleId);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['admin_message'] = ($newStatus === 'active') 
                ? 'User can now apply for loans' 
                : 'User restricted from applying for loans';
        } 
        elseif ($role === 'Lender' && $restrictionType === 'create_loan') {
            $newStatus = ($currentStatus === 'restricted_create') ? 'active' : 'restricted_create';
            $query = "UPDATE lenders SET status = ? WHERE lender_id = ?";
            $stmt = mysqli_prepare($myconn, $query);
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $roleId);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['admin_message'] = ($newStatus === 'active') 
                ? 'User can now create loans' 
                : 'User restricted from creating loans';
        } 
        else {
            $_SESSION['admin_message'] = 'Invalid restriction type for this role';
        }
        break;

    default:
        $_SESSION['admin_message'] = 'Invalid action';
}

mysqli_close($myconn);
header("Location: adminDashboard.php?role=$roleFilter#viewUsers");

exit();
?>