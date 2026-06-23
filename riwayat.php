<?php include 'includes/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-clock-history"></i> Riwayat Pengajuan Surat</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white"><h5 class="mb-0">Cek Riwayat Berdasarkan NIK</h5></div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="mb-3">
                        <label for="nik" class="form-label">Nomor Induk Kependudukan (NIK)</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="Masukkan 16 digit NIK" maxlength="16" required>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Cek Riwayat</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }
    $nik = trim($_POST['nik'] ?? '');
    if (!validate_nik($nik)) {
        echo '<div class="alert alert-danger">NIK harus 16 digit angka.</div>';
    } else {
        $result = db_query($conn,
            "SELECT sp.jenis_surat, sp.keperluan, sp.status, sp.tanggal_ajuan FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id WHERE w.nik = ? ORDER BY sp.tanggal_ajuan DESC",
            "s", $nik
        );
        if ($result && mysqli_num_rows($result) > 0) {
            echo '<div class="card"><div class="card-header bg-info text-white"><h5 class="mb-0"><i class="bi bi-list-check"></i> Hasil Riwayat</h5></div><div class="card-body"><div class="table-responsive"><table class="table table-striped"><thead><tr><th>No</th><th>Jenis Surat</th><th>Keperluan</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>';
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $status_badge = match($row['status']) {
                    'menunggu' => '<span class="badge bg-warning">Menunggu</span>',
                    'diproses' => '<span class="badge bg-info">Diproses</span>',
                    'selesai'  => '<span class="badge bg-success">Selesai</span>',
                    'ditolak'  => '<span class="badge bg-danger">Ditolak</span>',
                    default    => '<span class="badge bg-secondary">-</span>',
                };
                echo '<tr><td>' . $no++ . '</td><td>' . e(ucfirst(str_replace('_', ' ', $row['jenis_surat']))) . '</td><td>' . e($row['keperluan']) . '</td><td>' . $status_badge . '</td><td>' . date('d M Y H:i', strtotime($row['tanggal_ajuan'])) . '</td></tr>';
            }
            echo '</tbody></table></div></div></div>';
        } else {
            echo '<div class="alert alert-warning">Tidak ada riwayat pengajuan ditemukan untuk NIK tersebut.</div>';
        }
    }
}
?>
<?php include 'includes/footer.php'; ?>
