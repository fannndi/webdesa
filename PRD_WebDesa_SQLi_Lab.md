# Product Requirements Document (PRD)
## Sistem Informasi Web Desa - SQL Injection Research Lab (Versi Rentan)

### 1. Informasi Proyek
- **Nama Proyek**: Web Desa - SQLi Lab (Branch `master`)
- **Tujuan**: Menyediakan platform simulasi (laboratorium) bagi mahasiswa untuk memahami, mempraktekkan, dan mengeksploitasi kerentanan web, khususnya berfokus pada SQL Injection, Password Plaintext, dan Brute Force.
- **Target Pengguna**: Mahasiswa Keamanan Informasi.

### 2. Fokus Pembelajaran (3 Kerentanan Utama)
Aplikasi ini secara sengaja dirancang memiliki 3 kerentanan utama untuk tujuan edukasi:

#### A. SQL Injection (SQLi)
- **Deskripsi**: Sistem menerima input dari pengguna (seperti NIK atau kredensial login) dan menggabungkannya secara langsung (string concatenation) ke dalam query SQL tanpa sanitasi atau penggunaan Prepared Statement.
- **Titik Rentan**:
  - `admin/login.php` (Bypass otentikasi)
  - `cek_warga.php` (Ekstraksi data warga)
  - `berita_detail.php?id=` (Injeksi berbasis Union/Error)

#### B. Insecure Password Storage (Plaintext)
- **Deskripsi**: Sistem menyimpan password pengguna (admin/petugas) di database secara langsung tanpa melalui proses hashing kriptografi.
- **Titik Rentan**:
  - Tabel `users` pada kolom `password`.
  - Dampaknya, apabila database berhasil diretas melalui SQL Injection, penyerang langsung mengetahui password admin.

#### C. Tidak Ada Proteksi Brute Force
- **Deskripsi**: Sistem otentikasi (login) tidak menerapkan *rate limiting* atau pembatasan percobaan login.
- **Titik Rentan**:
  - `admin/login.php` dapat di-brute force menggunakan tool eksternal (misal: Burp Suite Intruder atau Hydra) untuk menebak password tanpa ada blokir IP atau waktu jeda.

### 3. Fungsionalitas Utama (Sistem Informasi)
Agar simulasi terasa nyata, aplikasi ini dilengkapi dengan fungsi-fungsi dasar layaknya web desa sungguhan:
1. **Beranda Publik**: Menampilkan statistik desa, berita, dan sambutan.
2. **Pengecekan NIK**: Memungkinkan warga mencari data mereka berdasarkan 16 digit NIK.
3. **Detail Berita**: Menampilkan isi berita berdasarkan ID.
4. **Panel Admin**:
   - Login admin.
   - Dashboard dengan statistik sederhana.
   - CRUD Data Warga, Berita, dan Pengajuan Surat.

### 4. Lingkungan dan Setup
- **Teknologi**: PHP Native (7.4/8.x), MySQL/MariaDB.
- **Desain UI**: Menggunakan Bootstrap 5 dengan tema Biru Pemerintah untuk tampilan modern dan profesional.
- **Database**: Skema dan data tiruan tersedia di `database/schema.sql` dan `database/dummy_data.sql`.
