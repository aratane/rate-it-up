<?php
session_start();
require_once __DIR__ . '../../config/functions.php';

// Define pages that are accessible to guests
$public_pages = [
    'index.php',
    'restaurants.php',
    'reviews.php',
    'restaurant-detail.php' // Allows guests to view restaurant details
];

// Get the current script's filename
$current_page = basename($_SERVER['PHP_SELF']);

// If the user is not logged in AND the current page is NOT in the public_pages list, then redirect to login.
// This allows guests to access pages defined in $public_pages.
if (!isLoggedIn() && !in_array($current_page, $public_pages)) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    redirect('../auth/login.php');
}

// For admin pages, ensure the user is an administrator.
// This check remains the same as it's not related to guest access.
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false && !isAdmin()) {
    redirect('../user/dashboard.php');
}

// For user-specific pages, ensure the user is not an administrator.
// This check also remains the same.
if (strpos($_SERVER['REQUEST_URI'], '/user/') !== false && isAdmin()) {
    redirect('../admin/dashboard.php');
}
?>