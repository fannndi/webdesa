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

---

## Cara Demo di UAS

```bash
# 1. Switch ke master → demo SQLi BERHASIL
git checkout master

# 2. Switch ke secure-v2 → SERANGAN SAMA GAGAL
git checkout secure-v2

# 3. Bandingkan kode langsung
git diff master..secure-v2 -- cek_warga.php
git diff master..secure-v2 -- admin/login.php
```

---

## Panduan Praktikum Burp Suite — Versi Secure

Panduan ini menunjukkan **setiap payload dari non-secure.md GAGAL** di versi secure.
Gunakan Burp Suite dengan cara yang SAMA PERSIS — hasilnya berbeda total.

---

### Skenario 1: Login Bypass GAGAL

**Burp Repeater — payload SAMA dengan non-secure:**

```
# ❌ GAGAL — tanpilkan halaman login lagi
POST /webdesa/admin/login.php
username=admin'-- -&password=x
```

| # | Aksi | Response | Mengapa |
|---|------|----------|---------|
| 1 | Intercept POST login | Request terkirim normal | - |
| 2 | Send to Repeater | - | - |
| 3 | `username=admin'-- -` | **❌ 200 OK + "Username atau password salah"** | Prepared statement: `'-- -` = string literal |
| 4 | `username=' OR 1=1 --` | **❌ 200 OK + "Username atau password salah"** | Sama — diperlakukan sebagai data |
| 5 | Password: `apaaja` | **❌ 200 OK** | `password_verify()` gagal |

**Kode pembeda:**
```php
// master
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// → string concatenation → SQLi BERHASIL

// secure-v2
$stmt = mysqli_prepare($conn, "SELECT id, username, password FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
// → parameter binding → ' OR 1=1 = string literal → GAGAL
```

---

### Skenario 2: Brute Force DIBLOKIR

**Burp Intruder — Intruder → Resource pool → 1 concurrent:**

| # | Aksi | Response |
|---|------|----------|
| 1 | Kirim 6x login salah berturut-turut | Percobaan 1-5: "Username atau password salah" |
| 2 | Kirim percobaan ke-6 | **❌ "Terlalu banyak percobaan login. Coba lagi dalam 15 menit."** |
| 3 | Login dengan password benar | **❌ Masih diblokir** (sampai 15 menit) |

**Kode pembeda:**
```php
// secure-v2
$ip = $_SERVER['REMOTE_ADDR'];
if (check_rate_limit($conn, $ip)) {
    $error = "Terlalu banyak percobaan login. Coba lagi dalam 15 menit.";
}
```

Burp Intruder config:
1. Target: `POST /webdesa/admin/login.php`
2. Payload position: `username=§admin§&password=§admin123§`
3. Payload: Simple list (20 password umum)
4. Resource pool: **Max concurrent requests = 1** (biar sequential)
5. Start → amati response ke-6 berbeda

---

### Skenario 3: UNION SELECT GAGAL di Cek NIK

**Burp Repeater:**

```
# ❌ GAGAL — "NIK harus 16 digit angka"
POST /webdesa/cek_warga.php
nik=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -
```

| # | Aksi | Response | Mengapa |
|---|------|----------|---------|
| 1 | Intercept POST cek_warga | Request terkirim | - |
| 2 | Send to Repeater | - | - |
| 3 | `nik=' UNION SELECT 1,2,3...-- -` | **❌ "NIK harus 16 digit angka"** | Regex `^\d{16}$` reject |
| 4 | `nik=' OR 1=1 --` | **❌ "NIK harus 16 digit angka"** | Sama — mengandung karakter non-digit |
| 5 | `nik=3273010101000001` | ✅ Data ditemukan | NIK valid 16 digit |

