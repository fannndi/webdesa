# Dokumentasi Keamanan — Versi Secure (secure-v2)

## Ringkasan

Dokumen ini menjelaskan seluruh perubahan keamanan yang diterapkan di branch `secure-v2`. Digunakan sebagai referensi teknis untuk presentasi UAS — jelaskan **mengapa** serangan SQLi gagal di versi ini.

---

## 1. Prepared Statement — Anti SQL Injection

### Sebelum (master)
```php
$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);
```
Input user langsung dikonkatenasi ke SQL → **SQLi 100% berhasil**.

### Sesudah (secure-v2)
```php
$nik = $_POST['nik'];
$result = db_query($conn, "SELECT * FROM warga WHERE nik = ?", "s", $nik);
```
Parameter `?` di-binding sebagai **data**, bukan kode SQL. MySQL engine tidak akan mengeksekusi karakter SQL dalam parameter.

### Fungsi `db_query()`
```php
function db_query($conn, $sql, $types, ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
```
**File:** `config/security.php:4`

### File yang Diubah

| File | Jumlah Query | Parameter yang Di-binding |
|------|-------------|--------------------------|
| cek_warga.php | 1 | s (nik) |
| ajukan_surat.php | 2 | s (nik), isssss (warga_id, jenis_surat, ...) |
| riwayat.php | 1 | s (nik) |
| berita_detail.php | 2 | i (id), i (id sidebar) |
| berita.php | 2 | ii (limit, offset) |
| index.php | 4 | - (statik, tanpa parameter) |
| admin/login.php | 1 | s (username) |
| admin/warga.php | 3 | ss (search), 11s (insert), ssssssi (update) |
| admin/surat.php | 2 | s (status), sssii (update) |
| admin/berita.php | 4 | i (delete), i (toggle), sssi (insert), sssii (update) |
| admin/dashboard.php | 5 | - (statik) |

---

## 2. Bcrypt Password — Anti Credential Theft

### Sebelum (master)
```sql
password VARCHAR(100) NOT NULL,  -- plaintext!
-- admin/admin123
```
Password bisa dibaca langsung dari database via SQLi dump.

### Sesudah (secure-v2)
```sql
password VARCHAR(255) NOT NULL,  -- bcrypt hash ($2y$12$...)
```
Password diverifikasi dengan `password_verify()` — bukan string comparison SQL:
```php
$result = db_query($conn, "SELECT id, username, password FROM users WHERE username = ?", "s", $username);
$user = mysqli_fetch_assoc($result);
if ($user && password_verify($password, $user['password'])) {
    // login berhasil
}
```
**Mengapa gagal:** Payload `' OR 1=1 --` di username → prepared statement → string literal. Payload di password → `password_verify()` membandingkan dengan hash, bukan SQL.

**File:** `admin/login.php:31`

---

## 3. Rate Limiting — Anti Brute Force

### Mekanisme
```php
function check_rate_limit($conn, $ip) {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $result = db_query($conn,
        "SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?",
        "ss", $ip, $window
    );
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'] >= 5; // true = diblokir
}
```
### Alur
```
Percobaan 1-5 → "Username atau password salah."
Percobaan 6+   → "Terlalu banyak percobaan login. Coba lagi dalam 15 menit."
```
- Tabel `login_attempts` mencatat IP + timestamp
- IP diblokir setelah 5 gagal dalam 15 menit
- Login sukses → `clear_attempts()` menghapus riwayat

**File:** `config/security.php:20`

---

## 4. CSRF Token — Anti Cross-Site Request Forgery

Setiap form POST menyertakan token unik per sesi:
```php
// Generate di form
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

// Validasi di handler
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Request tidak valid.");
}
```

**Fungsi:**
```php
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
```

**File:** `config/security.php:40`

### Form yang Dilindungi
| Halaman | Method | Form |
|---------|--------|------|
| cek_warga.php | POST | Cek NIK |
| ajukan_surat.php | POST | Step 1 + Step 3 |
| riwayat.php | POST | Cek Riwayat |
| admin/login.php | POST | Login |
| admin/warga.php | POST | Tambah + Edit |
| admin/surat.php | POST | Update Status |
| admin/berita.php | POST | Tambah + Edit |

---

## 5. Output Escaping — Anti XSS

### Sebelum (master)
```php
echo $warga['nama'];   // langsung dari DB → XSS jika DB terkontaminasi
```

### Sesudah (secure-v2)
```php
echo e($warga['nama']); // htmlspecialchars → aman

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
```
Setiap output dari database atau input user melewati `e()`.

**File:** `config/security.php:13`

---

## 6. Validasi Input — Anti Injection di Parameter Integer

### NIK (16 digit angka)
```php
function validate_nik($nik) {
    return preg_match('/^\d{16}$/', $nik);
}
```
Payload `' OR 1=1 --` → regex gagal → "NIK harus 16 digit angka."

