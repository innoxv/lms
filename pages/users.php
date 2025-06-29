<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Checks if form was submitted via POST request
if (isset($_POST['submit'])) { // isset() checks if submit button was clicked
    // Fetches common form fields
    $role = $_POST['role']; // Gets user role
    $firstName = $_POST['firstName']; // Gets first name
    $secondName = $_POST['secondName']; // Gets second name
    $email = $_POST['email']; // Gets email
    $phone = $_POST['phone']; // Gets phone number
    $password = $_POST['password']; // Gets password

    // Combines first and second names
    $userName = $firstName . " " . $secondName; // Concatenates names with a space

    // Hashes the password for secure storage
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // password_hash() creates a secure hash using BCRYPT

    // Checks if the email already exists in the users table
    $checkEmailQuery = "SELECT email FROM users WHERE email = '$email'"; // Query to check for existing email
    $result = mysqli_query($myconn, $checkEmailQuery); // Executes the query

    if (mysqli_num_rows($result) > 0) { // Checks if email already exists
        // Displays error message and exits
        echo "<script>alert('Email already exists. Please use a different email.'); </script>";
        exit();
    }

    // Sets default status and registration date
    $status = "Active"; // Sets default status to Active
    $registrationDate = date('Y-m-d H:i:s'); // Gets current timestamp in YYYY-MM-DD HH:MM:SS format

    // Inserts user into the users table
    $insertUserQuery = "INSERT INTO users (user_name, email, phone, password, role) 
                        VALUES ('$userName', '$email', '$phone', '$hashedPassword', '$role')"; // Query to insert user
    if (mysqli_query($myconn, $insertUserQuery)) { // Executes the query
        // Gets the ID of the newly inserted user
        $userId = mysqli_insert_id($myconn); // mysqli_insert_id() returns the last auto-incremented ID

        // Logs the registration activity
        $activity = "New $role registration"; // Creates activity description
        mysqli_query($myconn, 
            "INSERT INTO activity (user_id, activity, activity_time, activity_type)
            VALUES ($userId, '$activity', NOW(), 'account registration')"
        ); // Logs activity

        // Stores user data in session if registering self
        if (!isset($_SESSION['user_id'])) { // Checks if user is not already logged in
            $_SESSION['user_id'] = $userId; // Stores user_id in session
            $_SESSION['email'] = $email; // Stores email in session
            $_SESSION['role'] = $role; // Stores role in session
            $_SESSION['user_name'] = $userName; // Stores user name in session
        }

        $address = $_POST['address']; // Gets address from form

        if ($role === 'Customer') { // Checks if role is Customer
            // Fetches customer-specific fields
            $dob = $_POST['dob']; // Gets date of birth in DD-MM-YYYY format
            $nationalId = $_POST['nationalId']; // Gets national ID
            $bankAccount = $_POST['accountNumber']; // Gets bank account number

            // Converts date of birth to database format
            $dateObj = DateTime::createFromFormat('d-m-Y', $dob); // Parses date using specified format
            if (!$dateObj) { // Checks if date parsing failed
                // Rolls back user insertion
                mysqli_query($myconn, "DELETE FROM users WHERE user_id = '$userId'"); // Deletes user record
                echo "<script>alert('Invalid date of birth. Please use the format DD-MM-YYYY.'); window.location.href = 'signup.html';</script>";
                exit(); // Terminates script execution
            }
            $dobFormatted = $dateObj->format('Y-m-d'); // Converts to YYYY-MM-DD format

            // Inserts into customers table
            $sql = "INSERT INTO customers (user_id, name, email, phone, password, dob, national_id, address, status, registration_date, bank_account) 
                    VALUES ('$userId', '$userName', '$email', '$phone', '$hashedPassword', '$dobFormatted', '$nationalId', '$address', '$status', '$registrationDate', '$bankAccount')"; // Query to insert customer
        } elseif ($role === 'Lender') { // Checks if role is Lender
            // Inserts into lenders table
            $sql = "INSERT INTO lenders (user_id, name, email, phone, password, address, status, registration_date, total_loans, average_interest_rate) 
                    VALUES ('$userId', '$userName', '$email', '$phone', '$hashedPassword', '$address', '$status', '$registrationDate', 0, 0)"; // Query to insert lender
        } else {
            // Skips additional inserts for Admin role
            $sql = true; // Indicates no further insertion needed
        }

        // Executes the role-specific query if needed
        if ($sql === true || mysqli_query($myconn, $sql)) { // Checks if query is true or executed successfully
            // Logs successful registration
            $activity = "Registered $role: $email"; // Creates activity description
            $user_id = $_SESSION['user_id'] ?? $userId; // Uses current or new user_id

            mysqli_query($myconn, 
                "INSERT INTO activity (user_id, activity, activity_time, activity_type)
                VALUES ($user_id, '$activity', NOW(), 'user registration')"
            ); // Logs activity

            // Redirects based on role
            if (!isset($_SESSION['user_id'])) { // Checks if registering self
                if ($role === 'Customer') {
                    header("Location: customerDashboard.php"); // Redirects to customer dashboard
                } elseif ($role === 'Lender') {
                    header("Location: lenderDashboard.php"); // Redirects to lender dashboard
                } else {
                    header("Location: adminDashboard.php"); // Redirects to admin dashboard
                }
                exit(); // Terminates script execution after redirection
            } else {
                // Redirects to admin dashboard for user management
                header("Location: adminDashboard.php"); // Redirects to admin dashboard
                exit(); // Terminates script execution after redirection
            }
        } else {
            // Rolls back user insertion on failure
            mysqli_query($myconn, "DELETE FROM users WHERE user_id = '$userId'"); // Deletes user record
            echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'signup.html';</script>";
        }
    } else {
        echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'signup.html';</script>";
    }
}

// Closes the database connection
mysqli_close($myconn); // Terminates the database connection
?>