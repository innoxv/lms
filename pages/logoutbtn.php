<?php
session_start(); // Starts or resumes a session to manage user data

// Checks if the user is logged in before performing logout operations
if (isset($_SESSION['user_id'])) { // Verifies that the 'user_id' is set in the session
    // Includes the database configuration file for connection settings
    include '../phpconfig/config.php'; // Loads the configuration file to connect to the database

    // Proceeds only if the database connection ($myconn) is successful
    if ($myconn) { // Checks if the database connection object is valid
        // Prepares the logout activity message
        $activity = "User logged out"; // Sets a descriptive activity message for logging
        
        // Logs the logout activity in the database
        mysqli_query($myconn, 
            "INSERT INTO activity (user_id, activity, activity_time, activity_type)
            VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'logout')"
        ); 
        // Closes the database connection to free up resources
        mysqli_close($myconn); // Closes the connection object to the database
    }
}
// Ends the session and clears all session data
session_destroy(); // Destroys the session, removing all session variables and associated data

// Redirects the user to the landing page after logging out
header("Location: landingpage.html"); // Sends a HTTP header to redirect the user to 'landingpage.html'
exit(); // Ensures no further code is executed after the redirection
?>
