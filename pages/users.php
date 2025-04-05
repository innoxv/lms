<?php
// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed");
}

//Determine if session variables are considered set
if (isset($_POST['submit'])) {
    // Fetch data from the form and store in variables
    $role = $_POST['role'];
    $firstName = $_POST['firstName'];
    $secondName = $_POST['secondName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    // Combine first and second names to be stored in one column
    $userName = $firstName . " " . $secondName;

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if the email already exists in the database
    $checkEmailQuery = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($myconn, $checkEmailQuery);

    if (mysqli_num_rows($result) > 0) {
        // Email already exists, show an error message
        echo "<script>alert('Email already exists. Please use a different email.'); window.location.href = 'adminDashboard.php#addUsers';</script>";
    } else {
        // Email does not exist, proceed with registration
        $sql = "INSERT INTO users (user_name, email, phone, password, role) 
                VALUES ('$userName', '$email', '$phone', '$hashedPassword', '$role')";

        // Execute the query
        if (mysqli_query($myconn, $sql)) {
            echo "<script>alert('User registered successfully!'); window.location.href = 'adminDashboard.php#addUsers';</script>";
        } else {
            echo "<script>alert('Unable to register user. Error: " . mysqli_error($myconn) . "'); window.location.href = 'adminDashboard.php#addUsers';</script>";
        }
    }

    // Close the database connection
    mysqli_close($myconn);
}
?>