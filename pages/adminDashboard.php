<?php
require_once 'adminDashboardData.php'; // has the dashboard data
?>

<!DOCTYPE html> <!-- Declares the document type and version of HTML (HTML5) -->
<html lang="en"> <!-- Root element of the HTML document; 'lang="en"' specifies the language as English -->
<head>
    <meta charset="UTF-8"> <!-- Sets the character encoding for the document to UTF-8, supporting most characters from all languages -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 
        The viewport meta tag controls the layout on mobile browsers.
        width=device-width: sets the width of the page to follow the screen-width of the device.
        initial-scale=1.0: sets the initial zoom level when the page is first loaded by the browser.
    -->
    <title>Administrator's Dashboard</title> <!-- Sets the title of the page, shown in the browser tab -->
    <link rel="stylesheet" href="../css/style.css">
    <!-- 
        The link tag links an external CSS stylesheet to the HTML document.
        rel="stylesheet": specifies the relationship as a stylesheet.
        href="../css/style.css": path to the CSS file that styles the page.
    -->
</head>
<body>
    <main>
        <div class="header">
            <div class="header2">
                <div class="logo">loanSqr</div>
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
                        <li><a href="#riskAssessment">Application Review
                            <?php if ($pendingLoans > 0): ?>
                                <span class="badge"><?php echo $pendingLoans; ?></span> 
                            <?php endif; ?>
                        </a></li>
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
                                    <option value="password update" <?= $activityFilter==='password update'?'selected':'' ?>>Password Update</option>
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
                    
                    <!-- Messages -->
                    <?php if (isset($_SESSION['profile_message'])): ?>
                        <div id="profileMessage" class="alert <?= $_SESSION['profile_message_type'] ?? 'info' ?>">
                            <?= htmlspecialchars($_SESSION['profile_message']) ?>
                        </div>
                        <?php 
                        unset($_SESSION['profile_message']);
                        unset($_SESSION['profile_message_type']);
                        ?>
                    <?php endif; ?>

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

                <!-- Change Password Popup Overlay -->
                <div class="popup-overlay3" id="changePasswordOverlay" style="display: none;">
                    <div class="popup-content3">
                        <h2>Change Password</h2>
                        <form id="changePasswordForm" action="changePassword.php" method="post" onsubmit="return validateChangePasswordForm()" style="text-align: left;">
                            <!-- Error Message -->
                            <div id="password_error" style="color: tomato;font-weight:700"></div>

                            <div class="form-group">
                                <label for="oldPassword">Old Password</label>
                                <input type="password" id="oldPassword" name="old_password" style="text-align: left;">
                            </div>
                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="new_password" style="text-align: left;">
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" id="confirmPassword" name="confirm_password" style="text-align: left;">
                            </div>
                            <div class="form-actions">
                                <button type="button" id="cancelChangePassBtn" class="cancel-btn">Cancel</button>
                                <button type="submit" class="save-btn">Change Password</button>
                            </div>
                        </form>
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

    <!-- input validation javascript -->
    <script src="../js/validinput.js"></script>

    <!-- admin dashboard javascript -->
    <script src="../js/adminDashboard.js"></script>

<!-- CHART FUNCTIONS -->
<script>
 // PIE CHART SECTION
