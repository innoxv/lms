<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['submit'])) {
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
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if the email already exists in either table
    $checkEmailQuery = "SELECT email FROM customers WHERE email = '$email'
                        UNION
                        SELECT email FROM lenders WHERE email = '$email'";
    $result = mysqli_query($myconn, $checkEmailQuery);

    if (mysqli_num_rows($result) > 0) {
        // Email already exists, show an error message
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href = 'signup.html';</script>";
    } else {
        // Email does not exist, proceed with registration
        $status = "Active"; // Set status to "Active" by default
        $registrationDate = date('Y-m-d H:i:s'); // Current timestamp

        // Insert into users table
        $insertUserQuery = "INSERT INTO users (user_name, email, phone, password, role) 
                            VALUES ('$userName', '$email', '$phone', '$hashedPassword', '$role')";
        if (mysqli_query($myconn, $insertUserQuery)) {
            // Get the ID of the newly inserted user
            $userId = mysqli_insert_id($myconn);

            if ($role === 'Customer') {
                // Fetch Customer-specific fields
                $dob = $_POST['dob']; // Date in DD-MM-YYYY format
                $nationalId = $_POST['nationalId'];
                $address = $_POST['address'];
                $bankAccount = $_POST['accountNumber'];

                // Convert date from DD-MM-YYYY to YYYY-MM-DD
                $dateObj = DateTime::createFromFormat('d-m-Y', $dob);
                if (!$dateObj) {
                    echo "<script>alert('Invalid date of birth. Please use the format DD-MM-YYYY.'); window.location.href = 'signup.html';</script>";
                    exit();
                }
                $dobFormatted = $dateObj->format('Y-m-d'); // Convert to YYYY-MM-DD

                // Insert into customers table
                $sql = "INSERT INTO customers (user_id, name, email, phone, password, dob, national_id, address, status, registration_date, bank_account) 
                        VALUES ('$userId', '$userName', '$email', '$phone', '$hashedPassword', '$dobFormatted', '$nationalId', '$address', '$status', '$registrationDate', '$bankAccount')";
            } elseif ($role === 'Lender') {
                // Fetch Lender-specific fields
                $address = $_POST['address'];

                // Insert into lenders table
                $sql = "INSERT INTO lenders (user_id, name, email, phone, password, address, status, registration_date, total_loans, average_interest_rate) 
                        VALUES ('$userId', '$userName', '$email', '$phone', '$hashedPassword', '$address', '$status', '$registrationDate', 0, 0)";
            }

            // Execute the query
            if (mysqli_query($myconn, $sql)) {
                // Redirect based on role
                if ($role === 'Customer') {
                    header("Location: customerDashboard.html");
                } elseif ($role === 'Lender') {
                    header("Location: lenderDashboard.html");
                }
                exit();
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
}
?>