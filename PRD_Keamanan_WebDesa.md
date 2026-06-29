# Panduan Praktikum Keamanan Web Desa

Dokumen ini adalah panduan lengkap langkah demi langkah (step-by-step) untuk mempraktekkan **3 percobaan keamanan utama**. Panduan ini dirancang agar mudah dipahami oleh pemula.

Aplikasi ini memiliki dua versi (berada di *branch* Git yang berbeda):
- **Branch `master`**: Versi Rentan (Belum aman, sengaja dibuat untuk simulasi diserang).
- **Branch `secure-v2`**: Versi Aman (Sudah diperbaiki dan kebal dari serangan).

---

## 🛠️ Persiapan Awal (Wajib Dilakukan)
Sebelum memulai percobaan, ikuti langkah persiapan berikut:

1. Buka aplikasi **XAMPP / Laragon** dan aktifkan Apache serta MySQL.
2. Buka browser dan masuk ke **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Buat database baru dengan nama: `webdesa`.
4. Pilih menu **Import**, lalu masukkan file `database/schema.sql` dilanjutkan dengan file `database/dummy_data.sql` yang ada di folder project ini.
5. Akses aplikasi melalui browser di alamat: `http://localhost/webdesa`.

*(Catatan: Setiap kali Anda berpindah versi / branch, sangat disarankan untuk melakukan import ulang database agar strukturnknya sesuai dengan versi yang sedang diuji).*

---

## 🧪 Percobaan 1: SQL Injection (Membobol Login)

**Apa itu SQL Injection?** 
Serangan di mana *hacker* memasukkan perintah sistem (SQL) ke dalam kolom input (seperti form login) untuk mengelabui aplikasi agar memberikan akses tanpa password yang benar.

### A. Skenario Menyerang (Gunakan Branch `master`)
1. Pastikan Anda berada di branch `master` (`git checkout master`).
2. Buka halaman login admin: `http://localhost/webdesa/admin/login.php`
3. Pada kolom **Username**, ketikkan kode ajaib berikut:
   `admin' OR '1'='1`
4. Pada kolom **Password**, ketikkan huruf asal-asalan (misal: `bebas123`).
5. Klik tombol **Login**.
6. **Hasil:** Anda akan langsung berhasil masuk ke Dashboard Admin! Sistem tertipu oleh kode `'1'='1'` yang berarti "Benar", sehingga mengizinkan Anda masuk.

### B. Skenario Pembuktian Perbaikan (Gunakan Branch `secure-v2`)
1. Pindah ke branch aman: `git checkout secure-v2`.
2. Buka halaman login admin yang sama.
3. Masukkan kode ajaib yang sama di Username: `admin' OR '1'='1`
4. Masukkan password sembarangan dan klik **Login**.
5. **Hasil:** Login **Gagal** dan muncul tulisan "Username atau password salah". Sistem yang baru menggunakan fitur *Prepared Statement* sehingga kode peretas hanya dianggap sebagai teks biasa, bukan sebagai perintah pembobolan.

---

## 🧪 Percobaan 2: Keamanan Password (Melihat Isi Database)

**Mengapa ini penting?**
Jika sistem database sebuah website bocor atau diretas, data penting seperti password tidak boleh bisa dibaca secara langsung oleh siapapun.

### A. Skenario Membaca Password Terbuka (Gunakan Branch `master`)
1. Buka **phpMyAdmin** dan pastikan Anda menggunakan database dari branch `master`.
2. Klik tabel bernama `users`.
3. Perhatikan kolom `password`.
4. **Hasil:** Anda bisa melihat dengan jelas bahwa password admin adalah `admin123`. Ini sangat berbahaya! Jika hacker mencuri data ini, mereka bisa mencoba password yang sama di akun media sosial korban.

### B. Skenario Password Terenkripsi (Gunakan Branch `secure-v2`)
1. Ganti database Anda dengan import file SQL dari branch `secure-v2`.
2. Buka tabel `users` di phpMyAdmin.
3. Perhatikan kolom `password` milik pengguna.
4. **Hasil:** Password tidak lagi terbaca. Isinya berubah menjadi sandi acak yang panjang (contoh: `$2y$12$c73y1dfyUZ...`). Ini menggunakan teknologi kriptografi bernama **Bcrypt**, sehingga meski database dicuri, peretas tidak tahu apa password aslinya.
5. **Pengujian Ekstra:** Anda bisa mencoba login dengan akun `admin` (password: `admin123`), lalu ke menu **Manajemen Pengguna** untuk mencoba membuat akun baru. Cek kembali phpMyAdmin, akun baru Anda akan otomatis terlindungi sandi acaknya.

---

## 🧪 Percobaan 3: Brute Force (Serangan Tebak Password)

**Apa itu Brute Force?**
Serangan di mana hacker menggunakan alat khusus untuk mencoba ribuan tebakan password dalam hitungan detik sampai tebakannya benar.

### A. Skenario Sistem Tak Berdaya (Gunakan Branch `master`)
1. Buka halaman login di branch `master`.
2. Masukkan username `admin`, lalu coba masukkan password yang salah berulang kali (klik Login terus menerus dengan cepat).
3. **Hasil:** Sistem terus memproses tanpa henti. Tidak ada peringatan, tidak ada pemblokiran. Hacker dapat menggunakan alat seperti *Burp Suite* untuk menebak jutaan kali tanpa hambatan.

### B. Skenario Sistem Bertahan (Gunakan Branch `secure-v2`)
1. Buka halaman login di branch `secure-v2` (Pastikan Anda sudah logout).
2. Masukkan username `admin` dan password asal-asalan.
3. Lakukan proses menekan tombol Login dengan password salah sebanyak **5 kali secara berturut-turut**.
4. **Hasil:** Pada percobaan ke-6, sistem akan menolak klik Anda dan memunculkan notifikasi merah: **"Terlalu banyak percobaan login. Coba lagi dalam 15 menit."** Sistem berhasil memblokir serangan Anda berkat mekanisme *Rate Limiting*.
5. **Cek Log:** Loginlah secara sukses di komputer lain (atau gunakan akun lain), masuk ke **Dashboard Admin**, lalu scroll ke bawah. Anda akan melihat log alamat IP Anda direkam dengan status **Terblokir 15 Menit**!
