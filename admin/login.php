<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
secure_session_start();
security_headers();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    if (check_rate_limit($conn, $ip)) {
        $error = "Terlalu banyak percobaan login. Coba lagi dalam 15 menit.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Username dan password wajib diisi.";
        } else {
            $result = db_query($conn,
                "SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?",
                "s", $username
            );
            $user = $result ? mysqli_fetch_assoc($result) : null;

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                clear_attempts($conn, $ip);
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];
                header("Location: dashboard.php");
                exit;
            } else {
                record_failed_attempt($conn, $ip);
                $error = "Username atau password salah.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?= e(DESA_NAMA) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="bi bi-shield-lock"></i> Admin Panel</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small><a href="../">Kembali ke Website</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
