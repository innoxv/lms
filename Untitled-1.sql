-- Populating the customers table 

INSERT INTO customers (name, email, phone, dob, national_id, address, status, registration_date, bank_account) VALUES
('John Kamau', 'johnkamau@gmail.com', '0712345678', '1990-01-01', 'AB12345678', '123 Nairobi Street', 'Active', '2025-01-01 10:00:00', '12345678901'),
('Jane Mwangi', 'janemwangi@gmail.com', '0723456789', '1985-05-15', 'CD78901234', '456 Mombasa Avenue', 'Active', '2025-01-02 11:00:00', '98765432109'),
('Alice Otieno', 'aliceotieno@gmail.com', '0734567890', '1992-03-12', 'EF34567890', '789 Kisumu Road', 'Inactive', '2025-01-03 12:00:00', '11235813213'),
('Bob Ochieng', 'bobochieng@gmail.com', '0745678901', '1988-07-23', 'GH90123456', '321 Eldoret Lane', 'Active', '2025-01-04 13:00:00', '31415926535'),
('Charles Njoroge', 'charlesnjoroge@gmail.com', '0756789012', '1995-02-28', 'IJ56789012', '654 Nakuru Street', 'Inactive', '2025-01-05 14:00:00', '27182818284'),
('Eve Wanjiku', 'evewanjiku@gmail.com', '0767890123', '1993-09-18', 'KL12345678', '987 Naivasha Drive', 'Active', '2025-01-06 15:00:00', '14142135623'),
('Frank Kiplagat', 'frankkiplagat@gmail.com', '0778901234', '1991-06-10', 'MN78901234', '123 Kericho Blvd', 'Active', '2025-01-07 16:00:00', '16180339887'),
('Grace Chebet', 'gracechebet@gmail.com', '0789012345', '1987-11-30', 'OP34567890', '456 Bomet Terrace', 'Inactive', '2025-01-08 17:00:00', '27182845904'),
('Hank Kiptoo', 'hankkiptoo@gmail.com', '0790123456', '1994-04-20', 'QR90123456', '789 Kajiado Circle', 'Active', '2025-01-09 18:00:00', '31415926536'),
('Ivy Wairimu', 'ivywairimu@gmail.com', '0701234567', '1989-08-25', 'ST56789012', '321 Nyeri Way', 'Inactive', '2025-01-10 19:00:00', '27182818285'),
('Jack Omondi', 'jackomondi@gmail.com', '0712345671', '1996-01-05', 'UV12345678', '654 Kakamega Trail', 'Active', '2025-01-11 20:00:00', '31415926537'),
('Kate Achieng', 'kateachieng@gmail.com', '0723456712', '1990-12-15', 'WX78901234', '987 Migori Crescent', 'Inactive', '2025-01-12 21:00:00', '27182818286'),
('Liam Kiprono', 'liamkiprono@gmail.com', '0734567123', '1986-03-08', 'YZ34567890', '123 Bungoma Loop', 'Active', '2025-01-13 22:00:00', '31415926538'),
('Mia Nyambura', 'miayambura@gmail.com', '0745671234', '1998-07-29', 'AB90123412', '456 Meru Square', 'Inactive', '2025-01-14 23:00:00', '27182818287'),
('Noah Njeri', 'noahnjeri@gmail.com', '0756781234', '1997-11-11', 'CD56789045', '789 Embu Place', 'Active', '2025-01-15 08:00:00', '31415926539'),
('Olivia Wafula', 'oliviawafula@gmail.com', '0767892345', '1983-05-19', 'EF12345678', '321 Busia Alley', 'Inactive', '2025-01-16 09:00:00', '27182818288'),
('Paul Wekesa', 'paulwekesa@gmail.com', '0778903456', '1991-02-01', 'GH78901234', '654 Kisii Bend', 'Active', '2025-01-17 10:00:00', '31415926540'),
('Quinn Cherono', 'quinncherono@gmail.com', '0789014567', '1993-06-21', 'IJ34567890', '987 Eldama Park', 'Inactive', '2025-01-18 11:00:00', '27182818289'),
('Rose Njuguna', 'rosenjuguna@gmail.com', '0790125678', '1989-10-30', 'KL90123412', '123 Nyandarua Route', 'Active', '2025-01-19 12:00:00', '31415926541'),
('Sam Mworia', 'sammworia@gmail.com', '0701236789', '1995-09-12', 'MN56789023', '456 Machakos Court', 'Inactive', '2025-01-20 13:00:00', '27182818290');

