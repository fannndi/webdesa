<?php include 'includes/header.php'; ?>

<h2 class="mb-4"><i class="bi bi-file-earmark-plus"></i> Pengajuan Surat</h2>

<?php
$step = isset($_POST['step']) ? $_POST['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 1) {
    $nik = $_POST['nik'];
    $sql = "SELECT * FROM warga WHERE nik = '$nik'";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    } else {
        $warga = mysqli_fetch_assoc($result);
        if ($warga) {
            $step = 2;
        } else {
            echo '<div class="alert alert-warning">NIK tidak ditemukan dalam database.</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 3) {
    $warga_id = $_POST['warga_id'];
    $jenis_surat = $_POST['jenis_surat'];
    $keperluan = $_POST['keperluan'];
    $nama_usaha = $_POST['nama_usaha'];
    $alamat_usaha = $_POST['alamat_usaha'];
    $nama_pasangan = $_POST['nama_pasangan'];
    
    $sql = "INSERT INTO surat_pengajuan (warga_id, jenis_surat, keperluan, nama_usaha, alamat_usaha, nama_pasangan) VALUES ('$warga_id','$jenis_surat','$keperluan','$nama_usaha','$alamat_usaha','$nama_pasangan')";
    
    if (mysqli_query($conn, $sql)) {
        echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Pengajuan surat berhasil dikirim. Silakan cek riwayat untuk mengetahui status pengajuan Anda.</div>';
        echo '<a href="riwayat.php" class="btn btn-primary"><i class="bi bi-clock-history"></i> Cek Riwayat</a>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}
?>

<?php if ($step == 1): ?>
<!-- Step 1: Cek NIK -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Step 1: Verifikasi NIK</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="step" value="1">
                    <div class="mb-3">
                        <label for="nik" class="form-label">Masukkan NIK Anda</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="16 digit NIK" maxlength="16" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Verifikasi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php elseif ($step == 2): ?>
<!-- Step 2: Pilih Jenis Surat -->
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-person-check"></i> Data Warga</h5>
            </div>
            <div class="card-body">
                <p><strong>NIK:</strong> <?= $warga['nik'] ?></p>
                <p><strong>Nama:</strong> <?= $warga['nama'] ?></p>
                <p><strong>Alamat:</strong> <?= $warga['alamat'] ?>, RT <?= $warga['rt'] ?>/RW <?= $warga['rw'] ?>, Dusun <?= $warga['dusun'] ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Step 2: Pilih Jenis Surat</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="warga_id" value="<?= $warga['id'] ?>">
                    
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
                        <div class="mb-3">
                            <label for="nama_usaha" class="form-label">Nama Usaha</label>
                            <input type="text" class="form-control" id="nama_usaha" name="nama_usaha">
                        </div>
                        <div class="mb-3">
                            <label for="alamat_usaha" class="form-label">Alamat Usaha</label>
                            <input type="text" class="form-control" id="alamat_usaha" name="alamat_usaha">
                        </div>
                    </div>
                    
                    <div id="field_nikah" style="display:none;">
                        <div class="mb-3">
                            <label for="nama_pasangan" class="form-label">Nama Pasangan</label>
                            <input type="text" class="form-control" id="nama_pasangan" name="nama_pasangan">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Ajukan Surat</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    var jenis = document.getElementById('jenis_surat').value;
    document.getElementById('field_usaha').style.display = (jenis == 'usaha') ? 'block' : 'none';
    document.getElementById('field_nikah').style.display = (jenis == 'pengantar_nikah') ? 'block' : 'none';
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
