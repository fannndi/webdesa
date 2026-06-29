# Panduan Demo & Praktikum Keamanan WebDesa
## Studi Kasus: Sistem Informasi Desa — Versi Non-Secure vs Secure

**Target 1 (Non-Secure):** `master` branch — PHP Native + Raw Query + Plaintext
**Target 2 (Secure):** `secure-v2` branch — PHP Native + Prepared Statement + Bcrypt + Rate Limiting
**Tools Utama:** Burp Suite Community Edition (Proxy, Repeater, Intruder) + Browser + phpMyAdmin
**Database:** `webdesa`
**Format Demo:** Serang non-secure (BERHASIL) → Pindah ke secure → Serang dengan payload SAMA PERSIS (GAGAL) → Jelaskan MENGAPA.

---

## 0. Etika & Ruang Lingkup

- Seluruh pengujian HANYA dilakukan di localhost sendiri. Jangan *deploy* aplikasi rentan ini ke publik.
- Data warga di dalam sistem adalah data *dummy*/fiktif.
- Tujuan praktikum: Edukasi keamanan siber — memahami dampak serangan (SQL Injection, Brute Force) dan efektivitas mitigasinya.

---

## 1. Persiapan Lab (Setup)

### 1.1 Kebutuhan Aplikasi
| No | Software | Fungsi |
|---|---|---|
| 1 | **XAMPP / Laragon** | Menjalankan Apache (Web Server) dan MySQL (Database) |
| 2 | **Git** | Mengunduh kode dan berpindah branch versi |
| 3 | **Java (JRE/JDK)** | Wajib untuk menjalankan Burp Suite |
| 4 | **Burp Suite CE** | Menangkap dan memodifikasi HTTP Request |

### 1.2 Konfigurasi Burp Suite & Browser

| # | Aksi di Burp Suite & Browser | Keterangan |
|---|------|------------|
| 1 | Buka **Burp Suite** -> Tab **Proxy** -> **Proxy settings** | Pastikan listener berjalan di `127.0.0.1:8080` |
| 2 | Buka **Browser** (disarankan Firefox) -> **Settings** -> **Network Settings** | Set Manual Proxy ke `127.0.0.1` port `8080` |
| 3 | Browser -> Buka `http://burpsuite` -> Download **CA Certificate** | Agar koneksi tidak diblokir browser |
| 4 | Browser -> Settings -> **Certificates** -> Import CA Certificate | Centang "Trust this CA to identify websites" |
| 5 | Burp Suite -> Tab Proxy -> **Intercept** | Pastikan tombol berbunyi **Intercept is off** selama navigasi biasa |

### 1.3 Shortcut Penting Burp Suite

| Shortcut | Fungsi | Digunakan Untuk |
|----------|--------|----------|
| `Ctrl+R` | Send to Repeater | Mengulang dan memodifikasi request manual (SQLi) |
| `Ctrl+I` | Send to Intruder | Mengirim request berulang otomatis (Brute Force) |
| `Ctrl+U` | URL-encode selection | Mengubah karakter khusus (`'` atau `=`) menjadi format URL (`%27`, `%3D`) |
| `Spasi`  | Forward request | Meneruskan request saat Intercept sedang ON |

### 1.4 Instalasi Database & Branching

Project ini mengandalkan perpindahan *branch* Git. Karena struktur database berbeda antara versi aman dan rentan, Anda **WAJIB** meng-import ulang SQL setiap kali berpindah branch.

```bash
# Buka Terminal di folder project htdocs
cd C:\xampp\htdocs\webdesa
```

**Alur Wajib Setiap Pindah Branch:**
1. Git Checkout branch yang diinginkan (`git checkout master` atau `git checkout secure-v2`).
2. Buka phpMyAdmin (`http://localhost/phpmyadmin`).
3. Buat/Pilih database `webdesa`.
4. Import file `database/schema.sql` (Timpa jika sudah ada).
5. Import file `database/dummy_data.sql`.

---

## 2. Peta 3 Fitur Utama (Fokus Praktikum)

