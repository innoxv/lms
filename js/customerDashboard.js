// ACTIVE NAV LINK SECTION
// Manages the visual indication of the active navigation link based on the current URL hash
function updateActiveNavLink() {
    // Selects all anchor tags (<a>) inside list items within the navigation menu (.nav ul li a)
    const navLinks = document.querySelectorAll('.nav ul li a');
    // Gets the current URL hash - no default to prevent dashboard flash
    const currentHash = window.location.hash;

    // Loops through all navigation links to remove the 'active' CSS class, resetting their appearance
    navLinks.forEach(link => link.classList.remove('active'));

    // Finds the navigation link whose href attribute matches the current hash
    const activeLink = document.querySelector(`.nav ul li a[href="${currentHash}"]`);
    // Checks if a matching link was found
    if (activeLink) {
        // Adds the 'active' CSS class to the matching link to highlight it visually
        activeLink.classList.add('active');
    } else if (!currentHash) {
        // Only defaults to dashboard if no hash exists (initial load)
        document.querySelector('.nav ul li a[href="#dashboard"]').classList.add('active');
    }

}

// Sets up navigation link behavior when the page loads or the URL hash changes
document.addEventListener('DOMContentLoaded', function() {
    // Ensures hash exists immediately to prevent dashboard flash
    if (!window.location.hash) {
        // Sets dashboard hash without adding to browser history
        history.replaceState(null, null, '#dashboard');
    }

    // Calls updateActiveNavLink to set the initial active link when the page loads
    updateActiveNavLink();

    // Adds an event listener to detect changes in the URL hash (when the user navigates)
    window.addEventListener('hashchange', updateActiveNavLink);

    // Uses event delegation for more efficient click handling
    document.querySelector('.nav').addEventListener('click', function(e) {
        // Checks if clicked element is a navigation link
        const navLink = e.target.closest('a');
        if (navLink && navLink.getAttribute('href').startsWith('#')) {
            // Removes the 'active' class from all navigation links
            document.querySelectorAll('.nav ul li a').forEach(l => l.classList.remove('active'));
            // Adds the 'active' class to the clicked link
            navLink.classList.add('active');
        }
    });
});

// METRICS FONT SIZE ADJUSTMENT
// Adjusts the font size of metric values to prevent text overflow in containers
function adjustMetricsFontSize() {
    // Selects all metric value elements with class 'span-2' inside .metrics
    const metricValues = document.querySelectorAll('.metrics .span-2');
    
    // Defines a function to adjust font sizes based on container width
    function adjustSizes() {
        // Loops through each metric value element
        metricValues.forEach(span => {
            // Get the metric-value-container (the actual container we need to fit within)
            const container = span.closest('.metric-value-container');
            if (!container) return;
            
            // Reset font size to default for accurate measurement
            span.style.fontSize = '4em';
            
            // Force a reflow to ensure the reset takes effect
            container.offsetHeight;
            
            // Get container dimensions (accounting for padding)
            const containerWidth = container.clientWidth;
            const containerHeight = container.clientHeight;
            
            // Get text dimensions
            const textWidth = span.scrollWidth;
            const textHeight = span.scrollHeight;
            
            // Calculate scale ratios for both width and height
            const widthRatio = containerWidth / textWidth;
            const heightRatio = containerHeight / textHeight;
            
            // Use the smaller ratio to ensure content fits in both dimensions
            const scaleRatio = Math.min(widthRatio, heightRatio);
            
            // Only scale down if content is too large (scaleRatio < 1)
            if (scaleRatio < 1) {
                // Calculate new font size (start from 4em and scale down)
                const newSize = Math.max(1, 4 * scaleRatio * 0.9); // 0.9 for safety margin
                span.style.fontSize = `${newSize}em`;
            }
            // If scaleRatio >= 1, keep the default 4em size
        });
    }
    
    // Debounce function to prevent excessive calls during resize
    let resizeTimeout;
    function debouncedAdjustSizes() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(adjustSizes, 100);
    }
    
    // Run on page load with a small delay to ensure layout is complete
    setTimeout(adjustSizes, 100);
    
    // Add resize event listener with debouncing
    window.addEventListener('resize', debouncedAdjustSizes);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', adjustMetricsFontSize);
} else {
    adjustMetricsFontSize();
}

