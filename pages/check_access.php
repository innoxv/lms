<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

// Database config file
include '../phpconfig/config.php';

// Get user role and status
$userId = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($myconn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $role = $user['role'];
    
    // Determine status query based on role
    if ($role === 'Lender') {
        $statusQuery = "SELECT status FROM lenders WHERE user_id = ?";
        $restrictedValue = 'restricted_create';
    } elseif ($role === 'Customer') {
        $statusQuery = "SELECT status FROM customers WHERE user_id = ?";
        $restrictedValue = 'restricted_apply';
    } else {
        // Admin or other roles - no restrictions
        $status = 'active';
    }
    
    if (isset($statusQuery)) {
        $stmt = mysqli_prepare($myconn, $statusQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $statusResult = mysqli_stmt_get_result($stmt);
        $statusRow = mysqli_fetch_assoc($statusResult);
        $status = $statusRow['status'] ?? 'inactive';
    }

    // Check if user is blocked
    if ($status === 'inactive') {
        session_destroy();
        header("Location: blocked.html");
        exit();
    }
    
    // Restrictions Functionality still has issues

    // Get current URL information
    $currentUrl = $_SERVER['REQUEST_URI'];
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // Handle lender restrictions
    if ($role === 'Lender' && $status === 'restricted_create') {
        $isCreateLoanPage = (strpos($currentPage, 'createLoan.php') !== false);
        $isCreateLoanAnchor = ($currentPage === 'lenderDashboard.php' && 
                             (isset($_GET['createLoan']) || strpos($currentUrl, '#createLoan') !== false));
        
        if ($isCreateLoanPage || $isCreateLoanAnchor) {
            header("Location: restricted.php");
            exit();
        }
    }
    
    // Handle customer restrictions
    if ($role === 'Customer' && $status === 'restricted_apply') {
        $isApplyLoanPage = (strpos($currentPage, 'applyLoan.php') !== false);
        $isApplyLoanAnchor = ($currentPage === 'customerDashboard.php' && 
                             (isset($_GET['applyLoan']) || strpos($currentUrl, '#applyLoan') !== false));
        
        if ($isApplyLoanPage || $isApplyLoanAnchor) {
            header("Location: restricted.php");
            exit();
        }
    }
    
    // Role-based page access control
    if ($role === 'Customer' && $currentPage === 'lenderDashboard.php') {
        header("Location: unauthorized.php");
        exit();
    }
    
    if ($role === 'Lender' && $currentPage === 'customerDashboard.php') {
        header("Location: unauthorized.php");
        exit();
    }
} else {
    // User not found in database
    session_destroy();
    header("Location: signin.html");
    exit();
}

// mysqli_close($myconn);
?>