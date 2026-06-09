# PRD — Sistem Informasi Desa: SQL Injection Research Lab
**Versi:** 2.0.0
**Stack:** PHP 8 Native · MySQL/MariaDB · Bootstrap 5 · mysqli
**Environment:** Localhost (XAMPP/Laragon/manual LAMP)
**Tujuan:** Objek pengujian SQL Injection untuk Tugas Akhir Keamanan Sistem Informasi

---

## 1. Ringkasan Proyek

Bangun aplikasi web **Sistem Informasi Desa** berbasis PHP Native + MySQL yang berfungsi sebagai **target pengujian SQL Injection** di lingkungan localhost yang terisolasi.

Aplikasi ini mensimulasikan sistem informasi desa nyata dengan data warga, fitur pengajuan surat, dan panel admin — seluruhnya diimplementasikan **tanpa proteksi keamanan** sehingga dapat dieksploitasi dan dianalisis menggunakan Burp Suite.

**Batasan penting:**
- Hanya dijalankan di **localhost yang terisolasi** — tidak di-deploy ke publik.
- Semua data warga adalah **fiktif/dummy** — tidak menggunakan data penduduk asli.
- Tidak ada fitur serangan otomatis atau payload hardcoded di dalam kode.

---

## 2. Struktur Folder

```
webdesa/
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
│   └── constants.php
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
│   ├── schema.sql
│   └── dummy_data.sql
│
└── README.md
```

---

## 3. Database Schema

