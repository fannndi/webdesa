<?php include '../includes/admin_header.php'; ?>
<h2 class="mb-4"><i class="bi bi-newspaper"></i> Kelola Berita</h2>
<?php
if (isset($_GET['hapus'])) {
    $id = filter_var($_GET['hapus'], FILTER_VALIDATE_INT);
    if ($id) {
        db_query($conn, "DELETE FROM berita WHERE id = ?", "i", $id);
        echo '<div class="alert alert-success">Berita berhasil dihapus.</div>';
    }
}

if (isset($_GET['toggle'])) {
    $id = filter_var($_GET['toggle'], FILTER_VALIDATE_INT);
    if ($id) {
        db_query($conn, "UPDATE berita SET diterbitkan = NOT diterbitkan WHERE id = ?", "i", $id);
        echo '<div class="alert alert-success">Status berita berhasil diubah.</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Request tidak valid.");
    db_query($conn,
        "INSERT INTO berita (judul, isi, penulis, diterbitkan) VALUES (?, ?, ?, ?)",
        "sssi", trim($_POST['judul']), $_POST['isi'], trim($_POST['penulis']), isset($_POST['diterbitkan']) ? 1 : 0
    );
    echo '<div class="alert alert-success">Berita berhasil ditambahkan.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Request tidak valid.");
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id) {
        db_query($conn,
            "UPDATE berita SET judul=?, isi=?, penulis=?, diterbitkan=? WHERE id=?",
            "sssii", trim($_POST['judul']), $_POST['isi'], trim($_POST['penulis']), isset($_POST['diterbitkan']) ? 1 : 0, $id
        );
        echo '<div class="alert alert-success">Berita berhasil diperbarui.</div>';
    }
}

$result = db_query($conn, "SELECT * FROM berita ORDER BY created_at DESC", "");
?>

<div class="row mb-3">
    <div class="col-md-12 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle"></i> Tambah Berita</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>No</th><th>Judul</th><th>Penulis</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['judul']) ?></td>
                        <td><?= e($row['penulis']) ?></td>
                        <td><?= $row['diterbitkan'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?></td>
                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-sm <?= $row['diterbitkan'] ? 'btn-secondary' : 'btn-success' ?>"><i class="bi <?= $row['diterbitkan'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i></a>
                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Berita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="mb-3"><label class="form-label">Judul</label><input type="text" class="form-control" name="judul" value="<?= e($row['judul']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Isi Berita</label><textarea class="form-control" name="isi" rows="6" required><?= e($row['isi']) ?></textarea></div>
                                <div class="mb-3"><label class="form-label">Penulis</label><input type="text" class="form-control" name="penulis" value="<?= e($row['penulis']) ?>" required></div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="diterbitkan" id="diterbitkan<?= $row['id'] ?>" <?= $row['diterbitkan'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="diterbitkan<?= $row['id'] ?>">Terbitkan</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div></div></div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Tambah Berita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="">
        <div class="modal-body">
            <input type="hidden" name="action" value="tambah">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="mb-3"><label class="form-label">Judul</label><input type="text" class="form-control" name="judul" required></div>
            <div class="mb-3"><label class="form-label">Isi Berita</label><textarea class="form-control" name="isi" rows="6" required></textarea></div>
            <div class="mb-3"><label class="form-label">Penulis</label><input type="text" class="form-control" name="penulis" required></div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="diterbitkan" id="diterbitkanBaru">
                <label class="form-check-label" for="diterbitkanBaru">Terbitkan</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Simpan</button>
        </div>
    </form>
</div></div></div>
<?php include '../includes/footer.php'; ?>
