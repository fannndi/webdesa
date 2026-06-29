# Product Requirements Document (PRD)
## Sistem Informasi Web Desa - SQL Injection Research Lab (Versi Aman)

### 1. Informasi Proyek
- **Nama Proyek**: Web Desa - Secure Version (Branch `secure-v2`)
- **Tujuan**: Menjadi referensi kode yang aman (secure code) sebagai hasil mitigasi dari kerentanan yang ada pada versi rentan (branch `master`).
- **Target Pengguna**: Mahasiswa Keamanan Informasi (sebagai referensi perbaikan).

### 2. Fokus Perbaikan Keamanan (3 Mitigasi Utama)
Aplikasi ini telah diperbaiki secara khusus pada 3 aspek keamanan berikut:

#### A. SQL Injection Prevention
- **Mitigasi**: Mengganti semua eksekusi query SQL yang menggunakan penggabungan string (concatenation) dengan metode **Prepared Statement**.
- **Implementasi**:
  - Dibuat fungsi helper `db_query()` di `config/security.php` yang membungkus fungsi `mysqli_prepare` dan `mysqli_stmt_bind_param`.
  - Halaman `admin/login.php`, `cek_warga.php`, dan `berita_detail.php` kini memisahkan secara ketat antara logika SQL dan data input pengguna.

#### B. Password Security (Bcrypt Hash)
- **Mitigasi**: Menggunakan fungsi enkripsi searah (hash) yang dirancang khusus untuk password dengan algoritma **Bcrypt**, menggantikan penyimpanan plaintext.
- **Implementasi**:
  - Tipe data kolom `password` pada tabel `users` diubah menjadi `VARCHAR(255)`.
  - Registrasi dan perubahan password (fitur di `admin/users.php`) menggunakan fungsi `password_hash($password, PASSWORD_BCRYPT)`.
  - Otentikasi pada `admin/login.php` menggunakan fungsi `password_verify()`.

#### C. Brute Force Protection (Rate Limiting)
- **Mitigasi**: Menerapkan mekanisme *Rate Limiting* untuk mencegah serangan tebakan password secara masif dan otomatis.
- **Implementasi**:
  - Dibuat tabel `login_attempts` untuk mencatat log IP Address setiap kali terjadi kegagalan login.
  - Pada `admin/login.php`, jika IP gagal login sebanyak 5 kali berturut-turut, sistem akan memblokir akses login dari IP tersebut selama 15 menit.
  - Log aktivitas percobaan brute force (beserta status blokir) ditampilkan pada halaman Dashboard Admin secara langsung (real-time).

### 3. Fungsionalitas Utama (Sistem Informasi)
1. **Beranda Publik**: Menampilkan statistik desa, berita, dan sambutan.
2. **Pengecekan NIK**: Pencarian aman berbasis Prepared Statement.
3. **Detail Berita**: Menampilkan isi berita berdasarkan ID.
4. **Panel Admin**:
   - Login admin (Terlindungi dari SQLi & Brute Force).
   - Dashboard dengan tabel peringatan Keamanan.
   - Manajemen Pengguna (CRUD Admin & Ganti Password) untuk demonstrasi Bcrypt.
   - CRUD Data Warga, Berita, dan Pengajuan Surat.

### 4. Lingkungan dan Setup
- **Teknologi**: PHP Native (7.4/8.x), MySQL/MariaDB.
- **Desain UI**: Menggunakan Bootstrap 5 dengan tema Biru Pemerintah untuk tampilan modern dan profesional.
- **Database**: Skema versi aman (`login_attempts` & password bcrypt) tersedia di `database/schema.sql` dan `database/dummy_data.sql`.
