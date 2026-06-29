# PRD — Sistem Informasi Desa: Versi AMAN (Secure)
**Versi:** 2.0.0-secure  
**Stack:** PHP 8 Native · MySQL/MariaDB · Bootstrap 5 · mysqli (Prepared Statements)  
**Environment:** Localhost (Docker / XAMPP / Laragon)  
**Tujuan:** Versi hardened dari webdesa non-secure — digunakan sebagai pembanding dalam pengujian keamanan UAS Sistem Informasi

---

## 1. Ringkasan Proyek

Dokumen ini mendefinisikan versi **aman (secure)** dari Sistem Informasi Desa yang sebelumnya dibuat rentan secara sengaja. Aplikasi ini memiliki **fitur dan tampilan yang identik** dengan versi non-secure, namun seluruh celah keamanan telah ditutup menggunakan teknik mitigasi standar industri.

**Tujuan demo UAS:**
- Tunjukkan serangan SQL Injection berhasil di versi non-secure
- Tunjukkan serangan yang **sama persis gagal** di versi secure
- Jelaskan **mengapa** dan **apa yang berbeda** secara teknis

**Batasan penting:**
- Hanya dijalankan di **localhost yang terisolasi**
- Semua data warga adalah **fiktif/dummy** — tidak menggunakan data penduduk asli
- Kode ini **tidak untuk di-deploy ke publik**

---

## 2. Struktur Folder

Identik dengan versi non-secure, dengan penambahan satu folder:

```
webdesa-secure/
│
├── index.php
├── profile.php
├── berita.php
├── berita_detail.php
├── cek_warga.php
├── ajukan_surat.php
├── riwayat.php
│
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── warga.php
│   ├── surat.php
│   └── berita.php
│
├── config/
│   ├── database.php
│   ├── constants.php
│   └── security.php          ← BARU: fungsi-fungsi keamanan terpusat
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── admin_header.php
│
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── img/logo_desa.png
│
├── database/
│   ├── schema.sql             ← password di-hash bcrypt
│   └── dummy_data.sql
│
└── README.md
```

---

## 3. Database Schema

### Perbedaan dari Versi Non-Secure

**Tabel `users`** — password disimpan sebagai **bcrypt hash**, bukan plaintext:
```sql
CREATE TABLE users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(50)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,  -- bcrypt hash ($2y$12$...)
  nama_lengkap VARCHAR(100) NOT NULL,
  role         ENUM('admin','petugas') DEFAULT 'petugas',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

> Seed: `admin` / `admin123` → disimpan sebagai `$2y$12$...` (bcrypt cost 12)  
> Seed: `petugas1` / `petugas123` → disimpan sebagai `$2y$12$...`

**Tabel `warga`** — identik dengan versi non-secure (data warga fiktif, tidak perlu enkripsi di sini karena fokus demo adalah SQL Injection)

**Tabel `login_attempts`** — BARU, untuk rate limiting:
```sql
CREATE TABLE login_attempts (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at)
);
```

---

## 4. `config/security.php` — Modul Keamanan Terpusat

File baru ini berisi semua fungsi keamanan yang digunakan di seluruh aplikasi:

```php
<?php
// ============================================================
// FUNGSI 1: Prepared Statement Helper
// Menggantikan raw query di seluruh aplikasi
// ============================================================
function db_query($conn, $sql, $types, ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// ============================================================
// FUNGSI 2: Output Escaping
// Menggantikan echo $var; menjadi echo e($var);
// ============================================================
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ============================================================
// FUNGSI 3: Rate Limiting Login
// Blokir IP setelah 5 percobaan gagal dalam 15 menit
// ============================================================
function check_rate_limit($conn, $ip) {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $result = db_query($conn,
        "SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?",
        "ss", $ip, $window
    );
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'] >= 5; // true = diblokir
}

function record_failed_attempt($conn, $ip) {
    db_query($conn,
        "INSERT INTO login_attempts (ip_address) VALUES (?)",
        "s", $ip
    );
}

function clear_attempts($conn, $ip) {
    db_query($conn,
        "DELETE FROM login_attempts WHERE ip_address = ?",
        "s", $ip
    );
}

// ============================================================
// FUNGSI 4: CSRF Token
// Generate dan validasi token unik per sesi
// ============================================================
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
// FUNGSI 5: Validasi Input NIK
// NIK harus 16 digit angka — tolak selain itu
// ============================================================
function validate_nik($nik) {
    return preg_match('/^\d{16}$/', $nik);
}

// ============================================================
// FUNGSI 6: Session Management yang Aman
// ============================================================
function secure_session_start() {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => false, // set true jika pakai HTTPS
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}
```

---

## 5. `config/database.php`

Identik dengan versi non-secure — koneksi ke database lokal:
```php
<?php
$conn = mysqli_connect('localhost', 'root', '', 'webdesa_secure');
if (!$conn) {
    die("Koneksi gagal."); // Pesan error generik — tidak ekspos detail
}
mysqli_set_charset($conn, 'utf8mb4');
```

> **Perbedaan:** Tidak ada `mysqli_connect_error()` yang ditampilkan ke user.

---

## 6. Pola Query — Seluruh Aplikasi

### Versi Non-Secure (RENTAN):
```php
// String concatenation langsung — BERBAHAYA
$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);
if (!$result) {
    echo "Error: " . mysqli_error($conn); // error ekspos!
}
```

### Versi Secure (AMAN):
```php
// Prepared statement — AMAN
$nik = $_POST['nik'] ?? '';

