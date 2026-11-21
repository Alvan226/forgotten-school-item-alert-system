<?php
session_start();

// Clear all session data.
$_SESSION = array();

// Destroy the session.
session_destroy();

// Return to the login page.
header("Location: login.php");
exit();
?>