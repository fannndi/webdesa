<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('DESA_NAMA') ? DESA_NAMA : 'Desa' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= defined('BASE_URL') ? BASE_URL : '' ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>">
                <i class="bi bi-building"></i> <?= defined('DESA_NAMA') ? DESA_NAMA : 'Desa' ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>profile.php">Profil Desa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>berita.php">Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>cek_warga.php">Cek NIK</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>ajukan_surat.php">Ajukan Surat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>riwayat.php">Riwayat</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4">
