<?php
include 'includes/header.php';
$per_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$total = mysqli_fetch_assoc(db_query($conn, "SELECT COUNT(*) as total FROM berita WHERE diterbitkan = 1", ""))['total'];
$total_pages = ceil($total / $per_page);
$berita = db_query($conn, "SELECT * FROM berita WHERE diterbitkan = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?", "ii", $per_page, $offset);
?>

<h2 class="mb-4"><i class="bi bi-newspaper"></i> Berita Desa</h2>
<div class="row">
    <?php while ($item = mysqli_fetch_assoc($berita)): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= e($item['judul']) ?></h5>
                <p class="text-muted"><i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($item['created_at'])) ?> | <i class="bi bi-person"></i> <?= e($item['penulis']) ?></p>
                <p class="card-text"><?= e(substr($item['isi'], 0, 150)) ?>...</p>
                <a href="berita_detail.php?id=<?= (int)$item['id'] ?>" class="btn btn-success btn-sm">Baca Selengkapnya</a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php if ($total_pages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
