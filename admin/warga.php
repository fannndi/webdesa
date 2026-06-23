<?php include '../includes/admin_header.php'; ?>
<h2 class="mb-4"><i class="bi bi-people"></i> Data Warga</h2>
<?php
if (isset($_GET['hapus'])) {
    $id = filter_var($_GET['hapus'], FILTER_VALIDATE_INT);
    if ($id) {
        db_query($conn, "DELETE FROM warga WHERE id = ?", "i", $id);
        echo '<div class="alert alert-success">Data warga berhasil dihapus.</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Request tidak valid.");
    db_query($conn,
        "INSERT INTO warga (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, rt, rw, dusun, pekerjaan, status_perkawinan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        "sssssssssss",
        trim($_POST['nik']), trim($_POST['nama']), trim($_POST['tempat_lahir']),
        $_POST['tanggal_lahir'], $_POST['jenis_kelamin'], trim($_POST['alamat']),
        trim($_POST['rt']), trim($_POST['rw']), $_POST['dusun'],
        trim($_POST['pekerjaan']), $_POST['status_perkawinan']
    );
    echo '<div class="alert alert-success">Data warga berhasil ditambahkan.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Request tidak valid.");
    db_query($conn,
        "UPDATE warga SET nama=?, alamat=?, rt=?, rw=?, dusun=?, pekerjaan=?, status_perkawinan=? WHERE id=?",
        "sssssssi",
        trim($_POST['nama']), trim($_POST['alamat']), trim($_POST['rt']),
        trim($_POST['rw']), $_POST['dusun'], trim($_POST['pekerjaan']),
        $_POST['status_perkawinan'], filter_var($_POST['id'], FILTER_VALIDATE_INT)
    );
    echo '<div class="alert alert-success">Data warga berhasil diperbarui.</div>';
}

$search = trim($_GET['q'] ?? '');
$search_param = "%{$search}%";
$result = db_query($conn,
    "SELECT id, nik, nama, dusun, rt, rw, pekerjaan FROM warga WHERE nama LIKE ? OR nik LIKE ? ORDER BY id DESC",
    "ss", $search_param, $search_param
);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" action="" class="d-flex">
            <input type="text" class="form-control me-2" name="q" placeholder="Cari nama atau NIK..." value="<?= e($search) ?>">
            <button type="submit" class="btn btn-success"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle"></i> Tambah Warga</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>NIK</th><th>Nama</th><th>Dusun</th><th>RT/RW</th><th>Pekerjaan</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= e($row['nik']) ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['dusun']) ?></td>
                        <td><?= e($row['rt']) ?>/<?= e($row['rw']) ?></td>
                        <td><?= e($row['pekerjaan']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <a href="?hapus=<?= $row['id'] ?>&q=<?= e($search) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Data Warga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="mb-3"><label class="form-label">NIK</label><input type="text" class="form-control" value="<?= e($row['nik']) ?>" disabled></div>
                                <div class="mb-3"><label class="form-label">Nama</label><input type="text" class="form-control" name="nama" value="<?= e($row['nama']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Alamat</label><input type="text" class="form-control" name="alamat" required></div>
                                <div class="row">
                                    <div class="col-md-4 mb-3"><label class="form-label">RT</label><input type="text" class="form-control" name="rt" value="<?= e($row['rt']) ?>" required></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">RW</label><input type="text" class="form-control" name="rw" value="<?= e($row['rw']) ?>" required></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">Dusun</label>
                                        <select class="form-select" name="dusun" required>
                                            <?php foreach(['Sukamaju','Suka Damai','Cirendeu','Mekarjaya','Pasirsari'] as $d): ?>
                                            <option value="<?= $d ?>" <?= $row['dusun'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">Pekerjaan</label><input type="text" class="form-control" name="pekerjaan" value="<?= e($row['pekerjaan']) ?>" required></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Status Perkawinan</label>
                                        <select class="form-select" name="status_perkawinan" required>
                                            <?php foreach(['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $row['status_perkawinan'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
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
    <div class="modal-header"><h5 class="modal-title">Tambah Data Warga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="">
        <div class="modal-body">
            <input type="hidden" name="action" value="tambah">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <div class="mb-3"><label class="form-label">NIK</label><input type="text" class="form-control" name="nik" maxlength="16" required></div>
            <div class="mb-3"><label class="form-label">Nama</label><input type="text" class="form-control" name="nama" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Tempat Lahir</label><input type="text" class="form-control" name="tempat_lahir" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Tanggal Lahir</label><input type="date" class="form-control" name="tanggal_lahir" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Jenis Kelamin</label>
                <select class="form-select" name="jenis_kelamin" required><option value="L">Laki-laki</option><option value="P">Perempuan</option></select>
            </div>
            <div class="mb-3"><label class="form-label">Alamat</label><input type="text" class="form-control" name="alamat" required></div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">RT</label><input type="text" class="form-control" name="rt" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">RW</label><input type="text" class="form-control" name="rw" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Dusun</label>
                    <select class="form-select" name="dusun" required>
                        <option value="Sukamaju">Sukamaju</option><option value="Suka Damai">Suka Damai</option>
                        <option value="Cirendeu">Cirendeu</option><option value="Mekarjaya">Mekarjaya</option><option value="Pasirsari">Pasirsari</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Pekerjaan</label><input type="text" class="form-control" name="pekerjaan" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Status Perkawinan</label>
                    <select class="form-select" name="status_perkawinan" required>
                        <option value="Belum Kawin">Belum Kawin</option><option value="Kawin">Kawin</option>
                        <option value="Cerai Hidup">Cerai Hidup</option><option value="Cerai Mati">Cerai Mati</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Simpan</button>
        </div>
    </form>
</div></div></div>
<?php include '../includes/footer.php'; ?>