-- Populating the lenders table

INSERT INTO lenders (name, email, phone, address, status, registration_date, total_loans, average_interest_rate) VALUES
('Lender 1', 'lender1@gmail.com', '0712345678', '123 Nairobi Street', 'Active', '2025-01-01 10:00:00', 500000.00, 5.50),
('Lender 2', 'lender2@gmail.com', '0723456789', '456 Mombasa Avenue', 'Active', '2025-01-02 11:00:00', 250000.00, 4.75),
('Lender 3', 'lender3@gmail.com', '0734567890', '789 Kisumu Road', 'Inactive', '2025-01-03 12:00:00', 150000.00, 6.25),
('Lender 4', 'lender4@gmail.com', '0745678901', '321 Eldoret Lane', 'Active', '2025-01-04 13:00:00', 300000.00, 5.00),
('Lender 5', 'lender5@gmail.com', '0756789012', '654 Nakuru Street', 'Inactive', '2025-01-05 14:00:00', 200000.00, 4.50),
('Lender 6', 'lender6@gmail.com', '0767890123', '987 Naivasha Drive', 'Active', '2025-01-06 15:00:00', 350000.00, 6.00),
('Lender 7', 'lender7@gmail.com', '0778901234', '123 Kericho Blvd', 'Active', '2025-01-07 16:00:00', 400000.00, 4.80),
('Lender 8', 'lender8@gmail.com', '0789012345', '456 Bomet Terrace', 'Inactive', '2025-01-08 17:00:00', 180000.00, 5.30),
('Lender 9', 'lender9@gmail.com', '0790123456', '789 Kajiado Circle', 'Active', '2025-01-09 18:00:00', 220000.00, 4.90),
('Lender 10', 'lender10@gmail.com', '0701234567', '321 Nyeri Way', 'Inactive', '2025-01-10 19:00:00', 270000.00, 5.10),
('Lender 11', 'lender11@gmail.com', '0712345671', '654 Kakamega Trail', 'Active', '2025-01-11 20:00:00', 310000.00, 6.10),
('Lender 12', 'lender12@gmail.com', '0723456712', '987 Migori Crescent', 'Inactive', '2025-01-12 21:00:00', 150000.00, 4.70),
('Lender 13', 'lender13@gmail.com', '0734567123', '123 Bungoma Loop', 'Active', '2025-01-13 22:00:00', 280000.00, 5.40),
('Lender 14', 'lender14@gmail.com', '0745671234', '456 Meru Square', 'Inactive', '2025-01-14 23:00:00', 220000.00, 4.95),
('Lender 15', 'lender15@gmail.com', '0756781234', '789 Embu Place', 'Active', '2025-01-15 08:00:00', 320000.00, 5.80),
('Lender 16', 'lender16@gmail.com', '0767892345', '321 Busia Alley', 'Inactive', '2025-01-16 09:00:00', 200000.00, 4.60),
('Lender 17', 'lender17@gmail.com', '0778903456', '654 Kisii Bend', 'Active', '2025-01-17 10:00:00', 400000.00, 6.30),
('Lender 18', 'lender18@gmail.com', '0789014567', '987 Eldama Park', 'Inactive', '2025-01-18 11:00:00', 230000.00, 5.25),
('Lender 19', 'lender19@gmail.com', '0790125678', '123 Nyandarua Route', 'Active', '2025-01-19 12:00:00', 270000.00, 5.00),
('Lender 20', 'lender20@gmail.com', '0701236789', '456 Machakos Court', 'Inactive', '2025-01-20 13:00:00', 190000.00, 4.85);

-- Populating the users table

