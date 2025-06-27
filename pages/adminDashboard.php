<?php
require_once 'adminDashboardData.php'; // has the dashboard data
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
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
            <!-- reporting -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="loan-message" id="admin-message">  <!-- loan message class is for styling    -->
                    <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
                    <?php unset($_SESSION['admin_message']); ?>
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
                        <li><a href="#riskAssessment">Application Review</a></li>
                        <li><a href="#viewUsers">View Users</a></li>
                        <li><a href="#addUsers">Add New User</a></li>
                        <li><a href="#activityLogs">Activity Logs</a></li>
                        <!-- <li class="disabled-link"><a href="#notifications">Notifications</a></li>  this is still in production -->
                        <li><a href="#profile">Profile</a></li>
                    </div>
                    <div class="bottom">
                    <li><a href="#contactSupport">Help</a></li>
                                <!-- Copyright -->
                                <div class="copyright">
                                    <p><?php
                                        $currentYear = date("Y");
                                        echo "&copy; $currentYear";
                                        ?>
                                    </p>
                                </div>
                    </div>
                </ul>
            </div>

            <!-- Dynamic display enabled by CSS -->
            <div class="display">
                            
            <!-- Application Review -->
            <div id="riskAssessment" class="margin">
                <h1>Application Review</h1>
                <p>Assess submitted loan applications to approve for lender processing or reject.</p>
            
                <!-- Messages -->
                <?php if (isset($_SESSION['admin_message'])): ?>
                    <div class="alert <?= $_SESSION['admin_message_type'] ?? 'info' ?>">
                        <?= htmlspecialchars($_SESSION['admin_message']) ?>
                    </div>
                    <?php 
                    unset($_SESSION['admin_message']);
                    unset($_SESSION['admin_message_type']);
                    ?>
                <?php endif; ?>
            
                <div class="risk-assessment-container">
                    <div class="active-loans-table">
                        <?php
                        // Include risk assessment logic
                        include 'riskAssessment.php';
            
                        // Fetch pending loans
                        $_SESSION['pending_loans'] = fetchAllLoans($myconn);
                        $pendingLoans = $_SESSION['pending_loans'];
                        ?>
                        <?php if (empty($pendingLoans)): ?>
                            <div style="color: tomato; font-size: 1.2em; margin-top: .3em;">No loan applications found</div>
                        <?php else: ?>
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>Customer ID</th>
                                        <th>Customer Name</th>
                                        <th>Amount</th>
                                        <th>Duration</th>
                                        <th>Collateral Value</th>
                                        <th>Collateral Description</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingLoans as $loan): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($loan['loan_id']) ?></td>
                                            <td><?= htmlspecialchars($loan['customer_id']) ?></td>
                                            <td><?php echo htmlspecialchars($loan['customer_name'] ?? 'Unknown Customer'); ?></td>
                                            <td><?= number_format($loan['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($loan['duration']) ?></td>
                                            <td><?= number_format($loan['collateral_value'], 2) ?></td>
                                            <td><?= htmlspecialchars($loan['collateral_description']) ?></td>
                                            <td>
                                                <button class="view-btn" onclick="openLoanPopup(<?= htmlspecialchars(json_encode($loan)) ?>)">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            
                <!-- Popup for Loan Details -->
                <div id="loanPopup" class="popup-overlay3" style="display: none;">
                    <div class="popup-content3">
                        <span class="close-btn" onclick="closeLoanPopup()">&times;</span>
                        <h2>Loan Details</h2>
                        <div class="loan-details-grid">
                            <div class="detail-row">
                                <span class="detail-label">Loan ID:</span>
                                <span class="detail-value" id="popup-loan-id"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Customer ID:</span>
                                <span class="detail-value" id="popup-customer-id"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Customer Name:</span>
                                <span class="detail-value" id="popup-customer-name"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount:</span>
                                <span class="detail-value" id="popup-amount"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Duration:</span>
                                <span class="detail-value" id="popup-duration"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Collateral Value:</span>
                                <span class="detail-value" id="popup-collateral-value"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Collateral Description:</span>
                                <span class="detail-value" id="popup-collateral-desc"></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Collateral Image:</span> <br>
                            </div>
                            <img id="popup-collateral-image" src="" alt="Collateral Image" style="width: 100%; max-height: 200px;">
                            
                            <div class="risk-action-buttons">
                                <div>
                                    <form class="risk" action="riskAssessment.php" method="post" style="display: inline;">
                                        <input type="hidden" name="loan_id" id="popup-loan-id-input">
                                        <button type="submit" name="approve" class="risk-approve-btn">Approve</button>
                                    </form>
                                </div>
                                <div>
                                    <form class="risk" action="riskAssessment.php" method="post" style="display: inline;">
                                        <input type="hidden" name="loan_id" id="popup-loan-id-input-reject">
                                        <button type="submit" name="reject" class="risk-reject-btn">Reject</button>
                                    </form>
                                </div>
                                
                                
                            </div>
                        </div>

                        
                    </div>
                </div>
            </div>

            <!-- View Users -->
            <div id="viewUsers" class="margin">
                <h1>View and manage Users</h1>
                <p>View all the users and perform necessary activities.</p>
                
                <!-- Role Filter Form -->
                <div class="user-filter">
                    <form method="get" action="#viewUsers">
                    <div class="filter-row">

                        <div class="filter-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role" onchange="this.form.submit()">  <!-- this submits the form on select -->
                            <option value="">All Roles</option>
                            <option value="Admin" <?= $roleFilter==='Admin'?'selected':'' ?>>Admin</option>
                            <option value="Lender" <?= $roleFilter==='Lender'?'selected':'' ?>>Lender</option>
                            <option value="Customer" <?= $roleFilter==='Customer'?'selected':'' ?>>Customer</option>
                        </select>
                        </div>
                        <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status2" onchange="this.form.submit()">  <!-- this submits the form on select -->
                            <option value="">All Statuses</option>
                            <option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Active</option>
                            <option value="restricted" <?= $statusFilter==='restricted'?'selected':'' ?>>Restricted</option>
                            <option value="blocked" <?= $statusFilter==='blocked'?'selected':'' ?>>Blocked</option>
                        </select>
                        </div>
                        <a href="adminDashboard.php#viewUsers"><button type="button" class="reset">Reset</button></a>
                    </div>
                    </form>
                </div>
                

                <?php if (!empty($users)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Restrictions</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                                        <td><?= htmlspecialchars($user['user_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                        <td><?= htmlspecialchars($user['role']) ?></td>
                                        <td class="status-<?= 
                                            strpos($user['status'] ?? 'active', 'restricted') !== false ? 'restricted' : 
                                            ($user['status'] === 'inactive' ? 'inactive' : 'active') 
                                        ?>">
                                            <?= ($user['status'] ?? 'active') ?>
                                        </td>
                                        <td>
                                            <?php
                                                $restrictions = [];
                                                if ($user['role'] === 'Customer' && ($user['status'] ?? '') === 'restricted_apply') {
                                                    $restrictions[] = "Cannot apply for loans";
                                                }
                                                if ($user['role'] === 'Lender' && ($user['status'] ?? '') === 'restricted_create') {
                                                    $restrictions[] = "Cannot create loan offers";
                                                }
                                                echo $restrictions ? implode(', ', $restrictions) : 'None';
                                            ?>
                                        </td>

                                        <td class="action-buttons">
    
                                            <!-- Restriction Form -->
                                            <?php
                                                $restrict_btn_visible = true;
                                                if (($user['status'] ?? 'active') === 'inactive' && ($user['status'] ?? '') !== 'restricted_apply' && ($user['status'] ?? '') !== 'restricted_create') {
                                                    $restrict_btn_visible = false;
                                                }
                                            ?>
                                            <?php if ($user['role'] === 'Customer'): ?>
                                                <form method="post" action="user_actions.php">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <input type="hidden" name="action" value="toggle_restriction">
                                                    <input type="hidden" name="restriction_type" value="apply_loan">
                                                    <button type="submit" class="<?= 
                                                        ($user['status'] ?? '') === 'restricted_apply' ? 'unrestrict-btn' : 'restrict-btn' 
                                                    ?>" <?= $restrict_btn_visible ? '' : 'style="display: none;"' ?>>
                                                        <?= ($user['status'] ?? '') === 'restricted_apply' ? 'Permit' : 'Forbid' ?>
                                                    </button>
                                                </form>
                                            <?php elseif ($user['role'] === 'Lender'): ?>
                                                <form method="post" action="user_actions.php">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <input type="hidden" name="action" value="toggle_restriction">
                                                    <input type="hidden" name="restriction_type" value="create_loan">
                                                    <button type="submit" class="<?= 
                                                        ($user['status'] ?? '') === 'restricted_create' ? 'unrestrict-btn' : 'restrict-btn' 
                                                    ?>" <?= $restrict_btn_visible ? '' : 'style="display: none;"' ?>>
                                                        <?= ($user['status'] ?? '') === 'restricted_create' ? 'Permit' : 'Forbid' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        
                                            <!-- Status Form -->
                                            <form method="post" action="user_actions.php">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="role" value="<?= $user['role'] ?>">
                                                
                                                <?php if (($user['status'] ?? 'active') === 'active' || 
                                                        strpos($user['status'] ?? '', 'restricted') !== false): ?>
                                                    <button type="submit" name="new_status" value="inactive" class="block-btn">Block</button>
                                                <?php else: ?>
                                                    <button type="submit" name="new_status" value="active" class="unblock-btn">Unblock</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                    
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                    </table>
                <?php else: ?>
                        <p style="color: tomato; font-size: 1.2em;">No users found<?= !empty($roleFilter) ? " with role $roleFilter" : '' ?>.</p>
                <?php endif; ?>
            </div>



                <!-- Add Users -->
                <div id="addUsers" class="margin">
                    <h1>Add Users</h1>
                    <p>Add a new user to the system.</p>
                    <form action="users.php" id="signupForm" method="post" onsubmit="return validateFormUsers()">
                        <table>
                            <!-- Error tag for displaying validation errors -->
                            <div id="error" style="color: tomato; font-weight:700;"></div>
                            <tr>
                                <td><label>Register as?</label></td>
                                <td>
                                    <select name="role" id="user-role" class="select">
                                        <option value="--select option--" selected>--select option--</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Customer">Customer</option>
                                        <option value="Lender">Lender</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label for="firstName">First Name</label></td>
                                <td><input type="text" id="firstName" name="firstName"></td>
                                <td><label for="secondName">Second Name</label></td>
                                <td><input type="text" id="secondName" name="secondName"></td>
                            </tr>
                            <tr>
                                <td><label for="email">Email</label></td>
                                <td><input type="text" id="email" name="email"></td>
                                <td><label for="phone">Phone</label></td>
                                <td><input type="text" id="phone" name="phone"></td>
                            </tr>
                            <tr>
                                <td><label for="address">Address</label></td>
                                <td><input type="text" id="address" name="address"></td>
                                <!-- Fields only relevant to customers are in class hidden -->
                                <td id="customerFields" class="hidden"><label for="dob">Date of Birth</label></td>
                                <td id="customerFields" class="hidden"><input type="text" placeholder="dd-mm-yyyy" id="dob" name="dob"></td>
                            </tr>
                            <tr id="customerFields" class="hidden">
                                <td><label for="nationalId">National ID</label></td>
                                <td><input type="text" id="nationalId" name="nationalId"></td>
                                <td><label for="accountNumber">Account No.</label></td>
                                <td><input type="text" id="accountNumber" name="accountNumber"></td>
                            </tr>
                            <tr>
                                <td><label for="password">Password</label></td>
                                <td><input type="password" id="password" name="password"></td>
                            </tr>
                            <tr>
                                <td><label for="confPassword">Confirm <br> Password</label></td>
                                <td><input type="password" id="confPassword" name="confPassword"></td>
                            </tr>
                            <tr class="submit-action">
                                <td><button type="submit" name="submit">REGISTER</button></td>
                            </tr>
                        </table>
                    </form>
                </div>
                                    
                <!-- Activity Logs -->
                <div id="activityLogs" class="margin">
                    <h1>Activity Logs</h1>
                    <p>View user activity logs.</p>
                    
                    <!-- Activity Type Filter -->
                    <div class="user-filter">
                        <form method="get" action="#activityLogs">
                            <div class="filter-row">
                                <div class="filter-group">
                                <label for="activity_type">Filter:</label>
                            <select name="activity_type" id="activity_type" onchange="this.form.submit()">
                                <option value="">All Activities</option>
                                
                                <optgroup label="Authentication">
                                    <option value="login" <?= $activityFilter==='login'?'selected':'' ?>>Log In</option>
                                    <option value="logout" <?= $activityFilter==='logout'?'selected':'' ?>>Log Out</option>
                                    <option value="failed login" <?= $activityFilter==='failed login'?'selected':'' ?>>Failed Log In</option>
                                </optgroup>
                                
                                <optgroup label="Loan Activities">
                                    <option value="loan application" <?= $activityFilter==='loan application'?'selected':'' ?>>Loan Application</option>
                                    <option value="application deletion" <?= $activityFilter==='application deletion'?'selected':'' ?>>Application Deletion</option>
                                    <option value="loan approval" <?= $activityFilter==='loan approval'?'selected':'' ?>>Loan Approval</option>
                                    <option value="loan rejection" <?= $activityFilter==='loan rejection'?'selected':'' ?>>Loan Rejection</option>
                                    <option value="loan disbursal" <?= $activityFilter==='loan disbursal'?'selected':'' ?>>Loan Disbursal</option>
                                    <option value="loan offer creation" <?= $activityFilter==='loan offer creation'?'selected':'' ?>>Loan Offer Creation</option>
                                    <option value="loan offer edit" <?= $activityFilter==='loan offer edit'?'selected':'' ?>>Loan Offer Edit</option>
                                    <option value="loan offer deletion" <?= $activityFilter==='loan offer deletion'?'selected':'' ?>>Loan Offer Deletion</option>
                                    <option value="payment" <?= $activityFilter==='payment'?'selected':'' ?>>Loan Payment</option>
                                </optgroup>
                                
                                <optgroup label="User Management">
                                    <option value="account registration" <?= $activityFilter==='account registration'?'selected':'' ?>>Account Registration</option>
                                    <option value="user registration" <?= $activityFilter==='user registration'?'selected':'' ?>>User Registration</option>
                                    <option value="profile update" <?= $activityFilter==='profile update'?'selected':'' ?>>Profile Update</option>
                                    <option value="user restriction" <?= $activityFilter==='user restriction'?'selected':'' ?>>User Restriction</option>
                                    <option value="user block" <?= $activityFilter==='user block'?'selected':'' ?>>User Block</option>
                                    <option value="user unblock" <?= $activityFilter==='user unblock'?'selected':'' ?>>User Unblock</option>
                                </optgroup>
                            </select>
                                </div>
                                <div class="filter-group">
                                <label for="date_range">Date Range:</label>
                                    <select name="date_range" id="date_range" onchange="this.form.submit()">
                                        <option value="">All Time</option>
                                        <option value="today" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'today' ? 'selected' : '' ?>>Today</option>
                                        <option value="week" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'week' ? 'selected' : '' ?>>This Week</option>
                                        <option value="month" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'month' ? 'selected' : '' ?>>This Month</option>
                                        <option value="year" <?= isset($_GET['date_range']) && $_GET['date_range'] === 'year' ? 'selected' : '' ?>>This Year</option>
                                    </select>
                                </div>
                                <a href="adminDashboard.php#activityLogs"><button type="button" class="reset">Reset</button></a>
                            </div>
                            
                            
                        </form>
                    </div>
    
                    <!-- Activity Logs Display -->
                    <?php if (!empty($activityLogs)): ?>
                        <div class="activity-logs-container">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>Log ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Activity</th>
                                        <th>Type</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['log_id']) ?></td>
                                            <td><?= htmlspecialchars($log['user_name']) ?></td>
                                            <td><?= htmlspecialchars($log['email']) ?></td>
                                            <td><?= htmlspecialchars($log['activity']) ?></td>
                                            <td class="log-type-<?= strtolower($log['activity_type']) ?>">
                                                <?= htmlspecialchars($log['activity_type']) ?>
                                            </td>
                                            <td><?= date('M j, Y G:i ', strtotime($log['activity_time'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No activity logs found.</p>
                    <?php endif; ?>
                </div>

                <!-- Notifications -->
                <!-- <div id="notifications" class="margin">
                    <h1>Notifications</h1>
                    <p>View your alerts and reminders.</p>
                    </div> -->
                                
                <!-- Profile -->
                <div id="profile" class="margin">
                    <h1>Profile</h1>
                    <p>View and update your personal information.</p>
                    
                    
                    <div class="profile-container">
                        <div class="profile-details">
                            <h2>Personal Information</h2>
                            <div class="profile-row">
                                <span class="profile-label">Full Name:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($adminProfile['user_name']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Email:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($adminProfile['email']); ?></span>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Phone:</span>
                                <span class="profile-value"><?php echo htmlspecialchars($adminProfile['phone']); ?></span>
                            </div>
                            
                            <button id="editProfileBtn" style="cursor:not-allowed;">Edit Profile</button>
                            
                        </div>
                        <div class="additional-settings">
                                <h2>Additional Settings</h2>
                                <p class="change">Change Password</p>
                                <p class="delete">Delete Account</p>
                            </div>
                    </div>
                </div>

                

                <!-- Contact Support -->
                <div id="contactSupport" class="margin">
                    <h1>Contact Support</h1>
                    <p>Reach out to our support team for assistance.</p>
                </div>

                <!-- Dashboard -->
                <div id="dashboard" class="margin">
                    <div class="dash-header">
                        <div>
                            <h1>Administrator's Dashboard</h1>
                            <p>Overview the systems performance.</p>
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
                    <div class="metrics" style="height:unset;">
                    <div>
                            <p>Total Users</p>
                            <span class="span-2"><?php echo $totalUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Active Users</p>
                            <span class="span-2"><?php echo $activeUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Blocked Users</p>
                            <span class="span-2"><?php echo $blockedUsersCount; ?></span>
                        </div>
                        <div>
                            <p>Total Borrowers</p>
                            <span class="span-2"><?php echo $totalCustomers; ?></span>
                        </div>
                        <div>
                            <p>Total Lenders</p>
                            <span class="span-2"><?php echo $totalLenders; ?></span>
                        </div>
                        <div>
                            <p>Total Admins</p>
                            <span class="span-2"><?php echo $totalAdmins; ?></span>
                        </div>
                    </div>
                    <div class="admin-visuals">
                        <div class="visuals-left">
                            <p><span>Recent Activities</span><span><a href="#activityLogs"><button>View All &#9660</button></a></span></p>
                            <div class="recent-activities">
                                <?php if (!empty($recentActivityLogs)): ?>
                                <div class="activity-logs-container">
                                    <table class="recent-activity-table">
                                        <thead>
                                            <tr>
                                                <th>Log ID</th>
                                                <th>Email</th>
                                                <th>Type</th>
                                                <th style="text-align:center;">Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivityLogs as $log): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($log['log_id']) ?></td>
                                                    <td><?= htmlspecialchars($log['email']) ?></td>
                                                    <td class="log-type-<?= strtolower($log['activity_type']) ?>">
                                                        <?= htmlspecialchars($log['activity_type']) ?>
                                                    </td>
                                                    <td style="text-align:right;"><?= date('M j, Y G:i ', strtotime($log['activity_time'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <p>No activity logs found.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                        <div class="visuals-right">
                        <p>User Distribution</p>
                        
                        <canvas id="pieChart" width="400" height="200"></canvas>
                        </div>
                        </div>
                    

 
                    </div>
                </div>
            </div>
        </div>



                 
    </main>

    <!-- External JavaScript for validation -->
    <script src="../js/validinput.js"></script>
   
<script>
    // ACTIVE NAV LINK
function updateActiveNavLink() {
    const navLinks = document.querySelectorAll('.nav ul li a');
    const currentHash = window.location.hash || '#dashboard'; // Default to #dashboard if no hash

    // Remove .active class from all links
    navLinks.forEach(link => link.classList.remove('active'));

    // Find the link that matches the current hash and add .active class
    const activeLink = document.querySelector(`.nav ul li a[href="${currentHash}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    } else {
        // Default to Dashboard if no matching link
        document.querySelector('.nav ul li a[href="#dashboard"]').classList.add('active');
    }
}

// Initialize active link on page load and handle hash changes
document.addEventListener('DOMContentLoaded', function() {
    updateActiveNavLink();

    // Update active link when hash changes
    window.addEventListener('hashchange', updateActiveNavLink);

    // Update active link when navigation links are clicked
    document.querySelectorAll('.nav ul li a').forEach(link => {
        link.addEventListener('click', function() {
            // Remove .active from all links
            document.querySelectorAll('.nav ul li a').forEach(l => l.classList.remove('active'));
            // Add .active to the clicked link
            this.classList.add('active');
        });
    });
});
</script>
<!-- Pie Chart for user Distribution -->
<script>
    function initializePieChart() {
    const pieData = <?= json_encode($pieData) ?>;
    const pieCanvas = document.getElementById('pieChart');
    const pieCtx = pieCanvas.getContext('2d');
    
    // Corrected labels to match your data
    const labels = ['Admin', 'Lender', 'Customer'];
    const values = [
        pieData.Admin,
        pieData.Lender,
        pieData.Customer
    ];
    
    // Assign colors to the correct labels
    const statusColors = {
        'Admin': 'tomato',    
        'Lender': 'teal',  
        'Customer': '#ddd' 
    };

    const total = values.reduce((sum, value) => sum + value, 0);
    let startAngle = 0;
    const centerX = pieCanvas.width / 4;
    const centerY = pieCanvas.height / 2;
    const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10;

    // Draw pie slices
    labels.forEach((label, index) => {
        const value = values[index];
        if (value > 0) {
            const sliceAngle = (2 * Math.PI * value) / total;
            pieCtx.beginPath();
            pieCtx.moveTo(centerX, centerY);
            pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
            pieCtx.closePath();
            pieCtx.fillStyle = statusColors[label];
            pieCtx.fill();
            startAngle += sliceAngle;
        }
    });

    // Add legend
    pieCtx.font = '16px Trebuchet MS';
    let legendY = 20;
    const legendX = centerX + radius + 20;
    const legendSpacing = 20;

    labels.forEach((label, index) => {
        const value = values[index];
        if (value > 0) {
            pieCtx.fillStyle = statusColors[label];
            pieCtx.fillRect(legendX, legendY, 15, 15);
            pieCtx.fillStyle = 'whitesmoke';
            pieCtx.fillText(`${label}: ${value.toFixed(1)}%`, legendX + 20, legendY + 12);
            legendY += legendSpacing;
        }
    });
}

// Make sure to call this when the page loads
document.addEventListener('DOMContentLoaded', initializePieChart);
</script>

    <script>
    
        // Show/hide fields based on role selection
        const roleDropdown = document.getElementById('user-role');
        const customerFields = document.querySelectorAll('#customerFields');
    
        roleDropdown.addEventListener('change', function () {
            if (roleDropdown.value === 'Customer') {
                customerFields.forEach(field => field.classList.remove('hidden'));
            } else {
                customerFields.forEach(field => field.classList.add('hidden'));
            }
        });
    
        // Initialize visibility on page load
        window.onload = function () {
            if (roleDropdown.value === '--select option--') {
                customerFields.forEach(field => field.classList.remove('hidden'));
            }
        };
    </script>

<script>
        // Function to hide the admin message after 2 seconds
        function hideAdminMessage() {
            const adminMessage = document.getElementById('admin-message');
            if (adminMessage) {
                setTimeout(() => {
                    adminMessage.style.opacity = '0'; // Fade out the message
                    setTimeout(() => {
                        adminMessage.style.display = 'none'; // Hide the message after fading out
                    }, 700); // Wait for the transition to complete
                }, 2000); // 2000 milliseconds = 2seconds
            }
        }

        // Call the function when the page loads
        window.onload = hideAdminMessage;
    </script>

<!-- Pop Up -->
<script>
    function openLoanPopup(loan) {
        document.getElementById('popup-loan-id').textContent = loan.loan_id;
        document.getElementById('popup-customer-id').textContent = loan.customer_id;
        document.getElementById('popup-customer-name').textContent = loan.customer_name;
        document.getElementById('popup-amount').textContent = parseFloat(loan.amount).toFixed(2);
        document.getElementById('popup-duration').textContent = loan.duration;
        document.getElementById('popup-collateral-value').textContent = parseFloat(loan.collateral_value).toFixed(2);
        document.getElementById('popup-collateral-desc').textContent = loan.collateral_description;
        document.getElementById('popup-collateral-image').src = loan.collateral_image || '';
        document.getElementById('popup-loan-id-input').value = loan.loan_id;
        document.getElementById('popup-loan-id-input-reject').value = loan.loan_id;
        document.getElementById('loanPopup').style.display = 'block';
    }

    function closeLoanPopup() {
        document.getElementById('loanPopup').style.display = 'none';
    }

    // Close popup when clicking outside of it
    window.onclick = function(event) {
        const popup = document.getElementById('loanPopup');
        if (event.target == popup) {
            popup.style.display = 'none';
        }
    }
</script>
    
</body>
</html>