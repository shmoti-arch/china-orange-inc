<?php
require_once 'config/session.php';

// Destroy session
session_destroy();

// Redirect to home page
header('Location: /');
exit();
?>