// Validasi format dulu
if (!validate_nik($nik)) {
    $error = "Format NIK tidak valid (harus 16 digit angka).";
} else {
    $result = db_query($conn,
        "SELECT * FROM warga WHERE nik = ?",
        "s", $nik
    );
    // Error tidak ditampilkan ke user
}
```

**Prinsip:** Semua parameter dari user (`$_GET`, `$_POST`, `$_COOKIE`) **wajib** melalui prepared statement. Tidak ada satu pun string concatenation ke query SQL.

---

## 7. Halaman Publik — Perbedaan Implementasi

### 7.1 `cek_warga.php`

**Non-secure:** raw query, error ditampilkan  
**Secure:**
```php
require_once 'config/security.php';
secure_session_start();

$result_warga = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verifikasi CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }

    $nik = trim($_POST['nik'] ?? '');

    // 2. Validasi format NIK sebelum query
    if (!validate_nik($nik)) {
        $error = "NIK harus 16 digit angka.";
    } else {
        // 3. Prepared statement — tidak ada injection
        $result_warga = db_query($conn,
            "SELECT nik, nama, tempat_lahir, tanggal_lahir,
                    jenis_kelamin, alamat, rt, rw, dusun,
                    pekerjaan, status_perkawinan
             FROM warga WHERE nik = ?",
            "s", $nik
        );
        // Catatan: hanya kolom yang dibutuhkan yang di-SELECT
        // NIK asli tidak di-SELECT ulang untuk meminimalisir eksposur
    }
}
```

Output di HTML menggunakan fungsi `e()`:
```php
// Non-secure: echo $row['nama'];          ← rentan XSS
// Secure:     echo e($row['nama']);        ← aman
```

### 7.2 `berita_detail.php`

**Non-secure:**
```php
$id = $_GET['id'];  // rentan SQLi via GET
$sql = "SELECT * FROM berita WHERE id = '$id'";
```

**Secure:**
```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    die("Halaman tidak ditemukan.");
}
$result = db_query($conn,
    "SELECT * FROM berita WHERE id = ? AND diterbitkan = 1",
    "i", $id
);
```

### 7.3 `ajukan_surat.php`

Semua step menggunakan prepared statement. Tambahan validasi:
- `jenis_surat` divalidasi dengan whitelist: `['domisili','usaha','tidak_mampu','pengantar_nikah']`
- `warga_id` divalidasi sebagai integer positif
- CSRF token diperiksa di setiap step

### 7.4 `riwayat.php`

```php
// Secure: NIK divalidasi + prepared statement
if (!validate_nik($nik)) {
    $error = "Format NIK tidak valid.";
} else {
    $result = db_query($conn,
        "SELECT sp.jenis_surat, sp.keperluan, sp.status, sp.tanggal_ajuan
         FROM surat_pengajuan sp
         JOIN warga w ON sp.warga_id = w.id
         WHERE w.nik = ?
         ORDER BY sp.tanggal_ajuan DESC",
        "s", $nik
    );
}
```

---

## 8. Panel Admin

### 8.1 `admin/login.php`

Ini adalah titik perbedaan paling dramatis untuk demo:

**Non-secure:**
```php
$username = $_POST['username'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// → rentan SQLi login bypass + password plaintext
```

**Secure:**
```php
require_once '../config/security.php';
secure_session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verifikasi CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Request tidak valid.");
    }

    $ip = $_SERVER['REMOTE_ADDR'];

    // 2. Cek rate limit — blokir jika > 5 percobaan dalam 15 menit
    if (check_rate_limit($conn, $ip)) {
        $error = "Terlalu banyak percobaan login. Coba lagi dalam 15 menit.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // 3. Validasi input dasar
        if (empty($username) || empty($password)) {
            $error = "Username dan password wajib diisi.";
        } else {
            // 4. Prepared statement — HANYA cari berdasarkan username
            $result = db_query($conn,
                "SELECT id, username, password, nama_lengkap, role
                 FROM users WHERE username = ?",
                "s", $username
            );

            $user = mysqli_fetch_assoc($result);

            // 5. Verifikasi password dengan bcrypt — BUKAN string comparison
            if ($user && password_verify($password, $user['password'])) {
                // 6. Login berhasil — regenerate session ID (anti session fixation)
                session_regenerate_id(true);
                clear_attempts($conn, $ip);

                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];

                header("Location: dashboard.php");
                exit;
            } else {
                // 7. Catat percobaan gagal
                record_failed_attempt($conn, $ip);
                $error = "Username atau password salah."; // pesan generik
            }
        }
    }
}
```

**Yang membuat serangan gagal:**
| Vektor Serangan | Mengapa Gagal di Versi Secure |
|---|---|
| `' OR 1=1 --` di username | Prepared statement — karakter SQL diperlakukan sebagai data, bukan kode |
| `' OR 1=1 --` di password | Sama — password diverifikasi dengan `password_verify()`, bukan string SQL |
| Brute force wordlist | Rate limiting — IP diblokir setelah 5 percobaan gagal dalam 15 menit |
| Login langsung dengan password hash | `password_verify()` membandingkan hash, bukan plaintext |

