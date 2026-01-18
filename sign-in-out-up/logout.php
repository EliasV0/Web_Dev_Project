<?php
// Start session
session_start();

// Destroy session
session_destroy();

// Redirect home
header("Location: ../index.php");
exit;
?>