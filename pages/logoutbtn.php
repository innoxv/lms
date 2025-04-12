<?php
// Start the session
session_start();

// Check if user is logged in before logging out
if (isset($_SESSION['user_id'])) {
    // Database connection for logging
    $myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
    
    if ($myconn) {
        // Log the logout activity
        $activity = "User logged out";
        mysqli_query($myconn, 
            "INSERT INTO activity (user_id, activity, activity_time, activity_type)
            VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'logout')"
        );
        mysqli_close($myconn);
    }
}

// Destroy the session
session_destroy();

// Redirect to the landing page
header("Location: landingpage.html");
exit();
?>