**Kode pembeda:**
```php
// master — langsung query
$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);

// secure-v2 — validasi dulu
if (!validate_nik($nik)) {                  // ← BARU
    $error = "NIK harus 16 digit angka.";   // ← BARU
} else {
    $result = db_query($conn,               // ← prepared statement
        "SELECT nik, nama, ... FROM warga WHERE nik = ?",
        "s", $nik
    );
}
```

---

### Skenario 4: Error-Based GAGAL di berita_detail.php

**Browser langsung — payload SAMA dengan non-secure:**

```
http://localhost/webdesa/berita_detail.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```

| # | Aksi | master | secure-v2 |
|---|------|--------|-----------|
| 1 | `?id=1 AND SLEEP(5)--` | ✅ Delay 5 detik | **❌ 404 Not Found** |
| 2 | `?id=-1 UNION SELECT 1,2,3,4,5--` | ✅ Data tampil | **❌ 404 Not Found** |
| 3 | `?id=1` | ✅ Berita tampil | ✅ Berita tampil (normal) |

**Mengapa:** `FILTER_VALIDATE_INT` menolak string `"1 AND SLEEP(5)"` karena bukan integer valid.

```php
// secure-v2
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    die("Halaman tidak ditemukan.");
}
// → "1 AND SLEEP(5)" = null = 404
```

---

### Skenario 5: Time-Based GAGAL Total

**Burp Repeater — drop-down response time:**

**master:** Request `' AND SLEEP(5)--` → response **+5 detik**
**secure-v2:** Request SAMA → response **< 1 detik** (langsung di-reject regex)

| # | Payload | master (response time) | secure-v2 (response time) |
|---|---------|------------------------|---------------------------|
| 1 | `' AND SLEEP(5)--` | ~5.2 detik | **< 0.1 detik** |
| 2 | `' AND IF(1=1,SLEEP(5),0)--` | ~5.2 detik | **< 0.1 detik** |
| 3 | `' AND IF(SUBSTRING(...),SLEEP(5),0)--` | ~3-5 detik | **< 0.1 detik** |
| 4 | NIK valid `3273010101000001` | ~0.1 detik | ~0.1 detik (normal) |

**Kesimpulan:** Ekstraksi time-based blind **tidak mungkin** — payload di-reject sebelum menyentuh database.

---

### Skenario 6: Data Dump via UNION GAGAL

**Burp Repeater — semua payload UNION gagal di layer validasi:**

```
# ❌ Tidak ada satu pun payload ini lolos
nik=' UNION SELECT 1,version(),3,...-- -
nik=' UNION SELECT 1,GROUP_CONCAT(table_name),3,... FROM information_schema...-- -
nik=' UNION SELECT 1,GROUP_CONCAT(username,0x3a,password),3,... FROM users-- -
```

| Layer Pertahanan | Payload Lolos? |
|-----------------|----------------|
| Validasi NIK (regex 16 digit) | **❌ Tidak** |
| Prepared statement | **❌ Tidak** (tapi bahkan tidak sampai sini) |

---

### Skenario 7: Search Warga via GET GAGAL

```
http://localhost/webdesa/admin/warga.php?q=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -
```

**master:** ✅ Semua data tampil (LIKE wildcard + SQLi)
**secure-v2:** **❌ Tabel kosong** (prepared statement — data tidak match)

```php
// secure-v2
$search_param = "%{$search}%";
$result = db_query($conn,
    "SELECT id, nik, nama, dusun, rt, rw, pekerjaan FROM warga WHERE nama LIKE ? OR nik LIKE ? ORDER BY nama ASC",
    "ss", $search_param, $search_param
);
// → '%' UNION SELECT...' = LIKE mencari string literal '%' UNION...'
// → Tidak ada data yang match → tabel kosong
```

---

### Skenario 8: Filter Status Surat GAGAL

```
http://localhost/webdesa/admin/surat.php?status=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14-- -
```

**master:** ✅ Data tampil (SQLi via WHERE clause)
**secure-v2:** **❌ Status di-reset ke kosong** — whitelist reject.

