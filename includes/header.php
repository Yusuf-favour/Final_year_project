<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'SwiftGrade') ?> – Result Processing System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/swiftgrade.css" rel="stylesheet">
</head>
<body>

<?php
$activeRole = $_SESSION['role'] ?? 'student';
$roleLinks = [];
if ($activeRole === 'admin') {
    $roleLinks = [
        ['href' => BASE_URL . '/admin/index.php', 'icon' => 'speedometer2', 'label' => 'Overview'],
        ['href' => BASE_URL . '/admin/users.php', 'icon' => 'people', 'label' => 'Users'],
        ['href' => BASE_URL . '/admin/courses.php', 'icon' => 'journal-bookmark', 'label' => 'Courses'],
        ['href' => BASE_URL . '/admin/publish.php', 'icon' => 'cloud-upload', 'label' => 'Publish'],
    ];
} elseif ($activeRole === 'lecturer' || $activeRole === 'hod') {
    $roleLinks = [
        ['href' => BASE_URL . '/lecturer/index.php', 'icon' => 'speedometer2', 'label' => 'Overview'],
        ['href' => BASE_URL . '/lecturer/my_courses.php', 'icon' => 'journal-text', 'label' => 'Courses'],
        ['href' => BASE_URL . '/lecturer/my_courses.php', 'icon' => 'clipboard-check', 'label' => 'Attendance'],
        ['href' => BASE_URL . '/hod/index.php', 'icon' => 'diagram-3', 'label' => 'HOD Desk'],
    ];
} else {
    $roleLinks = [
        ['href' => BASE_URL . '/student/index.php', 'icon' => 'speedometer2', 'label' => 'Overview'],
        ['href' => BASE_URL . '/student/register_courses.php', 'icon' => 'journal-plus', 'label' => 'Registration'],
        ['href' => BASE_URL . '/student/results.php', 'icon' => 'file-earmark-bar-graph', 'label' => 'Results'],
        ['href' => BASE_URL . '/student/profile.php', 'icon' => 'person-circle', 'label' => 'Profile'],
    ];
}
?>

<nav class="navbar navbar-expand-lg navbar-swiftgrade sticky-top">
    <div class="container-xl">
        <a class="navbar-brand brand-text" href="<?= BASE_URL ?>/logo_redirect.php">
            <i class="bi bi-mortarboard-fill me-1"></i> SGU SWIFTGRADE
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#swiftgradeNav" aria-controls="swiftgradeNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="swiftgradeNav">
            <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1">
                <?php foreach ($roleLinks as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= h($link['href']) ?>">
                            <i class="bi bi-<?= h($link['icon']) ?> me-1"></i><?= h($link['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <ul class="navbar-nav ms-lg-3 align-items-lg-center gap-lg-1">
                <li class="nav-item d-none d-lg-block">
                    <span class="badge bg-light text-dark text-uppercase" style="font-size:.68rem; letter-spacing:.04em;">
                        <?= h($activeRole) ?>
                    </span>
                </li>
                <li class="nav-item">
                    <span class="navbar-text small text-white-50 px-2"><?= h(currentFullName()) ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/logo_redirect.php"><i class="bi bi-house-door me-1"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/swiftgrade_change_password.php"><i class="bi bi-key me-1"></i>Password</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/swiftgrade_logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-wrap">
