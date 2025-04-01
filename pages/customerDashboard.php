<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: signin.html");
    exit();
}

// Database connection
$myconn = mysqli_connect('localhost', 'root', 'figureitout', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch user data from the database (if needed)
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
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main>
        <div class="header">
            <div class="header2">
                <div class="logo">LMS</div>
            </div>
            <div class="header3">
                <ul>
                    <li><a href="logoutbtn.php" id="logout"class="no-col">Log Out</a></li>
                </ul>
            </div>
        </div>
        <div class="customer-content">
            <div class="nav">
                <ul class="nav-split">
                    <div class="top">
                        <li><a href="#dashboard">Dashboard</a></li>
                        <li><a href="#applyLoan">Apply for Loan</a></li>
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

            <!-- Dynamic display enabled by CSS -->
            <div class="display">
                <!-- Apply for Loan -->
                <div id="applyLoan" class="margin">
                    <div>
                        <h1>Apply for Loan</h1>
                        <p>Find a suitable Lender and fill out the form to apply for a new loan.</p>
                    </div>
                    <div class="loan-right">
                        <div class="loan-filter">
                            <p style="color: whitesmoke; font-weight: 900;">Filters</p>
                            <form method="GET" action="">
                                <div>
                                    <ul>
                                    <li>
                                        <p>Loan Type</p>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="personal" id="personal">
                                        <label for="personal">Personal</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="business" id="business">
                                        <label for="business">Business</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="mortgage" id="mortgage">
                                        <label for="mortgage">Mortgage</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="microfinance" id="microfinance">
                                        <label for="microfinance">MicroFinance</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="student" id="student">
                                        <label for="student">Student</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="construction" id="construction">
                                        <label for="construction">Construction</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="green" id="green">
                                        <label for="green">Green</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="medical" id="medical">
                                        <label for="medical">Medical</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="startup" id="startup">
                                        <label for="startup">Startup</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="loan_type" value="agricultural" id="agricultural">
                                        <label for="agricultural">Agricultural</label>
                                        </span>
                                    </li>
                                    <li>
                                        <p>Amount Range (sh)</p>
                                        <span class="range">
                                        <div>
                                            <input type="number" name="min_amount" placeholder="500" min="500" >
                                            <span>-</span>
                                            <input type="number" name="max_amount" placeholder="100000" min="500" >
                                        </div>
                                        <div>
                                            <div class="quick-amounts">
                                            <button type="button" data-min="1000" data-max="5000">1k-5k</button>
                                            <button type="button" data-min="5000" data-max="20000">5k-20k</button>
                                            <button type="button" data-min="20000" data-max="50000">20k-50k</button>
                                            </div>
                                        </div>
                                        </span>
                                    </li>
                                    <li>
                                        <p>Interest Rates</p>
                                        <span>
                                        <input type="checkbox" name="interest_range[]" value="0-5" id="0-5">
                                        <label for="0-5">0 - 5%</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="interest_range[]" value="5-10" id="5-10">
                                        <label for="5-10">5 - 10%</label>
                                        </span>
                                        <span>
                                        <input type="checkbox" name="interest_range[]" value="10+" id="10+">
                                        <label for="10+">10% +</label>
                                        </span>
                                    </li>
                                    <li>
                                        <button class="sub" type="submit">Apply Filters</button>
                                        <button class="res" type="reset">Reset</button>
                                    </li>
                                    </ul>
                                </div>
                                </form>
                        </div>
                        <div class="loan-lenders">
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
                            <div class="lender">
                                <div>
                                    <span>Lender Name</span>
                                    <span>Loan Type</span>
                                    <span>Interest Rate</span>
                                    <span>Maximum Duration</span>
                                    <span>Maximum Amount</span>
                                    <a href="alert.html" >More info</a>
                                </div>
                                <div>
                                    <button>Apply Now</button>
                                </div>
                                
                            </div>
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
                            <h1>Customer's Dashboard</h1>
                            <p>Overview of your loans and financial status.</p>
                        </div>
                        <div class="greeting">
                            <p>
                                <code>
                                <!-- Greeting based on time -->
                                <?php
                                    // Set the timezone to Nairobi, Kenya
                                    date_default_timezone_set('Africa/Nairobi');
                                    // 24-hour format
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
                            <p>Active Loans</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Loan Amounts</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Interest Rates</p>
                            <span class="span-2">0</span>
                        </div>
                        <div>
                            <p>Outstanding Balance</p>
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

<!-- Range -->
    <script>
        document.querySelectorAll('.quick-amounts button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('[name="min_amount"]').value = btn.dataset.min;
            document.querySelector('[name="max_amount"]').value = btn.dataset.max;
        });
        });
    </script>

</body>
</html>