| # | Fitur Keamanan | Target File | Metode Serangan | Non-Secure (master) | Secure (secure-v2) |
|---|------|-------|--------|--------------------|-------------------|
| 1 | **SQL Injection** | `admin/login.php` | Manipulasi input form | String Concatenation query | Prepared Statement |
| 2 | **Password Security** | Tabel `users` di DB | Pencurian Data (Data Breach) | Plaintext (Sandi terbaca) | Hashing Bcrypt |
| 3 | **Brute Force** | `admin/login.php` | Burp Intruder (Tebak sandi massal) | Tanpa batas percobaan | Rate Limiting (Blokir 5x gagal) |

---

## 3. Skenario Praktikum (Langkah Demi Langkah)

### Skenario 1: SQL Injection (Login Bypass)

**Target:** `admin/login.php` | Method: `POST` | Param: `username`, `password`

#### 1A. Non-Secure — HARUS BERHASIL

**Langkah:**

| # | Aksi | Expected / Keterangan |
|---|------|----------|
| 1 | Pastikan branch `master` dan DB telah di-import. | |
| 2 | Buka `http://localhost/webdesa/admin/login.php` | Halaman login admin terbuka |
| 3 | Burp Suite -> Intercept **ON** | Status: *Intercept is on* |
| 4 | Browser: Isi Username `apaaja`, Password `apaaja`, klik Login | Request tertahan di Burp |
| 5 | Burp -> Raw HTTP -> Klik Kanan -> **Send to Repeater** (`Ctrl+R`) | Tab Repeater menyala oranye |
| 6 | Buka Tab Repeater, ubah body menjadi:<br>`username=admin'%20OR%20'1'%3D'1&password=x` | `%20` adalah spasi, `%3D` adalah `=` |
| 7 | Klik **Send** | |
| 8 | Cek Response: Status `302 Found` dan Header `Location: dashboard.php` | ✅ **BERHASIL MASUK DASHBOARD TANPA PASSWORD** |

**Penjelasan Query (Non-Secure):**
```sql
SELECT * FROM users WHERE username = 'admin' OR '1'='1' AND password = 'x'
```
Karena `1=1` selalu benar (TRUE), query dieksekusi mengabaikan pengecekan password.

#### 1B. Secure — Payload SAMA -> GAGAL

| # | Aksi | Expected Response | Keterangan |
|---|------|----------|---------|
| 1 | Pindah ke branch `secure-v2` & **Import ulang DB**. | | |
| 2 | Buka Tab Repeater yang berisi payload yang sama. | Payload: `username=admin' OR '1'='1` | |
| 3 | Klik **Send** | Status: `200 OK` (Tidak Redirect) ❌ | Tetap di halaman login |
| 4 | Lihat response body HTML (di panel kanan) | ✅ Muncul pesan: `"Username atau password salah."` | Login gagal |

**Kode Pembeda (Mengapa Gagal?):**
```php
// SECURE (secure-v2)
$result = db_query($conn, "SELECT ... FROM users WHERE username = ?", "s", $username);
```
Dengan Prepared Statement (`?`), database menganggap `admin' OR '1'='1` sebagai sebuah nama pengguna biasa, bukan instruksi SQL. Karena tidak ada akun dengan nama aneh tersebut, login ditolak.

---

### Skenario 2: Password Security (Bcrypt)

**Target:** `http://localhost/phpmyadmin` (Tabel `users`)

#### 2A. Non-Secure — Kredensial Telanjang

**Langkah:**

| # | Aksi | Expected / Keterangan |
|---|------|----------|
| 1 | Pastikan branch `master` dan DB telah di-import. | |
| 2 | Buka `http://localhost/phpmyadmin` | |
| 3 | Pilih database `webdesa` -> klik tabel `users` | |
| 4 | Amati kolom `password` | ✅ Tertulis **`admin123`** (Plaintext). Jika DB bocor, hacker langsung tahu password aslinya. |

#### 2B. Secure — Kriptografi Satu Arah

| # | Aksi | Expected | Keterangan |
|---|------|----------|---------|
| 1 | Pindah ke branch `secure-v2` & **Import ulang DB**. | | |
| 2 | Buka kembali tabel `users` di phpMyAdmin. | | |
| 3 | Amati kolom `password` | ✅ Tertulis **`$2y$10$2LG1U2M...`** | Password diacak menggunakan algoritma *Bcrypt Hashing*. |

**Kode Pembeda:**
```php
// SECURE (secure-v2) Saat Registrasi:
$hash = password_hash('admin123', PASSWORD_BCRYPT);

// Saat Login:
if (password_verify($password_input, $hash_di_db)) { ... }
```
Bcrypt menghasilkan pengacakan yang tidak bisa dibalik (*irreversible*). Jika hacker meretas database, mereka hanya mendapatkan *hash* acak yang tidak bisa digunakan untuk *login*.