// LOAN APPLICATION SECTION
// Sets up event listeners for loan application and navigation functionality
function setupEventListeners() {
    // Adds a click event listener to the Apply Loan link, if it exists
    document.getElementById('applyLoanLink')?.addEventListener('click', function(e) {
        // Prevents the default link behavior (page navigation)
        e.preventDefault();
        // Sets the URL hash to '#applyLoan' to navigate to the loan application section
        window.location.hash = '#applyLoan';
    });
    
    // Adds a click event listener to the Loan History link, if it exists
    document.getElementById('loanHistoryLink')?.addEventListener('click', function(e) {
        // Prevents the default link behavior
        e.preventDefault();
        // Sets the URL hash to '#loanHistory' to navigate to the loan history section
        window.location.hash = '#loanHistory';
    });

    // Gets the loan filter form element
    const loanFilterForm = document.getElementById('loanFilterForm');
    
    // Selects all checkbox and radio inputs in the loan filter form
    loanFilterForm.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(input => {
        // Adds a change event listener to each input
        input.addEventListener('change', function() {
            // Submits the form automatically when a filter is changed
            loanFilterForm.submit();
        });
    });

    // Selects all quick amount buttons
    document.querySelectorAll('.quick-amounts button').forEach(btn => {
        // Adds a click event listener to each quick amount button
        btn.addEventListener('click', function() {
            // Sets the min_amount input value to the button’s data-min attribute
            loanFilterForm.querySelector('[name="min_amount"]').value = this.dataset.min;
            // Sets the max_amount input value to the button’s data-max attribute
            loanFilterForm.querySelector('[name="max_amount"]').value = this.dataset.max;
            // Submits the form to apply the amount range filter
            loanFilterForm.submit();
        });
    });

    // Adds a click event listener to the reset button, if it exists
    document.getElementById('res')?.addEventListener('click', function(e) {
        // Prevents the default button behavior
        e.preventDefault();
        // Redirects to the customer dashboard with a reset_filters parameter
        window.location.href = 'customerDashboard.php?reset_filters=true#applyLoan';
    });

    // Adds an input event listener to the amount needed field, if it exists
    document.getElementById('amountNeeded')?.addEventListener('input', calculateInstallments);
    // Adds an input event listener to the duration field, if it exists
    document.getElementById('duration')?.addEventListener('input', calculateInstallments);
    
    // Adds a click event listener to the cancel button, if it exists
    document.getElementById('cancelBtn')?.addEventListener('click', function() {
        // Hides the loan application popup
        document.getElementById('loanPopup').style.display = 'none';
        // Removes the popup-open class from the body to restore normal scrolling
        document.body.classList.remove('popup-open');
    });
    
    // Adds a click event listener to the close popup button, if it exists
    document.getElementById('closePopupBtn')?.addEventListener('click', function() {
        // Hides the loan details popup
        document.getElementById('loanDetailsPopup').style.display = 'none';
        // Removes the popup-open class from the body
        document.body.classList.remove('popup-open');
    });
    
}

// Calculates monthly installments for a loan based on amount, duration, and interest rate
function calculateInstallments() {
    // Parses the amount needed input value, defaulting to 0 if invalid
    const amount = parseFloat(document.getElementById('amountNeeded').value) || 0;
    // Parses the duration input value, defaulting to 1 if invalid
    const duration = parseInt(document.getElementById('duration').value) || 1;
    // Parses the interest rate input value, defaulting to 0 if invalid
    const rate = parseFloat(document.getElementById('interestRate').value) || 0;
    
    // Checks if all inputs are valid and non-zero
    if (amount > 0 && duration > 0 && rate > 0) {
        // Calculates the monthly interest rate by dividing annual rate by 100 and 12
        const monthlyRate = rate / 100 / 12;
        // Calculates the numerator for the amortization formula
        const numerator = amount * monthlyRate * Math.pow(1 + monthlyRate, duration);
        // Calculates the denominator for the amortization formula
        const denominator = Math.pow(1 + monthlyRate, duration) - 1;
        // Calculates the monthly installment amount
        const monthlyInstallment = numerator / denominator;
        
        // Sets the installments input value to the calculated amount, rounded to 2 decimals
        document.getElementById('installments').value = monthlyInstallment.toFixed(2);
    } else {
        // Clears the installments input if any input is invalid
        document.getElementById('installments').value = '';
    }
}

