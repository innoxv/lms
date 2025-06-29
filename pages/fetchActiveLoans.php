<?php
// Defines a function to fetch active loans for a customer with optional filters
function fetchActiveLoans($myconn, $customerId, $filters = []) { // Takes database connection, customer ID, and optional filters array as parameters
    // Builds the base SQL query to fetch active loans with relevant details
    $query = "SELECT 
        loans.loan_id,
        loan_offers.loan_type,
        loans.amount,
        loans.interest_rate,
        loans.duration,
        loans.installments,
        loans.due_date,
        loans.isDue,
        loans.status AS loan_status,
        lenders.name AS lender_name,
        loans.application_date,
        COALESCE(SUM(payments.amount), 0) AS amount_paid,
        COALESCE(
            (SELECT installment_balance 
             FROM payments 
             WHERE payments.loan_id = loans.loan_id 
             AND installment_balance IS NOT NULL 
             ORDER BY payment_date DESC 
             LIMIT 1),
            loans.installments
        ) AS installment_balance
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    JOIN lenders ON loans.lender_id = lenders.lender_id
    LEFT JOIN payments ON loans.loan_id = payments.loan_id
    WHERE loans.customer_id = ?
    AND loans.status = 'disbursed'"; // Base query joins tables and filters for disbursed loans by customer ID

    // Initializes parameters and types for the prepared statement
    $params = [$customerId]; // Starts with customerId as the first parameter
    $types = "i"; // Initializes types string with 'i' for integer (customerId)

    // Applies loan type filter if provided
    if (!empty($filters['loan_type'])) { // Checks if loan_type filter is not empty
        $query .= " AND loan_offers.loan_type = ?"; // Adds loan type condition to the query
        $params[] = $filters['loan_type']; // Adds loan type to parameters array
        $types .= "s"; // Appends 's' for string type
    }

    // Applies amount range filter if provided
    if (!empty($filters['amount_range'])) { // Checks if amount_range filter is not empty
        list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $filters['amount_range'])); // Splits range into min and max, replacing '+' with '-'
        $query .= " AND loans.amount >= ?"; // Adds minimum amount condition
        $params[] = $minAmount; // Adds minimum amount to parameters
        $types .= "d"; // Appends 'd' for double type
        if (is_numeric($maxAmount)) { // is_numeric() checks if maxAmount is a valid number
            $query .= " AND loans.amount <= ?"; // Adds maximum amount condition
            $params[] = $maxAmount; // Adds maximum amount to parameters
            $types .= "d"; // Appends 'd' for double type
        }
    }

    // Applies date range filter if provided
    if (!empty($filters['date_range'])) { // Checks if date_range filter is not empty
        switch ($filters['date_range']) { // switch() selects code based on the date range value
            case 'today':
                $query .= " AND DATE(loans.application_date) = CURDATE()"; // Filters for loans applied today
                break;
            case 'week':
                $query .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)"; // Filters for loans from this week
                break;
            case 'month':
                $query .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())"; // Filters for loans from this month
                break;
            case 'year':
                $query .= " AND YEAR(loans.application_date) = YEAR(CURDATE())"; // Filters for loans from this year
                break;
        }
    }

    // Applies due status filter if provided
    if ($filters['due_status']) { // Checks if due_status filter is set
        $query .= " AND loans.isDue = ?"; // Adds due status condition
        $params[] = ($filters['due_status'] === 'due') ? 1 : 0; // Converts due status to 1 (due) or 0 (not due)
        $types .= "i"; // Appends 'i' for integer type
    }

    // Groups results by loan ID and sorts by application date
    $query .= " GROUP BY loans.loan_id ORDER BY loans.application_date DESC"; // Groups by loan_id to aggregate payments and sorts by newest first

    // Prepares the SQL query for secure execution
    $stmt = $myconn->prepare($query); // prepare() creates a prepared statement
    if (!$stmt) { // Checks if statement preparation failed
        error_log("Query preparation failed: " . $myconn->error); // Logs error to server log using error_log()
        return []; // Returns empty array on failure
    }

    // Binds parameters to the prepared statement if any exist
    if ($params) { // Checks if there are parameters to bind
        $stmt->bind_param($types, ...$params); // bind_param() binds parameters using the types string
    }

    // Executes the query and fetches results
    $stmt->execute(); // Executes the prepared statement
    $result = $stmt->get_result(); // Gets the result set
    $activeLoans = []; // Initializes an empty array to store loan data

    // Processes each loan record
    while ($row = $result->fetch_assoc()) { // fetch_assoc() fetches each row as an associative array
        // Calculates financial details for the loan
        $principal = $row['amount']; // Stores the loan principal
        $interestRate = $row['interest_rate'] / 100; // Converts interest rate percentage to decimal
        $durationYears = $row['duration'] / 12; // Converts duration from months to years
        $totalAmountDue = $principal + ($principal * $interestRate * $durationYears); // Calculates total amount due with simple interest
        $amountPaid = $row['amount_paid']; // Stores total amount paid
        $remainingBalance = $totalAmountDue - $amountPaid; // Calculates remaining balance

        // Determines payment status based on remaining balance
        $paymentStatus = 'unpaid'; // Default status
        if ($remainingBalance <= 0) { // Checks if loan is fully paid
            $paymentStatus = 'fully_paid';
        } elseif ($amountPaid > 0) { // Checks if partial payments have been made
            $paymentStatus = 'partially_paid';
        }

        // Applies payment status filter if specified
        if (empty($filters['payment_status']) || $filters['payment_status'] === $paymentStatus) { // Checks if payment status matches filter or no filter is set
            $activeLoans[] = [ // Adds loan data to the result array
                'loan_id' => $row['loan_id'], // Loan ID
                'loan_type' => $row['loan_type'], // Loan type from loan_offers
                'amount' => $row['amount'], // Loan principal amount
                'interest_rate' => $row['interest_rate'], // Interest rate percentage
                'loan_status' => $row['loan_status'], // Loan status (disbursed)
                'lender_name' => $row['lender_name'], // Name of the lender
                'application_date' => $row['application_date'], // Date of loan application
                'amount_paid' => $amountPaid, // Total amount paid
                'remaining_balance' => $remainingBalance, // Remaining balance
                'total_amount_due' => $totalAmountDue, // Total amount due with interest
                'payment_status' => $paymentStatus, // Calculated payment status
                'installments' => $row['installments'], // Installment amount
                'due_date' => $row['due_date'], // Loan due date
                'isDue' => $row['isDue'], // Due status flag
                'installment_balance' => $row['installment_balance'] // Latest installment balance or original installments
            ];
        }
    }

    // Closes the prepared statement
    $stmt->close(); // Frees resources associated with the statement

    // Returns the array of active loans
    return $activeLoans; // Returns the processed loan data
}
?>