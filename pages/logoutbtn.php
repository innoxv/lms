<?php
session_start(); // Starts a new session 

// Verifies if the user is logged in before proceeding with logout
if (isset($_SESSION['user_id'])) { // Checks if user_id is set in the session array
    // Includes the database configuration file to establish a connection
    include '../phpconfig/config.php'; // Imports database connection settings from config.php
    
    // Proceeds if database connection is established
    if ($myconn) { // Checks if $myconn (database connection) is valid
        // Defines the logout activity message
        $activity = "User logged out"; // Sets the activity description for logging
        // Inserts a logout activity record into the activity table
        mysqli_query($myconn, 
            "INSERT INTO activity (user_id, activity, activity_time, activity_type)
            VALUES ({$_SESSION['user_id']}, '$activity', NOW(), 'logout')"
        ); // Executes SQL query to log user_id, activity, current timestamp, and 'logout' type
        // Closes the database connection
        mysqli_close($myconn); // Terminates the database connection
    }
}

// Terminates the session and clears all session data
session_destroy(); // Destroys the current session, removing all session variables

// Redirects the user to the landing page
header("Location: landingpage.html"); // Sends HTTP header to redirect to landingpage.html
exit(); // Terminates script execution after redirection
?>