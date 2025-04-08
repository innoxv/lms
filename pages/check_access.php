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

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user role
$userId = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($myconn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $role = $user['role'];
    
    // Check status based on role
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
    

    // These other restricions are not working YET!!

    // Check restrictions based on current page and role
    $currentPage = basename($_SERVER['PHP_SELF']);
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // For lenders trying to access create loan section
    if ($role === 'Lender') {
        // Check if accessing the create loan section directly
        if (strpos($requestUri, 'createLoan.php') !== false) {
            if ($status === 'restricted_create') {
                header("Location: restricted.php");
                exit();
            }
        }
        
        // Check if accessing the create loan section via dashboard
        if ($currentPage === 'lenderDashboard.php' && 
            (isset($_GET['createLoan']) || strpos($requestUri, '#createLoan') !== false)) {
            if ($status === 'restricted_create') {
                header("Location: restricted.php");
                exit();
            }
        }
    }
    
    // For customers trying to access apply loan section
    if ($role === 'Customer') {
        if (strpos($requestUri, 'applyLoan.php') !== false) {
            if ($status === 'restricted_apply') {
                header("Location: restricted.php");
                exit();
            }
        }
        
        if ($currentPage === 'customerDashboard.php' && 
            (isset($_GET['applyLoan']) || strpos($requestUri, '#applyLoan') !== false)) {
            if ($status === 'restricted_apply') {
                header("Location: restricted.php");
                exit();
            }
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
}

mysqli_close($myconn);
?>