---

### Skenario 3: Brute Force Protection (Rate Limiting)

**Target:** `admin/login.php` | Tools: **Burp Intruder**

#### 3A. Non-Secure — Unlimited Attacks

**Konfigurasi Intruder:**
| # | Aksi di Burp Suite |
|---|------|
| 1 | Pastikan branch `master` dan DB telah di-import. |
| 2 | Lakukan Intercept login biasa -> **Send to Intruder** (`Ctrl+I`) |
| 3 | Buka tab Intruder -> **Positions**. Klik *Clear §*, lalu blok nilai password dan klik *Add §* |
| 4 | Buka tab **Payloads** -> Payload type: *Simple list* -> Masukkan 10 sembarang password yang salah. |
| 5 | Buka tab **Resource pool** -> Buat pool baru -> *Max concurrent requests*: **1** (WAJIB berurutan). |
| 6 | Klik **Start attack** di pojok kanan atas. |

**Expected:** Semua 10 percobaan menghasilkan status `200 OK` dengan panjang response (Length) yang sama. Web server melayani penyerang tanpa batas, memungkinkan hacker mencoba jutaan sandi sampai ketemu.

#### 3B. Secure — Diblokir Setelah 5x Gagal

**Langkah:**
| # | Aksi | Expected | Keterangan |
|---|------|----------|---------|
| 1 | Pindah ke branch `secure-v2` & **Import ulang DB**. | | Tabel `login_attempts` kini tersedia. |
| 2 | Kembali ke jendela Intruder -> Klik **Start attack** lagi menggunakan konfigurasi yang persis sama. | | |
| 3 | Amati kolom **Length** pada hasil serangan Intruder. | ✅ Response ke-6 dan seterusnya memiliki ukuran file berbeda. | |
| 4 | Klik percobaan ke-6 -> tab Response | ✅ Terdapat pesan HTML: **"Terlalu banyak percobaan login. Coba lagi dalam 15 menit."** ❌ | Akun terkunci sementara |

**Kode Pembeda:**
```php
// SECURE (secure-v2)
function check_rate_limit($conn, $ip) {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $result = db_query($conn, "SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND attempted_at > ?", "ss", $ip, $window);
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'] >= 5; // Jika >= 5, blokir akses
}
```
Setiap *login* gagal dicatat berdasarkan Alamat IP. Mekanisme ini mematahkan algoritma penyerang yang mengandalkan kecepatan tebak otomatis (Brute Force).

---

## 4. Troubleshooting (Kendala Umum)

| Error / Masalah | Penyebab | Solusi |
|-------|-----|---|
| Halaman web terus *loading* (macet) | **Intercept is on** di Burp Suite | Buka Burp Suite -> matikan Intercept (klik hingga menjadi *Intercept is off*) |
| "Your connection is not private" | Sertifikat Burp belum diinstal | Buka `http://burpsuite` -> Download & Import CA Certificate ke browser |
| Login Secure-v2 error padahal sandi benar | Lupa Import DB | Setiap pindah ke `secure-v2`, Anda WAJIB import ulang `schema.sql` dan `dummy_data.sql` |
| Mau coba Brute Force ulang, tapi masih diblokir 15 menit | Alamat IP Anda terekam di DB | Buka phpMyAdmin -> tabel `login_attempts` -> Hapus (Empty/Delete) isi tabelnya. |
| Internet mati setelah praktikum | Proxy browser masih nyala | Buka Settings Browser -> kembalikan pengaturan Proxy ke "No Proxy" / matikan manual proxy Windows. |

---

## 5. Ringkasan Kesimpulan

1. **SQL Injection** tidak bisa diandalkan hanya dengan memfilter karakter. Solusi mutlak adalah **Prepared Statement** yang memisahkan data dari perintah eksekusi database.
2. **Plaintext Password** adalah bom waktu. Standard industri mewajibkan penggunaan *one-way hashing* seperti **Bcrypt**.
3. **Validasi Password Saja Tidak Cukup**. Autentikasi harus dilindungi dari bot otomatis menggunakan **Rate Limiting** (pembatasan akses setelah sekian kali gagal).
