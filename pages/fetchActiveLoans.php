
<!-- This is for Payment Tracking to fetch Active Loans -->
<?php
function fetchActiveLoans($conn, $customerId, $filters = []) {
    $query = "SELECT 
        loans.loan_id,
        loan_offers.loan_type,
        loans.amount,
        loans.interest_rate,
        loans.duration,
        loans.status AS loan_status,
        lenders.name AS lender_name,
        loans.created_at,
        COALESCE(SUM(payments.amount), 0) AS amount_paid
    FROM loans
    JOIN loan_offers ON loans.offer_id = loan_offers.offer_id
    JOIN lenders ON loans.lender_id = lenders.lender_id
    LEFT JOIN payments ON loans.loan_id = payments.loan_id
    WHERE loans.customer_id = ?
    AND loans.status IN ('approved', 'disbursed', 'active')";

    $params = [$customerId];
    $types = "i";

    // Apply filters
    if (!empty($filters['loan_type'])) {
        $query .= " AND loan_offers.loan_type = ?";
        $params[] = $filters['loan_type'];
        $types .= "s";
    }

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

    if (!empty($filters['date_range'])) {
        switch ($filters['date_range']) {
            case 'today':
                $query .= " AND DATE(loans.created_at) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(loans.created_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $query .= " AND MONTH(loans.created_at) = MONTH(CURDATE()) AND YEAR(loans.created_at) = YEAR(CURDATE())";
                break;
            case 'year':
                $query .= " AND YEAR(loans.created_at) = YEAR(CURDATE())";
                break;
        }
    }

    $query .= " GROUP BY loans.loan_id ORDER BY loans.created_at DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error);
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
                'created_at' => $row['created_at'],
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance,
                'total_amount_due' => $totalAmountDue,
                'payment_status' => $paymentStatus
            ];
        }
    }

    $stmt->close();
    return $activeLoans;
}
?>