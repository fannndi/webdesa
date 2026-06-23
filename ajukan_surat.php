<?php include 'includes/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-file-earmark-plus"></i> Pengajuan Surat</h2>
<?php
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$warga = null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }

    if ($step === 1) {
        $nik = trim($_POST['nik'] ?? '');
        if (!validate_nik($nik)) {
            $error = "NIK harus 16 digit angka.";
        } else {
            $result = db_query($conn, "SELECT * FROM warga WHERE nik = ?", "s", $nik);
            if ($result && mysqli_num_rows($result) > 0) {
                $warga = mysqli_fetch_assoc($result);
                $step = 2;
            } else {
                $error = "NIK tidak ditemukan dalam database.";
            }
        }
    } elseif ($step === 3) {
        $warga_id = filter_var($_POST['warga_id'] ?? 0, FILTER_VALIDATE_INT);
        $jenis_surat = $_POST['jenis_surat'] ?? '';
        $keperluan = trim($_POST['keperluan'] ?? '');
        $nama_usaha = trim($_POST['nama_usaha'] ?? '');
        $alamat_usaha = trim($_POST['alamat_usaha'] ?? '');
        $nama_pasangan = trim($_POST['nama_pasangan'] ?? '');

        $allowed_jenis = ['domisili', 'usaha', 'tidak_mampu', 'pengantar_nikah'];
        if (!$warga_id || !in_array($jenis_surat, $allowed_jenis) || empty($keperluan)) {
            $error = "Data yang dikirim tidak valid.";
        } else {
            db_query($conn,
                "INSERT INTO surat_pengajuan (warga_id, jenis_surat, keperluan, nama_usaha, alamat_usaha, nama_pasangan) VALUES (?, ?, ?, ?, ?, ?)",
                "isssss", $warga_id, $jenis_surat, $keperluan, $nama_usaha, $alamat_usaha, $nama_pasangan
            );
            $success = "Pengajuan surat berhasil dikirim.";
            $step = 1;
        }
    }
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= e($success) ?></div>
<?php endif; ?>

<?php if ($step === 1): ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white"><h5 class="mb-0">Step 1: Verifikasi NIK</h5></div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="step" value="1">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="mb-3">
                        <label for="nik" class="form-label">Masukkan NIK Anda</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="16 digit NIK" maxlength="16" required>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-search"></i> Verifikasi</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php elseif ($step === 2 && $warga): ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="bi bi-person-check"></i> Data Warga</h5></div>
            <div class="card-body">
                <p><strong>NIK:</strong> <?= e($warga['nik']) ?></p>
                <p><strong>Nama:</strong> <?= e($warga['nama']) ?></p>
                <p><strong>Alamat:</strong> <?= e($warga['alamat']) ?>, RT <?= e($warga['rt']) ?>/RW <?= e($warga['rw']) ?>, Dusun <?= e($warga['dusun']) ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-success text-white"><h5 class="mb-0">Step 2: Pilih Jenis Surat</h5></div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="warga_id" value="<?= (int)$warga['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="mb-3">
                        <label for="jenis_surat" class="form-label">Jenis Surat</label>
                        <select class="form-select" id="jenis_surat" name="jenis_surat" required onchange="toggleFields()">
                            <option value="">-- Pilih Jenis Surat --</option>
                            <option value="domisili">Surat Keterangan Domisili</option>
                            <option value="usaha">Surat Keterangan Usaha</option>
                            <option value="tidak_mampu">Surat Keterangan Tidak Mampu</option>
                            <option value="pengantar_nikah">Surat Pengantar Nikah</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="keperluan" class="form-label">Keperluan</label>
                        <textarea class="form-control" id="keperluan" name="keperluan" rows="3" required></textarea>
                    </div>
                    <div id="field_usaha" style="display:none;">
                        <div class="mb-3"><label for="nama_usaha" class="form-label">Nama Usaha</label><input type="text" class="form-control" id="nama_usaha" name="nama_usaha"></div>
                        <div class="mb-3"><label for="alamat_usaha" class="form-label">Alamat Usaha</label><input type="text" class="form-control" id="alamat_usaha" name="alamat_usaha"></div>
                    </div>
                    <div id="field_nikah" style="display:none;">
                        <div class="mb-3"><label for="nama_pasangan" class="form-label">Nama Pasangan</label><input type="text" class="form-control" id="nama_pasangan" name="nama_pasangan"></div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> Ajukan Surat</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function toggleFields() {
    var jenis = document.getElementById('jenis_surat').value;
    document.getElementById('field_usaha').style.display = (jenis === 'usaha') ? 'block' : 'none';
    document.getElementById('field_nikah').style.display = (jenis === 'pengantar_nikah') ? 'block' : 'none';
}
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