### ID Berita (integer positif)
```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    die("Halaman tidak ditemukan.");
}
```
Payload `1 AND SLEEP(5)--` → `FILTER_VALIDATE_INT` gagal → 404.

### Status Surat (whitelist enum)
```php
$allowed_status = ['menunggu', 'diproses', 'selesai', 'ditolak', ''];
$status = $_GET['status'] ?? '';
if (!in_array($status, $allowed_status)) {
    $status = '';
}
```
Payload `' UNION SELECT ...` → tidak ada di whitelist → di-reset ke string kosong.

**File:** `config/security.php:56`, `berita_detail.php:6`, `admin/surat.php:13`

---

## 7. Session Hardening

### Sebelum (master)
```php
session_start();
```
- Cookie bisa diakses JavaScript (httponly=false)
- Tidak ada proteksi fixed session ID

### Sesudah (secure-v2)
```php
function secure_session_start() {
    session_start([
        'cookie_httponly' => true,   // tidak bisa diakses JS
        'cookie_secure'   => false,
        'cookie_samesite' => 'Strict', // tidak dikirim ke domain lain
        'use_strict_mode' => true,     // tolak session ID dari user
    ]);
}
```

Plus `session_regenerate_id(true)` setelah login berhasil:
```php
session_regenerate_id(true); // cegah session fixation
```

**File:** `config/security.php:61`, `admin/login.php:37`

---

## 8. Security Headers

```php
function security_headers() {
    header("X-Content-Type-Options: nosniff");   // cegah MIME sniffing
    header("X-Frame-Options: DENY");              // cegah clickjacking
    header("Referrer-Policy: strict-origin-when-cross-origin");
}
```

Dipanggil di `header.php` (publik) dan `admin_header.php` (admin).

**File:** `config/security.php:70`

---

## 9. Error Suppression

### Sebelum (master)
```php
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());
if (!$result) echo "Error: " . mysqli_error($conn);
```
- Informasi sensitif bocor: host DB, versi MySQL, struktur query

### Sesudah (secure-v2)
```php
if (!$conn) die("Koneksi gagal.");  // generik
// Tidak ada mysqli_error() di seluruh aplikasi
```
- Error MySQL tidak pernah ditampilkan ke user
- Tidak ada informasi teknis yang bocor

**File:** `config/database.php:4`

---

## Tabel Perbandingan Lengkap

| Aspek | master (Non-Secure) | secure-v2 (Secure) | Teknik |
|-------|--------------------|--------------------|--------|
| Query DB | String concatenation | Prepared statement | Binding parameter |
| Password | Plaintext | bcrypt hash cost 12 | `password_hash()` |
| Error | `mysqli_error()` ditampilkan | Pesan generik | Supression |
| Output | `echo $var` | `echo e($var)` | `htmlspecialchars()` |
| CSRF | Tidak ada | Token per sesi | `random_bytes()` + `hash_equals()` |
| Rate Limit | Tidak ada | 5x / 15 menit | `login_attempts` table |
| Session | `session_start()` biasa | httponly + samesite + regenerate | Secure session config |
| NIK Input | Tidak divalidasi | Regex 16 digit | `preg_match()` |
| Enum Input | Tidak divalidasi | Whitelist `in_array()` | Array allowed values |
| Integer Input | Tidak divalidasi | `FILTER_VALIDATE_INT` | PHP filter |
| Security Headers | Tidak ada | nosniff, DENY, referrer | HTTP response header |

---

## Cara Demo di UAS

```bash
# 1. Switch ke master → demo SQLi
git checkout master
# Semua payload berhasil (injection_points.md)

# 2. Switch ke secure-v2 → demo gagal
git checkout secure-v2
# Payload SAMA PERSIS, tapi prepared statement block

# 3. Bandingkan kode
git diff master..secure-v2 -- cek_warga.php
```

### Skenario Demo

| No | Aksi | master | secure-v2 | Mengapa |
|----|------|--------|-----------|---------|
| 1 | Login: `admin'-- -` | ✅ Masuk dashboard | ❌ "Username atau password salah" | Prepared statement |
| 2 | NIK: `' OR 1=1 --` | ✅ Semua data tampil | ❌ "NIK harus 16 digit angka" | Validasi regex |
| 3 | ID: `1 AND SLEEP(5)` | ✅ Delay 5 detik | ❌ 404 not found | `FILTER_VALIDATE_INT` |
| 4 | Login brute force 6x | ✅ Tidak ada blokir | ❌ "Terlalu banyak percobaan" | Rate limiting |
| 5 | Dump password via SQLi | ✅ Password terlihat | ❌ Bcrypt hash tidak terbaca | Prepared statement + hash |
| 6 | SQL error injection | ✅ Stack trace bocor | ❌ Halaman tetap normal | Error suppression |
