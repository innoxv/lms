<?php
session_start();
require_once 'check_access.php'; // To verify user is logged in

// Get user role and restriction reason
$role = $_SESSION['role'] ?? '';
$message = ($role === 'Lender') 
    ? "Your account has restrictions on creating new loan offers." 
    : "You don't have permission to access this feature.";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Access Restricted</title>
</head>
<body>
    <h1>Access Restricted</h1>
    <p><?php echo htmlspecialchars($message); ?></p>
    <a href="<?php echo ($role === 'Lender') ? 'lenderDashboard.php' : 'customerDashboard.php'; ?>">
        Return to Dashboard
    </a>
</body>
</html>