<?php include 'includes/header.php'; ?>

<h2 class="mb-4"><i class="bi bi-search"></i> Cek Data Warga Berdasarkan NIK</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Masukkan NIK</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nik" class="form-label">Nomor Induk Kependudukan (NIK)</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="Masukkan 16 digit NIK" maxlength="16" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cek Data</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <?php
        require_once __DIR__ . '/config/security.php';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nik = trim($_POST['nik'] ?? '');
            
            // Fitur 1: SQL Injection Prevention (Prepared Statement)
            $result = db_query($conn, "SELECT * FROM warga WHERE nik = ?", "s", $nik);

            if (!$result) {
                echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
            } else {
                $warga = mysqli_fetch_assoc($result);
                if ($warga) {
                    echo '<div class="card">';
                    echo '<div class="card-header bg-info text-white"><h5 class="mb-0"><i class="bi bi-person-check"></i> Data Ditemukan</h5></div>';
                    echo '<div class="card-body">';
                    echo '<table class="table">';
                    echo '<tr><th width="40%">NIK</th><td>' . $warga['nik'] . '</td></tr>';
                    echo '<tr><th>Nama</th><td>' . $warga['nama'] . '</td></tr>';
                    echo '<tr><th>Tempat, Tanggal Lahir</th><td>' . $warga['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($warga['tanggal_lahir'])) . '</td></tr>';
                    echo '<tr><th>Jenis Kelamin</th><td>' . ($warga['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td></tr>';
                    echo '<tr><th>Alamat</th><td>' . $warga['alamat'] . '</td></tr>';
                    echo '<tr><th>RT/RW</th><td>' . $warga['rt'] . '/' . $warga['rw'] . '</td></tr>';
                    echo '<tr><th>Dusun</th><td>' . $warga['dusun'] . '</td></tr>';
                    echo '<tr><th>Pekerjaan</th><td>' . $warga['pekerjaan'] . '</td></tr>';
                    echo '<tr><th>Status Perkawinan</th><td>' . $warga['status_perkawinan'] . '</td></tr>';
                    echo '</table>';
                    echo '</div></div>';
                } else {
                    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> NIK tidak terdaftar dalam database.</div>';
                }
            }
        }
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
