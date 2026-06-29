<?php
include '../includes/admin_header.php';
require_once __DIR__ . '/../config/security.php';

// Proses Tambah User
if (isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    $password_plain = $_POST['password'];
    
    // Fitur 2: Password Security (Bcrypt)
    $password_hash = password_hash($password_plain, PASSWORD_BCRYPT);
    
    // Fitur 1: SQL Injection Prevention (Prepared Statement)
    $result = db_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)", 
        "ssss", $username, $password_hash, $nama_lengkap, $role);
        
    if ($result) {
        echo '<div class="alert alert-success">Berhasil menambahkan pengguna baru dengan password yang telah di-hash (bcrypt).</div>';
    } else {
        echo '<div class="alert alert-danger">Gagal menambahkan pengguna.</div>';
    }
}

// Proses Ganti Password
if (isset($_POST['ganti_password'])) {
    $id = $_POST['id'];
    $password_baru = $_POST['password_baru'];
    
    // Fitur 2: Password Security (Bcrypt)
    $password_hash = password_hash($password_baru, PASSWORD_BCRYPT);
    
    // Fitur 1: SQL Injection Prevention (Prepared Statement)
    $result = db_query($conn, "UPDATE users SET password = ? WHERE id = ?", "si", $password_hash, $id);
    
    if ($result) {
        echo '<div class="alert alert-success">Berhasil mengubah password (disimpan sebagai bcrypt hash).</div>';
    } else {
        echo '<div class="alert alert-danger">Gagal mengubah password.</div>';
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-shield-lock"></i> Manajemen Pengguna & Keamanan Password</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
        <i class="bi bi-plus-lg"></i> Tambah Pengguna
    </button>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Daftar Pengguna (Admin & Petugas)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Password Hash (Bcrypt)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = db_query($conn, "SELECT * FROM users", "");
                    if($query):
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($query)):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['nama_lengkap'] ?></td>
                        <td><span class="badge bg-secondary"><?= $row['role'] ?></span></td>
                        <td><code class="small text-muted" style="word-break: break-all;"><?= substr($row['password'], 0, 30) ?>...</code></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                <i class="bi bi-key"></i> Ganti Password
                            </button>
                        </td>
                    </tr>

                    <!-- Modal Ganti Password -->
                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Ganti Password: <?= $row['username'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" name="password_baru" required>
                                            <small class="text-muted">Password akan di-hash menggunakan algoritma Bcrypt.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="ganti_password" class="btn btn-primary">Simpan Password Baru</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php 
                    endwhile; 
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="petugas">Petugas</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
