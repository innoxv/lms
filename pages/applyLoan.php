<?php
// Initiates or resumes a session to manage user state
session_start(); // Starts a new session or resumes an existing one

// Includes the database configuration file to establish a connection
include '../phpconfig/config.php'; // Imports database connection settings from config.php

// Verifies if the form was submitted using the POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Checks if REQUEST_METHOD is not strictly equal to 'POST'
    $_SESSION['loan_message'] = "Invalid request method"; // Sets an error message in the session
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section of customerDashboard.php
    exit; // Terminates script execution after redirection
}

// Defines an array of required form fields
$required = [
    'offer_id', 'lender_id', 'interest_rate',
    'amount', 'duration', 'collateral_value', 
    'collateral_description', 'collateral_image'
]; // Creates an array listing all mandatory fields

// Validates required fields by checking for empty values
$missing = []; // Initializes an empty array to store missing fields
foreach ($required as $field) { // Iterates through each required field
    if (empty($_POST[$field]) && ($field !== 'collateral_image' || empty($_FILES['collateral_image']['name']))) { // Checks if POST field is empty AND (field is not collateral_image OR no file is uploaded)
        $missing[] = $field; // Adds missing field to the missing array
    }
}

// Handles missing required fields
if (!empty($missing)) { // Checks if missing array is not empty
    $_SESSION['loan_message'] = "Missing required fields: " . implode(', ', $missing); // Sets error message with comma-separated missing fields
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

// Sanitizes input data to prevent SQL injection and ensure correct types
$offer_id = intval($_POST['offer_id']); // Converts offer_id to integer
$lender_id = intval($_POST['lender_id']); // Converts lender_id to integer
$user_id = intval($_SESSION['user_id']); // Converts user_id from session to integer
$amount = floatval($_POST['amount']); // Converts amount to float
$interest_rate = floatval($_POST['interest_rate']); // Converts interest_rate to float
$duration = intval($_POST['duration']); // Converts duration to integer
$collateral_value = floatval($_POST['collateral_value']); // Converts collateral_value to float
$collateral_description = $myconn->real_escape_string($_POST['collateral_description']); // Escapes special characters in collateral_description for SQL safety
$installments = isset($_POST['installments']) ? floatval($_POST['installments']) : 0; // Converts installments to float if set, else defaults to 0

// Handles collateral image upload
$collateral_image = ''; // Initializes collateral_image as an empty string
if (!empty($_FILES['collateral_image']['name'])) { // Checks if a file name exists in the uploaded files
    $target_dir = "../uploads/"; // Defines the directory where the file will be stored
    $target_file = $target_dir . basename($_FILES['collateral_image']['name']); // Constructs the full path for the uploaded file
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION)); // Gets the file extension in lowercase
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif']; // Defines an array of allowed file extensions

    // Verifies if the uploaded file is an image
    $check = getimagesize($_FILES['collateral_image']['tmp_name']); // Attempts to get image size to verify it’s an image
    if ($check === false) { // Checks if getimagesize returned false
        $_SESSION['loan_message'] = "File is not an image."; // Sets error message for invalid image
        $_SESSION['message_type'] = "error"; // Sets the message type to error
        header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
        exit; // Terminates script execution after redirection
    }

    // Checks file size limit (2MB)
    if ($_FILES['collateral_image']['size'] > 2000000) { // Checks if file size exceeds 2MB (2000000 bytes)
        $_SESSION['loan_message'] = "File size too large (max 2MB)."; // Sets error message for oversized file
        $_SESSION['message_type'] = "error"; // Sets the message type to error
        header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
        exit; // Terminates script execution after redirection
    }

    // Verifies allowed file types
    if (!in_array($imageFileType, $allowed_types)) { // Checks if file extension is not in allowed_types
        $_SESSION['loan_message'] = "Only JPG, JPEG, PNG, and GIF files are allowed."; // Sets error message for invalid file type
        $_SESSION['message_type'] = "error"; // Sets the message type to error
        header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
        exit; // Terminates script execution after redirection
    }

    // Attempts to move the uploaded file to the target directory
    if (move_uploaded_file($_FILES['collateral_image']['tmp_name'], $target_file)) { // Moves file from temporary location to target_file
        $collateral_image = $target_file; // Assigns the file path to collateral_image
    } else { // Executes if file move fails
        $_SESSION['loan_message'] = "Error uploading file: " . $_FILES['collateral_image']['error']; // Sets error message with upload error code
        $_SESSION['message_type'] = "error"; // Sets the message type to error
        header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
        exit; // Terminates script execution after redirection
    }
} else { // Executes if no file was uploaded
    $_SESSION['loan_message'] = "Collateral image is required."; // Sets error message for missing image
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

// Verifies if the customer exists in the database
$customer_result = $myconn->query(
    "SELECT customer_id FROM customers WHERE user_id = $user_id LIMIT 1"
); // Executes SQL query to fetch customer_id for the given user_id, limited to 1 row

if (!$customer_result || $customer_result->num_rows === 0) { // Checks if query failed or no rows were returned
    $_SESSION['loan_message'] = "Customer account not found."; // Sets error message for missing customer account
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

$customer_id = $customer_result->fetch_assoc()['customer_id']; // Fetches customer_id from the result

// Verifies if the loan offer belongs to the specified lender
$offer_check = $myconn->query(
    "SELECT 1 FROM loan_offers 
    WHERE offer_id = $offer_id 
    AND lender_id = $lender_id 
    LIMIT 1"
); // Executes SQL query to check if offer_id and lender_id match, limited to 1 row

if (!$offer_check || $offer_check->num_rows === 0) { // Checks if query failed or no rows were returned
    $_SESSION['loan_message'] = "Invalid loan offer for selected lender."; // Sets error message for invalid loan offer
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

// Checks for existing loans of the same type that are submitted, pending, or active
$existing_loan_check = $myconn->query(
    "SELECT status, loan_id 
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    WHERE loans.customer_id = $customer_id
        AND loans.offer_id = $offer_id
        AND loans.status IN ('submitted', 'pending')
    UNION
    SELECT status, loan_id 
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    WHERE loans.customer_id = $customer_id
        AND loans.offer_id = $offer_id
        AND loans.status = 'disbursed'
        AND EXISTS (
            SELECT 1
            FROM payments
            WHERE payments.loan_id = loans.loan_id
            AND payments.customer_id = $customer_id
            AND payments.payment_date = (
                SELECT MAX(payment_date)
                FROM payments
                WHERE payments.loan_id = loans.loan_id
                AND payments.customer_id = $customer_id
            )
            AND payments.payment_type != 'full'
        )
    LIMIT 1"
); // Executes SQL query to find existing loans with specific statuses, combining results with UNION

if ($existing_loan_check && $existing_loan_check->num_rows > 0) { // Checks if query succeeded and rows were returned
    $loan = $existing_loan_check->fetch_assoc(); // Fetches loan data as an associative array
    $status = $loan['status']; // Assigns loan status to variable
    $message = $status === 'disbursed' 
        ? "This loan is '$status'. Settle it before reapplying."
        : "This applicaton is '$status'. Wait for approval before reapplying."; // Sets message based on loan status
    $_SESSION['loan_message'] = $message; // Sets error message in session
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

// Checks for unpaid loans (more than 2)
$unpaid_loans_query = "
    SELECT COUNT(DISTINCT loan_id) as unpaid_count
    FROM loans
    WHERE customer_id = $customer_id
    AND EXISTS (
        SELECT 1
        FROM payments
        WHERE payments.loan_id = loans.loan_id
        AND payments.customer_id = $customer_id
        AND payments.payment_date = (
            SELECT MAX(payment_date)
            FROM payments
            WHERE payments.loan_id = loans.loan_id
            AND payments.customer_id = $customer_id
        )
        AND payments.payment_type != 'full'
    )
"; // Defines SQL query to count unpaid loans with non-full payment status

$unpaid_loans_result = $myconn->query($unpaid_loans_query); // Executes the query

if (!$unpaid_loans_result) { // Checks if query failed
    $_SESSION['loan_message'] = "Error checking unpaid loans: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

$unpaid_count = $unpaid_loans_result->fetch_assoc()['unpaid_count']; // Fetches the count of unpaid loans

if ($unpaid_count > 2) { // Checks if unpaid loan count exceeds 2
    $_SESSION['loan_message'] = "You have more than 2 unpaid loans. Settle them to apply."; // Sets error message for too many unpaid loans
    $_SESSION['message_type'] = "error"; // Sets the message type to error
    header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
    exit; // Terminates script execution after redirection
}

// Inserts the loan application into the database
$insert_query = "INSERT INTO loans (
    offer_id, customer_id, lender_id,
    amount, interest_rate, duration,
    installments, collateral_description, collateral_value, collateral_image,
    status, application_date, due_date, isDue
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW(), 
    DATE_ADD(NOW(), INTERVAL 1 MONTH), 0
)"; // Defines SQL query to insert loan application with prepared statement placeholders
$stmt = $myconn->prepare($insert_query); // Prepares the SQL statement for execution
$stmt->bind_param(
    "iiiddidsss",
    $offer_id,
    $customer_id,
    $lender_id,
    $amount,
    $interest_rate,
    $duration,
    $installments,
    $collateral_description,
    $collateral_value,
    $collateral_image
); // Binds parameters with types: integer (i), double (d), string (s)

if ($stmt->execute()) { // Checks if the statement executed successfully
    // Logs the loan application activity
    $loan_id = $myconn->insert_id; // Retrieves the ID of the newly inserted loan
    $activity_description = "Applied for loan, Loan ID $loan_id"; // Creates activity description with loan ID
    $myconn->query(
        "INSERT INTO activity (user_id, activity, activity_time, activity_type)
        VALUES ($user_id, '$activity_description', NOW(), 'loan application')"
    ); // Executes SQL query to log user_id, activity description, current timestamp, and 'loan application' type
    
    $_SESSION['loan_message'] = "Application submitted successfully"; // Sets success message in session
    $_SESSION['message_type'] = "success"; // Sets the message type to success
} else { // Executes if insertion fails
    $_SESSION['loan_message'] = "Database error: " . $myconn->error; // Sets error message with database error
    $_SESSION['message_type'] = "error"; // Sets the message type to error
}

$stmt->close(); // Closes the prepared statement
header("Location: customerDashboard.php#applyLoan"); // Redirects to applyLoan section
exit; // Terminates script execution after redirection
?>