// MESSAGE HANDLING
// Manages alert messages with automatic fade-out
function handleMessages() {
    // Selects all elements with class 'alert'
    const alerts = document.querySelectorAll('.alert');
    // Loops through each alert element
    alerts.forEach(alert => {
        // Starts a timer to fade out the alert after 3 seconds
        setTimeout(() => {
            // Sets the alert opacity to 0 for a fade-out effect
            alert.style.opacity = '0';
            // Removes the alert from the DOM after the fade-out transition
            setTimeout(() => {
                alert.remove();
            }, 500); // Matches the CSS transition duration
        }, 3000); // Displays for 3 seconds
    });
}

// SEARCH FUNCTIONALITY
// Handles lender search with autocomplete suggestions
document.addEventListener('DOMContentLoaded', () => {
    // Gets the search input field
    const searchInput = document.getElementById('lenderSearch');
    // Gets the container for search suggestions
    const suggestionsDiv = document.getElementById('suggestions');
    // Gets the loan filter form
    const form = document.getElementById('loanFilterForm');

    // Declares a variable to store the debounce timer
    let debounceTimer;

    // Adds an input event listener to the search input field
    if (searchInput && suggestionsDiv && form) {
        searchInput.addEventListener('input', () => {
            // Clears any existing debounce timer to prevent multiple rapid calls
            clearTimeout(debounceTimer);
            // Sets a new debounce timer to delay the search by 100ms
            debounceTimer = setTimeout(() => {
                // Gets and trims the search query
                const query = searchInput.value.trim();
                // Checks if the query is at least 1 character long
                if (query.length >= 1) {
                    // Calls fetchSuggestions to get matching results
                    fetchSuggestions(query);
                } else {
                    // Hides and clears the suggestions container if the query is too short
                    suggestionsDiv.style.display = 'none';
                    suggestionsDiv.innerHTML = '';
                }
            }, 100); // 100ms delay to debounce input
        });
    }

    // Fetches suggestions from the server based on the search query
    function fetchSuggestions(query) {
        // Makes an AJAX request to searchSuggestions.php with the encoded query
        fetch(`searchSuggestions.php?query=${encodeURIComponent(query)}`)
            // Parses the response as JSON
            .then(response => response.json())
            // Processes the JSON data
            .then(data => {
                // Clears existing suggestions
                suggestionsDiv.innerHTML = '';
                // Checks if there are any loan types or lenders in the response
                if ((data.loan_types && data.loan_types.length > 0) || (data.lenders && data.lenders.length > 0)) {
                    // Loops through lender suggestions
                    data.lenders.forEach(item => {
                        // Creates a div for each lender suggestion
                        const div = document.createElement('div');
                        // Sets the class for styling the suggestion
                        div.className = 'suggestion-item suggestion-lender';
                        // Sets the text content to the lender name
                        div.textContent = item;
                        // Adds a click event listener to the suggestion
                        div.addEventListener('click', () => {
                            // Sets the search input value to the selected lender
                            searchInput.value = item;
                            // Finds or creates a hidden input for the lender name
                            let lenderInput = form.querySelector('input[name="lender_name"]');
                            if (!lenderInput) {
                                lenderInput = document.createElement('input');
                                lenderInput.type = 'hidden';
                                lenderInput.name = 'lender_name';
                                form.appendChild(lenderInput);
                            }
                            // Sets the lender input value
                            lenderInput.value = item;
                            // Finds or creates a hidden input for the search query
                            let hiddenInput = form.querySelector('input[name="search_query"]');
                            if (!hiddenInput) {
                                hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'search_query';
                                form.appendChild(hiddenInput);
                            }
                            // Sets the search query input value
                            hiddenInput.value = item;
                            // Submits the form to apply the filter
                            form.submit();
                            // Hides the suggestions container
                            suggestionsDiv.style.display = 'none';
                        });
                        // Appends the suggestion to the suggestions container
                        suggestionsDiv.appendChild(div);
                    });

                    // Loops through loan type suggestions
                    data.loan_types.forEach(item => {
                        // Creates a div for each loan type suggestion
                        const div = document.createElement('div');
                        // Sets the class for styling the suggestion
                        div.className = 'suggestion-item suggestion-type';
                        // Sets the text content to the loan type
                        div.textContent = item;
                        // Adds a click event listener to the suggestion
                        div.addEventListener('click', () => {
                            // Sets the search input value to the selected loan type
                            searchInput.value = item;
                            // Finds the checkbox for the selected loan type
                            const checkbox = document.querySelector(`input[name="loan_type[]"][value="${item}"]`);
                            // Checks if the checkbox exists
                            if (checkbox) {
                                // Checks the checkbox to apply the filter
                                checkbox.checked = true;
                                // Finds or creates a hidden input for the search query
                                let hiddenInput = form.querySelector('input[name="search_query"]');
                                if (!hiddenInput) {
                                    hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = 'search_query';
                                    form.appendChild(hiddenInput);
                                }
                                // Sets the search query input value
                                hiddenInput.value = item;
                                // Submits the form to apply the filter
                                form.submit();
                            }
                            // Hides the suggestions container
                            suggestionsDiv.style.display = 'none';
                        });
                        // Appends the suggestion to the suggestions container
                        suggestionsDiv.appendChild(div);
                    });

                    // Shows the suggestions dropdown
                    suggestionsDiv.style.display = 'block';
                } else {
                    // Show "No results found" if no lenders or loan types
                    const div = document.createElement('div');
                    div.className = 'no-search-results';
                    div.textContent = 'No results found!';
                    suggestionsDiv.appendChild(div);
                    suggestionsDiv.style.display = 'block';
                }
            });
    }

    // Adds a click event listener to hide suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (searchInput && suggestionsDiv) {
            // Checks if the click target is outside the search input and suggestions
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                // Hides the suggestions container
                suggestionsDiv.style.display = 'none';
            }
        }
    });
});