INSERT INTO users (user_name, email, mobile, password, role) VALUES
('Admin Inno', 'admin.inno@gmail.com', '0712345678', 'securepassword1', 'Admin'),
('Admin Glo', 'admin.glo@gmail.com', '0723456789', 'securepassword2', 'Admin'),
('Admin Zeus', 'admin.zeus@gmail.com', '0734567890', 'securepassword3', 'Admin'),
('Admin Kratos', 'admin.kratos@gmail.com', '0745678901', 'securepassword4', 'Admin'),
('Admin Sue', 'admin.sue@gmail.com', '0756789012', 'securepassword5', 'Admin'),
('John Kamau', 'johnkamau@gmail.com', '0712345678', 'customerpassword1', 'Customer'),
('Jane Mwangi', 'janemwangi@gmail.com', '0723456789', 'customerpassword2', 'Customer'),
('Alice Otieno', 'aliceotieno@gmail.com', '0734567890', 'customerpassword3', 'Customer'),
('Bob Ochieng', 'bobochieng@gmail.com', '0745678901', 'customerpassword4', 'Customer'),
('Charles Njoroge', 'charlesnjoroge@gmail.com', '0756789012', 'customerpassword5', 'Customer'),
('Eve Wanjiku', 'evewanjiku@gmail.com', '0767890123', 'customerpassword6', 'Customer'),
('Frank Kiplagat', 'frankkiplagat@gmail.com', '0778901234', 'customerpassword7', 'Customer'),
('Grace Chebet', 'gracechebet@gmail.com', '0789012345', 'customerpassword8', 'Customer'),
('Hank Kiptoo', 'hankkiptoo@gmail.com', '0790123456', 'customerpassword9', 'Customer'),
('Ivy Wairimu', 'ivywairimu@gmail.com', '0701234567', 'customerpassword10', 'Customer'),
('Jack Omondi', 'jackomondi@gmail.com', '0712345671', 'customerpassword11', 'Customer'),
('Kate Achieng', 'kateachieng@gmail.com', '0723456712', 'customerpassword12', 'Customer'),
('Liam Kiprono', 'liamkiprono@gmail.com', '0734567123', 'customerpassword13', 'Customer'),
('Mia Nyambura', 'miayambura@gmail.com', '0745671234', 'customerpassword14', 'Customer'),
('Noah Njeri', 'noahnjeri@gmail.com', '0756781234', 'customerpassword15', 'Customer'),
('Olivia Wafula', 'oliviawafula@gmail.com', '0767892345', 'customerpassword16', 'Customer'),
('Paul Wekesa', 'paulwekesa@gmail.com', '0778903456', 'customerpassword17', 'Customer'),
('Quinn Cherono', 'quinncherono@gmail.com', '0789014567', 'customerpassword18', 'Customer'),
('Rose Njuguna', 'rosenjuguna@gmail.com', '0790125678', 'customerpassword19', 'Customer'),
('Sam Mworia', 'sammworia@gmail.com', '0701236789', 'customerpassword20', 'Customer'),
('Lender 1', 'lender1@gmail.com', '0712345678', 'lenderpassword1', 'Lender'),
('Lender 2', 'lender2@gmail.com', '0723456789', 'lenderpassword2', 'Lender'),
('Lender 3', 'lender3@gmail.com', '0734567890', 'lenderpassword3', 'Lender'),
('Lender 4', 'lender4@gmail.com', '0745678901', 'lenderpassword4', 'Lender'),
('Lender 5', 'lender5@gmail.com', '0756789012', 'lenderpassword5', 'Lender'),
('Lender 6', 'lender6@gmail.com', '0767890123', 'lenderpassword6', 'Lender'),
('Lender 7', 'lender7@gmail.com', '0778901234', 'lenderpassword7', 'Lender'),
('Lender 8', 'lender8@gmail.com', '0789012345', 'lenderpassword8', 'Lender'),
('Lender 9', 'lender9@gmail.com', '0790123456', 'lenderpassword9', 'Lender'),
('Lender 10', 'lender10@gmail.com', '0701234567', 'lenderpassword10', 'Lender'),
('Lender 11', 'lender11@gmail.com', '0712345671', 'lenderpassword11', 'Lender'),
('Lender 12', 'lender12@gmail.com', '0723456712', 'lenderpassword12', 'Lender'),
('Lender 13', 'lender13@gmail.com', '0734567123', 'lenderpassword13', 'Lender'),
('Lender 14', 'lender14@gmail.com', '0745671234', 'lenderpassword14', 'Lender'),
('Lender 15', 'lender15@gmail.com', '0756781234', 'lenderpassword15', 'Lender'),
('Lender 16', 'lender16@gmail.com', '0767892345', 'lenderpassword16', 'Lender'),
('Lender 17', 'lender17@gmail.com', '0778903456', 'lenderpassword17', 'Lender'),
('Lender 18', 'lender18@gmail.com', '0789014567', 'lenderpassword18', 'Lender'),
('Lender 19', 'lender19@gmail.com', '0790125678', 'lenderpassword19', 'Lender'),
('Lender 20', 'lender20@gmail.com', '0701236789', 'lenderpassword20', 'Lender');


-- Populating loans table