```php
// secure-v2
$allowed_status = ['menunggu', 'diproses', 'selesai', 'ditolak', ''];
$status = $_GET['status'] ?? '';
if (!in_array($status, $allowed_status)) {
    $status = '';  // ← reject, reset ke default
}
// → "' UNION SELECT..." tidak ada di whitelist → $status = ''
// → Query tanpa WHERE → menampilkan SEMUA (tapi aman, tidak bocor)
```

---

### Skenario 9: CSRF Protection — Form POST DITOLAK

**Burp Repeater — tanpa CSRF token:**

```
# ❌ GAGAL — "Request tidak valid."
POST /webdesa/cek_warga.php
nik=3273010101000001
```
Response: **"Request tidak valid."**

**Solusi di browser:** Form normal bekerja karena CSRF token di-generate via session.
**Solusi di Burp:** Ambil CSRF token dari response HTML dulu, baru kirim ulang dengan token valid.

---

### Skenario 10: Error Info Disclosure GAGAL

**Browser — payload error:**

```
http://localhost/webdesa/berita_detail.php?id=1'
```

**master:** ✅ Error MySQL detail (syntax error, posisi, query)
**secure-v2:** **❌ 404 Not Found** — tidak ada informasi teknis bocor

```
http://localhost/webdesa/cek_warga.php
nik=TEST
```

**master:** ✅ "Query Error: Unknown column 'TEST'..."
**secure-v2:** **❌ "NIK harus 16 digit angka."** — pesan generik

---

## Tabel Perbandingan — Semua Serangan GAGAL

| No | Serangan | Payload | master | secure-v2 | Layer yang Blokir |
|----|----------|---------|--------|-----------|-------------------|
| 1 | Login bypass | `admin'-- -` | ✅ Login | ❌ Gagal | Prepared statement |
| 2 | Brute force | 6x percobaan | ✅ Tidak ada blokir | ❌ Blokir | Rate limit |
| 3 | UNION SELECT | `' UNION SELECT 1,2,3...` | ✅ Data bocor | ❌ "NIK harus 16 digit" | Regex validasi |
| 4 | Error-based | `id=1 AND EXTRACTVALUE...` | ✅ Versi MySQL bocor | ❌ 404 | FILTER_VALIDATE_INT |
| 5 | Time-based | `' AND SLEEP(5)--` | ✅ Delay 5 detik | ❌ No delay | Regex reject |
| 6 | Dump credentials | `UNION SELECT ... FROM users` | ✅ admin:admin123 | ❌ "NIK harus 16 digit" | Regex reject |
| 7 | Search LIKE | `?q=' UNION SELECT...` | ✅ Data terbaca | ❌ Tabel kosong | Prepared statement |
| 8 | Filter status | `?status=' UNION SELECT...` | ✅ Data bocor | ❌ Reset ke kosong | Whitelist enum |
| 9 | Form POST tanpa token | `nik=xxx` (no CSRF) | ✅ Data ditemukan | ❌ "Request tidak valid" | CSRF token |
| 10 | Error disclosure | `id=1'` | ✅ Error SQL muncul | ❌ 404 tidak jelas | Error suppression |

---

## Quick Reference: master vs secure-v2

| Aspek | master | secure-v2 |
|-------|--------|-----------|
| Branch | `master` | `secure-v2` |
| Database | `webdesa` | `webdesa_secure` |
| Password storage | Plaintext | bcrypt hash |
| Query method | `mysqli_query()` | `db_query()` prepared statement |
| Output encoding | `echo $var` | `echo e($var)` |
| Error handling | `mysqli_error()` | Pesan generik |
| CSRF | ❌ | ✅ Token per sesi |
| Rate limit | ❌ | ✅ 5x/15 menit |
| Session | `session_start()` biasa | httponly + samesite + regenerate |

```bash
# Cek perbedaan kode langsung
git diff master..secure-v2 --stat
```
