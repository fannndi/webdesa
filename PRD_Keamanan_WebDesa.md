# Panduan Praktikum Keamanan Web Desa

---

## 1. Pendahuluan

Selamat datang di Panduan Praktikum Keamanan Web Sistem Informasi Desa! 

**Tujuan Praktikum**
Praktikum ini dirancang untuk memberikan pemahaman dasar dan pengalaman langsung (praktik) mengenai celah keamanan yang sering terjadi pada aplikasi web, serta bagaimana cara memitigasi (memperbaiki) celah tersebut menggunakan standar keamanan terkini.

**Tujuan Project**
Project ini merupakan replika Sistem Informasi Desa berbasis web yang sengaja dirancang memiliki kerentanan keamanan untuk tujuan edukasi. Mahasiswa diharapkan dapat bertindak sebagai *Security Analyst* untuk mengeksploitasi celah tersebut, lalu belajar menjadi *Secure Developer* untuk menambal celahnya.

**Konsep Dua Branch (Cabang)**
Repository (kode sumber) project ini dibagi menjadi dua *branch* utama di Git:
1. `master` → Versi yang **rentan** (vulnerable). Digunakan untuk simulasi serangan.
2. `secure-v2` → Versi yang **aman** (secure). Digunakan untuk melihat hasil perbaikan kode keamanan.

**Alasan Dibuat Dua Branch**
Pemisahan branch ini bertujuan agar mahasiswa dapat dengan mudah membandingkan kode yang buruk (rentan) dengan kode yang baik (aman) tanpa harus takut merusak struktur aplikasi, serta merasakan dampak langsung dari perubahan kode keamanan.

**Hasil yang Akan Dipelajari**
Setelah menyelesaikan praktikum ini, mahasiswa akan memahami:
- Bahaya SQL Injection dan cara menanganinya dengan *Prepared Statement*.
- Mengapa menyimpan password dalam bentuk teks biasa sangat fatal, dan cara mengamankannya dengan *Bcrypt Hashing*.
- Cara mencegah serangan tembak sandi otomatis (*Brute Force*) menggunakan sistem blokir sementara (*Rate Limiting*).

---

## 2. Persiapan

Sebelum memulai, pastikan perangkat komputer Anda sudah terinstal perlengkapan berikut:

1. **Git**: Digunakan untuk mengunduh kode (cloning) dan berpindah branch. 
2. **XAMPP** atau **Laragon**: Digunakan sebagai *Local Web Server*. Pastikan versi PHP yang terinstal minimal PHP 7.4 (Disarankan PHP 8.x).
3. **Apache**: Server web (sudah satu paket dengan XAMPP/Laragon).
4. **MySQL / MariaDB**: Server database (sudah satu paket dengan XAMPP/Laragon).
5. **phpMyAdmin**: Aplikasi berbasis web untuk mengelola database (sudah satu paket dengan XAMPP/Laragon).
6. **Web Browser**: Google Chrome, Mozilla Firefox, atau Microsoft Edge versi terbaru.

---

## 3. Menjalankan Project

Langkah pertama adalah meletakkan kode aplikasi ke dalam direktori server lokal Anda agar bisa diakses melalui browser.

1. Buka aplikasi Terminal (Command Prompt / Git Bash) di komputer Anda.
2. Unduh project ini menggunakan Git dengan perintah berikut:
   ```bash
   git clone https://github.com/fannndi/webdesa.git
   ```
3. Pindahkan folder `webdesa` hasil unduhan ke dalam direktori *document root* web server lokal Anda:
   - Jika Anda menggunakan **XAMPP**, letakkan folder di dalam:
     ```text
     C:\xampp\htdocs\webdesa
     ```
   - Jika Anda menggunakan **Laragon**, letakkan folder di dalam:
     ```text
     C:\laragon\www\webdesa
     ```

**Mengapa harus diletakkan di sana?**
Aplikasi yang dibangun dengan PHP tidak bisa langsung dibuka dengan klik dua kali (seperti file HTML biasa). File PHP harus diterjemahkan (di-parsing) oleh Web Server (Apache) terlebih dahulu. Folder `htdocs` atau `www` adalah tempat Apache mencari file web untuk ditampilkan.

