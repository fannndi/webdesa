<?php
include 'includes/header.php';

// Statistik
$total_warga = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM warga"))['total'];
$total_kk = ceil($total_warga / 4);
$total_pengajuan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_pengajuan"))['total'];
$total_berita = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM berita WHERE diterbitkan = 1"))['total'];

// Berita terbaru
$berita_terbaru = mysqli_query($conn, "SELECT * FROM berita WHERE diterbitkan = 1 ORDER BY created_at DESC LIMIT 3");
?>

<!-- Hero Section -->
<div class="hero-section text-white text-center py-5 mb-5 rounded-3">
    <h1 class="display-4">Selamat Datang di <?= DESA_NAMA ?></h1>
    <p class="lead"><?= DESA_KECAMATAN ?>, <?= DESA_KABUPATEN ?>, <?= DESA_PROVINSI ?></p>
    <p class="mb-4">Sistem Informasi Desa untuk pelayanan publik yang lebih baik</p>
    <a href="cek_warga.php" class="btn btn-light btn-lg me-2"><i class="bi bi-search"></i> Cek NIK Saya</a>
    <a href="ajukan_surat.php" class="btn btn-outline-light btn-lg"><i class="bi bi-file-earmark-plus"></i> Ajukan Surat</a>
</div>

<!-- Statistik -->
<div class="row mb-5">
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-primary">
            <div class="card-body">
                <i class="bi bi-people fs-1 text-primary"></i>
                <h3 class="mt-2"><?= $total_warga ?></h3>
                <p class="text-muted">Total Penduduk</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-primary">
            <div class="card-body">
                <i class="bi bi-house fs-1 text-primary"></i>
                <h3 class="mt-2"><?= $total_kk ?></h3>
                <p class="text-muted">Jumlah KK</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-warning">
            <div class="card-body">
                <i class="bi bi-file-earmark-text fs-1 text-warning"></i>
                <h3 class="mt-2"><?= $total_pengajuan ?></h3>
                <p class="text-muted">Total Pengajuan</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-info">
            <div class="card-body">
                <i class="bi bi-newspaper fs-1 text-info"></i>
                <h3 class="mt-2"><?= $total_berita ?></h3>
                <p class="text-muted">Berita Tayang</p>
            </div>
        </div>
    </div>
</div>

<!-- Sambutan Kepala Desa -->
<div class="row mb-5">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title"><i class="bi bi-person-badge"></i> Sambutan Kepala Desa</h4>
                <p class="card-text">Assalamu'alaikum Warahmatullahi Wabarakatuh. Selamat datang di website resmi <?= DESA_NAMA ?>. Kami berkomitmen untuk memberikan pelayanan terbaik kepada seluruh warga desa. Melalui website ini, kami harapkan informasi dapat tersampaikan dengan cepat dan transparan. Terima kasih atas partisipasi aktif seluruh warga dalam membangun desa kita tercinta.</p>
                <p class="text-end fw-bold"><?= KEPALA_DESA ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Berita Terbaru -->
<h3 class="mb-3"><i class="bi bi-newspaper"></i> Berita Terbaru</h3>
<div class="row">
    <?php while ($berita = mysqli_fetch_assoc($berita_terbaru)): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= $berita['judul'] ?></h5>
                <p class="text-muted"><i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($berita['created_at'])) ?> | <i class="bi bi-person"></i> <?= $berita['penulis'] ?></p>
                <p class="card-text"><?= substr($berita['isi'], 0, 150) ?>...</p>
                <a href="berita_detail.php?id=<?= $berita['id'] ?>" class="btn btn-primary btn-sm">Baca Selengkapnya</a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>
