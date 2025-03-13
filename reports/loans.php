<?php
// Database connection
$myconn = mysqli_connect('localhost','root','figureitout','LMSDB') or die('connection failed');

// Check connection
if ($myconn->connect_error) {
    die("Connection failed: " . $myconn->connect_error);
}

// Query to fetch all loans
$sql = "SELECT loans.loan_id, loans.amount, loans.interest_rate, loans.duration, loans.installments, loans.collateral_description, loans.collateral_value, customers.name AS customer_name, lenders.name AS lender_name 
        FROM loans 
        JOIN customers ON loans.customer_id = customers.customer_id 
        JOIN lenders ON loans.lender_id = lenders.lender_id";
$result = $myconn->query($sql);

// Generate the report
if ($result->num_rows > 0) {
    echo "<h1>Loan Report</h1>";
    echo "<table border='1'><tr><th>Loan ID</th><th>Customer</th><th>Lender</th><th>Amount</th><th>Interest Rate</th><th>Duration</th><th>Installments</th><th>Collateral Description</th><th>Collateral Value</th></tr>";

    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".$row["loan_id"]."</td>
                <td>".$row["customer_name"]."</td>
                <td>".$row["lender_name"]."</td>
                <td>".$row["amount"]."</td>
                <td>".$row["interest_rate"]."</td>
                <td>".$row["duration"]."</td>
                <td>".$row["installments"]."</td>
                <td>".$row["collateral_description"]."</td>
                <td>".$row["collateral_value"]."</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No results found.";
}

$myconn->close();
?>

