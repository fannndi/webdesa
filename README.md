# Web Desa - Laboratorium Keamanan Web

Project ini merupakan **Sistem Informasi Web Desa** yang dikembangkan khusus sebagai media pembelajaran keamanan web. Aplikasi ini sengaja dibuat dengan beberapa kerentanan keamanan agar mahasiswa dapat mempraktekkan eksploitasi dan mitigasi kerentanan secara langsung.

Project ini memiliki dua branch utama:
- `master` : Versi **rentan** (vulnerable) yang belum mengimplementasikan proteksi.
- `secure-v2` : Versi **aman** (secure) yang sudah memperbaiki kerentanan tersebut.

## 🚀 Fokus 3 Percobaan Keamanan

Aplikasi ini difokuskan pada 3 jenis serangan dan mitigasi keamanan utama:

### 1. SQL Injection (SQLi)
- **Apa itu**: SQL Injection adalah teknik injeksi kode (code injection) yang memanfaatkan celah keamanan pada lapisan database aplikasi. Penyerang dapat memasukkan query SQL berbahaya melalui input pengguna.
- **Mengapa berbahaya**: Penyerang dapat membobol sistem login (bypass authentication), mencuri seluruh data warga, menghapus database, hingga mengambil alih server.
- **Cara mencegah**: Cara paling efektif adalah menggunakan **Prepared Statement** (atau Parameterized Queries) di mana struktur query dan data input dipisahkan secara ketat sehingga input pengguna tidak akan pernah dieksekusi sebagai perintah SQL.
- **Implementasi pada project**: Pada branch `secure-v2`, seluruh query di fitur Login, Pencarian Warga, dan Detail Berita telah diubah menggunakan `Prepared Statement` pada ekstensi `mysqli`.

### 2. Password Security (Kriptografi Hash)
- **Apa itu bcrypt**: *Bcrypt* adalah fungsi hash password yang dirancang khusus agar lambat dikomputasi (*key stretching*) dan secara otomatis menggunakan *salt* acak untuk setiap password, membuatnya sangat tahan terhadap serangan *Brute Force* dan *Rainbow Table*.
- **Mengapa password tidak boleh plaintext**: Jika database bocor, password *plaintext* (teks biasa) akan langsung diketahui oleh peretas dan berisiko digunakan untuk mengambil alih akun pengguna di platform lain.
- **Bagaimana project menerapkannya**: Pada versi rentan, password admin disimpan dalam bentuk teks biasa. Pada versi `secure-v2`, fungsi bawaan PHP `password_hash($password, PASSWORD_BCRYPT)` digunakan saat registrasi/tambah admin, dan `password_verify()` digunakan saat login.

### 3. Brute Force Protection (Rate Limiting)
- **Apa itu Brute Force**: Serangan *Brute Force* adalah upaya menebak username dan password secara berulang kali secara otomatis dan masif hingga menemukan kombinasi yang tepat.
- **Bagaimana Rate Limiting bekerja**: *Rate limiting* membatasi jumlah percobaan (request) dalam jendela waktu tertentu dari sebuah IP.
- **Bagaimana implementasinya pada project**: Sistem menyimpan riwayat IP dan waktu login yang gagal ke tabel `login_attempts`. Jika IP yang sama gagal login lebih dari 5 kali, maka IP tersebut akan diblokir selama 15 menit. Log percobaan ini juga dapat dipantau di Dashboard Admin.

---

## 🛠 Instalasi dan Persiapan

1. **Persyaratan Sistem**:
   - PHP (Versi 7.4 atau 8.x)
   - MySQL / MariaDB
   - Web Server (Apache/Nginx) atau XAMPP/Laragon

2. **Langkah Instalasi**:
   - Clone repository ini ke direktori web server Anda (`htdocs` atau `/var/www/html/`).
     ```bash
     git clone https://github.com/fannndi/webdesa.git
     ```
   - Buat database baru di MySQL dengan nama `webdesa`.
   - Import file `database/schema.sql` dilanjutkan dengan `database/dummy_data.sql` ke dalam database `webdesa`.
   - Akses melalui browser: `http://localhost/webdesa`.

## 🗄 Database

- **Tabel `users`**: Menyimpan data admin/petugas.
- **Tabel `warga`**: Menyimpan data penduduk.
- **Tabel `berita`**: Menyimpan publikasi desa.
- **Tabel `surat_pengajuan`**: Menyimpan data layanan persuratan.
- **Tabel `login_attempts`**: (Khusus secure-v2) Menyimpan catatan login gagal.

## 🔐 Akun Default

Gunakan akun berikut untuk masuk ke panel admin (`/admin/login.php`):

| Username | Password | Role |
|----------|----------|------|
| `admin`  | `admin123` | Admin |
| `petugas1` | `petugas123` | Petugas |

## 📁 Struktur Folder

- `/admin` - Halaman khusus panel admin (Login, Dashboard, Manajemen Data)
- `/assets` - File CSS, JS, dan Gambar (termasuk kustomisasi tema Biru Pemerintah)
- `/config` - Konfigurasi database dan konstanta aplikasi
- `/database` - File SQL untuk setup struktur dan data awal
- `/includes` - File parsial penyusun UI (Header, Footer)
- `index.php` - Halaman utama (Publik)

---
*Dikembangkan sebagai sarana edukasi keamanan informasi.*
