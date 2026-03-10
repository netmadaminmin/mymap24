<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ADS-B Accuracy Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- CSS -->
  <link rel="stylesheet" href="/map24/assets/css/header.css">

  <!-- Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>

<header class="top-header">

  <div class="header-left">
    <span class="logo-icon">🛫</span>
    <span class="logo-text">ADS-B Monitor</span>
  </div>


  <div class="header-right">
    <span class="header-date"><?= date('d M Y') ?></span>
  </div>

</header>

<main class="main-content">