// LOAN HISTORY SECTION
// Displays a popup with detailed information about a loan
function showLoanDetailsPopup(loanId, loanType, lenderName, amount, interestRate, duration, installments, collateralValue, collateralDescription, status, createdAt) {
    // Sets the text content of the element with ID 'viewLoanId' to the loan ID
    document.getElementById('viewLoanId').textContent = loanId;
    // Sets the text content of the element with ID 'viewLoanType' to the loan type
    document.getElementById('viewLoanType').textContent = loanType;
    // Sets the text content of the element with ID 'viewLenderName' to the lender name
    document.getElementById('viewLenderName').textContent = lenderName;
    // Formats the amount with KES prefix and two decimal places
    document.getElementById('viewAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Appends a percentage sign to the interest rate
    document.getElementById('viewInterestRate').textContent = parseFloat(interestRate) + '%';
    // Appends ' months' to the duration
    document.getElementById('viewDuration').textContent = duration + ' months';
    // Formats the installments with KES prefix and two decimal places
    document.getElementById('viewInstallments').textContent = parseFloat(installments).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Formats the collateral value with KES prefix and two decimal places
    document.getElementById('viewCollateralValue').textContent = parseFloat(collateralValue).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Sets the text content of the element with ID 'viewCollateralDescription'
    document.getElementById('viewCollateralDescription').textContent = collateralDescription;

    // Gets the status element to display the loan status
    const statusElement = document.getElementById('viewStatus');
    // Clears any existing content in the status element
    statusElement.innerHTML = '';
    // Creates a new span element for the status badge
    const statusBadge = document.createElement('span');
    // Sets the class of the badge based on the status (loan-status pending)
    statusBadge.className = `loan-status ${status.toLowerCase()}`;
    // Capitalizes the first letter of the status and sets it as the badge text
    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    // Appends the status badge to the status element
    statusElement.appendChild(statusBadge);

    // Creates a Date object from the createdAt timestamp
    const date = new Date(createdAt);
    // Formats the date and sets it in the createdAt element
    document.getElementById('viewCreatedAt').textContent = date.toLocaleString('en-US', {
        month: 'short', 
        day: 'numeric', 
        year: 'numeric', 
        hour: 'numeric', 
        minute: '2-digit'
    });

    // Adds a delete button for submitted loans
    const actionButtons = document.getElementById('loanActionButtons');
    // Clears any existing content in the action buttons container
    actionButtons.innerHTML = '';
    // Checks if the loan status is submitted
    if (['submitted'].includes(status.toLowerCase())) {
        // Creates a form for deleting the loan application
        const deleteForm = document.createElement('form');
        // Sets the form’s action to the PHP script for deletion
        deleteForm.action = 'deleteApplication.php';
        // Sets the form’s method to POST
        deleteForm.method = 'post';
        // Sets the form’s class for styling
        deleteForm.className = 'delete-form';
        // Sets the form’s HTML content with a hidden input and delete button
        deleteForm.innerHTML = `
            <input type="hidden" name="loan_id" value="${loanId}">
            <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this application?')">
                Delete Application
            </button>
        `;
        // Appends the delete form to the action buttons container
        actionButtons.appendChild(deleteForm);
    }

    // Shows the loan details popup
    document.getElementById('loanDetailsPopup').style.display = 'flex';
    // Adds the popup-open class to the body to prevent scrolling
    document.body.classList.add('popup-open');
}

// Hides the loan details popup
function hideLoanDetailsPopup() {
    // Hides the loan details popup
    document.getElementById('loanDetailsPopup').style.display = 'none';
    // Removes the popup-open class from the body
    document.body.classList.remove('popup-open');
}

// Initializes view buttons for loan history
document.addEventListener('DOMContentLoaded', function() {
    // Selects all elements with class 'view-btn' (view buttons for loans)
    document.querySelectorAll('.view-btn').forEach(btn => {
        // Adds a click event listener to each view button
        btn.addEventListener('click', function() {
            // Calls showLoanDetailsPopup with data attributes from the button
            showLoanDetailsPopup(
                this.dataset.loanId, // Accesses the 'data-loan-id' attribute
                this.dataset.loanType, // Accesses the 'data-loan-type' attribute
                this.dataset.lenderName, // Accesses the 'data-lender-name' attribute
                this.dataset.amount, // Accesses the 'data-amount' attribute
                this.dataset.interestRate, // Accesses the 'data-interest-rate' attribute
                this.dataset.duration, // Accesses the 'data-duration' attribute
                this.dataset.installments, // Accesses the 'data-installments' attribute
                this.dataset.collateralValue, // Accesses the 'data-collateral-value' attribute
                this.dataset.collateralDescription, // Accesses the 'data-collateral-description' attribute
                this.dataset.status, // Accesses the 'data-status' attribute
                this.dataset.createdAt // Accesses the 'data-created-at' attribute
            );
        });
    });

    // Adds a click event listener to the close loan popup button
    document.getElementById('closeLoanPopupBtn').addEventListener('click', hideLoanDetailsPopup);

    // Adds a click event listener to the loan details popup to close when clicking outside
    document.getElementById('loanDetailsPopup').addEventListener('click', function(e) {
        // Checks if the click target is the popup itself (overlay)
        if (e.target === this) {
            // Closes the popup
            hideLoanDetailsPopup();
        }
    });
});

// PAYMENT TRACKING SECTION
// Shows a popup for making a payment on a loan
function showPaymentPopup(button) {
    // Gets the loan ID from the button’s 'data-loan-id' attribute
    const loanId = button.getAttribute('data-loan-id');
    // Parses the loan amount from the button’s 'data-loan-amount' attribute, defaulting to 0
    const loanAmount = parseFloat(button.getAttribute('data-loan-amount')) || 0;
    // Parses the amount due from the button’s 'data-amount-due' attribute, defaulting to 0
    const amountDue = parseFloat(button.getAttribute('data-amount-due')) || 0;
    // Parses the amount paid from the button’s 'data-amount-paid' attribute, defaulting to 0
    const amountPaid = parseFloat(button.getAttribute('data-amount-paid')) || 0;
    // Parses the remaining balance from the button’s 'data-remaining-balance' attribute
    const remainingBalance = parseFloat(button.getAttribute('data-remaining-balance')) || 0;
    // Parses the installments from the button’s 'data-installments' attribute, defaulting to 0
    const installments = parseFloat(button.getAttribute('data-installments')) || 0;
    // Parses the installment balance from the button’s 'data-installment-balance' attribute
    const installmentBalance = parseFloat(button.getAttribute('data-installment-balance')) || 0;

    // Sets the value of the payment loan ID input
    document.getElementById('payment_loan_id').value = loanId;
    // Formats the loan amount as currency and sets it
    document.getElementById('payment_loan_amount').value = formatCurrency(loanAmount);
    // Formats the amount due as currency and sets it
    document.getElementById('payment_amount_due').value = formatCurrency(amountDue);
    // Formats the amount paid as currency and sets it
    document.getElementById('payment_amount_paid').value = formatCurrency(amountPaid);
    // Formats the remaining balance as currency and sets it
    document.getElementById('payment_balance').value = formatCurrency(remainingBalance);
    // Formats the installments as currency and sets it
    document.getElementById('payment_installments').value = formatCurrency(installments);
    // Formats the installment balance as currency and sets it
    document.getElementById('payment_installment_balance').value = formatCurrency(installmentBalance);

    // Resets the payment amount input to empty
    document.getElementById('payment_amount').value = '';
    // Resets the payment method dropdown to its first option
    document.getElementById('payment_method').selectedIndex = 0;
    
    // Shows the payment popup
    document.getElementById('paymentPopup').style.display = 'flex';
    // Adds the popup-open class to the body to prevent scrolling
    document.body.classList.add('popup-open');
}

// Closes the payment popup
function closePaymentPopup() {
    // Hides the payment popup
    document.getElementById('paymentPopup').style.display = 'none';
    // Removes the popup-open class from the body
    document.body.classList.remove('popup-open');
}

// Formats a number
function formatCurrency(x) {
    // Rounds up the number to two decimal places
    const roundedUp = Math.ceil(parseFloat(x) * 100) / 100;
    // Formats the number with two decimal places
    return roundedUp.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// TRANSACTION HISTORY SECTION
// Shows a popup with detailed information about a transaction payment
function showTransPaymentDetailsPopup(paymentId, loanId, lenderName, amount, paymentMethod, paymentType, paymentDate) {
    // Sets the text content of the element with ID 'transViewPaymentId' to the payment ID
    document.getElementById('transViewPaymentId').textContent = paymentId;
    // Sets the text content of the element with ID 'transViewLoanId' to the loan ID
    document.getElementById('transViewLoanId').textContent = loanId;
    // Sets the text content of the element with ID 'transViewLenderName' to the lender name
    document.getElementById('transViewLenderName').textContent = lenderName;
    // Formats the amount with two decimal places, or sets 'N/A' if invalid
    document.getElementById('transViewAmount').textContent = parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    // Formats the payment method ('credit_card' to 'Credit Card')
    document.getElementById('transViewPaymentMethod').textContent = paymentMethod.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

    // Gets the payment type element
    const typeElement = document.getElementById('transViewPaymentType');
    // Clears any existing content in the payment type element
    typeElement.innerHTML = '';
    // Creates a new span element for the payment type badge
    const typeBadge = document.createElement('span');
    // Sets the class of the badge based on the payment type (defaults to 'unknown')
    typeBadge.className = `payment-status ${paymentType.toLowerCase() || 'unknown'}`;
    // Capitalizes the first letter of the payment type and sets it as the badge text
    typeBadge.textContent = paymentType.charAt(0).toUpperCase() + paymentType.slice(1);
    // Appends the payment type badge to the payment type element
    typeElement.appendChild(typeBadge);

    // Creates a Date object from the payment date
    const date = new Date(paymentDate);
    // Formats the date and sets it in the payment date element
    document.getElementById('transViewPaymentDate').textContent = date.toLocaleString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    // Shows the transaction payment details popup overlay
    document.getElementById('transPaymentDetailsOverlay').style.display = 'block';
    // Shows the transaction payment details popup content
    document.getElementById('transPaymentDetailsPopup').style.display = 'block';
    // Adds the popup-open class to the body to prevent scrolling
    document.body.classList.add('popup-open');
}

// Hides the transaction payment details popup
function hideTransPaymentDetailsPopup() {
    // Hides the transaction payment details popup overlay
    document.getElementById('transPaymentDetailsOverlay').style.display = 'none';
    // Hides the transaction payment details popup content
    document.getElementById('transPaymentDetailsPopup').style.display = 'none';
    // Removes the popup-open class from the body
    document.body.classList.remove('popup-open');
}

// Initializes view buttons for transaction history
document.addEventListener('DOMContentLoaded', function() {
    // Selects all elements with class 'trans-btn-view' (view buttons for transactions)
    document.querySelectorAll('.trans-btn-view').forEach(btn => {
        // Adds a click event listener to each view button
        btn.addEventListener('click', function() {
            // Calls showTransPaymentDetailsPopup with data attributes from the button
            showTransPaymentDetailsPopup(
                this.dataset.transPaymentId, // Accesses the 'data-trans-payment-id' attribute
                this.dataset.transLoanId, // Accesses the 'data-trans-loan-id' attribute
                this.dataset.transLenderName, // Accesses the 'data-trans-lender-name' attribute
                this.dataset.transAmount, // Accesses the 'data-trans-amount' attribute
                this.dataset.transPaymentMethod, // Accesses the 'data-trans-payment-method' attribute
                this.dataset.transPaymentType, // Accesses the 'data-trans-payment-type' attribute
                this.dataset.transPaymentDate // Accesses the 'data-trans-payment-date' attribute
            );
        });
    });

    // Adds a click event listener to the close payment details button
    document.getElementById('transClosePaymentDetailsPopupBtn').addEventListener('click', hideTransPaymentDetailsPopup);

    // Adds a click event listener to the transaction payment details overlay
    document.getElementById('transPaymentDetailsOverlay').addEventListener('click', function(e) {
        // Checks if the click target is the overlay itself
        if (e.target === this) {
            // Closes the popup
            hideTransPaymentDetailsPopup();
        }
    });
});

// PROFILE EDIT SECTION
// Initializes event listeners for the profile edit popup functionality when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Retrieves the Edit Profile button element by its ID
    const editProfileBtn = document.getElementById('editProfileBtn');
    // Retrieves the profile edit overlay element by its ID
    const profileOverlay = document.getElementById('profileOverlay');
    // Retrieves the Cancel button within the profile edit popup by its ID
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    // Checks if both the Edit Profile button and profile overlay exist before adding event listeners
    if (editProfileBtn && profileOverlay) {
        // Adds a click event listener to the Edit Profile button to show the popup
        editProfileBtn.addEventListener('click', function () {
            // Displays the profile overlay as a flex container
            profileOverlay.style.display = 'flex';
            // Adds a class to the body to disable scrolling while the popup is open
            document.body.classList.add('popup-open');
        });
    }

    // Checks if both the Cancel button and profile overlay exist before adding event listeners
    if (cancelEditBtn && profileOverlay) {
        // Adds a click event listener to the Cancel button to hide the popup and clear the form
        cancelEditBtn.addEventListener('click', function () {
            // Hides the profile overlay
            profileOverlay.style.display = 'none';
            // Removes the popup-open class from the body to restore scrolling
            document.body.classList.remove('popup-open');
            // Retrieves the profile edit form element by its ID
            const profileForm = document.getElementById('profileEditForm');
            // Checks if the profile form exists before resetting it
            if (profileForm) {
                // Resets all form fields to their initial values
                profileForm.reset();
                // Retrieves the error message field by its ID
                const errorField = document.getElementById('profile_error');
                // Checks if the error field exists before clearing it
                if (errorField) errorField.innerText = ''; // Clears any existing error message
            }
        });
    }
});

