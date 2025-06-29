<?php
// Initiates or resumes a session to manage user state across pages
session_start(); // Starts a new session or resumes an existing one using session_start()

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Handles AJAX request to retrieve session error messages
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_error') { // Checks if request is GET and action is get_error
    // Sets response header to indicate JSON output
    header('Content-Type: application/json'); // Specifies JSON content type for AJAX response
    // Initializes response array for error messages
    $response = [
        'login_error' => null, // Placeholder for login error message
        'login_error_type' => null // Placeholder for error type
    ];
    // Checks for login error in session
    if (isset($_SESSION['login_error'])) { // isset() checks if login_error is set in $_SESSION global array
        $response['login_error'] = $_SESSION['login_error']; // Assigns session error message to response
        $response['login_error_type'] = $_SESSION['login_error_type'] ?? 'error'; // Assigns error type, defaults to 'error'
        // Clears session messages to prevent repeated display
        unset($_SESSION['login_error']); // Removes login_error from session
        unset($_SESSION['login_error_type']); // Removes login_error_type from session
    }
    // Outputs JSON-encoded response and terminates script
    echo json_encode($response); // json_encode() converts array to JSON string
    exit(); // Terminates script execution after response
}

// Handles form submission via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") { // Checks if request method is POST using $_SERVER global
    // Retrieves email and password from form submission
    $email = $_POST['signinEmail']; // Gets email from $_POST global array
    $password = $_POST['signinPassword']; // Gets password from $_POST global array

    // Validates that both fields are not empty (JavaScript validation is handled in validinput.js)
    if (!empty($email) && !empty($password)) { // empty() checks if email and password are not empty
        // Prepares SQL statement to prevent SQL injection
        $stmt = $myconn->prepare("SELECT user_id, email, password, role, user_name FROM users WHERE email = ?"); // Prepares query with placeholder
        // Binds the email variable to the placeholder
        $stmt->bind_param("s", $email); // bind_param() binds email as string ('s') to the query
        // Executes the prepared statement
        $stmt->execute(); // execute() runs the prepared query
        // Gets the result set from the executed statement
        $result = $stmt->get_result(); // get_result() retrieves query results as a mysqli_result object

        // Checks if any rows were returned (user exists)
        if ($result->num_rows > 0) { // num_rows property checks if results were found
            // Fetches user data as an associative array
            $user = $result->fetch_assoc(); // fetch_assoc() retrieves one row as an associative array
            
            // Verifies submitted password against hashed password in database
            if (password_verify($password, $user['password'])) { // password_verify() checks if password matches hash
                // Password is correct, sets session variables
                $_SESSION['user_id'] = $user['user_id']; // Stores user ID in session
                $_SESSION['email'] = $user['email']; // Stores email in session
                $_SESSION['role'] = $user['role']; // Stores role (Admin/Customer/Lender) in session
                $_SESSION['user_name'] = $user['user_name']; // Stores user's full name in session

                // Logs successful login to activity table
                $logStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_time, activity_type) 
                                            VALUES (?, ?, NOW(), 'login')"); // Prepares query to log login
                $activity = "User logged in"; // Defines activity description
                $logStmt->bind_param("is", $user['user_id'], $activity); // Binds user_id (integer) and activity (string)
                $logStmt->execute(); // Executes the activity log query
                $logStmt->close(); // Closes the prepared statement

                // Redirects based on user role
                if ($user['role'] == 'Admin') { // Checks if role is Admin
                    header("Location: adminDashboard.php"); // Redirects to admin dashboard
                } elseif ($user['role'] == 'Customer') { // Checks if role is Customer
                    header("Location: customerDashboard.php"); // Redirects to customer dashboard
                } elseif ($user['role'] == 'Lender') { // Checks if role is Lender
                    header("Location: lenderDashboard.php"); // Redirects to lender dashboard
                } else {
                    // Handles unknown roles
                    header("Location: alert.html"); // Redirects to generic alert page
                }
                exit(); // Terminates script execution after redirection
            } else {
                // Password verification failed, logs attempt
                $logStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_time, activity_type) 
                                           VALUES (?, ?, NOW(), 'failed login')"); // Prepares query to log failed login
                $activity = "Failed login attempt - incorrect password"; // Defines activity description
                $logStmt->bind_param("is", $user['user_id'], $activity); // Binds user_id (integer) and activity (string)
                $logStmt->execute(); // Executes the activity log query
                $logStmt->close(); // Closes the prepared statement
                
                // Stores error message for AJAX retrieval
                $_SESSION['login_error'] = "Invalid Email or Password!"; // Sets error message in session
                $_SESSION['login_error_type'] = "error"; // Sets error type in session
                header("Location: signin.html?error=1"); // Redirects to signin page with error flag
                exit(); // Terminates script execution after redirection
            }
        } else {
            // No user found with the provided email
            $_SESSION['login_error'] = "Invalid Email or Password!"; // Sets error message in session
            $_SESSION['login_error_type'] = "error"; // Sets error type in session
            header("Location: signin.html?error=1"); // Redirects to signin page with error flag
            $stmt->close(); // Closes the prepared statement
            exit(); // Terminates script execution after redirection
        }
    } else {
        // Form fields are empty
        $_SESSION['login_error'] = "Email and password are required!"; // Sets error message in session
        $_SESSION['login_error_type'] = "error"; // Sets error type in session
        header("Location: signin.html?error=1"); // Redirects to signin page with error flag
        exit(); // Terminates script execution after redirection
    }
}

// Closes the database connection
mysqli_close($myconn); // mysqli_close() terminates the database connection
?>