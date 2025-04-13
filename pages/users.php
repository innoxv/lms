<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Database connection
// $myconn is a global variable that creates a connection to MySQL database using mysqli_connect() function that opens a new connection to the MySQL server
// Parameters: server, username, password, database 
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed");
}

// checks if form was submitted (POST request exists) and the specific submit button was clicked
if (isset($_POST['submit'])) {  // isset is a PHP function that determines if a variable is considered set
    // Fetch common fields
    $role = $_POST['role'];
    $firstName = $_POST['firstName'];
    $secondName = $_POST['secondName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // Combine first and second names
    $userName = $firstName . " " . $secondName;

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // password_hash() is a PHP function that creates a password hash with PASSWORD_BCRYPT algorithm

    // Check if the email already exists in users table (including admins)
    $checkEmailQuery = "SELECT email FROM users WHERE email = '$email'";
    $result = mysqli_query($myconn, $checkEmailQuery); //mysqli_query() is a PHP function that performs a query on the database

    if (mysqli_num_rows($result) > 0) { //mysqli_num_rows() is a PHP function that returns the number of rows in the result set
        // Email already exists, show an error message
        echo "<script>alert('Email already exists. Please use a different email.'); </script>";
        exit();
    }

    // Email does not exist, proceed with registration
    $status = "Active"; // Set status to "Active" by default
    $registrationDate = date('Y-m-d H:i:s'); // Current timestamp

    // Insert into users table
    $insertUserQuery = "INSERT INTO users (user_name, email, phone, password, role) 
                        VALUES ('$userName', '$email', '$phone', '$hashedPassword', '$role')";
    if (mysqli_query($myconn, $insertUserQuery)) {
        // Get the ID of the newly inserted user
        $userId = mysqli_insert_id($myconn);    //mysqli_insert_id() is a PHP function that returns the value generated for an AUTO_INCREMENT column by the last query

        // Log the registration activity
        $activity = "New $role registration";
        mysqli_query($myconn, 
            "INSERT INTO activity (user_id, activity, activity_time, activity_type)
            VALUES ($userId, '$activity', NOW(), 'account registration')"
        );

        // Store user data in the session if registering self
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['user_name'] = $userName;
        }

        $address = $_POST['address'];

        if ($role === 'Customer') {
            // Fetch Customer-specific fields
            $dob = $_POST['dob']; // Date in DD-MM-YYYY format
            $nationalId = $_POST['nationalId'];
            $bankAccount = $_POST['accountNumber'];

            // Convert date from DD-MM-YYYY to YYYY-MM-DD
            $dateObj = DateTime::createFromFormat('d-m-Y', $dob);   //DateTime::createFromFormat() is a static function that parses a time string according to a specified format
            if (!$dateObj) {
                // Rollback user insertion
                mysqli_query($myconn, "DELETE FROM users WHERE user_id = '$userId'");
                echo "<script>alert('Invalid date of birth. Please use the format DD-MM-YYYY.'); window.location.href = 'signup.html';</script>";
                exit();
            }
            $dobFormatted = $dateObj->format('Y-m-d'); // Convert to YYYY-MM-DD

            // Insert into customers table
            $sql = "INSERT INTO customers (user_id, name, email, phone, password, dob, national_id, address, status, registration_date, bank_account) 
                    VALUES ('$userId', '$userName', '$email', '$phone', '$hashedPassword', '$dobFormatted', '$nationalId', '$address', '$status', '$registrationDate', '$bankAccount')";
        } elseif ($role === 'Lender') {
            // Insert into lenders table
            $sql = "INSERT INTO lenders (user_id, name, email, phone, password, address, status, registration_date, total_loans, average_interest_rate) 
                    VALUES ('$userId', '$userName', '$email', '$phone', '$hashedPassword', '$address', '$status', '$registrationDate', 0, 0)";
        } else {
            // For Admin, we only insert into users table (no duplicate)
            $sql = true; // Skip additional inserts for admin
        }

        // Execute the query if needed
        if ($sql === true || mysqli_query($myconn, $sql)) {
            // Log successful registration
            $activity = "Registered $role: $email";
            $user_id = $_SESSION['user_id'] ?? $userId;

            mysqli_query($myconn, 
                "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                VALUES ($user_id, '$activity', NOW(), 'user registration')"
            );

            // Redirect based on role
            if (!isset($_SESSION['user_id'])) {
                if ($role === 'Customer') {
                    header("Location: customerDashboard.php");
                } elseif ($role === 'Lender') {
                    header("Location: lenderDashboard.php");
                } else {
                    header("Location: adminDashboard.php");
                }
                exit();
            } else {
                // If admin is creating user, redirect back to user management
                header("Location: adminDashboard.php");
                exit();
            }
        } else {
            // Rollback user insertion if role-specific insertion fails
            mysqli_query($myconn, "DELETE FROM users WHERE user_id = '$userId'");
            echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'signup.html';</script>";
        }
    } else {
        echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'signup.html';</script>";
    }
}

// Close the database connection
mysqli_close($myconn);
?>