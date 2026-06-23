# Code Analysis - Sistem Informasi Desa

## 1. Arsitektur Aplikasi

### Stack Teknologi
- **Backend:** PHP 8 Native (tanpa framework)
- **Database:** MySQL/MariaDB dengan mysqli
- **Frontend:** Bootstrap 5 via CDN
- **Koneksi DB:** Raw mysqli tanpa prepared statement

### Pola Kode yang Rentan

Seluruh aplikasi menggunakan pola berikut untuk query database:

```php
// INPUT: langsung dari user tanpa sanitasi
$input = $_POST['field'];  // atau $_GET['field']

// QUERY: string concatenation langsung
$sql = "SELECT * FROM tabel WHERE kolom = '$input'";

// EKSEKUSI: raw query
$result = mysqli_query($conn, $sql);

// ERROR: ditampilkan langsung ke user
if (!$result) {
    echo "Query Error: " . mysqli_error($conn);
}
```

### Mengapa Rentan?

1. **No Prepared Statements** - Input langsung dikonkat ke query
2. **No Input Validation** - Tidak ada pengecekan format/type
3. **No Sanitization** - Tidak ada `mysqli_real_escape_string()`
4. **Error Disclosure** - Error MySQL ditampilkan ke user
5. **No htmlspecialchars()** - Output dari DB langsung ditampilkan (stored XSS risk)

---

## 2. Alur Aplikasi

### Alur Publik
```
index.php (Beranda)
    ├── profile.php (Profil Desa - statis)
    ├── berita.php (Daftar Berita)
    │   └── berita_detail.php?id=X (SQLi via GET)
    ├── cek_warga.php (POST: nik) (SQLi via POST)
    ├── ajukan_surat.php (POST: nik, step) (SQLi via POST)
    └── riwayat.php (POST: nik) (SQLi via POST)
```

### Alur Admin
```
admin/login.php (POST: username, password) (SQLi via POST)
    ├── admin/dashboard.php (Read only)
    ├── admin/warga.php?q=X (SQLi via GET - Search)
    │   ├── POST: tambah warga (INSERT raw)
    │   ├── POST: edit warga (UPDATE raw)
    │   └── GET: hapus warga (DELETE raw)
    ├── admin/surat.php?status=X (SQLi via GET - Filter)
    │   └── POST: update status (UPDATE raw)
    └── admin/berita.php
        ├── POST: tambah berita (INSERT raw)
        ├── POST: edit berita (UPDATE raw)
        ├── GET: hapus berita (DELETE raw)
        └── GET: toggle publish (UPDATE raw)
```

---

## 3. Database Schema

### Tabel Users
```sql
users (
    id INT PK AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(100),  -- PLAINTEXT!
    nama_lengkap VARCHAR(100),
    role ENUM('admin','petugas'),
    created_at DATETIME
)
```
**Seed:** admin/admin123, petugas1/petugas123

### Tabel Warga
```sql
warga (
    id INT PK AUTO_INCREMENT,
    nik CHAR(16) UNIQUE,
    nama VARCHAR(100),
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L','P'),
    alamat VARCHAR(255),
    rt VARCHAR(5),
    rw VARCHAR(5),
    dusun VARCHAR(100),
    pekerjaan VARCHAR(100),
    status_perkawinan ENUM(...),
    created_at DATETIME
)
```
**Seed:** 150 data dummy, NIK format 3273 + 12 digit

### Tabel Surat Pengajuan
```sql
surat_pengajuan (
    id INT PK AUTO_INCREMENT,
    warga_id INT FK->warga(id),
    jenis_surat ENUM('domisili','usaha','tidak_mampu','pengantar_nikah'),
    keperluan TEXT,
    nama_usaha VARCHAR(150),
    alamat_usaha VARCHAR(255),
    nama_pasangan VARCHAR(100),
    status ENUM('menunggu','diproses','selesai','ditolak'),
    catatan_admin TEXT,
    tanggal_ajuan DATETIME,
    tanggal_selesai DATETIME,
    diproses_oleh INT FK->users(id)
)
```

### Tabel Berita
```sql
berita (
    id INT PK AUTO_INCREMENT,
    judul VARCHAR(255),
    isi TEXT,
    penulis VARCHAR(100),
    diterbitkan TINYINT(1),
    created_at DATETIME
)
```

---

## 4. Session Management

Session handling sangat sederhana:

```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
```

**Kelemahan:**
- Tidak ada session regeneration
- Tidak ada CSRF token
- Session ID tidak di-invalidate setelah login
- Tidak ada rate limiting login

---

## 5. File yang Berisi Kerentanan

| File | Line | Query Pattern | Parameter |
|------|------|---------------|-----------|
| cek_warga.php | ~15 | SELECT * FROM warga WHERE nik = '$nik' | POST nik |
| ajukan_surat.php | ~15 | SELECT * FROM warga WHERE nik = '$nik' | POST nik |
| ajukan_surat.php | ~45 | INSERT INTO surat_pengajuan VALUES(...) | Multiple POST |
| riwayat.php | ~15 | SELECT ... WHERE w.nik = '$nik' | POST nik |
| berita_detail.php | ~4 | SELECT * FROM berita WHERE id = '$id' | GET id |
| admin/login.php | ~8 | SELECT * FROM users WHERE username='$u' AND password='$p' | POST |
| admin/warga.php | ~30 | SELECT * FROM warga WHERE nama LIKE '%$search%' | GET q |
| admin/warga.php | ~50 | DELETE FROM warga WHERE id='$id' | GET hapus |
| admin/warga.php | ~60 | INSERT INTO warga VALUES(...) | Multiple POST |
| admin/warga.php | ~75 | UPDATE warga SET ... WHERE id='$id' | POST |
| admin/surat.php | ~15 | SELECT ... WHERE sp.status = '$status' | GET status |
| admin/surat.php | ~30 | UPDATE surat_pengajuan SET ... WHERE id='$id' | POST |
| admin/berita.php | ~15 | DELETE FROM berita WHERE id='$id' | GET hapus |
| admin/berita.php | ~20 | UPDATE berita SET diterbitkan = NOT ... WHERE id='$id' | GET toggle |
| admin/berita.php | ~30 | INSERT INTO berita VALUES(...) | Multiple POST |
| admin/berita.php | ~45 | UPDATE berita SET ... WHERE id='$id' | POST |
| berita_detail.php | ~37 | SELECT ... WHERE id != '$id' | GET id (sidebar) |
