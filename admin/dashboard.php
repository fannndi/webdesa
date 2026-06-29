<?php include '../includes/admin_header.php'; ?>

<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>

<?php
$total_warga = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM warga"))['total'];
$menunggu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_pengajuan WHERE status = 'menunggu'"))['total'];
$selesai_bulan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_pengajuan WHERE status = 'selesai' AND MONTH(tanggal_selesai) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_selesai) = YEAR(CURRENT_DATE())"))['total'];
$total_berita = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM berita"))['total'];
?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-primary">
            <div class="card-body">
                <i class="bi bi-people fs-1 text-primary"></i>
                <h3 class="mt-2"><?= $total_warga ?></h3>
                <p class="text-muted">Total Warga</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-warning">
            <div class="card-body">
                <i class="bi bi-hourglass-split fs-1 text-warning"></i>
                <h3 class="mt-2"><?= $menunggu ?></h3>
                <p class="text-muted">Menunggu</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-success">
            <div class="card-body">
                <i class="bi bi-check-circle fs-1 text-success"></i>
                <h3 class="mt-2"><?= $selesai_bulan ?></h3>
                <p class="text-muted">Selesai Bulan Ini</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center h-100 border-info">
            <div class="card-body">
                <i class="bi bi-newspaper fs-1 text-info"></i>
                <h3 class="mt-2"><?= $total_berita ?></h3>
                <p class="text-muted">Total Berita</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> 10 Pengajuan Terbaru</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Warga</th>
                        <th>Jenis Surat</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = mysqli_query($conn, "SELECT sp.*, w.nama FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id ORDER BY sp.tanggal_ajuan DESC LIMIT 10");
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query)):
                        $status_badge = '';
                        switch ($row['status']) {
                            case 'menunggu': $status_badge = '<span class="badge bg-warning">Menunggu</span>'; break;
                            case 'diproses': $status_badge = '<span class="badge bg-info">Diproses</span>'; break;
                            case 'selesai': $status_badge = '<span class="badge bg-primary">Selesai</span>'; break;
                            case 'ditolak': $status_badge = '<span class="badge bg-danger">Ditolak</span>'; break;
                        }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['nama'] ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $row['jenis_surat'])) ?></td>
                        <td><?= $status_badge ?></td>
                        <td><?= date('d M Y H:i', strtotime($row['tanggal_ajuan'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-shield-exclamation"></i> Log Aktivitas Keamanan (Brute Force Attempts)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>IP Address</th>
                        <th>Waktu Percobaan (Terakhir)</th>
                        <th>Status Rate Limit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $log_query = mysqli_query($conn, "
                        SELECT ip_address, MAX(attempted_at) as last_attempt, COUNT(*) as total_attempts 
                        FROM login_attempts 
                        GROUP BY ip_address 
                        ORDER BY last_attempt DESC LIMIT 10
                    ");
                    if (mysqli_num_rows($log_query) > 0) {
                        $no_log = 1;
                        while ($log = mysqli_fetch_assoc($log_query)):
                            $is_blocked = $log['total_attempts'] >= 5;
                    ?>
                    <tr>
                        <td><?= $no_log++ ?></td>
                        <td><span class="badge bg-secondary"><?= $log['ip_address'] ?></span></td>
                        <td><?= date('d M Y H:i:s', strtotime($log['last_attempt'])) ?> (<?= $log['total_attempts'] ?> kali gagal)</td>
                        <td>
                            <?php if($is_blocked): ?>
                                <span class="badge bg-danger"><i class="bi bi-lock-fill"></i> Terblokir 15 Menit</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Terpantau</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<tr><td colspan="4" class="text-center text-muted">Belum ada aktivitas mencurigakan.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
