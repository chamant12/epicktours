<?php
/** @var string $adminTitle  @var string $activeNav */
$me = current_user();
$activeNav = $activeNav ?? '';
$newEnquiries = can('enquiries') ? (int) q("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn() : 0;

function nav_item(string $key, string $href, string $icon, string $label, string $badge = ''): string
{
    global $activeNav;
    return '<li class="nav-item' . ($activeNav === $key ? ' active' : '') . '"><a class="nav-link" href="' . $href . '"><i class="fas fa-fw ' . $icon . '"></i><span>' . $label . '</span>' . $badge . '</a></li>';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitle ?? 'Dashboard') ?> – <?= e(setting('site_name')) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/sb-admin-2.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body id="page-top">
<div id="wrapper">

    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
            <div class="sidebar-brand-icon"><i class="fas fa-mountain-sun"></i></div>
            <div class="sidebar-brand-text mx-3"><?= e(setting('site_name')) ?></div>
        </a>
        <hr class="sidebar-divider my-0">
        <?= nav_item('dashboard', 'index.php', 'fa-tachometer-alt', 'Dashboard') ?>

        <?php if (can('content')): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Website content</div>
            <?= nav_item('pages', 'pages.php', 'fa-file-lines', 'Pages') ?>
            <?= nav_item('tours', 'tours.php', 'fa-route', 'Tours') ?>
            <?= nav_item('destinations', 'destinations.php', 'fa-map-location-dot', 'Destinations') ?>
            <?= nav_item('experiences', 'experiences.php', 'fa-star', 'Experiences') ?>
            <?= nav_item('themes', 'themes.php', 'fa-icons', 'Home page themes') ?>
        <?php endif; ?>

        <?php if (can('enquiries')): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Customers</div>
            <?= nav_item('enquiries', 'enquiries.php', 'fa-envelope-open-text', 'Enquiries', $newEnquiries ? ' <span class="badge badge-danger ml-1">' . $newEnquiries . '</span>' : '') ?>
        <?php endif; ?>

        <?php if (can('users') || can('settings')): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Administration</div>
            <?php if (can('users')): ?><?= nav_item('users', 'users.php', 'fa-users', 'Users') ?><?php endif; ?>
            <?php if (can('settings')): ?><?= nav_item('images', 'images.php', 'fa-images', 'Images') ?><?php endif; ?>
            <?php if (can('settings')): ?><?= nav_item('settings', 'settings.php', 'fa-cog', 'Settings') ?><?php endif; ?>
        <?php endif; ?>

        <hr class="sidebar-divider">
        <li class="nav-item"><a class="nav-link" href="../index.php" target="_blank"><i class="fas fa-fw fa-arrow-up-right-from-square"></i><span>View website</span></a></li>
        <hr class="sidebar-divider d-none d-md-block">
        <div class="text-center d-none d-md-inline"><button class="rounded-circle border-0" id="sidebarToggle"></button></div>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
                <ul class="navbar-nav ml-auto">
                    <?php if ($newEnquiries): ?>
                        <li class="nav-item mx-1">
                            <a class="nav-link" href="enquiries.php?status=new" title="New enquiries">
                                <i class="fas fa-bell fa-fw"></i><span class="badge badge-danger badge-counter"><?= $newEnquiries ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= e($me['name']) ?> <span class="badge badge-light"><?= e($me['role_name']) ?></span></span>
                            <span class="avatar"><?= e(mb_strtoupper(mb_substr($me['name'], 0, 1))) ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="profile.php"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> My profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="container-fluid">
                <?php foreach (take_flashes() as [$type, $msg]): ?>
                    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
                        <?= e($msg) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endforeach; ?>
