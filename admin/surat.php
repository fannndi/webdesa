<?php include '../includes/admin_header.php'; ?>
<h2 class="mb-4"><i class="bi bi-file-earmark-text"></i> Kelola Pengajuan Surat</h2>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Request tidak valid.");
    $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan_admin'] ?? '');
    $allowed_status = ['menunggu', 'diproses', 'selesai', 'ditolak'];

    if ($id && in_array($status, $allowed_status)) {
        $tanggal_selesai = ($status === 'selesai') ? date('Y-m-d H:i:s') : null;
        if ($tanggal_selesai) {
            db_query($conn, "UPDATE surat_pengajuan SET status=?, catatan_admin=?, tanggal_selesai=?, diproses_oleh=? WHERE id=?",
                "sssii", $status, $catatan, $tanggal_selesai, $_SESSION['user_id'], $id);
        } else {
            db_query($conn, "UPDATE surat_pengajuan SET status=?, catatan_admin=?, diproses_oleh=? WHERE id=?",
                "ssii", $status, $catatan, $_SESSION['user_id'], $id);
        }
        echo '<div class="alert alert-success">Status pengajuan berhasil diperbarui.</div>';
    }
}

$allowed_status = ['menunggu', 'diproses', 'selesai', 'ditolak', ''];
$status_filter = $_GET['status'] ?? '';
if (!in_array($status_filter, $allowed_status)) $status_filter = '';

$result = $status_filter
    ? db_query($conn,
        "SELECT sp.*, w.nama, w.nik, u.nama_lengkap as petugas FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id LEFT JOIN users u ON sp.diproses_oleh = u.id WHERE sp.status = ? ORDER BY sp.tanggal_ajuan DESC",
        "s", $status_filter)
    : db_query($conn,
        "SELECT sp.*, w.nama, w.nik, u.nama_lengkap as petugas FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id LEFT JOIN users u ON sp.diproses_oleh = u.id ORDER BY sp.tanggal_ajuan DESC",
        "");
?>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="btn-group">
            <a href="surat.php" class="btn btn-outline-secondary <?= !$status_filter ? 'active' : '' ?>">Semua</a>
            <a href="?status=menunggu" class="btn btn-outline-warning <?= $status_filter === 'menunggu' ? 'active' : '' ?>">Menunggu</a>
            <a href="?status=diproses" class="btn btn-outline-info <?= $status_filter === 'diproses' ? 'active' : '' ?>">Diproses</a>
            <a href="?status=selesai" class="btn btn-outline-success <?= $status_filter === 'selesai' ? 'active' : '' ?>">Selesai</a>
            <a href="?status=ditolak" class="btn btn-outline-danger <?= $status_filter === 'ditolak' ? 'active' : '' ?>">Ditolak</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>No</th><th>Nama Warga</th><th>NIK</th><th>Jenis Surat</th><th>Keperluan</th><th>Status</th><th>Tanggal</th><th>Petugas</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)):
                        $status_badge = match($row['status']) {
                            'menunggu' => '<span class="badge bg-warning">Menunggu</span>',
                            'diproses' => '<span class="badge bg-info">Diproses</span>',
                            'selesai'  => '<span class="badge bg-success">Selesai</span>',
                            'ditolak'  => '<span class="badge bg-danger">Ditolak</span>',
                            default    => '<span class="badge bg-secondary">-</span>',
                        };
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['nik']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', $row['jenis_surat']))) ?></td>
                        <td><?= e(substr($row['keperluan'], 0, 50)) ?>...</td>
                        <td><?= $status_badge ?></td>
                        <td><?= date('d M Y', strtotime($row['tanggal_ajuan'])) ?></td>
                        <td><?= e($row['petugas'] ?? '-') ?></td>
                        <td><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id'] ?>"><i class="bi bi-eye"></i></button></td>
                    </tr>
                    <div class="modal fade" id="modalDetail<?= $row['id'] ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Detail Pengajuan #<?= $row['id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <form method="POST" action="">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="row mb-3">
                                    <div class="col-md-6"><p><strong>Nama:</strong> <?= e($row['nama']) ?></p><p><strong>NIK:</strong> <?= e($row['nik']) ?></p></div>
                                    <div class="col-md-6"><p><strong>Jenis Surat:</strong> <?= e(ucfirst(str_replace('_', ' ', $row['jenis_surat']))) ?></p><p><strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($row['tanggal_ajuan'])) ?></p></div>
                                </div>
                                <div class="mb-3"><label class="form-label">Keperluan</label><textarea class="form-control" rows="2" disabled><?= e($row['keperluan']) ?></textarea></div>
                                <?php if ($row['nama_usaha']): ?><div class="mb-3"><label class="form-label">Nama Usaha</label><input type="text" class="form-control" value="<?= e($row['nama_usaha']) ?>" disabled></div><?php endif; ?>
                                <?php if ($row['alamat_usaha']): ?><div class="mb-3"><label class="form-label">Alamat Usaha</label><input type="text" class="form-control" value="<?= e($row['alamat_usaha']) ?>" disabled></div><?php endif; ?>
                                <?php if ($row['nama_pasangan']): ?><div class="mb-3"><label class="form-label">Nama Pasangan</label><input type="text" class="form-control" value="<?= e($row['nama_pasangan']) ?>" disabled></div><?php endif; ?>
                                <div class="mb-3"><label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="menunggu" <?= $row['status']==='menunggu'?'selected':'' ?>>Menunggu</option>
                                        <option value="diproses" <?= $row['status']==='diproses'?'selected':'' ?>>Diproses</option>
                                        <option value="selesai" <?= $row['status']==='selesai'?'selected':'' ?>>Selesai</option>
                                        <option value="ditolak" <?= $row['status']==='ditolak'?'selected':'' ?>>Ditolak</option>
                                    </select>
                                </div>
                                <div class="mb-3"><label class="form-label">Catatan Admin</label><textarea class="form-control" name="catatan_admin" rows="3"><?= e($row['catatan_admin']) ?></textarea></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
<?php include '../includes/footer.php'; ?>
