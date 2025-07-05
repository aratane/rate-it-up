<?php
session_start();
require_once 'functions.php';

generateCaptcha();

header('Content-Type: application/json');
echo json_encode([
    'question' => $_SESSION['captcha']['question']
]);
?>