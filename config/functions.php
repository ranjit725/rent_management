<?php
// Common helper functions

// Escape output for HTML
function validHTML($var) {
    return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
}

// Generate unique number/id
function uniqueNumber() {
    return uniqid((string)time(), true);
}

// Generate secure session id
function makeSessId() {
    return bin2hex(random_bytes(16));
}

// Random password generator
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    return substr(str_shuffle($chars), 0, $length);
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Debug helper
function debug($var) {
    echo '<pre>'; print_r($var); echo '</pre>'; exit;
}

// Current datetime helper
function currentDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}
?>
