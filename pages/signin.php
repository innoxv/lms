<?php
// Enable error reporting for debugging (currently commented out)
ini_set('display_errors', 1);  // Shows runtime errors
ini_set('display_startup_errors', 1);  // Shows startup errors
error_reporting(E_ALL);  // Reports all PHP errors

// Function to start or resume the session to store user data across pages
session_start();

// Database config file
include '../phpconfig/config.php';


// Check if form was submitted using POST method
// $_SERVER["REQUEST_METHOD"] checks the HTTP request method
// "POST" means data was sent via form submission
// $_SESSION is a global variable array containing session variables available to the current script
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email and password from form submission
    // $_POST is a  global variable that gets data from client server 
    $email = $_POST['signinEmail'];
    $password = $_POST['signinPassword'];

    // Validate that both fields are not empty (Validation is done by Javascript - validinput.js )
    if (!empty($email) && !empty($password)) {
        // Prepare SQL statement to prevent SQL injection
        // The ? is a placeholder for parameterized queries
        // This selects user data where email matches the provided value
        $stmt = $myconn->prepare("SELECT user_id, email, password, role, user_name FROM users WHERE email = ?");
        
        // Bind the email variable to the ? placeholder
        // "s" indicates the parameter is a string
        $stmt->bind_param("s", $email); //bind_param() is a PHP function that binds variables to a prepared statement as parameters
        
        // Execute the prepared statement
        $stmt->execute();   // execute() is a PHP function that executes previously prepared statements
        
        // Get the result set from the executed statement
        $result = $stmt->get_result(); // get_result() is a PHP function that gets a result set from a prepared statement as a mysqli_result object

        // Check if any rows were returned (user exists)
        if ($result->num_rows > 0) {
            // Fetch user data as an associative array
            $user = $result->fetch_assoc(); //fetch_assoc() is a PHP function that fetches one row of data from the result set and returns it as an array.
            
            // Verify submitted password against hashed password in database
            if (password_verify($password, $user['password'])) {    // password_verify is a  PHP function that verifies that a password matches a hash
                // Password is correct - set session variables:
                $_SESSION['user_id'] = $user['user_id'];  // Store user ID in session
                $_SESSION['email'] = $user['email'];  // Store email in session
                $_SESSION['role'] = $user['role'];  // Store user role (Admin/Customer/Lender)
                $_SESSION['user_name'] = $user['user_name'];  // Store user's full name

                // Log successful login to activity table
                $logStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_time, activity_type) 
                                            VALUES (?, ?, NOW(), 'login')");
                $activity = "User logged in";
                $logStmt->bind_param("is", $user['user_id'], $activity);
                $logStmt->execute();
                $logStmt->close();

                // Redirect based on user role
                if ($user['role'] == 'Admin') {
                    header("Location: adminDashboard.php");
                } elseif ($user['role'] == 'Customer') {
                    header("Location: customerDashboard.php");
                } elseif ($user['role'] == 'Lender') {
                    header("Location: lenderDashboard.php");
                } else {
                    // Fallback for unknown roles
                    header("Location: alert.html");
                }
                exit();  // Terminate script after redirect
            } else {
                // Password verification failed - log failed attempt for existing user
                $logStmt = $myconn->prepare("INSERT INTO activity (user_id, activity, activity_time, activity_type) 
                                           VALUES (?, ?, NOW(), 'failed login')");
                $activity = "Failed login attempt - incorrect password";
                $logStmt->bind_param("is", $user['user_id'], $activity);
                $logStmt->execute();
                $logStmt->close();
                
                echo "<script>alert('Invalid email or password.'); window.location.href = 'signin.html';</script>";
                exit();
            }
        } else {
            // No user found with this email (don't log to prevent filling logs with spam attempts)
            echo "<script>alert('Invalid email or password.'); window.location.href = 'signin.html';</script>";
            exit();
        }
    } else {
        // Either email or password was empty
        echo "<script>alert('Email and password are required.'); window.location.href = 'signin.html';</script>";
        exit();
    }
}

// Close the database connection when done
mysqli_close($myconn);  // mysqli_close is a PHP function that closes a previously opened database connection.
?>