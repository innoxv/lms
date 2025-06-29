<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one using session_start()

// Includes the database configuration file to establish the $myconn connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Checks if form was submitted via POST request
if (isset($_POST['submit'])) { // isset() checks if submit button was clicked in $_POST global
    // Fetches common form fields
    $role = $_POST['role']; // Gets role from form submission
    $firstName = $_POST['firstName']; // Gets first name from form submission
    $secondName = $_POST['secondName']; // Gets second name from form submission
    $email = $_POST['email']; // Gets email from form submission
    $phone = $_POST['phone']; // Gets phone number from form submission
    $password = $_POST['password']; // Gets password from form submission

    // Combines first and second names into a full name
    $userName = $firstName . " " . $secondName; // Concatenates names with a space

    // Hashes the password for secure storage
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // password_hash() creates a secure hash using BCRYPT algorithm

    // Checks if the email already exists in customers or lenders tables
    $checkEmailQuery = "SELECT email FROM customers WHERE email = '$email'
                        UNION
                        SELECT email FROM lenders WHERE email = '$email'"; // Query to check email in both tables
    $result = mysqli_query($myconn, $checkEmailQuery); // mysqli_query() executes the query

    if (mysqli_num_rows($result) > 0) { // mysqli_num_rows() checks if any rows were returned
        // Email already exists, displays error message and redirects
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href = 'signup.html';</script>";
    } else {
        // Email does not exist, proceeds with registration
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
            ); // Executes query to log activity

            // Stores user data in the session
            $_SESSION['user_id'] = $userId; // Stores user ID in session
            $_SESSION['email'] = $email; // Stores email in session
            $_SESSION['role'] = $role; // Stores role in session
            $_SESSION['user_name'] = $userName; // Stores user name in session
            $address = $_POST['address']; // Gets address from form submission

            if ($role === 'Customer') { // Checks if role is Customer
                // Fetches customer-specific fields
                $dob = $_POST['dob']; // Gets date of birth in DD-MM-YYYY format
                $nationalId = $_POST['nationalId']; // Gets national ID
                $bankAccount = $_POST['accountNumber']; // Gets bank account number

                // Converts date from DD-MM-YYYY to YYYY-MM-DD for database
                $dateObj = DateTime::createFromFormat('d-m-Y', $dob); // DateTime::createFromFormat() parses date string
                if (!$dateObj) { // Checks if date parsing failed
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
                // Skips role-specific insertion for other roles (e.g., Admin)
                $sql = true; // Indicates no further insertion needed
            }

            // Executes the role-specific query
            if ($sql === true || mysqli_query($myconn, $sql)) { // Checks if query is true or executed successfully
                // Redirects based on role
                if ($role === 'Customer') { // Checks if role is Customer
                    header("Location: customerDashboard.php"); // Redirects to customer dashboard
                } elseif ($role === 'Lender') { // Checks if role is Lender
                    header("Location: lenderDashboard.php"); // Redirects to lender dashboard
                }
                exit(); // Terminates script execution after redirection
            } else {
                // Rolls back user insertion if role-specific insertion fails
                mysqli_query($myconn, "DELETE FROM users WHERE user_id = '$userId'"); // Deletes user record
                echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'signup.html';</script>";
            }
        } else {
            echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'signup.html';</script>";
        }
    }
}

// Closes the database connection
mysqli_close($myconn); // mysqli_close() terminates the database connection
?>