<?php include '../includes/admin_header.php'; ?>

<h2 class="mb-4"><i class="bi bi-newspaper"></i> Kelola Berita</h2>

<?php
// Handle Delete
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $sql = "DELETE FROM berita WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Berita berhasil dihapus.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Handle Toggle Publish
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $sql = "UPDATE berita SET diterbitkan = NOT diterbitkan WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Status berita berhasil diubah.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $penulis = $_POST['penulis'];
    $diterbitkan = isset($_POST['diterbitkan']) ? 1 : 0;
    
    $sql = "INSERT INTO berita (judul, isi, penulis, diterbitkan) VALUES ('$judul','$isi','$penulis','$diterbitkan')";
    
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Berita berhasil ditambahkan.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $penulis = $_POST['penulis'];
    $diterbitkan = isset($_POST['diterbitkan']) ? 1 : 0;
    
    $sql = "UPDATE berita SET judul='$judul', isi='$isi', penulis='$penulis', diterbitkan='$diterbitkan' WHERE id='$id'";
    
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Berita berhasil diperbarui.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

$result = mysqli_query($conn, "SELECT * FROM berita ORDER BY created_at DESC");
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
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['judul'] ?></td>
                        <td><?= $row['penulis'] ?></td>
                        <td>
                            <?php if ($row['diterbitkan']): ?>
                                <span class="badge bg-success">Published</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-sm <?= $row['diterbitkan'] ? 'btn-secondary' : 'btn-success' ?>">
                                <i class="bi <?= $row['diterbitkan'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                            </a>
                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    
                    <!-- Modal Edit -->
                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Berita</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Judul</label>
                                            <input type="text" class="form-control" name="judul" value="<?= $row['judul'] ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Isi Berita</label>
                                            <textarea class="form-control" name="isi" rows="6" required><?= $row['isi'] ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Penulis</label>
                                            <input type="text" class="form-control" name="penulis" value="<?= $row['penulis'] ?>" required>
                                        </div>
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
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Berita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" name="judul" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi Berita</label>
                        <textarea class="form-control" name="isi" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penulis</label>
                        <input type="text" class="form-control" name="penulis" required>
                    </div>
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
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