---

## 4. Menjalankan Apache dan MySQL

Agar aplikasi dan databasenya bisa hidup, Anda perlu mengaktifkan dua layanan utama:

**Jika menggunakan XAMPP:**
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada baris **Apache**. (Tunggu hingga tulisan Apache berlatar hijau).
3. Klik tombol **Start** pada baris **MySQL**. (Tunggu hingga tulisan MySQL berlatar hijau).

**Jika menggunakan Laragon:**
1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All**.
3. Pastikan Apache dan MySQL sudah berstatus *Running*.

---

## 5. Membuat Database

Aplikasi Web Desa membutuhkan database untuk menyimpan data warga, berita, dan akun login.

1. Buka browser dan akses **phpMyAdmin** melalui URL berikut:
   ```text
   http://localhost/phpmyadmin
   ```
2. Pada panel kiri, klik tombol **New** (Baru) untuk membuat database.
3. Beri nama database dengan: `webdesa`, lalu klik tombol **Create** (Buat).
4. Setelah database `webdesa` terbuat, klik nama database tersebut di panel sebelah kiri.
5. Klik tab **Import** di deretan menu bagian atas.
6. Klik tombol **Choose File** (Pilih File) atau **Browse**.
7. Cari folder project Anda (`htdocs/webdesa/database`), lalu pilih file bernama `schema.sql`.
8. Scroll ke bawah dan klik tombol **Go** (Kirim) untuk meng-import tabel.
9. Ulangi langkah ke-5 hingga ke-8, namun kali ini pilih file `dummy_data.sql` untuk memasukkan data contoh.

> **⚠️ CATATAN PENTING**: 
> Setiap kali Anda berpindah branch (dari `master` ke `secure-v2` atau sebaliknya), struktur database yang dibutuhkan bisa sedikit berbeda (terutama bentuk sandi dan tabel log). Oleh karena itu, Anda **Wajib melakukan import ulang (Timpa) file `schema.sql` dan `dummy_data.sql`** setiap selesai melakukan perpindahan branch.

---

## 6. Memilih Branch

Buka Terminal (atau Command Prompt / Git Bash) dan arahkan ke dalam folder project Anda:
```bash
cd C:\xampp\htdocs\webdesa
```

Untuk praktikum ini, Anda akan sering berpindah branch.
- **`master`**: Branch utama yang rentan. Gunakan ini untuk **Simulasi Serangan**.
  ```bash
  git checkout master
  ```
- **`secure-v2`**: Branch keamanan. Gunakan ini untuk **Melihat Hasil Perbaikan (Implementasi Keamanan)**.
  ```bash
  git checkout secure-v2
  ```

*(Pastikan Terminal Anda menunjukkan keterangan "Switched to branch...")*

---

## 7. Menjalankan Website

Jika Apache dan MySQL sudah menyala, serta database sudah dibuat, saatnya melihat aplikasi:

1. Buka tab baru di browser Anda.
2. Akses alamat berikut:
   ```text
   http://localhost/webdesa
   ```
3. Jika Anda melihat halaman utama portal "Web Desa", berarti project berhasil dijalankan!

---

# Praktikum

## Percobaan 1: SQL Injection

### Pendahuluan
- **Apa itu?** Serangan di mana peretas menyisipkan perintah SQL jahat ke dalam input form login untuk memanipulasi logika *database*.
- **Mengapa penting?** SQLi adalah salah satu kerentanan paling berbahaya di web, yang dapat membuat peretas mencuri seluruh data atau melewati sistem login.
- **Tujuan praktikum:** Memahami bagaimana input yang tidak divalidasi dapat menghancurkan logika query, dan mempraktikkan mitigasinya dengan *Prepared Statement*.

### Persiapan
- **Branch**: `master` (Untuk menyerang) dan `secure-v2` (Untuk menahan serangan).
- **Database**: `webdesa`.
- **Halaman yang diuji**: `http://localhost/webdesa/admin/login.php`

