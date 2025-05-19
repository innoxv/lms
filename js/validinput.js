// Displaying Error Messages

function displayError(message) {
    const errorField = document.getElementById("error");
    errorField.innerText = message;
    // Clear the error message after 2 seconds
    setTimeout(() => {
        clearError();
    }, 2000); // 2000 milliseconds = 2 seconds
}
// Function to clear the error message
function clearError() {
    const errorField = document.getElementById("error");
    errorField.innerText = "";
}

// Sign Up validation Function
function validateForm() {
    // Clear previous error
    displayError("");
    // Get the selected role
    var role = document.getElementById("role").value;
    // Validate Role
    if (role === "--select option--") {
        displayError("You must select a valid role (Customer or Lender).");
        return false;
    }
    // Validate First Name
    var firstName = document.getElementById("firstName").value;
    if (firstName === "") {
        displayError("Please enter your First Name.");
        document.getElementById("firstName").focus();
        return false;
    }
    // Validate Second Name
    var secondName = document.getElementById("secondName").value;
    if (secondName === "") {
        displayError("Please enter your Second Name.");
        document.getElementById("secondName").focus();
        return false;
    }
    // Validate Email
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        displayError("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }
    // Validate Phone
    var phone = document.getElementById("phone").value.trim();
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) {
        displayError("Phone number must be exactly 10 digits and start with 0.");
        document.getElementById("phone").focus();
        return false;
    }
    // Validate Address
    var address = document.getElementById("address").value;
    if (address === "") {
        displayError("Please enter your Address.");
        document.getElementById("address").focus();
        return false;
    }
    // Validate Password
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confPassword").value.trim();

    if (password === "") {
        displayError("Password cannot be empty.");
        return false;
    }
    if (password.length < 8) {
        displayError("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirmPassword) {
        displayError("Passwords do not match.");
        return false;
    }
    // Role-specific validation (Only for Customer)
    if (role === "Customer") {
        // Validate Date of Birth
        var date = document.getElementById("dob").value;
        if (date.indexOf("-") === -1) {
            displayError("Date must be entered and of the format (dd-mm-yyyy).");
            document.getElementById("dob").focus();
            return false;
        }
        var comps = date.split("-");
        if (comps.length !== 3 || comps[0].length !== 2 || comps[1].length !== 2 || comps[2].length !== 4) {
            displayError("Date must be of the format (dd-mm-yyyy).");
            document.getElementById("dob").focus();
            return false;
        }

        var day = parseInt(comps[0], 10);
        var month = parseInt(comps[1], 10);
        var year = parseInt(comps[2], 10);

        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            displayError("Date components must be integers.");
            document.getElementById("dob").focus();
            return false;
        }
        if (month < 1 || month > 12) {
            displayError("Month must be between 1 and 12.");
            document.getElementById("dob").focus();
            return false;
        }
        if (day < 1 || day > 31) {
            displayError("Day must be between 1 and 31.");
            document.getElementById("dob").focus();
            return false;
        }
        if (year < 1900 || year > new Date().getFullYear()) {
            displayError("Year must be a valid year (1900 or later).");
            document.getElementById("dob").focus();
            return false;
        }

        var today = new Date();
        var givenDate = new Date(year, month - 1, day);
        if (givenDate > today) {
            displayError("Date of Birth cannot be greater than today.");
            document.getElementById("dob").focus();
            return false;
        }
        // Validate National ID
        var nationalId = document.getElementById("nationalId").value;
        if (nationalId === "" || isNaN(nationalId)) {
            displayError("Please enter a valid National ID.");
            document.getElementById("nationalId").focus();
            return false;
        }
        // Validate Account Number
        var accountNumber = document.getElementById("accountNumber").value;
        if (accountNumber === "" || isNaN(accountNumber)) {
            displayError("Please enter a valid Account Number.");
            document.getElementById("accountNumber").focus();
            return false;
        }
    }
    return true;
}

// Log In Validation Function
function validateForm2() {
    displayError(""); // Clear any previous error

    // Validate Email
    var email = document.getElementById("signinEmail").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        displayError("Please enter a valid Email Address.");
        document.getElementById("signinEmail").focus();
        return false;
    }

    // Validate Password
    var password = document.getElementById("signinPassword").value.trim();
    if (password === "") {
        displayError("Password cannot be empty.");
        document.getElementById("signinPassword").focus();
        return false;
    }

    return true;
}



