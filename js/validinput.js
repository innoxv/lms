function displayError(message) {
    // Displays an error message in the element with id "error" and clears it after 2 seconds
    const errorField = document.getElementById("error"); // Gets the DOM element with id "error"
    errorField.innerText = message; // Sets the text content of the errorField to the provided message
    // Clear the error message after 2 seconds
    setTimeout(() => { // Executes the enclosed function after a delay
        clearError(); // Calls the clearError function to reset the error message
    }, 2000); // 2000 milliseconds delay equals 2 seconds
}

function clearError() {
    // Clears the error message in the element with id "error"
    const errorField = document.getElementById("error"); // Gets the DOM element with id "error"
    errorField.innerText = ""; // Sets the text content of the errorField to an empty string
}

function validateForm() {
    // Validates the sign-up form fields, including role, names, email, phone, address, password, and customer-specific fields
    displayError(""); // Calls displayError with an empty string to clear any previous error message
    // Get the selected role
    var role = document.getElementById("role").value; // Retrieves the value of the element with id "role"
    // Validate Role
    if (role === "--select option--") { // Checks if role is strictly equal to "--select option--"
        displayError("You must select a valid role (Customer or Lender)."); // Displays error if role is invalid
        return false; // Returns false to indicate validation failure
    }
    // Validate First Name
    var firstName = document.getElementById("firstName").value; // Retrieves the value of the element with id "firstName"
    if (firstName === "") { // Checks if firstName is strictly equal to an empty string
        displayError("Please enter your First Name."); // Displays error if firstName is empty
        document.getElementById("firstName").focus(); // Sets focus to the firstName input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Second Name
    var secondName = document.getElementById("secondName").value; // Retrieves the value of the element with id "secondName"
    if (secondName === "") { // Checks if secondName is strictly equal to an empty string
        displayError("Please enter your Second Name."); // Displays error if secondName is empty
        document.getElementById("secondName").focus(); // Sets focus to the secondName input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Email
    var email = document.getElementById("email").value; // Retrieves the value of the element with id "email"
    if (email === "" || !email.includes("@") || !email.includes(".")) { // Checks if email is empty OR does not include "@" OR does not include "."
        displayError("Please enter a valid Email address."); // Displays error if email is invalid
        document.getElementById("email").focus(); // Sets focus to the email input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Phone
    var phone = document.getElementById("phone").value.trim(); // Retrieves and trims the value of the element with id "phone"
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) { // Checks if phone is empty OR length is not exactly 10 OR is not a number OR does not start with "0"
        displayError("Phone number must be exactly 10 digits and start with 0."); // Displays error if phone is invalid
        document.getElementById("phone").focus(); // Sets focus to the phone input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Address
    var address = document.getElementById("address").value; // Retrieves the value of the element with id "address"
    if (address === "") { // Checks if address is strictly equal to an empty string
        displayError("Please enter your Address."); // Displays error if address is empty
        document.getElementById("address").focus(); // Sets focus to the address input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Password
    const password = document.getElementById("password").value.trim(); // Retrieves and trims the value of the element with id "password"
    const confirmPassword = document.getElementById("confPassword").value.trim(); // Retrieves and trims the value of the element with id "confPassword"
    if (password === "") { // Checks if password is strictly equal to an empty string
        displayError("Password cannot be empty."); // Displays error if password is empty
        return false; // Returns false to indicate validation failure
    }
    if (password.length < 8) { // Checks if password length is less than 8 characters
        displayError("Password must be at least 8 characters."); // Displays error if password is too short
        return false; // Returns false to indicate validation failure
    }
    if (password !== confirmPassword) { // Checks if password is not strictly equal to confirmPassword
        displayError("Passwords do not match."); // Displays error if passwords do not match
        return false; // Returns false to indicate validation failure
    }
    // Role-specific validation (Only for Customer)
    if (role === "Customer") { // Checks if role is strictly equal to "Customer"
        // Validate Date of Birth
        var date = document.getElementById("dob").value; // Retrieves the value of the element with id "dob"
        if (date.indexOf("-") === -1) { // Checks if date does not contain a hyphen
            displayError("Date must be entered and of the format (dd-mm-yyyy)."); // Displays error if date format is invalid
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        var comps = date.split("-"); // Splits the date string by hyphen into an array
        if (comps.length !== 3 || comps[0].length !== 2 || comps[1].length !== 2 || comps[2].length !== 4) { // Checks if array has exactly 3 parts AND day is 2 digits AND month is 2 digits AND year is 4 digits
            displayError("Date must be of the format (dd-mm-yyyy)."); // Displays error if date format is invalid
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        var day = parseInt(comps[0], 10); // Parses the day component as an integer in base 10
        var month = parseInt(comps[1], 10); // Parses the month component as an integer in base 10
        var year = parseInt(comps[2], 10); // Parses the year component as an integer in base 10
        if (isNaN(day) || isNaN(month) || isNaN(year)) { // Checks if day OR month OR year is not a number
            displayError("Date components must be integers."); // Displays error if date components are not integers
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        if (month < 1 || month > 12) { // Checks if month is less than 1 OR greater than 12
            displayError("Month must be between 1 and 12."); // Displays error if month is invalid
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        if (day < 1 || day > 31) { // Checks if day is less than 1 OR greater than 31
            displayError("Day must be between 1 and 31."); // Displays error if day is invalid
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        if (year < 1900 || year > new Date().getFullYear()) { // Checks if year is less than 1900 OR greater than current year
            displayError("Year must be a valid year (1900 or later)."); // Displays error if year is invalid
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        var today = new Date(); // Creates a new Date object for today’s date
        var givenDate = new Date(year, month - 1, day); // Creates a new Date object for the given date (month is 0-based)
        if (givenDate > today) { // Checks if givenDate is later than today
            displayError("Date of Birth cannot be greater than today."); // Displays error if date is in the future
            document.getElementById("dob").focus(); // Sets focus to the dob input field
            return false; // Returns false to indicate validation failure
        }
        // Validate National ID
        var nationalId = document.getElementById("nationalId").value; // Retrieves the value of the element with id "nationalId"
        if (nationalId === "" || isNaN(nationalId)) { // Checks if nationalId is empty OR is not a number
            displayError("Please enter a valid National ID."); // Displays error if nationalId is invalid
            document.getElementById("nationalId").focus(); // Sets focus to the nationalId input field
            return false; // Returns false to indicate validation failure
        }
        // Validate Account Number
        var accountNumber = document.getElementById("accountNumber").value; // Retrieves the value of the element with id "accountNumber"
        if (accountNumber === "" || isNaN(accountNumber)) { // Checks if accountNumber is empty OR is not a number
            displayError("Please enter a valid Account Number."); // Displays error if accountNumber is invalid
            document.getElementById("accountNumber").focus(); // Sets focus to the accountNumber input field
            return false; // Returns false to indicate validation failure
        }
    }
    return true; // Returns true to indicate all validations passed
}

function validateForm2() {
    // Validates the login form fields, checking for valid email and non-empty password
    displayError(""); // Calls displayError with an empty string to clear any previous error message
    // Validate Email
    var email = document.getElementById("signinEmail").value; // Retrieves the value of the element with id "signinEmail"
    if (email === "" || !email.includes("@") || !email.includes(".")) { // Checks if email is empty OR does not include "@" OR does not include "."
        displayError("Please enter a valid Email Address."); // Displays error if email is invalid
        document.getElementById("signinEmail").focus(); // Sets focus to the signinEmail input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Password
    var password = document.getElementById("signinPassword").value.trim(); // Retrieves and trims the value of the element with id "signinPassword"
    if (password === "") { // Checks if password is strictly equal to an empty string
        displayError("Password cannot be empty."); // Displays error if password is empty
        document.getElementById("signinPassword").focus(); // Sets focus to the signinPassword input field
        return false; // Returns false to indicate validation failure
    }
    return true; // Returns true to indicate all validations passed
}

function validateFormLoans() {
    // Validates the loan creation form, ensuring valid loan type, interest rate, amount, and duration
    displayError(""); // Calls displayError with an empty string to clear any previous error message
    // Validate Loan Type
    var role = document.getElementById("type").value; // Retrieves the value of the element with id "type"
    if (role === "--select option--") { // Checks if role is strictly equal to "--select option--"
        displayError("You must select a valid loan type from the options!"); // Displays error if loan type is invalid
        return false; // Returns false to indicate validation failure
    }
    // Validate Interest Rate
    var interestRate = document.getElementById("interestRate").value.trim(); // Retrieves and trims the value of the element with id "interestRate"
    if (interestRate === "" || isNaN(interestRate) || parseFloat(interestRate) <= 0 || parseFloat(interestRate) > 100) { // Checks if interestRate is empty OR is not a number OR is less than or equal to 0 OR is greater than 100
        displayError("Interest rate must be between 0.01 and 100."); // Displays error if interestRate is invalid
        document.getElementById("interestRate").focus(); // Sets focus to the interestRate input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Amount
    var amount = document.getElementById("maxAmount").value.trim(); // Retrieves and trims the value of the element with id "maxAmount"
    if (amount === "" || isNaN(amount) || parseInt(amount, 10) <= 0 || parseInt(amount, 10) < 500) { // Checks if amount is empty OR is not a number OR is less than or equal to 0 OR is less than 500
        displayError("Amount must be a positive number of at least 500."); // Displays error if amount is invalid
        document.getElementById("maxAmount").focus(); // Sets focus to the maxAmount input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Maximum Duration
    var duration = document.getElementById("maxDuration").value.trim(); // Retrieves and trims the value of the element with id "maxDuration"
    if (duration === "" || isNaN(duration) || parseInt(duration, 10) <= 0) { // Checks if duration is empty OR is not a number OR is less than or equal to 0
        displayError("Duration must be a positive number."); // Displays error if duration is invalid
        document.getElementById("maxDuration").focus(); // Sets focus to the maxDuration input field
        return false; // Returns false to indicate validation failure
    }
    return true; // Returns true to indicate all validations passed
}

function validateFormActivity() {
    // Validates the activity table form, checking user ID, activity, date/time, and activity type
    displayError(""); // Calls displayError with an empty string to clear any previous error message
    // Validate User ID
    var userId = document.getElementById("userId").value; // Retrieves the value of the element with id "userId"
    if (userId === "" || isNaN(userId)) { // Checks if userId is empty OR is not a number
        displayError("Please enter a valid user ID."); // Displays error if userId is invalid
        document.getElementById("userId").focus(); // Sets focus to the userId input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Activity
    var activity = document.getElementById("activity").value; // Retrieves the value of the element with id "activity"
    if (activity === "") { // Checks if activity is strictly equal to an empty string
        displayError("Please enter the Activity."); // Displays error if activity is empty
        document.getElementById("activity").focus(); // Sets focus to the activity input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Activity Date & Time
    var activityDateTime = document.getElementById("activityDateTime").value; // Retrieves the value of the element with id "activityDateTime"
    var dateTimeParts = activityDateTime.split(" "); // Splits the dateTime string by space into an array
    if (dateTimeParts.length !== 2) { // Checks if array does not have exactly 2 parts
        displayError("Activity Date & Time must be in the format dd-mm-yyyy HH:MM."); // Displays error if format is invalid
        document.getElementById("activityDateTime").focus(); // Sets focus to the activityDateTime input field
        return false; // Returns false to indicate validation failure
    }
    var dateParts = dateTimeParts[0].split("-"); // Splits the date part by hyphen into an array
    var timeParts = dateTimeParts[1].split(":"); // Splits the time part by colon into an array
    if (dateParts.length !== 3 || timeParts.length !== 2) { // Checks if date array does not have 3 parts OR time array does not have 2 parts
        displayError("Activity Date & Time must be in the format dd-mm-yyyy HH:MM."); // Displays error if format is invalid
        document.getElementById("activityDateTime").focus(); // Sets focus to the activityDateTime input field
        return false; // Returns false to indicate validation failure
    }
    var day = parseInt(dateParts[0], 10); // Parses the day component as an integer in base 10
    var month = parseInt(dateParts[1], 10); // Parses the month component as an integer in base 10
    var year = parseInt(dateParts[2], 10); // Parses the year component as an integer in base 10
    var hour = parseInt(timeParts[0], 10); // Parses the hour component as an integer in base 10
    var minute = parseInt(timeParts[1], 10); // Parses the minute component as an integer in base 10
    if (isNaN(day) || isNaN(month) || isNaN(year) || isNaN(hour) || isNaN(minute)) { // Checks if any component is not a number
        displayError("Date and time components must be integers."); // Displays error if components are not integers
        document.getElementById("activityDateTime").focus(); // Sets focus to the activityDateTime input field
        return false; // Returns false to indicate validation failure
    }
    if (month < 1 || month > 12) { // Checks if month is less than 1 OR greater than 12
        displayError("Month must be between 1 and 12."); // Displays error if month is invalid
        document.getElementById("activityDateTime").focus(); // Sets focus to the activityDateTime input field
        return false; // Returns false to indicate validation failure
    }
    if (day < 1 || day > 31) { // Checks if day is less than 1 OR greater than 31
        displayError("Day must be between 1 and 31."); // Displays error if day is invalid
        document.getElementById("activityDateTime").focus(); // Sets focus to the activityDateTime input field
        return false; // Returns false to indicate validation failure
    }
    if (hour < 0 || hour > 23 || minute < 0 || minute > 59) { // Checks if hour is less than 0 OR greater than 23 OR minute is less than 0 OR greater than 59
        displayError("Time must be in a valid format (HH:MM)."); // Displays error if time is invalid
        document.getElementById("activityDateTime").focus(); // Sets focus to the activityDateTime input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Activity Type
    var activityType = document.getElementById("activityType").value; // Retrieves the value of the element with id "activityType"
    if (activityType === "") { // Checks if activityType is strictly equal to an empty string
        displayError("Please enter the Activity Type."); // Displays error if activityType is empty
        document.getElementById("activityType").focus(); // Sets focus to the activityType input field
        return false; // Returns false to indicate validation failure
    }
    return true; // Returns true to indicate all validations passed
}

function validateFormUsers() {
    // Validates the users table form, checking role, names, email, phone, and password
    displayError(""); // Calls displayError with an empty string to clear any previous error message
    // Validate Role
    var role = document.getElementById("user-role").value; // Retrieves the value of the element with id "user-role"
    if (role === "--select option--") { // Checks if role is strictly equal to "--select option--"
        displayError("You must select a valid role (Customer, Lender, or Admin)."); // Displays error if role is invalid
        return false; // Returns false to indicate validation failure
    }
    // Validate First Name
    var firstName = document.getElementById("firstName").value; // Retrieves the value of the element with id "firstName"
    if (firstName === "") { // Checks if firstName is strictly equal to an empty string
        displayError("Please enter First Name."); // Displays error if firstName is empty
        document.getElementById("firstName").focus(); // Sets focus to the firstName input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Second Name
    var secondName = document.getElementById("secondName").value; // Retrieves the value of the element with id "secondName"
    if (secondName === "") { // Checks if secondName is strictly equal to an empty string
        displayError("Please enter Second Name."); // Displays error if secondName is empty
        document.getElementById("secondName").focus(); // Sets focus to the secondName input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Email
    var email = document.getElementById("email").value; // Retrieves the value of the element with id "email"
    if (email === "" || !email.includes("@") || !email.includes(".")) { // Checks if email is empty OR does not include "@" OR does not include "."
        displayError("Please enter a valid Email address."); // Displays error if email is invalid
        document.getElementById("email").focus(); // Sets focus to the email input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Phone
    var phone = document.getElementById("phone").value.trim(); // Retrieves and trims the value of the element with id "phone"
    if (phone === "" || phone.length !== 10 || isNaN(phone) || !phone.startsWith("0")) { // Checks if phone is empty OR length is not exactly 10 OR is not a number OR does not start with "0"
        displayError("Phone number must be exactly 10 digits and start with 0."); // Displays error if phone is invalid
        document.getElementById("phone").focus(); // Sets focus to the phone input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Password
    const password = document.getElementById("password").value.trim(); // Retrieves and trims the value of the element with id "password"
    const confirmPassword = document.getElementById("confPassword").value.trim(); // Retrieves and trims the value of the element with id "confPassword"
    if (password === "") { // Checks if password is strictly equal to an empty string
        displayError("Password cannot be empty."); // Displays error if password is empty
        return false; // Returns false to indicate validation failure
    }
    if (password.length < 8) { // Checks if password length is less than 8 characters
        displayError("Password must be at least 8 characters."); // Displays error if password is too short
        return false; // Returns false to indicate validation failure
    }
    if (password !== confirmPassword) { // Checks if password is not strictly equal to confirmPassword
        displayError("Passwords do not match."); // Displays error if passwords do not match
        return false; // Returns false to indicate validation failure
    }
    return true; // Returns true to indicate all validations passed
}

function validateLoanApplicationForm() {
    // Validates the loan application form, checking amount, duration, collateral, and image
    displayError(""); // Calls displayError with an empty string to clear any previous error message
    // Validate Amount Needed
    const amountNeeded = document.getElementById("amountNeeded").value.trim(); // Retrieves and trims the value of the element with id "amountNeeded"
    if (amountNeeded === "" || isNaN(amountNeeded) || parseFloat(amountNeeded) <= 0) { // Checks if amountNeeded is empty OR is not a number OR is less than or equal to 0
        displayError("Amount needed must be a positive number."); // Displays error if amountNeeded is invalid
        document.getElementById("amountNeeded").focus(); // Sets focus to the amountNeeded input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Duration
    const duration = document.getElementById("duration").value.trim(); // Retrieves and trims the value of the element with id "duration"
    if (duration === "" || isNaN(duration) || parseInt(duration, 10) <= 0) { // Checks if duration is empty OR is not a number OR is less than or equal to 0
        displayError("Duration must be a positive number."); // Displays error if duration is invalid
        document.getElementById("duration").focus(); // Sets focus to the duration input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Collateral Value
    const collateralValue = document.getElementById("collateralValue").value.trim(); // Retrieves and trims the value of the element with id "collateralValue"
    if (collateralValue === "" || isNaN(collateralValue) || parseFloat(collateralValue) <= 0) { // Checks if collateralValue is empty OR is not a number OR is less than or equal to 0
        displayError("Collateral value must be a positive number."); // Displays error if collateralValue is invalid
        document.getElementById("collateralValue").focus(); // Sets focus to the collateralValue input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Collateral Description
    const collateralDesc = document.getElementById("collateralDesc").value.trim(); // Retrieves and trims the value of the element with id "collateralDesc"
    if (collateralDesc === "") { // Checks if collateralDesc is strictly equal to an empty string
        displayError("Please enter a collateral description."); // Displays error if collateralDesc is empty
        document.getElementById("collateralDesc").focus(); // Sets focus to the collateralDesc input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Amount Needed against Maximum Amount
    const maxAmountText = document.getElementById("displayMaxAmount").innerText.replace(/,/g, ''); // Retrieves innerText of element with id "displayMaxAmount" and removes commas
    const maxAmount = parseFloat(maxAmountText); // Parses the cleaned text as a floating-point number
    if (isNaN(maxAmount)) { // Checks if maxAmount is not a number
        displayError("Invalid maximum amount value."); // Displays error if maxAmount is invalid
        return false; // Returns false to indicate validation failure
    }
    if (parseFloat(amountNeeded) > maxAmount) { // Checks if amountNeeded is greater than maxAmount
        displayError("Amount needed cannot exceed the maximum amount."); // Displays error if amountNeeded exceeds maxAmount
        document.getElementById("amountNeeded").focus(); // Sets focus to the amountNeeded input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Duration against Maximum Duration
    const maxDurationText = document.getElementById("displayMaxDuration").innerText.replace(/[^0-9]/g, ''); // Retrieves innerText of element with id "displayMaxDuration" and removes non-digits
    const maxDuration = parseInt(maxDurationText, 10); // Parses the cleaned text as an integer in base 10
    if (isNaN(maxDuration)) { // Checks if maxDuration is not a number
        displayError("Invalid maximum duration value."); // Displays error if maxDuration is invalid
        return false; // Returns false to indicate validation failure
    }
    if (parseInt(duration, 10) > maxDuration) { // Checks if duration is greater than maxDuration
        displayError("Duration cannot exceed the maximum duration."); // Displays error if duration exceeds maxDuration
        document.getElementById("duration").focus(); // Sets focus to the duration input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Collateral Image
    const collateralImageInput = document.getElementById("collateralImage"); // Retrieves the element with id "collateralImage"
    const file = collateralImageInput.files[0]; // Gets the first selected file from the input
    // Check if a file is selected
    if (!file) { // Checks if file is undefined or null
        displayError("Please select a collateral image."); // Displays error if no file is selected
        collateralImageInput.focus(); // Sets focus to the collateralImage input field
        return false; // Returns false to indicate validation failure
    }
    // Validate file type (image only)
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Defines an array of allowed MIME types
    if (!allowedTypes.includes(file.type)) { // Checks if file type is not in allowedTypes
        displayError("Only JPEG, PNG, and GIF images are allowed."); // Displays error if file type is invalid
        collateralImageInput.focus(); // Sets focus to the collateralImage input field
        return false; // Returns false to indicate validation failure
    }
    // Validate file size (max 2MB)
    const maxSize = 2 * 1024 * 1024; // Defines maximum file size as 2MB in bytes
    if (file.size > maxSize) { // Checks if file size exceeds maxSize
        displayError("Image file size must not exceed 2MB."); // Displays error if file is too large
        collateralImageInput.focus(); // Sets focus to the collateralImage input field
        return false; // Returns false to indicate validation failure
    }
    return true; // Returns true to indicate all validations passed
}

function displayPaymentError(message, autoClearTimeout = 2000) {
    // Displays an error message in the element with id "payment_error" and optionally clears it after a timeout
    const errorField = document.getElementById('payment_error'); // Gets the DOM element with id "payment_error"
    if (errorField) { // Checks if errorField exists
        errorField.innerText = message; // Sets the text content of the errorField to the provided message
        if (message && autoClearTimeout > 0) { // Checks if message is non-empty AND autoClearTimeout is positive
            setTimeout(() => clearPaymentError(), autoClearTimeout); // Schedules clearPaymentError after the specified timeout
        }
    } else { // Executes if errorField is not found
        console.error("Error div with ID 'payment_error' not found!"); // Logs an error to the console
    }
}

function clearPaymentError() {
    // Clears the error message in the element with id "payment_error"
    const errorField = document.getElementById('payment_error'); // Gets the DOM element with id "payment_error"
    if (errorField) { // Checks if errorField exists
        errorField.innerText = ""; // Sets the text content of the errorField to an empty string
    }
}

function validatePaymentForm() {
    // Validates the payment form, checking payment amount, balance, and payment method
    displayPaymentError(""); // Calls displayPaymentError with an empty string to clear any previous error message
    // Validate Payment Amount
    const paymentAmountInput = document.getElementById("payment_amount"); // Retrieves the element with id "payment_amount"
    if (!paymentAmountInput) { // Checks if paymentAmountInput does not exist
        displayPaymentError("Payment amount input field is missing. Please try again."); // Displays error if input is missing
        return false; // Returns false to indicate validation failure
    }
    const paymentAmount = paymentAmountInput.value.trim(); // Retrieves and trims the value of the paymentAmountInput
    if (paymentAmount === "" || isNaN(paymentAmount) || parseFloat(paymentAmount) <= 0) { // Checks if paymentAmount is empty OR is not a number OR is less than or equal to 0
        displayPaymentError("Payment amount must be a positive number."); // Displays error if paymentAmount is invalid
        paymentAmountInput.focus(); // Sets focus to the payment_amount input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Payment Amount against Balance
    const balanceElement = document.getElementById("payment_balance"); // Retrieves the element with id "payment_balance"
    if (!balanceElement) { // Checks if balanceElement does not exist
        displayPaymentError("Balance field is missing. Please try again."); // Displays error if balance field is missing
        return false; // Returns false to indicate validation failure
    }
    // Parse balance and validate
    const balanceText = balanceElement.value.replace(/[^0-9.]/g, ''); // Removes non-numeric and non-decimal characters from balance value
    const balance = parseFloat(balanceText); // Parses the cleaned text as a floating-point number
    if (isNaN(balance) || balance < 0) { // Checks if balance is not a number OR is negative
        displayPaymentError("Invalid balance value. Please contact support."); // Displays error if balance is invalid
        return false; // Returns false to indicate validation failure
    }
    const paymentAmountValue = parseFloat(paymentAmount); // Parses paymentAmount as a floating-point number
    if (paymentAmountValue > balance) { // Checks if paymentAmountValue exceeds balance
        displayPaymentError("Payment amount cannot exceed the remaining balance."); // Displays error if payment exceeds balance
        paymentAmountInput.focus(); // Sets focus to the payment_amount input field
        return false; // Returns false to indicate validation failure
    }
    // Validate Payment Method
    const paymentMethodInput = document.getElementById("payment_method"); // Retrieves the element with id "payment_method"
    if (!paymentMethodInput) { // Checks if paymentMethodInput does not exist
        displayPaymentError("Payment method field is missing. Please try again."); // Displays error if payment method field is missing
        return false; // Returns false to indicate validation failure
    }
    const paymentMethod = paymentMethodInput.value.trim(); // Retrieves and trims the value of the paymentMethodInput
    if (paymentMethod === "") { // Checks if paymentMethod is strictly equal to an empty string
        displayPaymentError("Please select a payment method."); // Displays error if paymentMethod is empty
        paymentMethodInput.focus(); // Sets focus to the payment_method input field
        return false; // Returns false to indicate validation failure
    }
    return true; // Returns true to indicate all validations passed
}