// CHANGE PASSWORD POPUP SECTION
// Initializes event listeners for the change password popup functionality when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Retrieves the Change Password button element by its class
    const changePassBtn = document.querySelector('.change');
    // Retrieves the Change Password overlay element by its ID
    const changePassOverlay = document.getElementById('changePasswordOverlay');
    // Retrieves the Cancel button within the change password popup by its ID
    const cancelChangePassBtn = document.getElementById('cancelChangePassBtn');

    // Checks if both the Change Password button and overlay exist before adding event listeners
    if (changePassBtn && changePassOverlay) {
        // Adds a click event listener to the Change Password button to show the popup
        changePassBtn.addEventListener('click', function () {
            // Displays the change password overlay as a flex container
            changePassOverlay.style.display = 'flex';
            // Adds a class to the body to disable scrolling while the popup is open
            document.body.classList.add('popup-open');
        });
    }

    // Checks if both the Cancel button and change password overlay exist before adding event listeners
    if (cancelChangePassBtn && changePassOverlay) {
        // Adds a click event listener to the Cancel button to hide the popup and clear the form
        cancelChangePassBtn.addEventListener('click', function () {
            // Hides the change password overlay
            changePassOverlay.style.display = 'none';
            // Removes the popup-open class from the body to restore scrolling
            document.body.classList.remove('popup-open');
            // Retrieves the change password form element by its ID
            const passwordForm = document.getElementById('changePasswordForm');
            // Checks if the password form exists before resetting it
            if (passwordForm) {
                // Resets all form fields to their initial values
                passwordForm.reset();
                // Retrieves the error message field by its ID
                const errorField = document.getElementById('password_error');
                // Checks if the error field exists before clearing it
                if (errorField) errorField.innerText = ''; // Clears any existing error message
            }
        });
    }
});