### Langkah Praktikum
**Fase 1: Penyerangan (Branch `master`)**
1. Pastikan Anda berada di branch `master` (`git checkout master`), lalu import ulang databasenya jika perlu.
2. Buka `http://localhost/webdesa/admin/login.php`.
3. Pada form **Username**, ketikkan *payload* (kode) berikut persis seperti ini:
   ```text
   admin' OR '1'='1
   ```
4. Pada form **Password**, ketikkan sembarang huruf, misal: `rahasia123`.
5. Klik **Login**.

**Fase 2: Mitigasi (Branch `secure-v2`)**
6. Di Terminal, pindah ke branch aman: `git checkout secure-v2`. (Jangan lupa import ulang file SQL di phpMyAdmin).
7. *Refresh* halaman login Anda (F5).
8. Ulangi kembali langkah 3 dan 4 (masukkan payload `admin' OR '1'='1` di Username).
9. Klik **Login**.

### Hasil yang Diharapkan
- **Di Fase 1 (`master`)**: Anda **BERHASIL MASUK** ke halaman Dashboard Admin secara instan.
- **Di Fase 2 (`secure-v2`)**: Anda **GAGAL MASUK** dan melihat notifikasi merah *"Username atau password salah"*.

### Penjelasan
Di versi `master`, sistem menggabungkan teks Anda langsung menjadi perintah:
`SELECT * FROM users WHERE username = 'admin' OR '1'='1' AND password = '...'`
Karena matematika `1=1` adalah **selalu benar (True)**, sistem otomatis mengabaikan password dan mengizinkan masuk!

Di versi `secure-v2`, metode *Prepared Statement* telah diaktifkan. Sistem membungkus input secara ketat. Tulisan `admin' OR '1'='1` hanya dianggap sebagai *sebuah string nama* biasa, bukan sebagai perintah operasi. Karena tidak ada orang yang bernama aneh seperti itu, maka login tertolak.

### Kesimpulan
Mahasiswa mengerti bahayanya input langsung ke *query* database dan memahami bahwa perlindungan terbaik terhadap SQLi adalah pemisahan ketat antara perintah SQL dan data input menggunakan *Prepared Statement*.

---

## Percobaan 2: Password Security (Menggunakan Bcrypt)

### Pendahuluan
- **Apa itu?** Kriptografi sandi. Mengacak password dari bentuk teks telanjang (*plaintext*) menjadi sandi rumit (*hash*) satu arah.
- **Mengapa penting?** Kebocoran database adalah hal yang sering terjadi. Jika database bocor, peretas tidak boleh bisa membaca password asli pengguna untuk mencegah mereka membajak akun media sosial lain milik pengguna (karena kebiasaan orang menyamakan password).
- **Tujuan praktikum:** Melihat bahaya penyimpanan teks biasa dan melihat perlindungan dari Hashing standar industri (Bcrypt).

### Persiapan
- **Branch**: `master` dan `secure-v2`.
- **Database**: `webdesa`.
- **Halaman yang diuji**: `http://localhost/phpmyadmin` (Tabel `users`) dan `http://localhost/webdesa/admin/users.php`.

### Langkah Praktikum
**Fase 1: Mengintip Database Rentan (Branch `master`)**
1. Pastikan Anda berada di branch `master` dan telah meng-import database-nya.
2. Buka **phpMyAdmin**.
3. Buka tabel `users`.
4. Lihat dengan mata Anda pada kolom `password`.

**Fase 2: Mengintip Database Aman (Branch `secure-v2`)**
5. Pindah ke branch `secure-v2` (`git checkout secure-v2`).
6. Buka kembali **phpMyAdmin** dan Import ulang database (karena ini wajib setiap pindah branch).
7. Buka tabel `users`.
8. Bandingkan kolom `password` dengan Fase 1.
9. Opsional: Di web, masuk dengan username: `admin` dan password: `admin123`.
10. Masuk ke halaman **Manajemen Pengguna**, coba gunakan fitur Tambah Pengguna. Lalu cek lagi tabel di phpMyAdmin untuk melihat password pengguna baru.