// Initializes and renders a pie chart to display the distribution of user roles
function initializePieChart() {
    // Parses JSON data (injected from PHP) into a JavaScript object containing user role counts
    const pieData = <?= json_encode($pieData) ?>;

    // Gets the canvas element with ID 'pieChart' for rendering the chart
    const pieCanvas = document.getElementById('pieChart');
    // Gets the 2D rendering context of the canvas for drawing
    const pieCtx = pieCanvas.getContext('2d');

    // Clears the canvas to ensure a fresh drawing surface
    pieCtx.clearRect(0, 0, pieCanvas.width, pieCanvas.height);

    // Defines the labels for the pie chart segments, corresponding to user roles
    const labels = ['Admin', 'Lender', 'Customer'];
    // Extracts values for each user role from the pieData object
    const values = [
        pieData.Admin || 0, // Number of Admin users, default to 0 if undefined
        pieData.Lender || 0, // Number of Lender users, default to 0 if undefined
        pieData.Customer || 0 // Number of Customer users, default to 0 if undefined
    ];

    // Defines colors for each user role segment
    const statusColors = {
        'Admin': 'tomato', // Red-orange color for Admin users
        'Lender': 'teal', // Teal color for Lender users
        'Customer': '#ddd' // Light gray color for Customer users
    };

    // Calculates the total sum of all values for proportion calculations
    const total = values.reduce((sum, value) => sum + value, 0);

    // Defines chart configuration for the donut chart
    const centerX = pieCanvas.width / 4; // Sets the x-coordinate of the chart’s center
    const centerY = pieCanvas.height / 2; // Sets the y-coordinate of the chart’s center
    const radius = Math.min(pieCanvas.width / 3, pieCanvas.height / 2) - 10; // Calculates the radius to fit within the canvas
    const lineWidth = 20; // Sets the thickness of the donut rings
    const animationDuration = 1000; // Sets the animation duration in milliseconds
    const startTime = performance.now(); // Records the start time for animation timing

    // Initializes an array to store the current angle for each segment during animation
    const currentAngles = new Array(labels.length).fill(0);
    // Calculates the final angle for each segment based on its proportion of the total
    const finalAngles = values.map(value => (value > 0 ? (2 * Math.PI * value) / total : 0));

    // Defines the animation function to progressively draw the donut chart
    function animate(currentTime) {
        // Clears the canvas on each frame to redraw with updated angles
        pieCtx.clearRect(0, 0, pieCanvas.width, pieCanvas.height);

        // Calculates the animation progress (0 to 1) based on elapsed time
        // Progress is the completion fraction of the animation, ranging from 0 (start) to 1 (end)
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / animationDuration, 1); // Caps progress at 1

        // Draws donut chart slices with animated angles
        let startAngle = 0; // Initializes the starting angle for the current frame
        labels.forEach((label, index) => {
            // Gets the value for the current user role
            const value = values[index];
            // Only draws a slice if the value is greater than 0
            if (value > 0) {
                // Calculates the current angle for the segment based on animation progress
                currentAngles[index] = progress * finalAngles[index];
                pieCtx.beginPath(); // Starts a new drawing path
                // Draws an arc from the start angle to the current animated angle
                pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + currentAngles[index]);
                pieCtx.lineWidth = lineWidth; // Sets the thickness of the ring
                pieCtx.strokeStyle = statusColors[label]; // Sets the color for the slice
                pieCtx.stroke(); // Draws the arc as a hollow ring
                // Updates the start angle for the next slice
                startAngle += currentAngles[index];
            }
        });


        // Continues the animation if progress is less than 1
        if (progress < 1) {
            requestAnimationFrame(animate); // Schedules the next animation frame
        } else {
            // Draws the legend for the donut chart after animation completes
            pieCtx.font = '16px Trebuchet MS'; // Sets the font for legend text
            let legendY = 20; // Sets the starting y-coordinate for the legend
            const legendX = centerX + radius + 20; // Sets the x-coordinate for the legend (right of chart)
            const legendSpacing = 20; // Sets the vertical spacing between legend items

            labels.forEach((label, index) => {
                // Gets the value for the current user role
                const value = values[index];
                // Only includes legend entries for non-zero values
                if (value > 0) {
                    // Draws a colored square for the legend item
                    pieCtx.fillStyle = statusColors[label];
                    pieCtx.fillRect(legendX, legendY, 15, 15);
                    // Sets the text color to a light gray for readability
                    pieCtx.fillStyle = 'whitesmoke';
                    // Draws the legend text, showing the user role and correct percentage
                    pieCtx.fillText(`${label}: ${((value / total) * 100).toFixed(1)}%`, legendX + 20, legendY + 12);
                    // Moves the y-coordinate down for the next legend item
                    legendY += legendSpacing;
                }
            });
        }
    }

    // Checks if there is valid data to animate
    if (total > 0) {
        // Starts the animation if there are non-zero values
        requestAnimationFrame(animate);
    } else {
        // Draws a static donut chart if no data is available
        let startAngle = 0; // Initializes the starting angle for the first slice
        labels.forEach((label, index) => {
            // Gets the value for the current user role
            const value = values[index];
            // Only draws a slice if the value is greater than 0
            if (value > 0) {
                // Calculates the angle for the current slice based on its proportion of the total
                const sliceAngle = (2 * Math.PI * value) / total;
                pieCtx.beginPath(); // Starts a new drawing path
                // Draws an arc from the start angle to the end angle with the specified radius
                pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                pieCtx.lineWidth = lineWidth; // Sets the thickness of the ring
                pieCtx.strokeStyle = statusColors[label]; // Sets the color for the slice
                pieCtx.stroke(); // Draws the arc as a hollow ring
                // Updates the start angle for the next slice
                startAngle += sliceAngle;
            }
        });

        // Draws the legend for the static donut chart
        pieCtx.font = '16px Trebuchet MS'; // Sets the font for legend text
        let legendY = 20; // Sets the starting y-coordinate for the legend
        const legendX = centerX + radius + 20; // Sets the x-coordinate for the legend (right of chart)
        const legendSpacing = 20; // Sets the vertical spacing between legend items

        labels.forEach((label, index) => {
            // Gets the value for the current user role
            const value = values[index];
            // Only includes legend entries for non-zero values
            if (value > 0) {
                // Draws a colored square for the legend item
                pieCtx.fillStyle = statusColors[label];
                pieCtx.fillRect(legendX, legendY, 15, 15);
                // Sets the text color to a light gray for readability
                pieCtx.fillStyle = 'whitesmoke';
                // Draws the legend text, showing the user role and correct percentage
                pieCtx.fillText(`${label}: ${((value / total) * 100).toFixed(1)}%`, legendX + 20, legendY + 12);
                // Moves the y-coordinate down for the next legend item
                legendY += legendSpacing;
            }
        });
    }
}

// Initializes the pie chart when the page is fully loaded
document.addEventListener('DOMContentLoaded', initializePieChart);
</script>
</body>
</html>