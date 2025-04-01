<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.html");
    exit();
}

$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');
$userId = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT user_name FROM users WHERE user_id = '$userId'";
$result = mysqli_query($myconn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_name'] = $user['user_name'];
} else {
    $_SESSION['user_name'] = "Guest";
}

// Fetch lender_id
$lenderQuery = "SELECT lender_id FROM lenders WHERE user_id = '$userId'";
$lenderResult = mysqli_query($myconn, $lenderQuery);

if (mysqli_num_rows($lenderResult) > 0) {
    $lender = mysqli_fetch_assoc($lenderResult);
    $_SESSION['lender_id'] = $lender['lender_id'];
} else {
    $_SESSION['loan_message'] = "You are not registered as a lender.";
    header("Location: lenderDashboard.php");
    exit();
}

$lender_id = $_SESSION['lender_id'];

// Get loan products count
$totalProductsQuery = "SELECT COUNT(*) AS total_products FROM loan_products WHERE lender_id = '$lender_id'";
$totalProductsResult = mysqli_query($myconn, $totalProductsQuery);
$totalProductsData = mysqli_fetch_assoc($totalProductsResult);
$totalProducts = $totalProductsData['total_products'];

// Get average interest rate from loan products
$avgInterestQuery = "SELECT AVG(interest_rate) AS avg_interest_rate FROM loan_products WHERE lender_id = '$lender_id'";
$avgInterestResult = mysqli_query($myconn, $avgInterestQuery);
$avgInterestData = mysqli_fetch_assoc($avgInterestResult);
$avgInterestRate = number_format($avgInterestData['avg_interest_rate'], 2);

// Get total loan capacity
$capacityQuery = "SELECT SUM(max_amount) AS total_capacity FROM loan_products WHERE lender_id = '$lender_id'";
$capacityResult = mysqli_query($myconn, $capacityQuery);
$capacityData = mysqli_fetch_assoc($capacityResult);
$totalCapacity = number_format($capacityData['total_capacity']);

// Get total active loans (approved but not completed)
$activeLoansQuery = "SELECT COUNT(*) AS active_loans FROM loans 
                    WHERE lender_id = '$lender_id' 
                    AND status IN ('approved', 'disbursed', 'active')";
$activeLoansResult = mysqli_query($myconn, $activeLoansQuery);
$activeLoansData = mysqli_fetch_assoc($activeLoansResult);
$activeLoans = $activeLoansData['active_loans'];

// Get total amount disbursed
$disbursedQuery = "SELECT SUM(amount) AS total_disbursed FROM loans 
                  WHERE lender_id = '$lender_id' 
                  AND status IN ('disbursed', 'active', 'completed')";
$disbursedResult = mysqli_query($myconn, $disbursedQuery);
$disbursedData = mysqli_fetch_assoc($disbursedResult);
$totalDisbursed = number_format($disbursedData['total_disbursed']);

// Get loan products for display
$productsQuery = "SELECT * FROM loan_products WHERE lender_id = '$lender_id'";
$productsResult = mysqli_query($myconn, $productsQuery);
$productsData = mysqli_fetch_all($productsResult, MYSQLI_ASSOC);

// Prepare data for charts
$allLoanTypes = [
    "Personal Loan", "Business Loan", "Mortgage Loan", 
    "MicroFinance Loan", "Student Loan", "Construction Loan",
    "Green Loan", "Medical Loan", "Startup Loan", "Agricultural Loan"
];

$loanCounts = array_fill_keys($allLoanTypes, 0);
foreach ($productsData as $product) {
    $loanCounts[$product['loan_type']] = 1;
}

// Get loan status distribution for pie chart
$statusQuery = "SELECT status, COUNT(*) as count FROM loans 
               WHERE lender_id = '$lender_id' 
               GROUP BY status";
$statusResult = mysqli_query($myconn, $statusQuery);
$statusData = mysqli_fetch_all($statusResult, MYSQLI_ASSOC);

