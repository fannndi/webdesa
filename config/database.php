<?php
$conn = mysqli_connect('localhost', 'root', '', 'webdesa_secure');
if (!$conn) {
    die("Koneksi gagal.");
}
mysqli_set_charset($conn, 'utf8mb4');
