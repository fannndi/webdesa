# Panduan Pengujian Keamanan Web Desa (Versi Rentan - Branch `master`)

Dokumen ini berisi panduan *step-by-step* untuk mempraktekkan (mendemonstrasikan) 3 kerentanan utama yang sengaja dibiarkan pada aplikasi versi rentan ini.

---

## Persiapan Awal
1. Pastikan Anda berada di branch `master`: `git checkout master`
2. Buka `phpMyAdmin` (atau tool database lain).
3. Buat database `webdesa`.
4. Import file `database/schema.sql` dan `database/dummy_data.sql` ke dalam database `webdesa`.
5. Buka browser dan akses `http://localhost/webdesa/admin/login.php`.

---

## 🧪 Skenario 1: Demonstrasi SQL Injection (Bypass Login)
Pada versi ini, query pengecekan login di `admin/login.php` menggunakan penggabungan string (concatenation) tanpa sanitasi:
`SELECT * FROM users WHERE username = '$username' AND password = '$password'`

### Langkah Pengujian:
1. Akses halaman login admin: `http://localhost/webdesa/admin/login.php`
2. Pada kolom **Username**, masukkan payload berikut:
   ```text
   admin' OR '1'='1
   ```
3. Pada kolom **Password**, masukkan karakter sembarang, misalnya:
   ```text
   bebas123
   ```
4. Klik tombol **Login**.
5. **Hasil yang Diharapkan:** Anda akan berhasil masuk ke Dashboard Admin tanpa mengetahui password asli admin. Hal ini karena payload memanipulasi query SQL menjadi:
   `SELECT * FROM users WHERE username = 'admin' OR '1'='1' AND password = '...'`
   Bagian `'1'='1'` selalu bernilai `TRUE` sehingga autentikasi berhasil di-bypass.

---

## 🧪 Skenario 2: Demonstrasi Insecure Password (Plaintext)
Pada versi ini, password disimpan langsung dalam bentuk *plaintext* tanpa di-hash.

### Langkah Pengujian:
1. Buka `phpMyAdmin` atau tool database Anda.
2. Buka database `webdesa` dan klik tabel `users`.
3. Lihat pada kolom `password`.
4. **Hasil yang Diharapkan:** Anda dapat membaca secara langsung password admin dan petugas, contohnya `admin123` dan `petugas123`. 
5. **Analisis Dampak:** Jika peretas berhasil mencuri isi database (misalnya melalui teknik SQL Injection - Union/Error based di pencarian berita/warga), mereka langsung mendapatkan password asli pengguna.

---

## 🧪 Skenario 3: Demonstrasi Brute Force (Tanpa Rate Limiting)
Sistem belum memiliki mekanisme pembatasan percobaan login, sehingga rentan terhadap serangan penembakan kata sandi secara massal (Brute Force).

### Langkah Pengujian (Manual):
1. Buka halaman login admin: `http://localhost/webdesa/admin/login.php`
2. Masukkan username `admin` dan password salah (misal: `salah1`). Klik Login.
3. Anda akan melihat pesan error.
4. Ulangi langkah di atas terus-menerus hingga puluhan kali.
5. **Hasil yang Diharapkan:** Sistem akan terus memproses permintaan tanpa memblokir IP Anda atau memberikan jeda waktu (time-out).

### Langkah Pengujian (Otomatis dengan Burp Suite):
1. Buka **Burp Suite** dan konfigurasikan proxy di browser.
2. Lakukan satu percobaan login gagal di web dan tangkap (intercept) request POST tersebut.
3. Kirim request tersebut ke menu **Intruder**.
4. Tandai (Highlight) value parameter `password` dan jadikan payload position (Klik `Add §`).
5. Pada tab **Payloads**, masukkan list password (contoh: `admin`, `password`, `123456`, `admin123`).
6. Klik **Start Attack**.
7. **Hasil yang Diharapkan:** Seluruh request akan diproses (HTTP 200). Request dengan password yang benar (`admin123`) akan menghasilkan respons berbeda (biasanya HTTP 302 Redirect ke dashboard). Ini membuktikan bahwa sistem tidak membatasi seberapa cepat atau seberapa banyak upaya login dilakukan.
