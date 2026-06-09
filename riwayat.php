<?php include 'includes/header.php'; ?>

<h2 class="mb-4"><i class="bi bi-clock-history"></i> Riwayat Pengajuan Surat</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Cek Riwayat Berdasarkan NIK</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = $_POST['nik'];
    $sql = "SELECT sp.*, w.nama FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id WHERE w.nik = '$nik' ORDER BY sp.tanggal_ajuan DESC";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    } else {
        if (mysqli_num_rows($result) > 0) {
            echo '<div class="card">';
            echo '<div class="card-header bg-info text-white"><h5 class="mb-0"><i class="bi bi-list-check"></i> Hasil Riwayat</h5></div>';
            echo '<div class="card-body">';
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>No</th><th>Jenis Surat</th><th>Keperluan</th><th>Status</th><th>Tanggal</th></tr></thead>';
            echo '<tbody>';
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $status_badge = '';
                switch ($row['status']) {
                    case 'menunggu': $status_badge = '<span class="badge bg-warning">Menunggu</span>'; break;
                    case 'diproses': $status_badge = '<span class="badge bg-info">Diproses</span>'; break;
                    case 'selesai': $status_badge = '<span class="badge bg-success">Selesai</span>'; break;
                    case 'ditolak': $status_badge = '<span class="badge bg-danger">Ditolak</span>'; break;
                }
                echo '<tr>';
                echo '<td>' . $no++ . '</td>';
                echo '<td>' . ucfirst(str_replace('_', ' ', $row['jenis_surat'])) . '</td>';
                echo '<td>' . $row['keperluan'] . '</td>';
                echo '<td>' . $status_badge . '</td>';
                echo '<td>' . date('d M Y H:i', strtotime($row['tanggal_ajuan'])) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div></div></div>';
        } else {
            echo '<div class="alert alert-warning">Tidak ada riwayat pengajuan ditemukan untuk NIK tersebut.</div>';
        }
    }
}
?>

<?php include 'includes/footer.php'; ?>
