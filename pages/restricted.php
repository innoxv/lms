<?php
// Starts the session to access session variables
session_start();

// Includes the access check script to verify the user is logged in and has proper permissions
require_once 'check_access.php'; // Ensures the user is authenticated and authorized

// Retrieves the user's role from the session or sets it to an empty string if not set
$role = $_SESSION['role'] ?? ''; // Uses the null coalescing operator to avoid undefined index errors

// Sets the restriction message based on the user's role
$message = ($role === 'Lender') 
    ? "Your account has restrictions on creating new loan offers." // Message for users with the 'Lender' role
    : "You don't have permission to access this feature."; // Message for other roles or unauthenticated users
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Restricted</title> <!-- Sets the page title -->
</head>
<body>
    <!-- Displays a heading for the restricted access page -->
    <h1>Access Restricted</h1>

    <!-- Displays the restriction message securely to prevent XSS attacks -->
    <p><?php echo htmlspecialchars($message); ?></p> <!-- Encodes special characters in the message for safety -->

    <!-- Provides a link to return to the appropriate dashboard based on the user's role -->
    <a href="<?php echo ($role === 'Lender') ? 'lenderDashboard.php' : 'customerDashboard.php'; ?>">
        Return to Dashboard <!-- Link text -->
    </a>
</body>
</html>
