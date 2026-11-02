<?php
/**
 * User Sign Out Handler
 * Module: Web and Mobile Application Development (SE102.3)
 */

require_once __DIR__ . '/config/functions.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Start fresh session just to flash sign out feedback
session_start();
setFlashMessage('info', 'You have been safely signed out. Have a productive day at NSBM!');
redirect('index.php');
