<?php
// Fitur 1: SQL Injection Prevention (Prepared Statement Helper)
function db_query($conn, $sql, $types, ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Fitur 3: Brute Force Protection (Rate Limiting)
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
