<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: signin.html");
    exit();
}
// Check if there's a loan message to display
if (isset($_SESSION['loan_message'])) {
    $loan_message = $_SESSION['loan_message'];
    unset($_SESSION['loan_message']); // Clear the message after displaying it
} else {
    $loan_message = null;
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch user data from the database
$userId = $_SESSION['user_id'];
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name']; // Update the session with the latest data
} else {
    // Handle error if user data is not found
    $_SESSION['user_name'] = "Guest";
}

// Close the database connection
mysqli_close($myconn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lender's Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main>
        <div class="header">
            <div class="header2">
                <div class="logo">LMS</div>
            </div>
            <div class="header4">
                <div>
                <?php if ($loan_message): ?>
                <div id="loan-message" class="loan-message <?php echo (strpos($loan_message, 'success') !== false) ? 'success' : ''; ?>">
                    <?php echo htmlspecialchars($loan_message); ?>
                </div>
            <?php endif; ?>
                </div>
                <div>
                <ul>
                    <li><a href="logoutbtn.php" class="no-col">Log Out</a></li>
                </ul>
                </div>
                
            </div>
            
        </div>
        <div class="customer-content">
            <div class="nav">
                <ul class="nav-split">
                    <div class="top">
                        <li><a href="#dashboard">Dashboard</a></li>
                        <li><a href="#createLoan">Create a Loan</a></li>
                        <li><a href="#loanHistory">Loan History</a></li>
                        <li><a href="#financialSummary">Financial Summary</a></li>
                        <li><a href="#notifications">Notifications</a></li>
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                        <li><a href="#feedback">Feedback</a></li>
                        <li><a href="#contactSupport">Contact Support</a></li>
                    </div>
                </ul>
            </div>
            <div class="display">
                <!-- Apply for Loan -->
                <div id="createLoan" class="margin">
                    <div>
                        <h1>Create a Loan Offer</h1>
                        <p>Fill out the form to create a new loan offer.</p>
                    </div>
                    <div class="marg3">
                        <form action="createLoan.php" method="post" onsubmit="return validateFormLoans()">
                            <table>
                                <tr>
                                <td><label>Loan Type</label></td>
                                <td>
                                    <select name="type" id="type" class="select">
                                        <option value="--select option--" selected>--select option--</option>
                                        <option value="Personal Loan">Personal Loan</option>
                                        <option value="Business Loan">Business Loan</option>
                                        <option value="Mortgage Loan">Mortgage Loan</option>
                                        <option value="MicroFinance Loan">MicroFinance Loan</option>
                                        <option value="Student Loan">Student Loan</option>
                                        <option value="Construction Loan">Construction Loan</option>
                                        <option value="Green Loan">Green Loan</option>
                                        <option value="Medical Loan">Medical Loan</option>
                                        <option value="Startup Loan">Startup Loan</option>
                                        <option value="Agricultural Loan">Agricultural Loan</option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
                                    <td><label for="interestRate">Interest Rate</label></td>
                                    <td><input type="text" id="interestRate" name="interestRate"></td>
                                </tr>
                                <tr>
                                    <td><label for="maxDuration">Maximum Duration <br>(in months)</label></td>
                                    <td><input type="text" id="maxDuration" name="maxDuration"></td>
                                </tr>
                                <tr class="submit-action">
                                    <td><button type="submit" name="submit">SUBMIT</button></td>
                            </tr>
                            </table>
                        </form>
                    </div>
                    
                </div>

                <!-- Loan History -->
                <div id="loanHistory" class="margin">
                    <h1>Loan History</h1>
                    <p>View your past loans and their status.</p>
                </div>

                <!-- Financial Summary -->
                <div id="financialSummary" class="margin">
                    <h1>Financial Summary</h1>
                    <p>Charts and graphs summarizing your financial data.</p>
                </div>

                <!-- Notifications -->
                <div id="notifications" class="margin">
                    <h1>Notifications</h1>
                    <p>View your alerts and reminders.</p>
                </div>

                <!-- Profile -->
                <div id="profile" class="margin">
                    <h1>Profile</h1>
                    <p>Update your personal information and settings.</p>
                </div>

                <!-- Feedback -->
                <div id="feedback" class="margin">
                    <h1>Feedback</h1>
                    <p>Share your feedback with us.</p>
                </div>

                <!-- Contact Support -->
                <div id="contactSupport" class="margin">
                    <h1>Contact Support</h1>
                    <p>Reach out to our support team for assistance.</p>
                </div>

                <!-- Dashboard -->
                <div id="dashboard" >
                    <div class="dash-header">
                        <div>
                            <h1>Lender's Dashboard</h1>
                            <p>Overview of your loans and financial status.</p>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                <!-- Greeting based on time -->
                                <?php
                                    // Set the timezone to Nairobi, Kenya
                                    date_default_timezone_set('Africa/Nairobi');
                                    $currentTime = date("H");
                                    $message = "";

                                    if ($currentTime < 12) {
                                        $message = "good morning,";
                                    } elseif ($currentTime < 18) {
                                        $message = "good afternoon,";
                                    } else {
                                        $message = "good evening,";
                                    }

                                    echo "<span>$message</span>";
                                ?>
                                <!-- Display the user's name -->
                                <span class="span"><?php echo $_SESSION['user_name']; ?>!</span>
                                </code>
                            </p>
                        </div>
                    </div>
                    <div class="metrics">
                        <div>
                            <p>Total Loans Disbursed</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Total Loan Amounts Disbursed</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Total Active Loans</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Average Interest Rate</p>
                            <span class="span-2">0</span>
                        </div>
                    </div>
                    <div class="visuals">
                        <div>
                        <canvas id="barChart" width="400" height="200"></canvas>
                        <p>dummy bar graph</p>
                        </div>
                        <div>
                             <canvas id="pieChart" width="400" height="200"></canvas>
                             <p>dummy pie chart</p>
                            
                        </div>
                    

 
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="../js/validinput.js"></script>

    <script>
        // Function to hide the loan message after 2 seconds
        function hideLoanMessage() {
            const loanMessage = document.getElementById('loan-message');
            if (loanMessage) {
                setTimeout(() => {
                    loanMessage.style.opacity = '0'; // Fade out the message
                    setTimeout(() => {
                        loanMessage.style.display = 'none'; // Hide the message after fading out
                    }, 500); // Wait for the transition to complete
                }, 1500); // 1500 milliseconds = 1.5 seconds
            }
        }

        // Call the function when the page loads
        window.onload = hideLoanMessage;
    </script>
    <script>
        
        //dummy bar graph
        // Get the canvas element and context
        const barCanvas = document.getElementById('barChart');
        const barCtx = barCanvas.getContext('2d');

        // Data for the bar chart
        const data = [30, 60, 90, 120, 150, 180];
        const labels = ['A', 'B', 'C', 'D', 'E', 'F'];
        const barWidth = 40;
        const barSpacing = 20;
        const startX = 50;
        const startY = barCanvas.height - 50;

        // Draw the bars
        data.forEach((value, index) => {
        const x = startX + (barWidth + barSpacing) * index;
        const y = startY - value;
        barCtx.fillStyle = 'rgba(75, 192, 192, 0.6)';
        barCtx.fillRect(x, y, barWidth, value);
        });

        // Draw the X-axis labels
        barCtx.fillStyle = 'whitesmoke';
        labels.forEach((label, index) => {
        const x = startX + (barWidth + barSpacing) * index + barWidth / 2;
        barCtx.fillText(label, x, startY + 20);
        });

        // Draw the Y-axis
        barCtx.beginPath();
        barCtx.moveTo(startX - 10, startY);
        barCtx.lineTo(startX - 10, 20);
        barCtx.stroke();

        // dummy pie chart
        // Get the canvas element and context
        const pieCanvas = document.getElementById('pieChart');
        const pieCtx = pieCanvas.getContext('2d');
        
        // Data for the pie chart
        const pieData = [30, 20, 15, 10, 25]; // Values for each slice
        const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];
        const total = pieData.reduce((sum, value) => sum + value, 0);
        
        // Draw the pie chart
        let startAngle = 0;
        const centerX = pieCanvas.width / 2;
        const centerY = pieCanvas.height / 2;
        const radius = 80;
        
        pieData.forEach((value, index) => {
        const sliceAngle = (2 * Math.PI * value) / total;
        pieCtx.beginPath();
        pieCtx.moveTo(centerX, centerY);
        pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        pieCtx.closePath();
        pieCtx.fillStyle = colors[index];
        pieCtx.fill();
        startAngle += sliceAngle;
        });
        
        // Optional: Add a legend
        pieCtx.font = '12px Arial';
        pieData.forEach((value, index) => {
        pieCtx.fillStyle = colors[index];
        pieCtx.fillRect(centerX + radius + 20, 20 + index * 20, 15, 15);
        pieCtx.fillStyle = 'whitesmoke';
        pieCtx.fillText(`${Math.round((value / total) * 100)}%`, centerX + radius + 40, 32 + index * 20);
        });
    </script>
</body>
</html>