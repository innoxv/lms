<?php
// Database connection setup:
// $myconn is a global variable that creates a connection to MySQL database using mysqli_connect() function that opens a new connection to the MySQL server
// Parameters: server, username, password, database 
$myconn = mysqli_connect('localhost', 'inno', 'figureitouttoo', 'LMSDB');

// Check connection
if (!$myconn) {
    die("Connection failed: " . mysqli_connect_error());
} 
?>