// Check for messages
if (isset($_SESSION['loan_message'])) {
    $loan_message = $_SESSION['loan_message'];
    unset($_SESSION['loan_message']);
} else {
    $loan_message = null;
}

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
                        <div id="loan-message" class="loan-message">
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
                                <div id="error" style="color: tomato; font-weight:700"></div>
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
                                            <td><label for="maxAmount">Maximum Amount <br>(in shillings)</label></td>
                                            <td><input type="text" id="maxAmount" name="maxAmount"></td>
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
                            <h3>Loan Products Information</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Loan Type</th>
                                        <th class="mid">Interest Rate</th>
                                        <th class="mid">Max Amount</th>
                                        <th class="mid">Max Duration</th>
                                        <th class="mid">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($productsData as $product): ?>
                                    <tr>        
                                        <td><?php echo htmlspecialchars($product['loan_type']); ?></td>
                                        <td class="mid"><?php echo htmlspecialchars($product['interest_rate']); ?>%</td>
                                        <td class="mid"><?php echo number_format(htmlspecialchars($product['max_amount'])); ?></td>
                                        <td class="mid"><?php echo htmlspecialchars($product['max_duration']); ?> months</td>
                                        <td class="action-buttons">
                                            <!-- Edit Button-->
                                            <button class="act edit-btn" 
                                                    data-product-id="<?= $product['product_id'] ?>" 
                                                    data-loan-type="<?= htmlspecialchars($product['loan_type']) ?>" 
                                                    data-interest-rate="<?= $product['interest_rate'] ?>" 
                                                    data-max-amount="<?= $product['max_amount'] ?>" 
                                                    data-max-duration="<?= $product['max_duration'] ?>">
                                                Edit
                                            </button>
                                            
                                            <!-- Delete Form-->
                                            <form action="deleteLoan.php" method="post" class="del-form">
                                                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                                <button type="submit" class="del-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>   
                </div>

                <!-- Edit Loan Functionality -->
                <div class="popup-overlay" id="popupOverlay"></div>

                <div class="edit-popup" id="editPopup">
                    <h3>Edit Loan Product</h3>
                    <form class="edit-form" id="editForm" method="post" action="editLoan.php">
                        <input type="hidden" name="product_id" id="editProductId">
                        
                        <div class="ltype">
                           <div><label for="editLoanType">Loan Type:</label></div> 
                            <div><span id="editLoanType"></span></div>
                        </div>
                        
                        <div>
                            <label for="editInterestRate">Interest Rate (%):</label>
                            <input type="number" step="0.01" name="interest_rate" id="editInterestRate">
                        </div>
                        
                        <div>
                            <label for="editMaxAmount">Max Amount (shillings):</label>
                            <input type="number" name="max_amount" id="editMaxAmount">
                        </div>
                        
                        <div>
                            <label for="editMaxDuration">Max Duration (months):</label>
                            <input type="number" name="max_duration" id="editMaxDuration">
                        </div>
                        
                        <div class="edit-act">
                            <button type="button" class="del" onclick="hideEditPopup()">Cancel</button>
                            <button type="submit" class="act">Save Changes</button>
                        </div>
                    </form>
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
                                    // Set the timezone to local Nairobi, Kenya
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
                            <p>Loan Products Offered</p>
                            <span class="span-2"><?php echo $totalProducts; ?></span>
                        </div>
                        <div>
                            <p>Total Loan Capacity</p>
                            <span class="span-2"><?php echo $totalCapacity; ?></span>
                        </div>
                        <div>
                            <p>Active Loans</p>
                            <span class="span-2"><?php echo $activeLoans; ?></span>
                        </div>
                        <div>
                            <p>Amount Disbursed</p>
                            <span class="span-2"><?php echo $totalDisbursed; ?></span>
                        </div>
                        <div>
                            <p>Avg Interest Rate</p>
                            <div class="span-3"><span class="avg"><?php echo $avgInterestRate; ?></span><span class="percentage">%</span></div>
                        </div>
                    </div>
                    <div class="visuals">
                        <div>
                        <p>Number of Active Loans per Loan Type (in production)</p>
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

    <!-- Pop Up Overlay -->
    <script>
    // Store original values
    let originalValues = {};

    // Show popup with product data
    function showEditPopup(productId, loanType, interestRate, maxAmount, maxDuration) {
        // Store original values
        originalValues = {
            interest_rate: interestRate,
            max_amount: maxAmount,
            max_duration: maxDuration
        };

        // Set form values
        document.getElementById('editProductId').value = productId;
        document.getElementById('editLoanType').textContent = loanType; // Display only
        document.getElementById('editInterestRate').value = interestRate;
        document.getElementById('editMaxAmount').value = maxAmount;
        document.getElementById('editMaxDuration').value = maxDuration;
        
        // Show popup
        document.getElementById('popupOverlay').style.display = 'block';
        document.getElementById('editPopup').style.display = 'block';
    }
    
    // Hide popup
    function hideEditPopup() {
        document.getElementById('popupOverlay').style.display = 'none';
        document.getElementById('editPopup').style.display = 'none';
    }
    
    // Handle form submission
    function handleFormSubmit(e) {
        e.preventDefault();
        
        // Create hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'editLoan.php';
        
        // Add product ID
        const productIdInput = document.createElement('input');
        productIdInput.type = 'hidden';
        productIdInput.name = 'product_id';
        productIdInput.value = document.getElementById('editProductId').value;
        form.appendChild(productIdInput);
        
        // Only add changed fields
        const currentInterest = document.getElementById('editInterestRate').value;
        if (currentInterest !== originalValues.interest_rate) {
            const interestInput = document.createElement('input');
            interestInput.type = 'hidden';
            interestInput.name = 'interest_rate';
            interestInput.value = currentInterest;
            form.appendChild(interestInput);
        }
        
        const currentAmount = document.getElementById('editMaxAmount').value;
        if (currentAmount !== originalValues.max_amount) {
            const amountInput = document.createElement('input');
            amountInput.type = 'hidden';
            amountInput.name = 'max_amount';
            amountInput.value = currentAmount;
            form.appendChild(amountInput);
        }
        
        const currentDuration = document.getElementById('editMaxDuration').value;
        if (currentDuration !== originalValues.max_duration) {
            const durationInput = document.createElement('input');
            durationInput.type = 'hidden';
            durationInput.name = 'max_duration';
            durationInput.value = currentDuration;
            form.appendChild(durationInput);
        }
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    }
    
    // Initialize edit buttons and form
    document.addEventListener('DOMContentLoaded', function() {
    // Attach to only edit buttons (not all .act buttons)
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default behavior
            showEditPopup(
                this.dataset.productId,
                this.dataset.loanType,
                this.dataset.interestRate,
                this.dataset.maxAmount,
                this.dataset.maxDuration
            );
        });
    });

        // Close when clicking overlay
        document.getElementById('popupOverlay').addEventListener('click', hideEditPopup);
        
        // Attach form submission handler
        document.getElementById('editForm').addEventListener('submit', handleFormSubmit);
    });
</script>

    <!-- barchart -->
     
    <script>
        // Pass the loan counts from PHP to JavaScript
        // uncomment getting data from PHP
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


    <!-- pie chart -->
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