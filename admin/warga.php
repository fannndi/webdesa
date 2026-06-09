<?php include '../includes/admin_header.php'; ?>

<h2 class="mb-4"><i class="bi bi-people"></i> Data Warga</h2>

<?php
// Handle Delete
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $sql = "DELETE FROM warga WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Data warga berhasil dihapus.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $alamat = $_POST['alamat'];
    $rt = $_POST['rt'];
    $rw = $_POST['rw'];
    $dusun = $_POST['dusun'];
    $pekerjaan = $_POST['pekerjaan'];
    $status_perkawinan = $_POST['status_perkawinan'];
    
    $sql = "INSERT INTO warga (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, rt, rw, dusun, pekerjaan, status_perkawinan) VALUES ('$nik','$nama','$tempat_lahir','$tanggal_lahir','$jenis_kelamin','$alamat','$rt','$rw','$dusun','$pekerjaan','$status_perkawinan')";
    
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Data warga berhasil ditambahkan.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $rt = $_POST['rt'];
    $rw = $_POST['rw'];
    $dusun = $_POST['dusun'];
    $pekerjaan = $_POST['pekerjaan'];
    $status_perkawinan = $_POST['status_perkawinan'];
    
    $sql = "UPDATE warga SET nama='$nama', alamat='$alamat', rt='$rt', rw='$rw', dusun='$dusun', pekerjaan='$pekerjaan', status_perkawinan='$status_perkawinan' WHERE id='$id'";
    
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success">Data warga berhasil diperbarui.</div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}

// Search
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM warga WHERE nama LIKE '%$search%' OR nik LIKE '%$search%' ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" action="" class="d-flex">
            <input type="text" class="form-control me-2" name="q" placeholder="Cari nama atau NIK..." value="<?= $search ?>">
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
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Dusun</th>
                        <th>RT/RW</th>
                        <th>Pekerjaan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['nik'] ?></td>
                        <td><?= $row['nama'] ?></td>
                        <td><?= $row['dusun'] ?></td>
                        <td><?= $row['rt'] ?>/<?= $row['rw'] ?></td>
                        <td><?= $row['pekerjaan'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <a href="?hapus=<?= $row['id'] ?>&q=<?= $search ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    
                    <!-- Modal Edit -->
                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Data Warga</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">NIK</label>
                                            <input type="text" class="form-control" value="<?= $row['nik'] ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nama</label>
                                            <input type="text" class="form-control" name="nama" value="<?= $row['nama'] ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <input type="text" class="form-control" name="alamat" value="<?= $row['alamat'] ?>" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">RT</label>
                                                <input type="text" class="form-control" name="rt" value="<?= $row['rt'] ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">RW</label>
                                                <input type="text" class="form-control" name="rw" value="<?= $row['rw'] ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Dusun</label>
                                                <select class="form-select" name="dusun" required>
                                                    <option value="Sukamaju" <?= $row['dusun'] == 'Sukamaju' ? 'selected' : '' ?>>Sukamaju</option>
                                                    <option value="Suka Damai" <?= $row['dusun'] == 'Suka Damai' ? 'selected' : '' ?>>Suka Damai</option>
                                                    <option value="Cirendeu" <?= $row['dusun'] == 'Cirendeu' ? 'selected' : '' ?>>Cirendeu</option>
                                                    <option value="Mekarjaya" <?= $row['dusun'] == 'Mekarjaya' ? 'selected' : '' ?>>Mekarjaya</option>
                                                    <option value="Pasirsari" <?= $row['dusun'] == 'Pasirsari' ? 'selected' : '' ?>>Pasirsari</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Pekerjaan</label>
                                                <input type="text" class="form-control" name="pekerjaan" value="<?= $row['pekerjaan'] ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status Perkawinan</label>
                                                <select class="form-select" name="status_perkawinan" required>
                                                    <option value="Belum Kawin" <?= $row['status_perkawinan'] == 'Belum Kawin' ? 'selected' : '' ?>>Belum Kawin</option>
                                                    <option value="Kawin" <?= $row['status_perkawinan'] == 'Kawin' ? 'selected' : '' ?>>Kawin</option>
                                                    <option value="Cerai Hidup" <?= $row['status_perkawinan'] == 'Cerai Hidup' ? 'selected' : '' ?>>Cerai Hidup</option>
                                                    <option value="Cerai Mati" <?= $row['status_perkawinan'] == 'Cerai Mati' ? 'selected' : '' ?>>Cerai Mati</option>
                                                </select>
                                            </div>
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
                <h5 class="modal-title">Tambah Data Warga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="tambah">
                    <div class="mb-3">
                        <label class="form-label">NIK</label>
                        <input type="text" class="form-control" name="nik" maxlength="16" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" name="tempat_lahir" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="jenis_kelamin" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <input type="text" class="form-control" name="alamat" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">RT</label>
                            <input type="text" class="form-control" name="rt" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">RW</label>
                            <input type="text" class="form-control" name="rw" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dusun</label>
                            <select class="form-select" name="dusun" required>
                                <option value="Sukamaju">Sukamaju</option>
                                <option value="Suka Damai">Suka Damai</option>
                                <option value="Cirendeu">Cirendeu</option>
                                <option value="Mekarjaya">Mekarjaya</option>
                                <option value="Pasirsari">Pasirsari</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control" name="pekerjaan" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status Perkawinan</label>
                            <select class="form-select" name="status_perkawinan" required>
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Kawin">Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati</option>
                            </select>
                        </div>
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