### Hasil yang Diharapkan
- **Di Fase 1 (`master`)**: Pada kolom `password`, tulisan `admin123` terbaca dengan sangat jelas.
- **Di Fase 2 (`secure-v2`)**: Tulisan `admin123` telah berubah menjadi teks acak yang panjang, misalnya: `$2y$12$c73y1dfyUZvvaDof9x66.aVarJfw...`

### Penjelasan
Di versi rentan, web membuang password langsung ke keranjang database apa adanya. Siapapun yang melihat isi keranjang bisa langsung tahu passwordnya.
Di versi aman, web menggunakan fungsi bawaan PHP `password_hash()` yang mengubah teks biasa menjadi algoritma *Bcrypt*. Sandi Bcrypt bersifat satu arah (tidak bisa dikembalikan ke kata asli) dan menghasilkan *salt* acak, sehingga meski dua orang memiliki password "12345", hasil acakannya akan tetap berbeda!

### Kesimpulan
Mahasiswa menyadari bahwa menyimpan data rahasia dalam bentuk Plaintext adalah pelanggaran etika programming. Dan fungsi standar Bcrypt adalah cara paling efisien dan diakui secara global untuk menyimpan sandi.

---

## Percobaan 3: Brute Force Protection (Rate Limiting)

### Pendahuluan
- **Apa itu?** Brute Force adalah teknik menyerang di mana *hacker* mencoba menebak password dengan cepat dan berulang kali (ribuan kali per menit) menggunakan bot/skrip sampai menemukan kata sandi yang tepat.
- **Mengapa penting?** Sebanyak apapun keamanan sandi, jika pintu login terbuka lebar untuk ditebak tiada henti, perlahan peretas pasti akan menemukan kombinasi sandi yang benar.
- **Tujuan praktikum:** Menerapkan pembatasan hitungan gagal (*Rate Limiting*) untuk menghentikan serangan otomatis.

### Persiapan
- **Branch**: `master` dan `secure-v2`.
- **Database**: `webdesa`.
- **Halaman yang diuji**: `http://localhost/webdesa/admin/login.php`

### Langkah Praktikum
**Fase 1: Diserbu Tanpa Ampun (Branch `master`)**
1. Pastikan branch `master` dan database sudah sesuai.
2. Buka `http://localhost/webdesa/admin/login.php`.
3. Masukkan Username: `admin`, Password: `salah1`, klik Login.
4. Muncul tulisan Username atau Password salah.
5. Ulangi kembali langkah 3 secara berulang-ulang, tekan secepat mungkin (sebanyak 10-15 kali).

**Fase 2: Pemblokiran Otomatis (Branch `secure-v2`)**
6. Ubah branch ke `secure-v2` dan import databasenya.
7. Buka halaman login admin.
8. Masukkan Username: `admin`, Password: `salah2`.
9. Klik Login. Muncul error password salah.
10. Ulangi menekan tombol Login dengan password yang sengaja disalahkan sebanyak **5 kali**.
11. Perhatikan pada klik ke-6.

### Hasil yang Diharapkan
- **Di Fase 1 (`master`)**: Web terus menerus melayani klik login Anda tanpa batasan. Bot milik hacker bisa melakukan ini seumur hidup sampai password ketemu.
- **Di Fase 2 (`secure-v2`)**: Pada klik ke-6, web menolak permintaan Anda dan memunculkan notifikasi merah: **"Terlalu banyak percobaan login. Coba lagi dalam 15 menit."** 

### Penjelasan
Di versi aman, kita telah membuat sebuah tabel baru bernama `login_attempts` di database. Setiap kali sebuah Alamat IP (IP Address) gagal login, web mencatatnya. Ketika web mendeteksi ada IP yang telah gagal 5 kali berturut-turut, sistem seketika mengunci akses dari IP tersebut selama 15 menit ke depan, merusak laju serangan *bot* yang mengandalkan kecepatan. Anda bahkan bisa melihat IP mana yang sedang dihukum di halaman Dashboard Admin.

### Kesimpulan
Mahasiswa belajar pentingnya mendeteksi *anomali* (ketidakwajaran) aktivitas pengguna, serta melindungi *endpoint* autentikasi dengan pembatasan (*Rate Limiting*).

---

