<?php
function fetchActiveLoans($myconn, $customerId, $filters = []) {
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
    AND loans.status = 'disbursed'";

    $params = [$customerId];
    $types = "i";

    // Apply filters
    if (!empty($filters['loan_type'])) {
        $query .= " AND loan_offers.loan_type = ?";
        $params[] = $filters['loan_type'];
        $types .= "s";
    }
    // Amount Range Filter
    if (!empty($filters['amount_range'])) {
        list($minAmount, $maxAmount) = explode('-', str_replace('+', '-', $filters['amount_range']));
        $query .= " AND loans.amount >= ?";
        $params[] = $minAmount;
        $types .= "d";
        if (is_numeric($maxAmount)) {
            $query .= " AND loans.amount <= ?";
            $params[] = $maxAmount;
            $types .= "d";
        }
    }
    // Date Range Filter
    if (!empty($filters['date_range'])) {
        switch ($filters['date_range']) {
            case 'today':
                $query .= " AND DATE(loans.application_date) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(loans.application_date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $query .= " AND MONTH(loans.application_date) = MONTH(CURDATE()) AND YEAR(loans.application_date) = YEAR(CURDATE())";
                break;
            case 'year':
                $query .= " AND YEAR(loans.application_date) = YEAR(CURDATE())";
                break;
        }
    }

    // Due Status Filter 
    if ($filters['due_status']) {
        $query .= " AND loans.isDue = ?";
        $params[] = ($filters['due_status'] === 'due') ? 1 : 0;
        $types .= "i";
    }

    $query .= " GROUP BY loans.loan_id ORDER BY loans.application_date DESC";

    $stmt = $myconn->prepare($query);
    if (!$stmt) {
        error_log("Query preparation failed: " . $myconn->error);
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $activeLoans = [];

    while ($row = $result->fetch_assoc()) {
        $principal = $row['amount'];
        $interestRate = $row['interest_rate'] / 100;
        $durationYears = $row['duration'] / 12;
        $totalAmountDue = $principal + ($principal * $interestRate * $durationYears);
        $amountPaid = $row['amount_paid'];
        $remainingBalance = $totalAmountDue - $amountPaid;

        $paymentStatus = 'unpaid';
        if ($remainingBalance <= 0) {
            $paymentStatus = 'fully_paid';
        } elseif ($amountPaid > 0) {
            $paymentStatus = 'partially_paid';
        }

        if (empty($filters['payment_status']) || $filters['payment_status'] === $paymentStatus) {
            $activeLoans[] = [
                'loan_id' => $row['loan_id'],
                'loan_type' => $row['loan_type'],
                'amount' => $row['amount'],
                'interest_rate' => $row['interest_rate'],
                'loan_status' => $row['loan_status'],
                'lender_name' => $row['lender_name'],
                'application_date' => $row['application_date'],
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance,
                'total_amount_due' => $totalAmountDue,
                'payment_status' => $paymentStatus,
                'installments' => $row['installments'],
                'due_date' => $row['due_date'],
                'isDue' => $row['isDue'],
                'installment_balance' => $row['installment_balance']
            ];
        }
    }

    $stmt->close();
    return $activeLoans;
}
?>