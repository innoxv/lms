<?php
// Database connection
$conn = new mysqli('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all customers
$sql = "SELECT customer_id, name, email FROM customers";
$result = $conn->query($sql);

// Generate the report
echo "<h1>All Customers</h1>";
echo "<table border='1'>
        <tr><th>Customer ID</th><th>Name</th><th>Email</th></tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['customer_id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['email']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No customers found.</p>";
}

$conn->close();
?>
