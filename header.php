<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>UNIDEL – Student Academic Record Management System</title>

<!-- BOOTSTRAP (GLOBAL DESIGN SYSTEM) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- YOUR CUSTOM CSS -->
<link rel="stylesheet" href="assets/css/swiftgrade.css">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="assets/css/navbar-modern.css">

</head>

<body>

<nav class="navbar-modern">
<div class="navbar-modern-container">
    <!-- LEFT: Logo & Brand -->
    <div class="navbar-brand-section">
        <a href="logo_redirect.php" class="navbar-brand-link">
            <img src="assets/logo.png" alt="UNIDEL Logo" class="navbar-brand-logo">
        </a>
        <div class="navbar-brand-text">
            <div class="navbar-institution-name">University of Delta</div>
            <div class="navbar-system-name">SARMS</div>
        </div>
    </div>

    <!-- CENTER: System Info -->
    <div class="navbar-info-section">
        <div class="navbar-info-badge">
            <i class="bi bi-lightning-fill"></i>
            <span>Student Academic Record Management</span>
        </div>
    </div>

    <!-- RIGHT: User Section -->
    <div class="navbar-user-section">
        <div class="navbar-user-info">
            <span class="navbar-user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'User'); ?></span>
            <span class="navbar-user-name"><?= htmlspecialchars(substr($_SESSION['full_name'] ?? 'User', 0, 20)); ?></span>
        </div>
        <a href="logout.php" class="navbar-logout-btn" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>
</nav>

<div class="main-wrap">