# Perbandingan Keseluruhan

| Fitur Keamanan | Kondisi di Branch `master` (Rentan) | Kondisi di Branch `secure-v2` (Aman) |
| :--- | :--- | :--- |
| **SQL Injection** | Rentan. Input teks disatukan langsung dengan perintah SQL (*Concatenation*). | Aman. Kode dipisahkan dari input pengguna (menggunakan *Prepared Statement*). |
| **Penyimpanan Password** | Rentan. Disimpan telanjang tanpa pengamanan (*Plaintext*). | Aman. Menggunakan teknik *hashing Bcrypt* searah. |
| **Serangan Brute Force**| Rentan. Hacker bisa menebak login berjuta-juta kali tanpa henti. | Aman. Jika gagal login 5 kali, akses diblokir selama 15 menit (*Rate Limiting*). |

---

# Troubleshooting (Penyelesaian Masalah)

Banyak kendala umum yang terjadi saat menjalankan project di lokal. Ikuti panduan ini jika Anda mengalami masalah:

- **Apache atau MySQL di XAMPP/Laragon Error (Tidak mau Start)**
  Biasanya terjadi karena *Port* bentrok (Port 80/3306 sudah dipakai aplikasi lain, seperti Skype atau VMware). Matikan aplikasi yang bentrok, lalu restart XAMPP.
- **Browser menampilkan `localhost tidak bisa dibuka`**
  Pastikan Apache benar-benar berstatus hijau (*Running*). Jika sudah berjalan tapi tetap gagal, cek apakah Anda menaruh foldernya di lokasi yang salah (harus di dalam `htdocs` atau `www`).
- **Peringatan Halaman 404 Not Found**
  Pastikan nama folder saat Anda meng-clone Git adalah `webdesa`. Jika foldernya bernama `webdesa-main`, maka URL Anda harus menjadi `http://localhost/webdesa-main`.
- **Gagal Meng-import Database**
  Seringkali karena Anda lupa membuat database `webdesa` di phpMyAdmin terlebih dahulu. Buat databasenya dulu, klik nama databasenya, baru import.
- **Login Gagal di `secure-v2` (Padahal password sudah benar `admin123`)**
  Anda **LUPA** meng-import ulang `schema.sql` dan `dummy_data.sql` saat pindah ke branch `secure-v2`. Jika database masih berisi *plaintext*, maka fitur pengecekan *Bcrypt* akan error dan menganggap sandinya salah. Selalu ingat: *Ganti branch = Import ulang database!*

---

# FAQ (Pertanyaan yang Sering Muncul)

- **Mengapa harus repot-repot berpindah branch Git?**
  Branch Git memudahkan Anda untuk berpindah 'dimensi ruang waktu' aplikasi tanpa harus meng-copy-paste dua folder yang berbeda (`webdesa-rentan` dan `webdesa-aman`). Ini melatih Anda menggunakan alur kerja standar industri perangkat lunak.
- **Mengapa password saya di database berubah menjadi karakter acak seperti `$2y...`?**
  Ini adalah ciri khas format algoritma *Bcrypt*. Karakter acak tersebut mustahil ditebak dan bahkan penciptanya sendiri tidak bisa membacanya kembali menjadi password asli Anda.
- **Loh, kalau *Bcrypt* tidak bisa dibaca kembali, bagaimana cara aplikasi mencocokkan password login saya?**
  PHP memiliki fungsi ajaib bernama `password_verify()`. Daripada mengubah hasil acakan kembali menjadi teks (yang tidak mungkin), sistem mengambil teks yang Anda ketik, mengacaknya dengan cara yang sama di balik layar, lalu **membandingkan hasilnya**. Jika hasil acakannya identik, Anda diizinkan masuk!
- **Bagaimana jika saya sedang terburu-buru mempraktikkan Brute Force dan tidak ingin menunggu 15 menit saat Terblokir?**
  Sebagai *SysAdmin*, Anda bebas dari aturan! Buka phpMyAdmin, buka tabel `login_attempts`, centang IP address Anda, lalu klik tombol *Delete (Hapus)*. Blokir Anda akan otomatis dicabut.
