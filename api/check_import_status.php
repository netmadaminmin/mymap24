<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['dataset']) || 
    !is_array($_SESSION['dataset']) || 
    count($_SESSION['dataset']) === 0) {

    echo json_encode([
        "imported" => false,
        "total"    => 0
    ]);
    exit;
}

echo json_encode([
    "imported"  => true,
    "total"     => count($_SESSION['dataset']),
    "uploaded_at" => $_SESSION['uploaded_at'] ?? null
], JSON_UNESCAPED_UNICODE);