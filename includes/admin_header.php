<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= defined('DESA_NAMA') ? DESA_NAMA : 'Desa' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <nav class="admin-sidebar text-white">
            <div class="p-3">
                <h5 class="text-center"><i class="bi bi-shield-lock"></i> Admin Panel</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="warga.php">
                            <i class="bi bi-people"></i> Data Warga
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="surat.php">
                            <i class="bi bi-file-earmark-text"></i> Pengajuan Surat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="berita.php">
                            <i class="bi bi-newspaper"></i> Berita
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-white" href="logout.php">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <div class="flex-grow-1">
            <nav class="navbar navbar-light bg-light px-3">
                <span class="navbar-text">
                    Selamat datang, <?= isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin' ?>
                </span>
                <a href="../" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-house"></i> Lihat Website
                </a>
            </nav>
            <div class="container-fluid p-4">
