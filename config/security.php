<?php
function db_query($conn, $sql, $types, ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function check_rate_limit($conn, $ip) {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $result = db_query($conn,
        "SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?",
        "ss", $ip, $window
    );
    if (!$result) return false;
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'] >= 5;
}

function record_failed_attempt($conn, $ip) {
    db_query($conn, "INSERT INTO login_attempts (ip_address) VALUES (?)", "s", $ip);
}

function clear_attempts($conn, $ip) {
    db_query($conn, "DELETE FROM login_attempts WHERE ip_address = ?", "s", $ip);
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function validate_nik($nik) {
    return preg_match('/^\d{16}$/', $nik);
}

function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => false,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
}

function security_headers() {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}