INSERT INTO loans (loan_id, lender_id, customer_id, amount, interest_rate, duration, installments, collateral_description, collateral_value)
VALUES
(1, 8, 43, 50000.00, 5.50, 12, 4166.67, 'Car', 30000.00),
(2, 6, 45, 25000.00, 4.75, 24, 1041.67, 'Motorcycle', 15000.00),
(3, 9, 47, 15000.00, 6.25, 6, 2500.00, 'Electronics', 10000.00),
(4, 3, 49, 30000.00, 5.00, 12, 2500.00, 'Furniture', 20000.00),
(5, 10, 44, 20000.00, 4.50, 18, 1111.11, 'Jewelry', 12000.00),
(6, 5, 46, 35000.00, 6.00, 36, 972.22, 'Land Title', 50000.00),
(7, 19, 50, 40000.00, 4.80, 24, 1666.67, 'Stock', 25000.00),
(8, 12, 48, 18000.00, 5.30, 12, 1500.00, 'Bonds', 10000.00),
(9, 7, 51, 22000.00, 4.90, 12, 1833.33, 'Savings Certificate', 15000.00),
(10, 21, 43, 27000.00, 5.10, 24, 1125.00, 'Gold', 18000.00),
(11, 4, 53, 31000.00, 6.10, 12, 2583.33, 'Vehicle Logbook', 25000.00),
(12, 13, 55, 15000.00, 4.70, 6, 2500.00, 'Machinery', 9000.00),
(13, 16, 52, 28000.00, 5.40, 36, 777.78, 'Real Estate', 60000.00),
(14, 18, 60, 22000.00, 4.95, 24, 916.67, 'Art Collection', 12000.00),
(15, 17, 57, 32000.00, 5.80, 12, 2666.67, 'Business Equipment', 25000.00),
(16, 15, 62, 20000.00, 4.60, 12, 1666.67, 'Antiques', 10000.00),
(17, 11, 58, 40000.00, 6.30, 24, 1666.67, 'Farm Equipment', 30000.00),
(18, 20, 56, 23000.00, 5.25, 36, 638.89, 'Investment Funds', 15000.00),
(19, 14, 54, 27000.00, 5.00, 12, 2250.00, 'Patent Rights', 17000.00),
(20, 22, 59, 19000.00, 4.85, 6, 3166.67, 'Insurance Policy', 11000.00);


-- Populating the activities table (latest)

INSERT INTO activity (user_id, activity, activity_time, activity_type) VALUES
(53, 'Logged into the admin dashboard', '2025-03-01 09:15:00', 'Login'),
(54, 'Applied for a business loan', '2025-03-02 14:30:00', 'Loan Application'),
(55, 'Updated profile information', '2025-03-03 10:45:00', 'Profile Update'),
(56, 'Logged into the customer portal', '2025-03-04 08:00:00', 'Login'),
(57, 'Created a new loan offer', '2025-03-05 16:20:00', 'Loan Creation'),
(58, 'Applied for a personal loan', '2025-03-06 11:10:00', 'Loan Application'),
(59, 'Logged into the customer portal', '2025-03-07 07:55:00', 'Login'),
(61, 'Updated profile information', '2025-03-08 13:25:00', 'Profile Update'),
(62, 'Applied for a student loan', '2025-03-09 15:40:00', 'Loan Application'),
(63, 'Logged into the customer portal', '2025-03-10 09:05:00', 'Login'),
(64, 'Applied for a medical loan', '2025-03-11 12:50:00', 'Loan Application'),
(65, 'Updated profile information', '2025-03-12 17:30:00', 'Profile Update'),
(66, 'Logged into the customer portal', '2025-03-13 08:20:00', 'Login'),
(67, 'Applied for a green loan', '2025-03-14 14:15:00', 'Loan Application'),
(68, 'Created a new loan offer', '2025-03-15 10:00:00', 'Loan Creation'),
(69, 'Logged into the lender dashboard', '2025-03-16 09:45:00', 'Login'),
(70, 'Applied for a startup loan', '2025-03-17 11:35:00', 'Loan Application'),
(71, 'Logged into the customer portal', '2025-03-18 07:10:00', 'Login'),
(72, 'Created a new loan offer', '2025-03-19 16:50:00', 'Loan Creation'),
(73, 'Logged into the lender dashboard', '2025-03-20 08:25:00', 'Login'),
(74, 'Applied for a construction loan', '2025-03-21 13:40:00', 'Loan Application'),
(75, 'Created a new loan offer', '2025-03-22 14:55:00', 'Loan Creation'),
(76, 'Logged into the lender dashboard', '2025-03-23 09:30:00', 'Login'),
(77, 'Updated profile information', '2025-03-24 12:05:00', 'Profile Update'),
(79, 'Created a new loan offer', '2025-03-25 15:20:00', 'Loan Creation'),
(80, 'Logged into the customer portal', '2025-03-26 10:10:00', 'Login');




