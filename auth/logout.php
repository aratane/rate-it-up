<?php
function redirect($url) {
    header("Location: $url");
    exit;
}

session_start();
session_destroy();
redirect('http://localhost/rate-it-up/');
