# Panduan Pengujian Keamanan Web Desa (Versi Aman - Branch `secure-v2`)

Dokumen ini berisi panduan *step-by-step* untuk mempraktekkan pengujian pada 3 mitigasi keamanan utama yang telah diterapkan di versi ini. Anda dapat mengujinya untuk membuktikan bahwa celah pada versi rentan telah berhasil ditutup.

---

## Persiapan Awal
1. Pastikan Anda berada di branch `secure-v2`: `git checkout secure-v2`
2. Buka `phpMyAdmin` (atau tool database lain).
3. Buat database `webdesa` (timpa yang sebelumnya jika ada).
4. Import file `database/schema.sql` dan `database/dummy_data.sql` ke dalam database `webdesa`. **Penting**: Struktur tabel versi aman ini berbeda (mengandung tabel `login_attempts` dan ukuran kolom `password` 255 karakter).
5. Buka browser dan akses `http://localhost/webdesa/admin/login.php`.

---

## 🛡️ Skenario 1: Pengujian SQL Injection Prevention (Prepared Statement)
Pada versi ini, seluruh query telah diamankan menggunakan metode *Prepared Statement* sehingga input pengguna diperlakukan secara ketat sebagai data (string), bukan sebagai eksekusi kode SQL.

### Langkah Pengujian:
1. Akses halaman login admin: `http://localhost/webdesa/admin/login.php`
2. Pada kolom **Username**, masukkan payload yang sebelumnya berhasil:
   ```text
   admin' OR '1'='1
   ```
3. Pada kolom **Password**, masukkan:
   ```text
   bebas123
   ```
4. Klik tombol **Login**.
5. **Hasil yang Diharapkan:** Login **GAGAL** dengan pesan error *"Username atau password salah"*. Sistem membaca username secara literal sebagai teks `admin' OR '1'='1`, bukan sebagai bagian dari instruksi SQL, sehingga data tidak ditemukan di database.

---

## 🛡️ Skenario 2: Pengujian Password Security (Bcrypt Hash)
Pada versi ini, database tidak lagi menyimpan *plaintext*. Password di-hash menggunakan fungsi `password_hash()` bawaan PHP yang mengadopsi algoritma Bcrypt (termasuk *salt* otomatis).

### Langkah Pengujian:
1. Buka `phpMyAdmin` dan lihat tabel `users`.
2. Perhatikan kolom `password`. Isinya kini berbentuk string acak sepanjang sekitar 60 karakter, contoh:
   `$2y$12$c73y1dfyUZvvaDof9x66.aVarJfw.WL6ymowXbazOKTxaRVPxhWu`
3. Hal ini membuktikan bahwa jika terjadi kebocoran database, password pengguna tetap aman dan sangat sulit di-*crack*.
4. **Demonstrasi Manajemen Pengguna:**
   - Login dengan akun `admin` (password: `admin123`).
   - Masuk ke menu **Manajemen Pengguna** di sidebar kiri.
   - Klik **Tambah Pengguna** dan buat admin baru.
   - Lihat pada tabel yang disajikan, password yang baru dibuat otomatis tersimpan dalam bentuk Bcrypt hash.
   - Anda juga dapat menggunakan fitur **Ganti Password** untuk melihat fungsi hash bekerja memperbarui password lama.

---

## 🛡️ Skenario 3: Pengujian Brute Force Protection (Rate Limiting)
Sistem sekarang mencatat *IP Address* dan membatasi percobaan login yang salah.

### Langkah Pengujian:
1. Buka halaman login admin (pastikan Anda sudah *logout* jika sebelumnya masuk).
2. Masukkan username `admin` dan password salah (misal: `salah1`). Klik Login.
3. Anda akan melihat peringatan *"Username atau password salah"*.
4. **Ulangi terus menerus sebanyak 5 kali** dengan password yang salah.
5. Pada percobaan ke-6, **Hasil yang Diharapkan:** Sistem akan memblokir Anda dan menampilkan pesan error merah:
   *"Terlalu banyak percobaan login. Coba lagi dalam 15 menit."*
6. **Melihat Log Keamanan:**
   - Login sukses menggunakan akun/browser/perangkat lain (atau hapus record di phpMyAdmin tabel `login_attempts` untuk membuka blokir sementara agar Anda bisa masuk).
   - Setelah masuk ke Dashboard Admin, *scroll* ke bagian bawah.
   - Anda akan melihat **Log Aktivitas Keamanan (Brute Force Attempts)** yang mencatat IP Address penyerang, jumlah kegagalan, dan status *Terblokir 15 Menit*.