### 8.2 `admin/warga.php` — Search Parameter

**Non-secure:**
```php
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM warga WHERE nama LIKE '%$search%' OR nik LIKE '%$search%'";
// → parameter GET rentan SQLi
```

**Secure:**
```php
$search = trim($_GET['q'] ?? '');
$search_param = "%{$search}%";
$result = db_query($conn,
    "SELECT id, nik, nama, dusun, rt, rw, pekerjaan
     FROM warga WHERE nama LIKE ? OR nik LIKE ?
     ORDER BY nama ASC",
    "ss", $search_param, $search_param
);
```

### 8.3 `admin/surat.php` — Filter Status

**Non-secure:**
```php
$status = $_GET['status']; // raw, tidak divalidasi
$sql = "SELECT ... WHERE status = '$status'";
```

**Secure:**
```php
// Whitelist validasi — hanya nilai yang diizinkan
$allowed_status = ['menunggu', 'diproses', 'selesai', 'ditolak', ''];
$status = $_GET['status'] ?? '';
if (!in_array($status, $allowed_status)) {
    $status = ''; // reset ke default jika tidak valid
}

if ($status) {
    $result = db_query($conn,
        "SELECT ... FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id
         WHERE sp.status = ? ORDER BY sp.tanggal_ajuan DESC",
        "s", $status
    );
} else {
    $result = db_query($conn,
        "SELECT ... FROM surat_pengajuan sp JOIN warga w ON sp.warga_id = w.id
         ORDER BY sp.tanggal_ajuan DESC",
        "", // tidak ada parameter
    );
}
```

### 8.4 Session Check — Semua Halaman Admin

**Non-secure:** session check minimal tanpa regenerasi  
**Secure:** tambahan verifikasi role dan regenerasi

```php
secure_session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cek role untuk halaman tertentu (opsional)
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: dashboard.php");
//     exit;
// }
```

