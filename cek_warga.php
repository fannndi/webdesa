<?php include 'includes/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-search"></i> Cek Data Warga Berdasarkan NIK</h2>
<?php
$result_warga = null;
$error = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }
    $nik = trim($_POST['nik'] ?? '');
    if (!validate_nik($nik)) {
        $error = "NIK harus 16 digit angka.";
    } else {
        $result_warga = db_query($conn,
            "SELECT nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, rt, rw, dusun, pekerjaan, status_perkawinan FROM warga WHERE nik = ?",
            "s", $nik
        );
        $searched = true;
    }
}
?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Masukkan NIK</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="mb-3">
                        <label for="nik" class="form-label">Nomor Induk Kependudukan (NIK)</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="Masukkan 16 digit NIK" maxlength="16" required>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Cek Data</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php elseif ($searched && $result_warga && mysqli_num_rows($result_warga) > 0): ?>
            <?php $warga = mysqli_fetch_assoc($result_warga); ?>
            <div class="card">
                <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="bi bi-person-check"></i> Data Ditemukan</h5></div>
                <div class="card-body">
                    <table class="table">
                        <tr><th width="40%">NIK</th><td><?= e($warga['nik']) ?></td></tr>
                        <tr><th>Nama</th><td><?= e($warga['nama']) ?></td></tr>
                        <tr><th>Tempat, Tanggal Lahir</th><td><?= e($warga['tempat_lahir']) ?>, <?= date('d-m-Y', strtotime($warga['tanggal_lahir'])) ?></td></tr>
                        <tr><th>Jenis Kelamin</th><td><?= $warga['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                        <tr><th>Alamat</th><td><?= e($warga['alamat']) ?></td></tr>
                        <tr><th>RT/RW</th><td><?= e($warga['rt']) ?>/<?= e($warga['rw']) ?></td></tr>
                        <tr><th>Dusun</th><td><?= e($warga['dusun']) ?></td></tr>
                        <tr><th>Pekerjaan</th><td><?= e($warga['pekerjaan']) ?></td></tr>
                        <tr><th>Status Perkawinan</th><td><?= e($warga['status_perkawinan']) ?></td></tr>
                    </table>
                </div>
            </div>
        <?php elseif ($searched): ?>
            <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> NIK tidak terdaftar dalam database.</div>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