// INITIALIZATIONS
// Sets up all dashboard functionalities when the page loads
document.addEventListener('DOMContentLoaded', function () {
    // Initializes the metrics font size adjustment
    // adjustMetricsFontSize(); // This line is now redundant as it's called in DOMContentLoaded

    // Initializes the bar chart 
    initializeBarChart();
    // Initializes the pie chart 
    initializePieChart();

    // Sets up event listeners for navigation and forms
    setupEventListeners();

    // Handles any existing alert messages
    handleMessages();

    // Initializes popup functionality
    initPopups();

    // Handles loan application popup messages
    const popup = document.getElementById('loanPopup');
    // Gets the alert element inside the loan popup, if it exists
    const alert = popup?.querySelector('.alert');

    // Checks if the popup and alert exist and if the alert has content
    if (popup && alert && alert.textContent.trim() !== '') {
        // Shows the loan popup
        popup.style.display = 'flex';
        // Adds the popup-open class to the body
        document.body.classList.add('popup-open');

        // Starts a timer to fade out the popup after 3 seconds
        setTimeout(() => {
            // Sets the popup opacity to 0 for a fade-out effect
            popup.style.opacity = '0';
            // Hides the popup after the fade-out transition
            setTimeout(() => {
                popup.style.display = 'none';
                // Resets the opacity
                popup.style.opacity = '';
                // Removes the popup-open class from the body
                document.body.classList.remove('popup-open');
            }, 500); // Matches the CSS transition duration
        }, 3000); // Displays for 3 seconds
    }

    // Handles profile popup messages
    const profileOverlay = document.getElementById('profileOverlay');
    // Gets the alert element inside the profile overlay, if it exists
    const profileAlert = profileOverlay?.querySelector('.alert');

    // Checks if the profile overlay and alert exist and if the alert has content
    if (profileOverlay && profileAlert && profileAlert.textContent.trim() !== '') {
        // Shows the profile overlay
        profileOverlay.style.display = 'flex';
        // Adds the popup-open class to the body
        document.body.classList.add('popup-open');

        // Starts a timer to fade out the alert after 3 seconds
        setTimeout(() => {
            // Sets the alert opacity to 0 for a fade-out effect
            profileAlert.style.opacity = '0';
            // Hides the alert and overlay after the fade-out transition
            setTimeout(() => {
                profileAlert.style.display = 'none';
                profileAlert.style.opacity = '';
                profileOverlay.style.display = 'none';
                document.body.classList.remove('popup-open');
            }, 500); // Matches the CSS transition duration
        }, 3000); // Displays for 3 seconds
    }
});