### 3.1 Tabel: `users`
```sql
CREATE TABLE users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(50)  NOT NULL UNIQUE,
  password     VARCHAR(100) NOT NULL,  -- plaintext untuk demo
  nama_lengkap VARCHAR(100) NOT NULL,
  role         ENUM('admin','petugas') DEFAULT 'petugas',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
> Seed: `admin` / `admin123`, `petugas1` / `petugas123` — disimpan plaintext.

### 3.2 Tabel: `warga`
```sql
CREATE TABLE warga (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  nik               CHAR(16)     NOT NULL UNIQUE,
  nama              VARCHAR(100) NOT NULL,
  tempat_lahir      VARCHAR(100) NOT NULL,
  tanggal_lahir     DATE         NOT NULL,
  jenis_kelamin     ENUM('L','P') NOT NULL,
  alamat            VARCHAR(255) NOT NULL,
  rt                VARCHAR(5)   NOT NULL,
  rw                VARCHAR(5)   NOT NULL,
  dusun             VARCHAR(100) NOT NULL,
  pekerjaan         VARCHAR(100) NOT NULL,
  status_perkawinan ENUM('Belum Kawin','Kawin','Cerai Hidup','Cerai Mati') NOT NULL,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
> Seed: **150 data dummy warga fiktif.** NIK format `3273` + 12 digit. Nama Indonesia umum. Dusun: Sukamaju, Suka Damai, Cirendeu, Mekarjaya, Pasirsari.

### 3.3 Tabel: `surat_pengajuan`
```sql
CREATE TABLE surat_pengajuan (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  warga_id        INT  NOT NULL,
  jenis_surat     ENUM('domisili','usaha','tidak_mampu','pengantar_nikah') NOT NULL,
  keperluan       TEXT NOT NULL,
  nama_usaha      VARCHAR(150) NULL,
  alamat_usaha    VARCHAR(255) NULL,
  nama_pasangan   VARCHAR(100) NULL,
  status          ENUM('menunggu','diproses','selesai','ditolak') DEFAULT 'menunggu',
  catatan_admin   TEXT NULL,
  tanggal_ajuan   DATETIME DEFAULT CURRENT_TIMESTAMP,
  tanggal_selesai DATETIME NULL,
  diproses_oleh   INT NULL,
  FOREIGN KEY (warga_id) REFERENCES warga(id),
  FOREIGN KEY (diproses_oleh) REFERENCES users(id)
);
```

### 3.4 Tabel: `berita`
```sql
CREATE TABLE berita (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  judul       VARCHAR(255) NOT NULL,
  isi         TEXT         NOT NULL,
  penulis     VARCHAR(100) NOT NULL,
  diterbitkan TINYINT(1)   DEFAULT 0,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
);
```
> Seed: 8 artikel berita dummy.

---

## 4. Implementasi Query — Seluruh Aplikasi

Seluruh interaksi database menggunakan **raw query mysqli dengan string concatenation** dari input user. Tidak ada prepared statement, tidak ada sanitasi, tidak ada validasi format.

Pola yang digunakan di semua file:
```php
$input = $_POST['field'];  // atau $_GET['field']
$sql   = "SELECT * FROM tabel WHERE kolom = '$input'";
$result = mysqli_query($conn, $sql);
```

Error MySQL **ditampilkan langsung ke layar** — tidak di-suppress:
```php
if (!$result) {
    echo "Query Error: " . mysqli_error($conn);
}
```

---

## 5. `config/database.php`

```php
<?php
$conn = mysqli_connect('localhost', 'root', '', 'webdesa');
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
```

---

## 6. `config/constants.php`

```php
<?php
define('DESA_NAMA',       'Desa Sukamaju');
define('DESA_KECAMATAN',  'Kecamatan Cikaret');
define('DESA_KABUPATEN',  'Kabupaten Bogor');
define('DESA_PROVINSI',   'Jawa Barat');
define('KEPALA_DESA',     'H. Suparman, S.Sos');
define('BASE_URL',        'http://localhost/webdesa/');
```

---

## 7. Halaman Publik

### 7.1 `index.php` — Beranda

Layout: Navbar → Hero Section → Statistik → Berita Terbaru → Footer

- Nama desa dari `constants.php`
- Sambutan singkat kepala desa (teks statis)
- 4 kartu statistik dari DB: Total Penduduk, Jumlah KK (estimasi warga/4), Total Pengajuan, Berita Tayang
- Grid 3 berita terbaru (WHERE diterbitkan = 1, ORDER BY created_at DESC LIMIT 3)
- Tombol CTA: "Cek NIK Saya" → `cek_warga.php`, "Ajukan Surat" → `ajukan_surat.php`

### 7.2 `profile.php` — Profil Desa

Konten statis:
- Sejarah singkat desa
- Visi dan misi
- Tabel struktur organisasi: Kepala Desa, Sekretaris, 3 Kaur, 5 Kadus

### 7.3 `berita.php` — Daftar Berita

- Daftar berita (WHERE diterbitkan = 1) dengan pagination 6 per halaman
- Klik → `berita_detail.php?id=X` (parameter `id` raw, tidak difilter)

### 7.4 `berita_detail.php`

```php
$id  = $_GET['id'];
$sql = "SELECT * FROM berita WHERE id = '$id'";
```
- Tampil judul, tanggal, penulis, isi berita
- Output dari DB tidak di-escape

---

## 8. Modul Cek Warga

### `cek_warga.php`

**Method:** POST
**Input form:** `nik` (text input)

```php
$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Error: " . mysqli_error($conn);  // error ditampilkan ke user
}
```

**Output jika ditemukan:** nama, tempat/tanggal lahir, alamat, rt/rw, dusun, pekerjaan, status perkawinan — semua ditampilkan tanpa htmlspecialchars.

**Output jika tidak ditemukan:** pesan "NIK tidak terdaftar dalam database."

**Catatan untuk pengujian:** Ini adalah titik injeksi utama. Parameter `nik` dikirim via POST, dapat diintersep via Burp Suite Repeater/Intruder.

---

## 9. Modul Pengajuan Surat

### `ajukan_surat.php`

**Alur 3 step dalam satu file (kontrol via `$_POST['step']`):**

**Step 1 — Cek NIK:**
```php
$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
// Jika ditemukan, tampilkan data warga dan form step 2
// warga_id diteruskan via hidden input
```

**Step 2 — Pilih Jenis Surat:**
- Dropdown: Domisili / Usaha / Tidak Mampu / Pengantar Nikah
- Field kondisional (JS show/hide):
  - Domisili: `keperluan`
  - Usaha: `keperluan`, `nama_usaha`, `alamat_usaha`
  - Tidak Mampu: `keperluan`
  - Pengantar Nikah: `keperluan`, `nama_pasangan`

**Step 3 — Simpan:**
```php
$warga_id    = $_POST['warga_id'];
$jenis_surat = $_POST['jenis_surat'];
$keperluan   = $_POST['keperluan'];
$nama_usaha  = $_POST['nama_usaha'];
$alamat_usaha = $_POST['alamat_usaha'];
$nama_pasangan = $_POST['nama_pasangan'];

$sql = "INSERT INTO surat_pengajuan
        (warga_id, jenis_surat, keperluan, nama_usaha, alamat_usaha, nama_pasangan)
        VALUES ('$warga_id','$jenis_surat','$keperluan','$nama_usaha','$alamat_usaha','$nama_pasangan')";
mysqli_query($conn, $sql);
```

Konfirmasi sukses: "Pengajuan surat berhasil dikirim."

### `riwayat.php`

- Input form: NIK
- Query riwayat pengajuan berdasarkan NIK
```php
$nik = $_POST['nik'];
$sql = "SELECT sp.*, w.nama FROM surat_pengajuan sp
        JOIN warga w ON sp.warga_id = w.id
        WHERE w.nik = '$nik'
        ORDER BY sp.tanggal_ajuan DESC";
```
- Tampil tabel: jenis surat, keperluan, status, tanggal

---

## 10. Panel Admin

### 10.1 `admin/login.php`

**Method:** POST, input: `username`, `password`

```php
$username = $_POST['username'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    header("Location: dashboard.php");
} else {
    $error = "Username atau password salah.";
}
```

Session check di semua halaman admin:
```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
```

### 10.2 `admin/dashboard.php`

- Kartu statistik: Total warga, pengajuan menunggu, pengajuan selesai bulan ini, total berita
- Tabel 10 pengajuan terbaru (raw query)

### 10.3 `admin/warga.php` — CRUD Warga

**Baca:**
```php
// Search via GET parameter — titik injeksi
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM warga WHERE nama LIKE '%$search%' OR nik LIKE '%$search%'";
```
Tabel hasil dengan kolom: NIK, Nama, Dusun, RT/RW, Pekerjaan, Aksi (Edit/Hapus)

**Tambah** (modal form → POST):
```php
$sql = "INSERT INTO warga (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin,
        alamat, rt, rw, dusun, pekerjaan, status_perkawinan)
        VALUES ('$nik','$nama','$tempat_lahir','$tanggal_lahir','$jenis_kelamin',
        '$alamat','$rt','$rw','$dusun','$pekerjaan','$status_perkawinan')";
```

**Edit** (modal form pre-filled → POST):
```php
$id  = $_POST['id'];
$sql = "UPDATE warga SET nama='$nama', alamat='$alamat', ... WHERE id='$id'";
```

**Hapus:**
```php
$id  = $_GET['hapus'];
$sql = "DELETE FROM warga WHERE id='$id'";
```

### 10.4 `admin/surat.php` — Kelola Pengajuan

- Tabel semua pengajuan dengan JOIN ke `warga`
- Filter status via GET: `?status=menunggu` (raw, tidak divalidasi)
- Ubah status via POST (raw UPDATE)
- Field catatan admin

### 10.5 `admin/berita.php` — Kelola Berita

- Tabel berita: judul, penulis, status terbit, tanggal
- Tambah/Edit (form POST → raw INSERT/UPDATE)
- Hapus (GET parameter `?hapus=id`)
- Toggle publish via GET: `?toggle=id`

---

## 11. UI & Desain

- **Framework:** Bootstrap 5 via CDN
- **Warna utama:** Hijau `#198754` (tema pemerintahan desa)

**Navbar publik:** Logo kiri + nama desa, menu: Beranda / Profil / Berita / Cek NIK / Ajukan Surat

**Hero section:** Background gradient hijau, judul "Selamat Datang di [DESA_NAMA]", tagline, 2 tombol CTA

**Footer:** 3 kolom — info desa, navigasi cepat, kontak desa

**Admin sidebar:** Lebar 250px, background `#2c3e50`, menu dengan icon Bootstrap Icons:
- Dashboard, Data Warga, Pengajuan Surat, Berita, Logout

**Output DB:** Semua output dari database ditampilkan langsung tanpa `htmlspecialchars()` — ini disengaja agar stored XSS juga dapat didemonstrasikan jika diperlukan.

---

## 12. `database/dummy_data.sql`

**Spesifikasi:**
- **150 data warga fiktif**, variasi jenis kelamin 50/50
- NIK: `3273` + 12 digit unik per baris
- Nama: gabungan nama depan dan belakang Indonesia umum
- Dusun: acak dari Sukamaju, Suka Damai, Cirendeu, Mekarjaya, Pasirsari
- RT/RW: `001`–`010` / `001`–`005`
- Pekerjaan: Petani, Wiraswasta, PNS, Buruh, Pedagang, Ibu Rumah Tangga, Pelajar, Nelayan
- Status perkawinan: mayoritas Kawin, sebagian Belum Kawin
- **10 data pengajuan surat** dummy dengan status campuran (menunggu, diproses, selesai)
- **2 akun users:** admin/admin123 dan petugas1/petugas123 (plaintext)
- **8 artikel berita** dummy dengan diterbitkan = 1

---

## 13. `README.md`

```markdown
## Instalasi
1. Copy folder webdesa ke htdocs/ (XAMPP) atau www/ (Laragon)
2. Buat database: CREATE DATABASE webdesa;
3. Import schema: mysql -u root webdesa < database/schema.sql
4. Import data:   mysql -u root webdesa < database/dummy_data.sql
5. Akses: http://localhost/webdesa/

## Akun Default
| Role    | Username | Password   | URL Login           |
|---------|----------|------------|---------------------|
| Admin   | admin    | admin123   | /admin/login.php    |
| Petugas | petugas1 | petugas123 | /admin/login.php    |

## Titik-Titik Injeksi

| File                    | Parameter | Method | Keterangan                         |
|-------------------------|-----------|--------|------------------------------------|
| cek_warga.php           | nik       | POST   | Pencarian warga berdasarkan NIK    |
| ajukan_surat.php        | nik       | POST   | Step 1 verifikasi warga            |
| riwayat.php             | nik       | POST   | Riwayat pengajuan warga            |
| admin/login.php         | username  | POST   | Login bypass                       |
| admin/login.php         | password  | POST   | Login bypass                       |
| admin/warga.php         | q         | GET    | Search warga                       |
| admin/surat.php         | status    | GET    | Filter pengajuan                   |
| berita_detail.php       | id        | GET    | Detail berita                      |

## Pengujian dengan Burp Suite
1. Set Burp Suite sebagai proxy (127.0.0.1:8080)
2. Gunakan browser yang sudah dikonfigurasi ke proxy Burp
3. Kirim request ke salah satu titik injeksi di atas
4. Intersep via Proxy tab, kirim ke Repeater
5. Modifikasi parameter dan amati respons
```

---

## 14. Checklist Output Final

### Struktur & Konfigurasi
- [ ] Semua file dan folder sesuai struktur di Bagian 2
- [ ] `config/database.php` menggunakan mysqli raw
- [ ] `config/constants.php` berisi data desa

### Database
- [ ] `schema.sql` berisi 4 tabel dengan FK yang benar
- [ ] `dummy_data.sql` berisi ≥150 warga fiktif
- [ ] Seed: 2 users (plaintext), 8 berita, 10 pengajuan

### Halaman Publik
- [ ] `index.php` statistik real dari DB
- [ ] `berita.php` + `berita_detail.php` berfungsi (parameter `id` tidak difilter)
- [ ] `cek_warga.php` raw query, error ditampilkan ke layar
- [ ] `ajukan_surat.php` 3-step, semua INSERT raw
- [ ] `riwayat.php` raw query by NIK

### Admin Panel
- [ ] `admin/login.php` raw query + password plaintext
- [ ] `admin/warga.php` CRUD raw + search parameter GET tidak difilter
- [ ] `admin/surat.php` raw query + filter via GET
- [ ] `admin/berita.php` CRUD raw
- [ ] Session check sederhana (tanpa regenerate) di semua halaman admin

### UI
- [ ] Bootstrap 5 konsisten di semua halaman
- [ ] Navbar publik responsif
- [ ] Sidebar admin fungsional
- [ ] Semua output DB ditampilkan tanpa escaping

### Dokumentasi
- [ ] `README.md` lengkap dengan tabel titik injeksi

---

*PRD ini adalah spesifikasi tunggal dan self-contained. Tidak diperlukan dokumen tambahan untuk membangun proyek.*
