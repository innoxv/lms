<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

$userId = $_SESSION['user_id'];

// Fetch user data from the database
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name']; // Update the session with the latest data
} else {
    $_SESSION['user_name'] = "Guest";
}

// Fetch lender_id from the lenders table
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'";
$lenderResult = mysqli_query($myconn, $lenderQuery);

if (mysqli_num_rows($lenderResult) > 0) {
    $lender = mysqli_fetch_assoc($lenderResult);
    $_SESSION['lender_id'] = $lender['lender_id']; // Store lender_id in the session
    
} else {
    $_SESSION['loan_message'] = "You are not registered as a lender.";
    header("Location: lenderDashboard.php");
    exit();
}
// Declaring Lender ID from session
$lender_id = $_SESSION['lender_id'];

// Fetch total number of loans created by the lender
$totalLoansQuery = "SELECT COUNT(*) AS total_loans FROM loans WHERE lender_id = '$lender_id'";
$totalLoansResult = mysqli_query($myconn, $totalLoansQuery);
$totalLoansData = mysqli_fetch_assoc($totalLoansResult);
$totalLoans = $totalLoansData['total_loans'];

// Fetch average interest rate of loans created by the lender
$avgInterestQuery = "SELECT AVG(interest_rate) AS avg_interest_rate FROM loans WHERE lender_id = '$lender_id'";
$avgInterestResult = mysqli_query($myconn, $avgInterestQuery);
$avgInterestData = mysqli_fetch_assoc($avgInterestResult);
$avgInterestRate = $avgInterestData['avg_interest_rate'];

// Format the average interest rate to 2 decimal places
$avgInterestRate = number_format($avgInterestRate, 2);

// Update the average_interest_rate in the lenders table
$updateAvgInterestQuery = "UPDATE lenders SET average_interest_rate = '$avgInterestRate' WHERE lender_id = '$lender_id'";
mysqli_query($myconn, $updateAvgInterestQuery);

// Define all possible loan types
$allLoanTypes = [
    "Personal Loan",
    "Business Loan",
    "Mortgage Loan",
    "MicroFinance Loan",
    "Student Loan",
    "Construction Loan",
    "Green Loan",
    "Medical Loan",
    "Startup Loan",
    "Agricultural Loan"
];


// Fetch loan type information for the logged-in lender
$loanQuery = "SELECT loan_type, COUNT(*) AS loan_count 
              FROM loans 
              WHERE lender_id = '$lender_id' 
              GROUP BY loan_type";
$loanResult = mysqli_query($myconn, $loanQuery);
$loanData = mysqli_fetch_all($loanResult, MYSQLI_ASSOC);

// Initialize an array to hold loan counts for all types
$loanCounts = array_fill_keys($allLoanTypes, 0);

// Populate the loan counts with data from the database
foreach ($loanData as $loan) {
    $loanCounts[$loan['loan_type']] = (int)$loan['loan_count'];
}

// Fetch loan slot information for the logged-in lender
$slotQuery = "SELECT loan_type, COUNT(*) AS total_slots, SUM(customer_id IS NULL) AS available_slots 
              FROM loans 
              WHERE lender_id = '$lender_id' 
              GROUP BY loan_type";
$slotResult = mysqli_query($myconn, $slotQuery);
$slotData = mysqli_fetch_all($slotResult, MYSQLI_ASSOC);