// Loans Creation Form Validation
function validateFormLoans() {
    displayError("");

    // Validate Loan Type
    // Get the selected role
    var role = document.getElementById("type").value;

    // Validate Role
    if (role === "--select option--") {
        displayError("You must select a valid loan type from the options!");
        return false;
    }
    // Validate Interest Rate
    var interestRate = document.getElementById("interestRate").value.trim();
    if (interestRate === "" || isNaN(interestRate) || parseFloat(interestRate) <= 0 || parseFloat(interestRate) > 100) {
        displayError("Interest rate must be between 0.01 and 100.");
        document.getElementById("interestRate").focus();
        return false;
    }
    //Validate Amount
    var amount = document.getElementById("maxAmount").value.trim();
    if (amount === "" || isNaN(amount) || parseInt(amount, 10) <= 0 || parseInt(amount, 10) < 500) {
        displayError("Amount must be a positive number of at least 500.");
        document.getElementById("maxAmount").focus();
        return false;
    }
    // Validate Maximum Duration
    var duration = document.getElementById("maxDuration").value.trim();
    if (duration === "" || isNaN(duration) || parseInt(duration, 10) <= 0) {
        displayError("Duration must be a positive number.");
        document.getElementById("maxDuration").focus();
        return false;
    }
    return true;
}

