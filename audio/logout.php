<?php
session_start();
date_default_timezone_set('Europe/Vilnius');
// Destroy all session data
session_unset();
session_destroy();

// Redirect to the login page
header("Location: index.php");
exit();
?>