-- Simple Reports
-- above 25k
SELECT loan_id, amount, duration 
FROM loans 
WHERE amount > 25000;

-- customers who borrowed more than once
SELECT customer_id, COUNT(loan_id) AS loan_count 
FROM loans 
GROUP BY customer_id 
HAVING loan_count > 1;

-- Total Loan Amount Given Out
SELECT SUM(amount) AS total_loans 
FROM loans;

-- Total number of customers
SELECT COUNT(customer_id) AS total_customers 
FROM customers;

-- Average Loan Amount
SELECT AVG(amount) AS average_loan 
FROM loans;


--  JOINS for Complex Reports

-- 1. list loans with the correct customer and lender details.

SELECT 
    loans.loan_id,
    lenders.name AS lender_name,
    customers.name AS customer_name,
    loans.amount,
    loans.interest_rate,
    loans.duration,
    loans.installments,
    loans.collateral_description,
    loans.collateral_value
FROM loans
JOIN lenders ON loans.lender_id = lenders.lender_id
JOIN customers ON loans.customer_id = customers.customer_id;

--2. Customer Loan Applications Report
-- This report lists all loan applications with details about customers and the loans they have applied for.

SELECT 
    customers.customer_id,
    customers.name AS customer_name,
    loans.loan_id,
    loans.amount,
    loans.interest_rate,
    loans.duration,
    loans.collateral_description,
    loans.collateral_value,
    loans.installments
FROM loans
JOIN customers ON loans.customer_id = customers.customer_id
ORDER BY customers.customer_id, loans.loan_id;


-- Top 10 borrowers
SELECT 
    customers.customer_id,
    customers.name AS customer_name,
    SUM(loans.amount) AS total_loans
FROM loans
JOIN customers ON loans.customer_id = customers.customer_id
GROUP BY customers.customer_id, customers.name
ORDER BY total_loans DESC
LIMIT 10;

--3. List lenders and the customers they have approved loans for.
SELECT 
    lenders.name AS lender_name,
    customers.name AS customer_name,
    loans.loan_id,
    loans.amount
FROM loans
JOIN lenders ON loans.lender_id = lenders.lender_id
JOIN customers ON loans.customer_id = customers.customer_id
ORDER BY lenders.lender_id, customers.customer_id;


--  Total Loans issued by each lender
SELECT lenders.name AS lender_name, SUM(loans.amount) AS total_loan_amount
FROM loans
JOIN lenders ON loans.lender_id = lenders.lender_id
GROUP BY lenders.name
ORDER BY total_loan_amount DESC;

-- Loans with interest rates in a specific range
SELECT loans.loan_id, loans.amount, loans.interest_rate, loans.duration, loans.installments, 
       loans.collateral_description, loans.collateral_value, 
       customers.name AS customer_name, lenders.name AS lender_name
FROM loans
JOIN customers ON loans.customer_id = customers.customer_id
JOIN lenders ON loans.lender_id = lenders.lender_id
WHERE loans.interest_rate BETWEEN 5 AND 7;

-- Multiple conditions
SELECT loans.loan_id, loans.amount, loans.interest_rate, loans.duration
FROM loans
WHERE (loans.amount BETWEEN 15000 AND 20000 AND loans.interest_rate < 5)
OR loans.duration > 24;

-- Loans where collateral value is above average of all loans
SELECT 
    loans.loan_id, 
    loans.amount, 
    loans.collateral_value, 
    customers.name AS customer_name
FROM loans
INNER JOIN customers ON loans.customer_id = customers.customer_id
WHERE loans.collateral_value > (
    SELECT AVG(collateral_value) 
    FROM loans
);

-- Using Where Like OR
SELECT loans.loan_id, loans.amount, loans.interest_rate, customers.name AS customer_name, lenders.name AS lender_name
FROM loans
JOIN customers ON loans.customer_id = customers.customer_id
JOIN lenders ON loans.lender_id = lenders.lender_id
WHERE customers.name LIKE '%Bob%' OR lenders.name LIKE '%Lender 9%';




-- SET FOREIGN_KEY_CHECKS = 0;  disables key checks

-- SET FOREIGN_KEY_CHECKS = 1; enables key checks