// POPUP MANAGEMENT
// Initializes popup functionality for loan applications and profile editing
function initPopups() {
    // Selects all elements with class 'popup-close' or 'cancel-btn'
    document.querySelectorAll('.popup-close, .cancel-btn').forEach(btn => {
        // Adds a click event listener to close all popups
        btn.addEventListener('click', closeAllPopups);
    });

    // Selects all popup overlay elements
    document.querySelectorAll('.popup-overlay').forEach(popup => {
        // Adds a click event listener to close popups when clicking outside
        popup.addEventListener('click', function (e) {
            // Checks if the click target is the overlay itself
            if (e.target === this) closeAllPopups();
        });
    });

    // Selects all Apply Now buttons
    document.querySelectorAll('.applynow').forEach(btn => {
        // Adds a click event listener to each Apply Now button
        btn.addEventListener('click', function () {
            // Sets the value of the offer ID input from the button’s 'data-offer' attribute
            document.getElementById('offerId').value = this.dataset.offer;
            // Sets the value of the lender ID input from the button’s 'data-lender' attribute
            document.getElementById('lenderId').value = this.dataset.lender;
            // Sets the value of the interest rate input from the button’s 'data-rate' attribute
            document.getElementById('interestRate').value = this.dataset.rate;

            // Sets the text content of the display lender name element
            document.getElementById('displayLenderName').textContent = this.dataset.name;
            // Sets the text content of the display loan type element
            document.getElementById('displayType').textContent = this.dataset.type;
            // Appends a percentage sign to the interest rate for display
            document.getElementById('displayInterestRate').textContent = this.dataset.rate + '%';
            // Formats the maximum amount with commas for display
            document.getElementById('displayMaxAmount').textContent = numberWithCommas(this.dataset.maxamount);
            // Appends ' months' to the maximum duration for display
            document.getElementById('displayMaxDuration').textContent = this.dataset.maxduration + ' months';

            // Shows the loan application popup
            document.getElementById('loanPopup').style.display = 'flex';
            // Adds the popup-open class to the body
            document.body.classList.add('popup-open');
        });
    });

}

// Closes all popups
function closeAllPopups() {
    // Selects all popup overlay elements
    document.querySelectorAll('.popup-overlay').forEach(popup => {
        // Hides each popup
        popup.style.display = 'none';
    });
    // Removes the popup-open class from the body
    document.body.classList.remove('popup-open');
}

// Formats a number with commas for readability
function numberWithCommas(x) {
    // Converts the number to a string and adds commas every three digits
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Confirms deletion of a loan application
function confirmDelete() {
    // Displays a confirmation dialog and returns the user’s choice
    return confirm('Are you sure you want to delete this application?');
}