// Check if there's a loan message to display
if (isset($_SESSION['loan_message'])) {
    $loan_message = $_SESSION['loan_message'];
    unset($_SESSION['loan_message']); // Clear the message after displaying it
} else {
    $loan_message = null;
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
                    <div class="loan-split">
                        <div class="loan-split-left">
                            <div>
                                <h1>Create a Loan Offer</h1>
                                <p>Fill out the form to create a new loan offer.</p>
                            </div>         
                            <div>
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
                        <div class="loan-slot">
                            <h3>Loan Slot Information</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Loan Type</th>
                                        <th>Total Slots</th>
                                        <th>Available Slots</th>
                                        <th class="mid">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slotData as $slot): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($slot['loan_type']); ?></td>
                                            <td class="mid"><?php echo htmlspecialchars($slot['total_slots']); ?></td>
                                            <td class="mid"><?php echo htmlspecialchars($slot['available_slots']); ?></td>
                                            <td>
                                                <!-- Add Slot Form -->
                                                <form action="addSlot.php" method="post" class="act">
                                                    <input type="hidden" name="loan_type" value="<?php echo htmlspecialchars($slot['loan_type']); ?>">
                                                    <button type="submit">Add Slot</button>
                                                </form>
                                                <!-- Delete Slot Form -->
                                                <form action="deleteSlot.php" method="post" class="act">
                                                    <input type="hidden" name="loan_type" value="<?php echo htmlspecialchars($slot['loan_type']); ?>">
                                                    <button type="submit">Delete Slot</button>
                                                </form>
                                                <!-- Delete Loan Type Form -->
                                                <form action="deleteLoanType.php" method="post" class="del">
                                                    <input type="hidden" name="loan_type" value="<?php echo htmlspecialchars($slot['loan_type']); ?>">
                                                    <button type="submit">Delete Loan Type</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
                            <p>Total Loans Created</p>
                            <span class="span-2"><?php echo $totalLoans; ?></span>
                        </div>
                        <div>
                            <p>Total Amount Disbursed</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Total Active Loans</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Average Interest Rate</p>
                            <span class="span-2"><?php echo $avgInterestRate; ?>%</span>
                        </div>
                    </div>
                    <div class="visuals">
                        <div>
                        <p>Number of loans created per loan type</p>
                        <canvas id="barChart" width="800" height="300"></canvas>
                        
                        </div>
                        <div>
                            <p>Loan Status (dummy data)</p>
                            <canvas id="pieChart" width="400" height="200"></canvas>
                             
                            
                        </div>
                    

 
                    </div>
                </div>
            </div>
        </div>


                <!-- Copyright -->
                <div class="copyright">
                    <p><?php
                        $currentYear = date("Y");
                        echo "&copy; $currentYear";
                        ?>
                        <a href="mailto:innocentmukabwa@gmail.com">dev</a>
                    </p>
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
                    }, 700); // Wait for the transition to complete
                }, 2000); // 2000 milliseconds = 2seconds
            }
        }

        // Call the function when the page loads
        window.onload = hideLoanMessage;
    </script>

    <script>
        // Pass the loan counts from PHP to JavaScript
        const loanCounts = <?php echo json_encode($loanCounts); ?>;

        // Get the canvas element and context
        const barCanvas = document.getElementById('barChart');
        const barCtx = barCanvas.getContext('2d');

        // Define all loan types
        const loanTypes = Object.keys(loanCounts);
        const counts = Object.values(loanCounts);

        // Abbreviate labels (first 2 letters)
        const abbreviatedLabels = loanTypes.map(label => label.substring(0, 2).toUpperCase());

        // Define chart dimensions
        const barWidth = 30; // Width of each bar
        const barSpacing = 20; // Spacing between bars
        const startX = 50; // Starting X position for the first bar (reduced margin)
        const startY = barCanvas.height - 80; // Starting Y position (bottom of the chart)
        const axisPadding = 5; // Reduced padding for the Y-axis

        // Calculate the maximum value for the Y-axis scale
        const maxCount = Math.max(...counts);
        const yAxisMax = Math.ceil(maxCount / 5) * 5; // Round up to the nearest multiple of 5

        // Draw the bars
        counts.forEach((value, index) => {
            const x = startX + (barWidth + barSpacing) * index;
            const y = startY - (value / yAxisMax) * (startY - 20); // Scale bar height to fit Y-axis
            barCtx.fillStyle = '#74C0FC'; // Bar color
            barCtx.fillRect(x, y, barWidth, startY - y); // Draw the bar
        });

        // Draw the X-axis labels (abbreviated)
        barCtx.fillStyle = 'white'; // Label color
        barCtx.font = '14px Trebuchet MS'; // Label font
        barCtx.textAlign = 'center'; // Center-align the text
        abbreviatedLabels.forEach((label, index) => {
            const x = startX + (barWidth + barSpacing) * index + barWidth / 2;
            barCtx.fillText(label, x, startY + 20); // Draw the label below the bar
        });

        // Draw the Y-axis
        barCtx.beginPath();
        barCtx.moveTo(startX - axisPadding, startY);
        barCtx.lineTo(startX - axisPadding, 20);
        barCtx.strokeStyle = 'white'; // Y-axis color
        barCtx.stroke();

        // Draw Y-axis labels and grid lines (steps of 2 for better readability)
        barCtx.fillStyle = 'whitesmoke';
        barCtx.font = '14px Trebuchet MS';
        barCtx.textAlign = 'right'; // Right-align Y-axis labels
        barCtx.strokeStyle = 'rgba(255, 255, 255, 0.2)'; // Grid line color

        for (let i = 0; i <= yAxisMax; i += 2) { // Steps of 2 for better readability
            const y = startY - (i / yAxisMax) * (startY - 20); // Scale Y-axis labels

            // Draw Y-axis labels
            barCtx.fillText(i, startX - axisPadding - 5, y + 5);

            // Draw horizontal grid lines
            barCtx.beginPath();
            barCtx.moveTo(startX - axisPadding, y);
            barCtx.lineTo(barCanvas.width - 250, y); // Extend grid line across the chart
            barCtx.stroke();
        }

        // Draw the legend (key) on the side
        const legendX = barCanvas.width - 250; // X position for the legend
        const legendY = 40; // Y position for the legend
        const legendSpacing = 20; // Spacing between legend items

        barCtx.font = '16px Trebuchet MS';
        barCtx.textAlign = 'left'; // Left-align legend text
        loanTypes.forEach((label, index) => {
            

            // Draw the label text
            barCtx.fillStyle = 'lightgray';
            barCtx.fillText(`${abbreviatedLabels[index]}: ${label}`, legendX + 20, legendY + index * legendSpacing + 12);
        });
    </script>



    <!-- Dummy Data - Should actually analyze data from the database -->
    <script>
    // Get the canvas element and context
    const pieCanvas = document.getElementById('pieChart');
    const pieCtx = pieCanvas.getContext('2d');

    // Define dummy data
    const pieData = {
        labels: ['Pending', 'Approved', 'Rejected'],
        values: [50, 30, 20], // Percentages for each status
    };

    // Define colors for each status
    const statusColors = {
        'Pending': 'white', 
        'Approved': 'lightgreen', 
        'Rejected': 'tomato',
    };

    // Extract labels and values from pieData
    const labels = pieData.labels;
    const values = pieData.values;

    // Calculate the total for percentage calculations
    const total = values.reduce((sum, value) => sum + value, 0);

    // Draw the pie chart
    let startAngle = 0;
    const centerX = pieCanvas.width / 3;
    const centerY = pieCanvas.height / 2;
    const radius = 80;

    values.forEach((value, index) => {
        const sliceAngle = (2 * Math.PI * value) / total;
        pieCtx.beginPath();
        pieCtx.moveTo(centerX, centerY);
        pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        pieCtx.closePath();
        pieCtx.fillStyle = statusColors[labels[index]]; // Use color based on status
        pieCtx.fill();
        startAngle += sliceAngle;
    });

    // Add a legend
    pieCtx.font = '14px Trebuchet MS';
    values.forEach((value, index) => {
        const percentage = ((value / total) * 100).toFixed(2);
        pieCtx.fillStyle = statusColors[labels[index]];
        pieCtx.fillRect(centerX + radius + 20, 20 + index * 20, 15, 15);
        pieCtx.fillStyle = 'whitesmoke';
        pieCtx.fillText(`${labels[index]}: ${value}%`, centerX + radius + 40, 32 + index * 20);
    });
</script>
</body>
</html>