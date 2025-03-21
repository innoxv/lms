<?php
// Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Start the session
session_start();

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the email and password from the form
    $email = $_POST['email'];
    $password = $_POST['password'];


    // Validate the email and password
    if (!empty($email) && !empty($password)) {
        // Prepare a SQL statement to prevent SQL injection
        $stmt = $myconn->prepare("SELECT user_id, email, password, role, user_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User found, verify the password
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['user_id']; 
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_name'] = $user['user_name']; // Store the user's name in the session


                // Redirect based on role
                if ($user['role'] == 'Admin') {
                    header("Location: adminDashboard.php");
                } elseif ($user['role'] == 'Customer') {
                    header("Location: customerDashboard.php");
                } elseif ($user['role'] == 'Lender') {
                    header("Location: lenderDashboard.php");
                } else {
                    // Default fallback for unknown roles
                    header("Location: alert.html");
                }
                exit();
            } else {
                // Password is incorrect
                echo "<script>alert('Invalid email or password.'); window.location.href = 'signin.html';</script>";
                exit();
            }
        } else {
            // User not found
            echo "<script>alert('Invalid email or password.'); window.location.href = 'signin.html';</script>";
            exit();
        }
    } else {
        // Email or password is empty
        echo "<script>alert('Email and password are required.'); window.location.href = 'signin.html';</script>";
        exit();
    }
}

// Close the database connection
mysqli_close($myconn);
?>