---

## 9. Security Headers — `config/security.php`

Tambahkan di awal setiap halaman (atau di `config/security.php` yang di-include pertama):

```php
// HTTP Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://cdn.jsdelivr.net");
// Catatan: hapus CDN dari CSP jika Bootstrap diunduh lokal
```

---

## 10. Tabel Perbandingan Lengkap: Non-Secure vs Secure

| Aspek | Versi Non-Secure | Versi Secure | Referensi OWASP |
|---|---|---|---|
| **Query Database** | String concatenation raw | Prepared statement mysqli | A03: Injection |
| **Password Storage** | Plaintext di DB | bcrypt hash (cost 12) | A02: Crypto Failures |
| **Verifikasi Password** | String SQL comparison | `password_verify()` | A02: Crypto Failures |
| **Error Messages** | Stack trace + query SQL ditampilkan | Pesan generik ke user, log di server | A05: Misconfiguration |
| **Validasi Input NIK** | Tidak ada | Regex 16 digit wajib | A03: Injection |
| **Validasi Enum** | Tidak ada (status, jenis_surat) | Whitelist `in_array()` | A03: Injection |
| **Output Encoding** | `echo $var` langsung | `echo e($var)` = htmlspecialchars | A03: XSS |
| **CSRF Protection** | Tidak ada | Token per sesi di semua form | A01: BAC |
| **Rate Limiting** | Tidak ada | 5 percobaan / 15 menit per IP | A07: Auth Failures |
| **Session Management** | `session_start()` biasa | `secure_session_start()` + regenerate ID | A07: Auth Failures |
| **Security Headers** | Tidak ada | X-Frame-Options, CSP, X-Content-Type | A05: Misconfiguration |
| **SQL Error** | `echo mysqli_error($conn)` | Error disembunyikan dari user | A05: Misconfiguration |

---

## 11. Skenario Demo UAS — Step by Step

### Skenario 1: SQL Injection Login Bypass

**Step 1 — Demo di versi NON-SECURE:**
1. Buka `http://localhost/webdesa/admin/login.php`
2. Gunakan Burp Suite — intercept request login
3. Masukkan payload: `username = ' OR 1=1 -- -`, `password = apapun`
4. **Hasil:** Login berhasil sebagai admin tanpa password yang benar ✅ (celah terbukti)

**Step 2 — Demo di versi SECURE:**
1. Buka `http://localhost/webdesa-secure/admin/login.php`
2. Gunakan payload yang **sama persis** via Burp Suite
3. **Hasil:** Login gagal, muncul "Username atau password salah" ❌ (serangan gagal)
4. Jelaskan: prepared statement membuat `' OR 1=1 -- -` diperlakukan sebagai string literal, bukan SQL

### Skenario 2: Rate Limiting

**Di versi NON-SECURE:**
- Kirim 20 request login salah berturut-turut → semua diproses, tidak ada blokir

**Di versi SECURE:**
- Percobaan 1-5: "Username atau password salah"
- Percobaan ke-6+: "Terlalu banyak percobaan login. Coba lagi dalam 15 menit." ❌

### Skenario 3: SQL Injection di Pencarian NIK

**Di versi NON-SECURE:**
```
Input NIK: ' OR '1'='1
Hasil: Semua data warga tampil!
```

**Di versi SECURE:**
```
Input NIK: ' OR '1'='1
Hasil: "Format NIK tidak valid (harus 16 digit angka)." ❌
```

### Skenario 4: Error Information Disclosure

**Di versi NON-SECURE:**
```
Input: 3273' AND SLEEP(5)--
Hasil: Query Error: SQLITE_ERROR blah blah... (stack trace terekspos)
```

**Di versi SECURE:**
```
Input: 3273' AND SLEEP(5)--
Hasil: "Format NIK tidak valid." ❌ (tidak ada info teknis yang bocor)
```

---

## 12. `database/schema.sql` — Versi Secure

Perbedaan utama: tambah tabel `login_attempts`, password seed menggunakan bcrypt:

