<?php
// Start the session
session_start();

// Destroy the session
session_destroy();

// Redirect to the landing page
header("Location: landingpage.html");
exit();
?>