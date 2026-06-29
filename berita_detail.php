<?php
include 'includes/header.php';
require_once __DIR__ . '/config/security.php';

$id = $_GET['id'] ?? '';

// Fitur 1: SQL Injection Prevention (Prepared Statement)
$result = db_query($conn, "SELECT * FROM berita WHERE id = ?", "s", $id);

if (!$result) {
    echo "Error: " . mysqli_error($conn);
}

$berita = $result ? mysqli_fetch_assoc($result) : null;
?>

<?php if ($berita): ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2><?= $berita['judul'] ?></h2>
                <p class="text-muted"><i class="bi bi-calendar"></i> <?= date('d M Y H:i', strtotime($berita['created_at'])) ?> | <i class="bi bi-person"></i> <?= $berita['penulis'] ?></p>
                <hr>
                <div class="berita-isi">
                    <?= nl2br($berita['isi']) ?>
                </div>
            </div>
        </div>
        <a href="berita.php" class="btn btn-primary mt-3"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Berita</a>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-newspaper"></i> Berita Lainnya</h5>
            </div>
            <div class="card-body">
                <?php
                $berita_lain = db_query($conn, "SELECT id, judul, created_at FROM berita WHERE diterbitkan = 1 AND id != ? ORDER BY created_at DESC LIMIT 5", "s", $id);
                if($berita_lain) while ($item = mysqli_fetch_assoc($berita_lain)):
                ?>
                <div class="mb-2">
                    <a href="berita_detail.php?id=<?= $item['id'] ?>" class="text-decoration-none"><?= $item['judul'] ?></a>
                    <small class="text-muted d-block"><?= date('d M Y', strtotime($item['created_at'])) ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Berita tidak ditemukan.</div>
<a href="berita.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Berita</a>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