```sql
-- Generate hash bcrypt di PHP:
-- echo password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
-- Hasilnya: $2y$12$... (berbeda setiap generate, ini normal)

INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin',    '$2y$12$eImiTXuWVxfM37uY4JANjQ==...', 'Administrator Desa', 'admin'),
('petugas1', '$2y$12$TYx4CfV2kFPe3nXwQ7YmZu==...', 'Petugas Satu',        'petugas');

-- Tabel login_attempts untuk rate limiting
CREATE TABLE login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip_address   VARCHAR(45) NOT NULL,
  attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at)
);
```

> **Catatan implementasi:** Jalankan script PHP sekali untuk generate hash bcrypt yang valid, lalu paste hasilnya ke `dummy_data.sql`.

---

## 13. `README.md` — Versi Secure

```markdown
# Web Desa Secure — v2.0.0-secure

Versi aman dari Sistem Informasi Desa untuk demonstrasi pengujian keamanan.
Digunakan sebagai perbandingan "SESUDAH diamankan" dalam pengujian UAS.

## Instalasi
1. Copy folder webdesa-secure ke htdocs/
2. Buat database: CREATE DATABASE webdesa_secure;
3. Import: mysql -u root webdesa_secure < database/schema_secure.sql
4. Import data: mysql -u root webdesa_secure < database/dummy_data.sql
5. Generate bcrypt hash: php generate_hash.php
6. Akses: http://localhost/webdesa-secure/

## Akun Default
| Role    | Username | Password   |
|---------|----------|------------|
| Admin   | admin    | admin123   |
| Petugas | petugas1 | petugas123 |
(password disimpan sebagai bcrypt hash di database)

## Perlindungan yang Diimplementasikan

| Kerentanan | Perlindungan |
|---|---|
| SQL Injection | Prepared Statements (semua query) |
| Password plaintext | bcrypt hash cost factor 12 |
| Brute Force | Rate limiting 5x/15 menit per IP |
| XSS | htmlspecialchars() semua output |
| CSRF | Token per sesi di semua form POST |
| Info Disclosure | Pesan error generik |
| Clickjacking | X-Frame-Options: DENY |

## Titik Injeksi — Semua GAGAL di Versi Ini

| File | Parameter | Mengapa Gagal |
|---|---|---|
| cek_warga.php | nik | Validasi regex + prepared statement |
| ajukan_surat.php | nik | Validasi regex + prepared statement |
| admin/login.php | username, password | Prepared statement + bcrypt + rate limit |
| admin/warga.php | q (search) | Prepared statement |
| admin/surat.php | status | Whitelist validation |
| berita_detail.php | id | filter_input FILTER_VALIDATE_INT |
```

---

## 14. Checklist Output Final — Versi Secure

### Keamanan Query
- [ ] Semua `mysqli_query($conn, "...{$var}...")` diganti dengan `db_query()` + prepared statement
- [ ] Tidak ada satu pun string concatenation ke SQL query
- [ ] Semua `echo $var` dari DB diganti dengan `echo e($var)`

### Autentikasi
- [ ] Password di DB disimpan sebagai bcrypt hash (`password_hash()`)
- [ ] Login menggunakan `password_verify()` — bukan SQL comparison
- [ ] Rate limiting aktif: tabel `login_attempts` berfungsi
- [ ] `session_regenerate_id(true)` dipanggil setelah login berhasil

### Validasi Input
- [ ] NIK divalidasi regex 16 digit sebelum query
- [ ] Parameter enum (jenis_surat, status) divalidasi dengan whitelist
- [ ] Parameter integer (id berita, warga_id) divalidasi `FILTER_VALIDATE_INT`

### CSRF & Session
- [ ] CSRF token ada di semua form POST
- [ ] `verify_csrf_token()` dipanggil sebelum memproses POST
- [ ] Session menggunakan httponly + samesite strict

### Headers & Error
- [ ] Security headers terpasang di semua halaman
- [ ] Tidak ada `mysqli_error()` yang di-echo ke user
- [ ] Tidak ada stack trace yang ditampilkan ke browser

---

*PRD ini adalah spesifikasi lengkap untuk versi secure. Digunakan bersama PRD versi non-secure untuk demonstrasi perbandingan keamanan dalam UAS Sistem Informasi.*