// Activity Table Validation
function validateFormActivity() {
    displayError("");

    // Validate User ID
    var userId = document.getElementById("userId").value;
    if (userId === "" || isNaN(userId)) {
        displayError("Please enter a valid user ID.");
        document.getElementById("userId").focus();
        return false;
    }

    // Validate Activity
    var activity = document.getElementById("activity").value;
    if (activity === "") {
        displayError("Please enter the Activity.");
        document.getElementById("activity").focus();
        return false;
    }

    // Validate Activity Date & Time
    var activityDateTime = document.getElementById("activityDateTime").value;
    var dateTimeParts = activityDateTime.split(" ");
    if (dateTimeParts.length !== 2) {
        displayError("Activity Date & Time must be in the format dd-mm-yyyy HH:MM.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    var dateParts = dateTimeParts[0].split("-");
    var timeParts = dateTimeParts[1].split(":");

    if (dateParts.length !== 3 || timeParts.length !== 2) {
        displayError("Activity Date & Time must be in the format dd-mm-yyyy HH:MM.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    var day = parseInt(dateParts[0], 10);
    var month = parseInt(dateParts[1], 10);
    var year = parseInt(dateParts[2], 10);
    var hour = parseInt(timeParts[0], 10);
    var minute = parseInt(timeParts[1], 10);

    if (isNaN(day) || isNaN(month) || isNaN(year) || isNaN(hour) || isNaN(minute)) {
        displayError("Date and time components must be integers.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    if (month < 1 || month > 12) {
        displayError("Month must be between 1 and 12.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    if (day < 1 || day > 31) {
        displayError("Day must be between 1 and 31.");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    if (hour < 0 || hour > 23 || minute < 0 || minute > 59) {
        displayError("Time must be in a valid format (HH:MM).");
        document.getElementById("activityDateTime").focus();
        return false;
    }

    // Validate Activity Type
    var activityType = document.getElementById("activityType").value;
    if (activityType === "") {
        displayError("Please enter the Activity Type.");
        document.getElementById("activityType").focus();
        return false;
    }

    return true;
}


// Users Table Validation
function validateFormUsers() {
    displayError("");

    // Validate Role
    var role = document.getElementById("user-role").value;
    if (role === "--select option--") {
        displayError("You must select a valid role (Customer, Lender, or Admin).");
        return false;
    }

    // Validate First Name
    var firstName = document.getElementById("firstName").value;
    if (firstName === "") {
        displayError("Please enter First Name.");
        document.getElementById("firstName").focus();
        return false;
    }

    // Validate Second Name
    var secondName = document.getElementById("secondName").value;
    if (secondName === "") {
        displayError("Please enter Second Name.");
        document.getElementById("secondName").focus();
        return false;
    }

    // Validate Email
    var email = document.getElementById("email").value;
    if (email === "" || !email.includes("@") || !email.includes(".")) {
        displayError("Please enter a valid Email address.");
        document.getElementById("email").focus();
        return false;
    }

    // Validate Phone
    var phone = document.getElementById("phone").value.trim();
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) {
        displayError("Phone number must be exactly 10 digits and start with 0.");
        document.getElementById("phone").focus();
        return false;
    }

    // Validate Password
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confPassword").value.trim();

    if (password === "") {
        displayError("Password cannot be empty.");
        return false;
    }
    if (password.length < 8) {
        displayError("Password must be at least 8 characters.");
        return false;
    }
    if (password !== confirmPassword) {
        displayError("Passwords do not match.");
        return false;
    }
    return true;
}


// Loan Application Form Validation
function validateLoanApplicationForm() {
    displayError("");


    // Validate Amount Needed
    const amountNeeded = document.getElementById("amountNeeded").value.trim();
    if (amountNeeded === "" || isNaN(amountNeeded) || parseFloat(amountNeeded) <= 0) {
        displayError("Amount needed must be a positive number.");
        document.getElementById("amountNeeded").focus();
        return false;
    }

    // Validate Duration
    const duration = document.getElementById("duration").value.trim();
    if (duration === "" || isNaN(duration) || parseInt(duration, 10) <= 0) {
        displayError("Duration must be a positive number.");
        document.getElementById("duration").focus();
        return false;
    }

    // Validate Collateral Value
    const collateralValue = document.getElementById("collateralValue").value.trim();
    if (collateralValue === "" || isNaN(collateralValue) || parseFloat(collateralValue) <= 0) {
        displayError("Collateral value must be a positive number.");
        document.getElementById("collateralValue").focus();
        return false;
    }

    // Validate Collateral Description
    const collateralDesc = document.getElementById("collateralDesc").value.trim();
    if (collateralDesc === "") {
        displayError("Please enter a collateral description.");
        document.getElementById("collateralDesc").focus();
        return false;
    }

    // Validate Amount Needed against Maximum Amount
    const maxAmountText = document.getElementById("displayMaxAmount").innerText.replace(/,/g, '');
    const maxAmount = parseFloat(maxAmountText);
    if (isNaN(maxAmount)) {
        displayError("Invalid maximum amount value.");
        return false;
    }
    if (parseFloat(amountNeeded) > maxAmount) {
        displayError("Amount needed cannot exceed the maximum amount.");
        document.getElementById("amountNeeded").focus();
        return false;
    }

    // Validate Duration against Maximum Duration
    const maxDurationText = document.getElementById("displayMaxDuration").innerText.replace(/[^0-9]/g, '');
    const maxDuration = parseInt(maxDurationText, 10);
    if (isNaN(maxDuration)) {
        displayError("Invalid maximum duration value.");
        return false;
    }
    if (parseInt(duration, 10) > maxDuration) {
        displayError("Duration cannot exceed the maximum duration.");
        document.getElementById("duration").focus();
        return false;
    }

    return true;
}


// Payment Pop Up Validation
// Display error message for Payment Form (id="payment_error")
function displayPaymentError(message, autoClearTimeout = 2000) {
    const errorField = document.getElementById('payment_error');
    if (errorField) {
        errorField.innerText = message;
        if (message && autoClearTimeout > 0) {
            setTimeout(() => clearPaymentError(), autoClearTimeout);
        }
    } else {
        console.error("Error div with ID 'payment_error' not found!");
    }
}

// Clear error message for Payment Form (id="payment_error")
function clearPaymentError() {
    const errorField = document.getElementById('payment_error');
    if (errorField) {
        errorField.innerText = "";
    }
}

function validatePaymentForm() {
    displayPaymentError(""); // Clear previous error

    // Validate Payment Amount
    const paymentAmountInput = document.getElementById("payment_amount");
    if (!paymentAmountInput) {
        displayPaymentError("Payment amount input field is missing. Please try again.");
        return false;
    }

    const paymentAmount = paymentAmountInput.value.trim();
    if (paymentAmount === "" || isNaN(paymentAmount) || parseFloat(paymentAmount) <= 0) {
        displayPaymentError("Payment amount must be a positive number.");
        paymentAmountInput.focus();
        return false;
    }

    // Validate Payment Amount against Balance
    const balanceElement = document.getElementById("payment_balance");
    if (!balanceElement) {
        displayPaymentError("Balance field is missing. Please try again.");
        return false;
    }

    // Parse balance and validate
    const balanceText = balanceElement.value.replace(/[^0-9.]/g, '');
    const balance = parseFloat(balanceText);
    if (isNaN(balance) || balance < 0) {
        displayPaymentError("Invalid balance value. Please contact support.");
        return false;
    }

    const paymentAmountValue = parseFloat(paymentAmount);
    if (paymentAmountValue > balance) {
        displayPaymentError("Payment amount cannot exceed the remaining balance.");
        paymentAmountInput.focus();
        return false;
    }

    // Validate Payment Method
    const paymentMethodInput = document.getElementById("payment_method");
    if (!paymentMethodInput) {
        displayPaymentError("Payment method field is missing. Please try again.");
        return false;
    }

    const paymentMethod = paymentMethodInput.value.trim();
    if (paymentMethod === "") {
        displayPaymentError("Please select a payment method.");
        paymentMethodInput.focus();
        return false;
    }

    return true; // Validation passed
}