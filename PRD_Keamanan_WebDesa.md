# Panduan Praktikum Keamanan Web Desa

> **Versi**: 3.0 — Menggunakan **Burp Suite Community Edition** sebagai alat utama pengamatan lalu lintas HTTP.

---

## Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
2. [Persiapan Umum](#2-persiapan-umum)
3. [Instalasi & Konfigurasi Burp Suite](#3-instalasi--konfigurasi-burp-suite)
4. [Menjalankan Project](#4-menjalankan-project)
5. [Membuat Database](#5-membuat-database)
6. [Memilih Branch](#6-memilih-branch)
7. [Menjalankan Website](#7-menjalankan-website)
8. [Percobaan 1 — SQL Injection](#percobaan-1--sql-injection)
9. [Percobaan 2 — Password Security (Bcrypt)](#percobaan-2--password-security-bcrypt)
10. [Percobaan 3 — Brute Force Protection (Rate Limiting)](#percobaan-3--brute-force-protection-rate-limiting)
11. [Perbandingan Keseluruhan](#perbandingan-keseluruhan)
12. [Troubleshooting Burp Suite](#troubleshooting-burp-suite)
13. [Troubleshooting Umum](#troubleshooting-umum)
14. [FAQ](#faq)

---

## 1. Pendahuluan

Selamat datang di Panduan Praktikum Keamanan Web Sistem Informasi Desa!

### Tujuan Praktikum

Praktikum ini dirancang untuk memberikan pemahaman dasar dan pengalaman langsung mengenai celah keamanan yang sering terjadi pada aplikasi web, serta bagaimana cara memperbaiki celah tersebut menggunakan standar keamanan terkini.

Pada praktikum ini, Anda akan menggunakan **Burp Suite Community Edition** — sebuah alat profesional yang digunakan oleh *Security Analyst* di seluruh dunia — untuk **mengamati lalu lintas HTTP** antara browser dan server secara langsung (*real-time*). Dengan Burp Suite, Anda bisa melihat persis apa yang dikirim browser ke server dan apa yang dijawab oleh server, termasuk data sensitif seperti username dan password.

### Apa Itu HTTP?

HTTP (*HyperText Transfer Protocol*) adalah "bahasa" yang digunakan browser (seperti Chrome) dan server (seperti Apache) untuk berkomunikasi. Setiap kali Anda membuka halaman web atau menekan tombol Login, browser mengirim sebuah **HTTP Request** (permintaan) ke server, dan server membalas dengan **HTTP Response** (jawaban). Biasanya proses ini tidak terlihat oleh mata. Burp Suite memungkinkan kita **mengintip** percakapan rahasia ini.

### Apa Itu Proxy?

Proxy adalah "perantara" antara browser dan server. Bayangkan seorang penerjemah di tengah dua orang yang sedang bicara — penerjemah bisa mendengar, mencatat, bahkan mengubah isi percakapan. Burp Suite bertindak sebagai proxy: semua data dari browser melewati Burp Suite terlebih dahulu sebelum sampai ke server, sehingga kita bisa melihat isinya.

### Konsep Dua Branch

Repository project ini dibagi menjadi dua *branch* (cabang) utama di Git:

| Branch | Peran | Kapan Digunakan |
|:---|:---|:---|
| `master` | Versi **rentan** (*vulnerable*) | Untuk simulasi serangan |
| `secure-v2` | Versi **aman** (*secure*) | Untuk melihat hasil perbaikan keamanan |

### Hasil yang Akan Dipelajari

Setelah menyelesaikan praktikum ini, Anda akan memahami:

- Bahaya **SQL Injection** dan cara menanganinya dengan *Prepared Statement*.
- Mengapa menyimpan password dalam bentuk teks biasa sangat fatal, dan cara mengamankannya dengan **Bcrypt Hashing**.
- Cara mencegah serangan tebak sandi otomatis (*Brute Force*) menggunakan **Rate Limiting**.
- Cara menggunakan **Burp Suite** untuk mengamati dan menganalisis lalu lintas HTTP.

---

## 2. Persiapan Umum

Pastikan komputer Anda sudah terinstal perlengkapan berikut:

| No | Software | Fungsi |
|:---|:---|:---|
| 1 | **Git** | Mengunduh kode (*cloning*) dan berpindah branch |
| 2 | **XAMPP** atau **Laragon** | *Local Web Server* (berisi Apache, MySQL, phpMyAdmin) |
| 3 | **Web Browser** (Chrome / Firefox) | Mengakses website dan mengatur proxy |
| 4 | **Burp Suite Community Edition** | Mengamati lalu lintas HTTP |
| 5 | **Java (JRE/JDK)** versi 17+ | Diperlukan untuk menjalankan Burp Suite |

---

## 3. Instalasi & Konfigurasi Burp Suite

Bab ini menjelaskan cara menginstal Burp Suite, mengatur proxy browser, memasang sertifikat keamanan, dan memastikan Burp Suite berhasil menangkap *request*.

### 3.1 Menginstal Java

Burp Suite membutuhkan Java untuk berjalan. Cek apakah Java sudah terinstal:

1. Buka **Command Prompt** (tekan `Win + R`, ketik `cmd`, tekan Enter).
2. Ketik perintah berikut, lalu tekan Enter:
   ```
   java -version
   ```
3. Jika muncul tulisan seperti `java version "17.0.x"` atau lebih baru, Java sudah terinstal. **Lanjut ke langkah 3.2.**
4. Jika muncul pesan error `'java' is not recognized...`, unduh dan instal Java dari:
   ```
   https://adoptium.net/
   ```
5. Pilih versi **JDK 17** (atau lebih baru), unduh installer-nya, lalu jalankan file `.msi` dan ikuti prosesnya hingga selesai.
6. Setelah instalasi selesai, **tutup dan buka ulang** Command Prompt, lalu ulangi langkah 2 untuk memastikan Java sudah terdeteksi.

> 📸 **[Screenshot Placeholder: Hasil perintah `java -version` di Command Prompt]**

### 3.2 Mengunduh dan Menginstal Burp Suite Community Edition

1. Buka browser dan kunjungi halaman resmi Burp Suite:
   ```
   https://portswigger.net/burp/communitydownload
   ```
2. Klik tombol **Download** untuk versi **Community Edition** (gratis).
3. Pilih installer sesuai sistem operasi Anda (Windows 64-bit).
4. Jalankan file installer yang sudah diunduh (contoh: `burpsuite_community_windows-x64_vXXXX.exe`).
5. Ikuti langkah instalasi: klik **Next** → **Next** → **Install** → **Finish**.
6. Setelah selesai, Burp Suite akan muncul di menu Start.

> 📸 **[Screenshot Placeholder: Halaman download Burp Suite di portswigger.net]**

### 3.3 Menjalankan Burp Suite untuk Pertama Kali

1. Buka **Burp Suite Community Edition** dari menu Start.
2. Pada layar pertama, pilih **Temporary Project** (project sementara), lalu klik **Next**.
3. Pada layar kedua, pilih **Use Burp defaults** (pengaturan bawaan), lalu klik **Start Burp**.
4. Tunggu beberapa detik hingga tampilan utama Burp Suite muncul.

> 📸 **[Screenshot Placeholder: Layar awal Burp Suite — pilih Temporary Project]**

> 📸 **[Screenshot Placeholder: Tampilan utama Burp Suite setelah berhasil dibuka]**

### 3.4 Memastikan Proxy Burp Suite Aktif

Burp Suite secara bawaan sudah menjalankan proxy di alamat `127.0.0.1` port `8080`. Untuk memastikannya:

1. Di Burp Suite, klik tab **Proxy** di bagian atas.
2. Klik sub-tab **Proxy settings** (atau **Options** di versi lama).
3. Pastikan ada baris bertuliskan:
   ```
   127.0.0.1:8080   ✔ Running
   ```
4. Jika statusnya **Running**, berarti proxy Burp Suite sudah siap menerima koneksi dari browser Anda.

> 📸 **[Screenshot Placeholder: Proxy Listeners di Burp Suite menunjukkan status Running]**

### 3.5 Mengatur Proxy di Browser

Agar browser mengirim semua lalu lintas HTTP melalui Burp Suite, Anda perlu mengarahkan browser ke proxy Burp Suite.

#### Cara A: Menggunakan Google Chrome / Microsoft Edge (via Pengaturan Windows)

1. Tekan tombol `Win + I` untuk membuka **Settings** (Pengaturan) Windows.
2. Klik **Network & Internet** → **Proxy**.
3. Di bagian **Manual proxy setup**, klik **Set up** (atau aktifkan toggle).
4. Isi kolom berikut:
   - **Address**: `127.0.0.1`
   - **Port**: `8080`
5. Pada kolom "Do not use proxy for these addresses", **biarkan kosong** untuk saat ini.
6. Klik **Save** (Simpan).

> 📸 **[Screenshot Placeholder: Pengaturan proxy manual di Windows Settings]**

#### Cara B: Menggunakan Mozilla Firefox (Direkomendasikan — Lebih Mudah)

Firefox memiliki pengaturan proxy sendiri yang terpisah dari Windows, sehingga lebih mudah diaktifkan dan dimatikan:

1. Buka **Firefox**.
2. Klik ikon **☰** (tiga garis) di kanan atas → klik **Settings** (Pengaturan).
3. Scroll ke paling bawah hingga menemukan bagian **Network Settings**, klik **Settings...**.
4. Pilih **Manual proxy configuration**.
5. Isi kolom berikut:
   - **HTTP Proxy**: `127.0.0.1`
   - **Port**: `8080`
6. Centang ☑ **Also use this proxy for HTTPS**.
7. Klik **OK**.

> 📸 **[Screenshot Placeholder: Pengaturan proxy manual di Firefox Network Settings]**

### 3.6 Memasang Sertifikat Burp Suite (CA Certificate)

Ketika Burp Suite bertindak sebagai proxy untuk koneksi HTTPS, browser akan menolak koneksi tersebut karena menganggapnya tidak aman. Anda perlu menginstal sertifikat khusus dari Burp Suite agar browser mempercayainya.

> **Catatan:** Langkah ini wajib dilakukan **satu kali saja**. Setelah sertifikat terpasang, Anda tidak perlu mengulanginya.

#### Langkah 1: Mengunduh Sertifikat Burp

1. Pastikan **Burp Suite sudah berjalan** dan **proxy browser sudah diatur** (langkah 3.5).
2. Buka browser yang sudah diatur proxy-nya.
3. Ketik alamat berikut di address bar, lalu tekan Enter:
   ```
   http://burpsuite
   ```
4. Halaman Burp Suite akan muncul. Klik tombol **CA Certificate** di pojok kanan atas.
5. File bernama `cacert.der` akan terunduh. Simpan file ini.

> 📸 **[Screenshot Placeholder: Halaman http://burpsuite — klik CA Certificate]**

#### Langkah 2: Menginstal Sertifikat di Browser

**Untuk Google Chrome / Microsoft Edge:**

1. Buka Chrome/Edge → ketik di address bar:
   ```
   chrome://settings/security
   ```
   (untuk Edge, gunakan `edge://settings/privacy`)
2. Scroll ke bawah → klik **Manage certificates** (Kelola sertifikat).
3. Pilih tab **Trusted Root Certification Authorities** (Otoritas Sertifikasi Akar Terpercaya).
4. Klik **Import...** → klik **Next**.
5. Klik **Browse**, ubah filter file ke **All Files (*.*)**, lalu pilih file `cacert.der` yang sudah diunduh.
6. Klik **Next** → pastikan tujuan penyimpanan adalah **Trusted Root Certification Authorities** → **Next** → **Finish**.
7. Jika muncul peringatan keamanan, klik **Yes** (Ya) untuk mengonfirmasi.

**Untuk Mozilla Firefox:**

1. Buka Firefox → ketik di address bar:
   ```
   about:preferences#privacy
   ```
2. Scroll ke bawah ke bagian **Certificates** → klik **View Certificates...**.
3. Pilih tab **Authorities**.
4. Klik **Import...** → pilih file `cacert.der`.
5. Centang ☑ **Trust this CA to identify websites** → klik **OK**.

> 📸 **[Screenshot Placeholder: Import sertifikat Burp di browser — dialog Trust]**

### 3.7 Menguji Burp Suite — Memastikan Request Berhasil Ditangkap

Sekarang saatnya memastikan semuanya bekerja:

1. Pastikan **Burp Suite berjalan**, **proxy browser sudah diatur**, dan **sertifikat sudah terpasang**.
2. Di Burp Suite, klik tab **Proxy** → sub-tab **HTTP history**.
3. Pastikan tombol **Intercept** di sub-tab **Intercept** dalam keadaan **Intercept is off** (kita hanya ingin mengamati, bukan menahan *request*).

   > **Penting:** Selama praktikum, pastikan Intercept selalu dalam posisi **off** kecuali diperintahkan sebaliknya. Jika Intercept aktif (*on*), halaman web Anda akan tampak "macet" karena Burp Suite menahan *request*.

4. Buka browser dan akses:
   ```
   http://localhost/webdesa
   ```
5. Kembali ke Burp Suite → lihat sub-tab **HTTP history**.
6. Jika Anda melihat daftar baris-baris *request* muncul (seperti `GET /webdesa/ HTTP/1.1`), selamat — **Burp Suite berhasil menangkap lalu lintas HTTP Anda!** ✅

> 📸 **[Screenshot Placeholder: HTTP history di Burp Suite menampilkan request ke localhost/webdesa]**

**Cara Membaca HTTP History:**

| Kolom | Artinya |
|:---|:---|
| **#** | Nomor urut *request* |
| **Host** | Alamat server tujuan (contoh: `localhost`) |
| **Method** | Jenis permintaan: `GET` = meminta halaman, `POST` = mengirim data (misal form login) |
| **URL** | Alamat halaman yang diminta |
| **Status** | Kode jawaban server: `200` = berhasil, `302` = dialihkan, `404` = tidak ditemukan |
| **Length** | Ukuran jawaban dari server |

**Cara Melihat Detail Sebuah Request:**

1. Klik salah satu baris *request* di HTTP history.
2. Di panel bawah, klik tab **Request** untuk melihat apa yang dikirim browser.
3. Klik tab **Response** untuk melihat apa yang dijawab server.

---

## 4. Menjalankan Project

1. Buka Terminal (Command Prompt / Git Bash).
2. Unduh project menggunakan Git:
   ```bash
   git clone https://github.com/fannndi/webdesa.git
   ```
3. Pindahkan folder `webdesa` ke dalam direktori web server lokal Anda:
   - **XAMPP:**
     ```
     C:\xampp\htdocs\webdesa
     ```
   - **Laragon:**
     ```
     C:\laragon\www\webdesa
     ```

> **Mengapa harus diletakkan di sana?**
> File PHP tidak bisa langsung dibuka dengan klik dua kali. File PHP harus diterjemahkan oleh Web Server (Apache) terlebih dahulu. Folder `htdocs` atau `www` adalah tempat Apache mencari file web.

---

## 5. Membuat Database

### 5.1 Menjalankan Apache dan MySQL

**XAMPP:**
1. Buka **XAMPP Control Panel**.
2. Klik **Start** pada baris **Apache** (tunggu hingga berlatar hijau).
3. Klik **Start** pada baris **MySQL** (tunggu hingga berlatar hijau).

**Laragon:**
1. Buka **Laragon** → klik **Start All**.

### 5.2 Import Database

1. Buka browser dan akses:
   ```
   http://localhost/phpmyadmin
   ```
2. Pada panel kiri, klik **New** (Baru) untuk membuat database.
3. Beri nama database: `webdesa`, lalu klik **Create**.
4. Klik nama database `webdesa` di panel kiri.
5. Klik tab **Import** di menu atas.
6. Klik **Choose File** → cari folder `htdocs/webdesa/database` → pilih file `schema.sql`.
7. Scroll ke bawah → klik **Go**.
8. Ulangi langkah 5–7, namun kali ini pilih file `dummy_data.sql`.

> ⚠️ **CATATAN PENTING:**
> Setiap kali Anda berpindah branch (dari `master` ke `secure-v2` atau sebaliknya), Anda **WAJIB** melakukan import ulang (timpa) kedua file `schema.sql` dan `dummy_data.sql`. Struktur database kedua branch berbeda!

> 📸 **[Screenshot Placeholder: phpMyAdmin — proses import schema.sql berhasil]**

---

## 6. Memilih Branch

Buka Terminal dan arahkan ke folder project:
```bash
cd C:\xampp\htdocs\webdesa
```

Untuk berpindah branch:

- **Branch Rentan** (untuk simulasi serangan):
  ```bash
  git checkout master
  ```
- **Branch Aman** (untuk melihat perbaikan keamanan):
  ```bash
  git checkout secure-v2
  ```

Pastikan Terminal menunjukkan pesan `Switched to branch '...'`.

> ⚠️ **Jangan lupa:** Setiap kali pindah branch → **import ulang database** di phpMyAdmin!

---

## 7. Menjalankan Website

Jika Apache, MySQL, dan database sudah siap:

1. Buka browser.
2. Akses:
   ```
   http://localhost/webdesa
   ```
3. Jika halaman portal "Web Desa" muncul, project berhasil dijalankan!

> 📸 **[Screenshot Placeholder: Halaman utama Web Desa di browser]**

---

# Praktikum

> **Sebelum memulai setiap percobaan**, pastikan:
> 1. Burp Suite **sudah berjalan**.
> 2. Proxy browser **sudah diarahkan** ke `127.0.0.1:8080`.
> 3. Intercept dalam keadaan **off** (kecuali diperintahkan sebaliknya).
> 4. Anda membuka tab **Proxy → HTTP history** di Burp Suite untuk mengamati.

---

## Percobaan 1 — SQL Injection

### Pendahuluan

| Item | Keterangan |
|:---|:---|
| **Apa itu?** | Serangan di mana peretas menyisipkan perintah SQL jahat ke dalam form input (seperti form login) untuk memanipulasi logika database. |
| **Mengapa penting?** | SQL Injection adalah salah satu kerentanan paling berbahaya. Peretas bisa mencuri data atau melewati sistem login tanpa mengetahui password. |
| **Tujuan percobaan** | Memahami bagaimana input yang tidak divalidasi dapat menghancurkan logika *query*, dan mempraktikkan mitigasinya dengan *Prepared Statement*. |

### Tujuan Pengamatan di Burp Suite

Anda akan mengamati **POST request** yang dikirim saat menekan tombol Login. Perhatikan:
- **Parameter** `username` dan `password` yang dikirim ke server.
- **Response** dari server: apakah server mengarahkan Anda ke Dashboard (login berhasil) atau kembali ke halaman login (login gagal).

### Persiapan

- **Branch awal**: `master`
- **Database**: `webdesa` (sudah di-import)
- **Halaman yang diuji**: `http://localhost/webdesa/admin/login.php`

### Langkah Praktikum

#### Fase 1: Penyerangan (Branch `master`)

1. Pastikan Anda berada di branch `master`:
   ```bash
   git checkout master
   ```
   Import ulang database jika belum dilakukan.

2. Di Burp Suite, klik tab **Proxy → HTTP history**. Bersihkan history sebelumnya dengan klik kanan → **Clear history** (opsional, agar lebih mudah diamati).

   > 📸 **[Screenshot Placeholder: HTTP history kosong — siap mengamati]**

3. Buka browser dan akses:
   ```
   http://localhost/webdesa/admin/login.php
   ```

4. Pada form login, masukkan data berikut:
   - **Username**:
     ```
     admin' OR '1'='1
     ```
   - **Password** (isi sembarang):
     ```
     rahasia123
     ```

5. Klik tombol **Login**.

6. **Amati di Browser:** Anda langsung masuk ke halaman **Dashboard Admin** tanpa mengetahui password yang benar. ⚠️ Ini adalah bukti kerentanan SQL Injection!

   > 📸 **[Screenshot Placeholder: Browser — berhasil masuk ke Dashboard Admin menggunakan payload SQLi]**

7. **Amati di Burp Suite:** Kembali ke Burp Suite → lihat **HTTP history**. Cari baris dengan:
   - **Method**: `POST`
   - **URL**: `/webdesa/admin/login.php`

8. Klik baris tersebut. Di panel bawah, klik tab **Request**. Anda akan melihat isi data yang dikirim browser:
   ```
   username=admin'+OR+'1'%3D'1&password=rahasia123
   ```
   > **Penjelasan:** Karakter khusus seperti `'` dan `=` diubah menjadi kode URL (*URL encoding*): `'` menjadi `%27`, `=` menjadi `%3D`. Ini normal — browser otomatis melakukannya.

   > 📸 **[Screenshot Placeholder: Burp Suite — Request tab menampilkan parameter username berisi payload SQLi]**

9. Klik tab **Response**. Perhatikan:
   - Kode status: **302 Found** (artinya server mengarahkan Anda ke halaman lain — Dashboard).
   - Header `Location: dashboard.php` — ini membuktikan server mengizinkan Anda masuk.

   > 📸 **[Screenshot Placeholder: Burp Suite — Response tab menampilkan status 302 redirect ke dashboard.php]**

#### Fase 2: Mitigasi (Branch `secure-v2`)

10. Di Terminal, pindah ke branch aman:
    ```bash
    git checkout secure-v2
    ```
    **Import ulang database** di phpMyAdmin (wajib!).

11. Di Burp Suite, bersihkan HTTP history (klik kanan → **Clear history**).

12. Buka browser dan akses kembali:
    ```
    http://localhost/webdesa/admin/login.php
    ```

13. Masukkan **payload yang sama persis**:
    - **Username**: `admin' OR '1'='1`
    - **Password**: `rahasia123`

14. Klik **Login**.

15. **Amati di Browser:** Anda **GAGAL MASUK**. Muncul notifikasi merah: *"Username atau password salah"*. ✅

    > 📸 **[Screenshot Placeholder: Browser — login ditolak dengan pesan error di branch secure-v2]**

16. **Amati di Burp Suite:** Cari request `POST /webdesa/admin/login.php` di HTTP history.

17. Klik baris tersebut → tab **Request**: parameter yang dikirim **sama persis** dengan Fase 1.
    ```
    username=admin'+OR+'1'%3D'1&password=rahasia123
    ```

18. Klik tab **Response**: Perhatikan perbedaannya:
    - Kode status: **200 OK** (server tidak mengarahkan ke Dashboard, melainkan menampilkan ulang halaman login).
    - Di dalam body HTML, terdapat pesan: `Username atau password salah`.

    > 📸 **[Screenshot Placeholder: Burp Suite — Response di secure-v2 menampilkan status 200 dan pesan error]**

### Perbandingan Hasil di Burp Suite

| Aspek | Branch `master` (Rentan) | Branch `secure-v2` (Aman) |
|:---|:---|:---|
| **Parameter yang dikirim** | `username=admin'+OR+'1'%3D'1` | `username=admin'+OR+'1'%3D'1` (sama) |
| **Status Response** | `302 Found` (redirect ke Dashboard) | `200 OK` (tetap di halaman login) |
| **Header Location** | `Location: dashboard.php` | Tidak ada |
| **Isi Response** | Halaman Dashboard | Pesan *"Username atau password salah"* |

### Indikator Keamanan Bekerja

✅ Request `POST` dengan payload SQLi **tetap dikirim** (parameternya sama), tetapi server **menolak** login karena menggunakan *Prepared Statement* — input diperlakukan sebagai teks biasa, bukan perintah SQL.

### Penjelasan Teknis

Di versi `master`, server menggabungkan input Anda langsung ke dalam perintah SQL:
```sql
SELECT * FROM users WHERE username = 'admin' OR '1'='1' AND password = '...'
```
Karena `1=1` selalu benar, server mengabaikan password dan mengizinkan masuk.

Di versi `secure-v2`, *Prepared Statement* memisahkan perintah SQL dari data input. Tulisan `admin' OR '1'='1` hanya dianggap sebagai sebuah nama biasa — dan karena tidak ada pengguna bernama seperti itu, login ditolak.

---

## Percobaan 2 — Password Security (Bcrypt)

### Pendahuluan

| Item | Keterangan |
|:---|:---|
| **Apa itu?** | Kriptografi sandi — mengacak password dari teks biasa (*plaintext*) menjadi sandi rumit (*hash*) satu arah. |
| **Mengapa penting?** | Kebocoran database sering terjadi. Jika password tersimpan dalam bentuk teks biasa, peretas langsung mengetahui password asli semua pengguna. |
| **Tujuan percobaan** | Melihat bahaya penyimpanan teks biasa dan melihat perlindungan dari *Bcrypt Hashing*. |

### Tujuan Pengamatan di Burp Suite

Anda akan mengamati **POST request** saat login berhasil di kedua branch, lalu membandingkan **bagaimana password dikirim dari browser** dan **bagaimana password tersimpan di database**.

### Persiapan

- **Branch**: `master` dan `secure-v2`
- **Database**: `webdesa`
- **Halaman yang diuji**:
  - `http://localhost/webdesa/admin/login.php` (form login)
  - `http://localhost/phpmyadmin` (melihat isi tabel `users`)

### Langkah Praktikum

#### Fase 1: Mengintip Database Rentan (Branch `master`)

1. Pastikan branch `master` dan database sudah di-import.

2. Buka **phpMyAdmin** di browser:
   ```
   http://localhost/phpmyadmin
   ```

3. Klik database `webdesa` di panel kiri → klik tabel `users` → klik tab **Browse**.

4. Amati kolom `password`:
   - Tertulis **`admin123`** dan **`petugas123`** secara jelas dan terang benderang.
   - Siapapun yang melihat database ini (termasuk hacker yang berhasil membobol server) langsung mengetahui password semua pengguna!

   > 📸 **[Screenshot Placeholder: phpMyAdmin — tabel users di branch master, password terlihat plaintext]**

5. Sekarang amati di Burp Suite. Bersihkan HTTP history, lalu lakukan login normal:
   - **Username**: `admin`
   - **Password**: `admin123`
   - Klik **Login**.

6. Di Burp Suite, cari `POST /webdesa/admin/login.php`. Klik → tab **Request**:
   ```
   username=admin&password=admin123
   ```
   > **Perhatikan:** Password dikirim dalam bentuk **teks biasa** di dalam request HTTP. Di sisi server, password ini juga **disimpan dalam bentuk teks biasa** di database. Tidak ada perlindungan sama sekali.

   > 📸 **[Screenshot Placeholder: Burp Suite — Request menunjukkan password=admin123 dikirim plaintext]**

#### Fase 2: Mengintip Database Aman (Branch `secure-v2`)

7. Pindah ke branch `secure-v2` dan **import ulang database** (wajib!):
   ```bash
   git checkout secure-v2
   ```

8. Buka kembali **phpMyAdmin** → database `webdesa` → tabel `users` → tab **Browse**.

9. Amati kolom `password`:
   - Tulisan `admin123` telah **berubah menjadi teks acak panjang**, misalnya:
     ```
     $2y$10$2LG1U2M/l424bGlGeTD1WeSa1a6OrXOID8Z0c1xgi/wX2JEoL/hMW
     ```
   - Ini adalah **hash Bcrypt**. Tidak ada cara untuk mengubahnya kembali menjadi `admin123`.

   > 📸 **[Screenshot Placeholder: phpMyAdmin — tabel users di branch secure-v2, password berbentuk hash Bcrypt]**

10. Bersihkan HTTP history di Burp Suite, lalu login dengan:
    - **Username**: `admin`
    - **Password**: `admin123`
    - Klik **Login**.

11. Di Burp Suite, cari `POST /webdesa/admin/login.php`. Klik → tab **Request**:
    ```
    username=admin&password=admin123
    ```
    > **Perhatikan:** Password yang **dikirim browser tetap sama** (`admin123`). Perbedaannya ada di **sisi server**: server tidak lagi membandingkan teks langsung, tetapi menggunakan fungsi `password_verify()` untuk membandingkan hash.

    > 📸 **[Screenshot Placeholder: Burp Suite — Request di secure-v2, password tetap dikirim plaintext dari browser]**

12. Klik tab **Response**:
    - Status: **302 Found** → `Location: dashboard.php` — login berhasil!
    - Meskipun di database tersimpan hash acak, server berhasil memverifikasi bahwa `admin123` cocok dengan hash tersebut.

    > 📸 **[Screenshot Placeholder: Burp Suite — Response 302 redirect ke dashboard di secure-v2]**

### Perbandingan Hasil di Burp Suite

| Aspek | Branch `master` (Rentan) | Branch `secure-v2` (Aman) |
|:---|:---|:---|
| **Password di Request HTTP** | `password=admin123` | `password=admin123` (sama) |
| **Password di Database** | `admin123` (plaintext) | `$2y$10$2LG1U2M/...` (Bcrypt hash) |
| **Metode Pencocokan** | String comparison (`==`) | `password_verify()` |
| **Jika Database Bocor** | Peretas langsung tahu password | Peretas hanya dapat hash yang tidak berguna |

### Indikator Keamanan Bekerja

✅ Di Burp Suite, request dari browser **terlihat sama** di kedua branch. Perbedaan keamanan terjadi **di sisi server** (database) — bukan di jaringan. Bukti keamanan terlihat di phpMyAdmin: password tersimpan dalam bentuk hash, bukan teks biasa.

### Penjelasan Teknis

Bcrypt adalah algoritma *hashing* satu arah. Berbeda dengan enkripsi yang bisa dikembalikan (di-dekripsi), hash **tidak bisa dibalik**. PHP menyediakan:
- `password_hash('admin123', PASSWORD_BCRYPT)` → menghasilkan hash acak.
- `password_verify('admin123', $hash)` → mengecek apakah teks cocok dengan hash, **tanpa** perlu mengetahui isi asli hash.

Bahkan jika dua orang memiliki password yang sama (`admin123`), hash yang dihasilkan tetap **berbeda** karena setiap hash memiliki *salt* (bumbu acak) unik.

---

## Percobaan 3 — Brute Force Protection (Rate Limiting)

### Pendahuluan

| Item | Keterangan |
|:---|:---|
| **Apa itu?** | Brute Force adalah teknik serangan di mana hacker mencoba menebak password dengan cepat dan berulang kali (ribuan kali per menit) menggunakan bot/skrip. |
| **Mengapa penting?** | Jika pintu login terbuka tanpa batas, peretas pasti akhirnya menemukan password yang benar — hanya masalah waktu. |
| **Tujuan percobaan** | Menerapkan pembatasan percobaan login gagal (*Rate Limiting*) untuk menghentikan serangan otomatis. |

### Tujuan Pengamatan di Burp Suite

Anda akan mengamati **beberapa POST request login gagal berturut-turut** dan membandingkan:
- Di `master`: server selalu menjawab dengan cara yang sama (tidak ada pembatasan).
- Di `secure-v2`: setelah 5 kali gagal, server menjawab dengan pesan blokir.

### Persiapan

- **Branch**: `master` dan `secure-v2`
- **Database**: `webdesa`
- **Halaman yang diuji**: `http://localhost/webdesa/admin/login.php`

### Langkah Praktikum

#### Fase 1: Diserbu Tanpa Ampun (Branch `master`)

1. Pastikan branch `master` dan database sudah di-import.

2. Bersihkan HTTP history di Burp Suite.

3. Buka browser dan akses:
   ```
   http://localhost/webdesa/admin/login.php
   ```

4. Lakukan **login gagal berulang kali** (minimal 7 kali) dengan data berikut:
   - **Username**: `admin`
   - **Password**: `salah1` (atau sembarang password yang salah)
   - Klik **Login** → muncul error → ulangi terus.

5. **Amati di Browser:** Setiap kali klik Login, pesan error yang sama muncul: *"Username atau password salah"*. Tidak ada pembatasan — Anda bisa mencoba selamanya.

   > 📸 **[Screenshot Placeholder: Browser — login gagal ke-7 di master, tetap bisa mencoba]**

6. **Amati di Burp Suite:** Lihat HTTP history. Anda akan melihat **7 baris POST request** berturut-turut, semuanya ke `/webdesa/admin/login.php`.

7. Klik setiap baris POST. Perhatikan tab **Response** semuanya:
   - Status: **200 OK**
   - Body berisi pesan *"Username atau password salah"*
   - **Tidak ada perbedaan antara percobaan ke-1 dan ke-7.** Server tidak pernah menolak atau memblokir.

   > 📸 **[Screenshot Placeholder: Burp Suite — HTTP history menunjukkan 7 POST request berturut-turut, semua status 200]**

   > 📸 **[Screenshot Placeholder: Burp Suite — Response percobaan ke-7, masih pesan error biasa tanpa blokir]**

#### Fase 2: Pemblokiran Otomatis (Branch `secure-v2`)

8. Pindah ke branch `secure-v2` dan **import ulang database** (wajib!):
   ```bash
   git checkout secure-v2
   ```

9. Bersihkan HTTP history di Burp Suite.

10. Buka browser dan akses kembali:
    ```
    http://localhost/webdesa/admin/login.php
    ```

11. Lakukan **login gagal sebanyak 5 kali**:
    - **Username**: `admin`
    - **Password**: `salah2` (atau sembarang password yang salah)
    - Klik **Login** → muncul error → ulangi (total 5 kali).

12. Pada percobaan **ke-6**, masukkan lagi data yang salah dan klik **Login**.

13. **Amati di Browser:** Pesan error berubah menjadi:
    > **"Terlalu banyak percobaan login. Coba lagi dalam 15 menit."** 🔒

    > 📸 **[Screenshot Placeholder: Browser — pesan rate limit muncul pada percobaan ke-6 di secure-v2]**

14. **Amati di Burp Suite:** Lihat HTTP history. Anda akan melihat **6 baris POST request**.

15. Klik **POST request ke-1 sampai ke-5**. Pada tab **Response**, semuanya menunjukkan:
    - Status: **200 OK**
    - Body berisi: *"Username atau password salah"*

16. Klik **POST request ke-6**. Pada tab **Response**, perhatikan perbedaannya:
    - Status: **200 OK** (atau **403 Forbidden**, tergantung implementasi)
    - Body berisi: *"Terlalu banyak percobaan login. Coba lagi dalam 15 menit."*
    - **Ini adalah bukti Rate Limiting bekerja!** ✅

    > 📸 **[Screenshot Placeholder: Burp Suite — Response percobaan ke-6 menunjukkan pesan rate limit]**

17. **(Opsional)** Coba login lagi beberapa kali. Semua percobaan selanjutnya akan langsung diblokir — server bahkan tidak mau memeriksa password Anda lagi.

    > 📸 **[Screenshot Placeholder: Burp Suite — Response percobaan ke-7 dan seterusnya tetap diblokir]**

### Perbandingan Hasil di Burp Suite

| Aspek | Branch `master` (Rentan) | Branch `secure-v2` (Aman) |
|:---|:---|:---|
| **Percobaan ke-1 s/d ke-5** | Pesan: *"Username atau password salah"* | Pesan: *"Username atau password salah"* (sama) |
| **Percobaan ke-6** | Pesan: *"Username atau password salah"* (masih sama!) | Pesan: **"Terlalu banyak percobaan login..."** 🔒 |
| **Percobaan ke-7 dan seterusnya** | Tetap dilayani tanpa batas | Semua **langsung diblokir** |
| **Jumlah POST yang dilayani** | Tidak terbatas | Maksimal 5 percobaan per 15 menit |

### Indikator Keamanan Bekerja

✅ Setelah percobaan ke-5, semua POST request berikutnya di Burp Suite mendapat response berisi pesan blokir, bukan pesan error biasa. Server menolak memproses login lebih lanjut dari IP yang sama.

### Penjelasan Teknis

Di versi aman, sebuah tabel `login_attempts` di database mencatat setiap kegagalan login berdasarkan alamat IP. Ketika sebuah IP sudah mencapai 5 kali gagal dalam 15 menit terakhir, fungsi `check_rate_limit()` langsung menolak semua request berikutnya tanpa memeriksa password. Ini merusak strategi *brute force* yang mengandalkan kecepatan dan volume percobaan.

> **Tips:** Jika Anda ingin mereset blokir untuk melanjutkan percobaan, buka phpMyAdmin → tabel `login_attempts` → centang semua baris → klik **Delete**. Blokir langsung dicabut.

---

## Perbandingan Keseluruhan

| Fitur Keamanan | Branch `master` (Rentan) | Branch `secure-v2` (Aman) | Yang Diamati di Burp Suite |
|:---|:---|:---|:---|
| **SQL Injection** | Rentan — input digabung langsung ke SQL | Aman — *Prepared Statement* | Response: `302` (masuk) vs `200` (ditolak) |
| **Password Storage** | Plaintext di database | Bcrypt Hash di database | Request sama; bukti di phpMyAdmin |
| **Brute Force** | Tidak ada batasan login gagal | Blokir setelah 5 kali gagal | Response ke-6: pesan blokir muncul |

---

## Troubleshooting Burp Suite

Berikut adalah masalah yang sering terjadi saat menggunakan Burp Suite dan cara mengatasinya:

### ❌ Browser tidak bisa membuka website setelah proxy aktif

**Gejala:** Setelah mengatur proxy di browser, semua halaman menampilkan error *"The proxy server is refusing connections"* atau *"Unable to connect"*.

**Penyebab:** Burp Suite belum berjalan, atau proxy listener-nya tidak aktif.

**Solusi:**
1. Pastikan Burp Suite **sudah dibuka** dan menampilkan tampilan utama (bukan masih loading).
2. Buka tab **Proxy → Proxy settings** di Burp Suite.
3. Periksa apakah listener `127.0.0.1:8080` berstatus **Running**.
4. Jika tidak ada listener, klik **Add** → isi **Bind to port**: `8080`, **Bind to address**: `Loopback only` → klik **OK**.

---

### ❌ Browser menampilkan peringatan sertifikat / "Your connection is not private"

**Gejala:** Browser menampilkan halaman peringatan merah bertuliskan *"Your connection is not private"* atau *"NET::ERR_CERT_AUTHORITY_INVALID"*.

**Penyebab:** Sertifikat Burp Suite (CA Certificate) belum dipasang di browser.

**Solusi:**
Ikuti langkah 3.6 pada panduan di atas untuk mengunduh dan memasang sertifikat Burp Suite.

---

### ❌ Request tidak muncul di HTTP history

**Gejala:** Anda sudah membuka halaman web, tetapi HTTP history di Burp Suite tetap kosong.

**Penyebab 1:** Proxy browser belum diarahkan ke Burp Suite.

**Solusi:** Periksa kembali pengaturan proxy browser Anda (langkah 3.5). Pastikan:
- Address: `127.0.0.1`
- Port: `8080`

**Penyebab 2:** Anda menggunakan browser yang berbeda dari yang diatur proxy-nya.

**Solusi:** Pastikan Anda membuka website menggunakan browser **yang sama** dengan yang sudah diatur proxy-nya.

**Penyebab 3:** Website yang Anda akses menggunakan `localhost` dan browser melewatkan proxy untuk alamat lokal.

**Solusi:**
- Di **Windows proxy settings**, pastikan kotak *"Don't use the proxy server for local (intranet) addresses"* **tidak dicentang**.
- Di **Firefox**, ketik `about:config` di address bar → cari `network.proxy.allow_hijacking_localhost` → ubah nilainya menjadi `true`.

---

### ❌ Halaman web "macet" / tidak pernah selesai loading

**Gejala:** Setelah mengakses halaman web, browser terus loading tanpa henti (ikon berputar terus).

**Penyebab:** **Intercept** di Burp Suite dalam keadaan **on** — artinya Burp Suite sedang menahan request Anda dan menunggu Anda menekan tombol **Forward**.

**Solusi:**
1. Buka Burp Suite → tab **Proxy → Intercept**.
2. Jika tombol bertuliskan **"Intercept is on"**, klik tombol tersebut untuk mengubahnya menjadi **"Intercept is off"**.
3. Semua request yang tertahan akan langsung dikirimkan, dan halaman web akan selesai loading.

> 💡 **Tips:** Selama praktikum ini, selalu pastikan Intercept dalam keadaan **off** kecuali diperintahkan sebaliknya.

---

### ❌ Lupa mematikan proxy setelah praktikum

**Gejala:** Setelah menutup Burp Suite, browser tidak bisa membuka website manapun (termasuk Google, YouTube, dll).

**Penyebab:** Proxy browser masih diarahkan ke `127.0.0.1:8080`, tetapi Burp Suite sudah ditutup sehingga tidak ada yang melayani koneksi.

**Solusi:**

**Untuk Chrome/Edge (Windows):**
1. Buka **Settings Windows** → **Network & Internet** → **Proxy**.
2. Di bagian **Manual proxy setup**, matikan toggle / klik **Edit** → matikan → **Save**.

**Untuk Firefox:**
1. Buka **Settings** → scroll ke **Network Settings** → klik **Settings...**.
2. Pilih **No proxy** atau **Use system proxy settings**.
3. Klik **OK**.

> ⚠️ **PENTING:** Biasakan **selalu mematikan proxy** setelah selesai praktikum! Jika lupa, internet Anda tidak akan berfungsi normal.

---

### ❌ Port 8080 sudah dipakai aplikasi lain

**Gejala:** Burp Suite menampilkan error saat mencoba menjalankan proxy listener di port 8080.

**Penyebab:** Aplikasi lain (misalnya Jenkins, Tomcat, atau XAMPP Tomcat) sudah menggunakan port 8080.

**Solusi:**
1. Di Burp Suite, buka **Proxy → Proxy settings**.
2. Edit listener yang ada, atau tambahkan listener baru dengan port lain, misalnya `8888`.
3. **Jangan lupa** mengubah pengaturan proxy browser Anda agar mengarah ke port yang baru (misalnya `127.0.0.1:8888`).

---

## Troubleshooting Umum

Berikut masalah umum yang tidak berkaitan dengan Burp Suite:

| Masalah | Penyebab | Solusi |
|:---|:---|:---|
| Apache/MySQL tidak mau Start | Port 80/3306 sudah dipakai aplikasi lain | Matikan aplikasi yang bentrok, lalu restart XAMPP |
| `localhost` tidak bisa dibuka | Apache belum berjalan | Pastikan Apache berstatus hijau (Running) di XAMPP |
| Halaman 404 Not Found | Nama folder salah | Pastikan folder bernama `webdesa` di dalam `htdocs` |
| Gagal import database | Database belum dibuat | Buat database `webdesa` dulu di phpMyAdmin, baru import |
| Login gagal di `secure-v2` padahal password benar | Lupa import ulang database | Setiap pindah branch → wajib import ulang `schema.sql` dan `dummy_data.sql` |

---

## FAQ

**T: Mengapa harus repot-repot berpindah branch Git?**
> Branch Git memudahkan Anda berpindah "dimensi" aplikasi tanpa harus menyalin dua folder berbeda. Ini juga melatih Anda menggunakan alur kerja standar industri.

**T: Mengapa password di database berubah menjadi karakter acak `$2y...`?**
> Ini adalah format *Bcrypt hash*. Karakter tersebut mustahil ditebak dan tidak bisa dikembalikan ke password asli.

**T: Kalau Bcrypt tidak bisa dibaca kembali, bagaimana cara server mencocokkan password?**
> PHP menggunakan fungsi `password_verify()`. Sistem mengambil teks yang Anda ketik, mengacaknya dengan cara yang sama, lalu **membandingkan hasilnya**. Jika cocok, Anda diizinkan masuk.

**T: Bagaimana jika saat praktikum Brute Force, saya tidak ingin menunggu 15 menit?**
> Buka phpMyAdmin → tabel `login_attempts` → centang semua baris → klik **Delete**. Blokir langsung dicabut.

**T: Apakah Burp Suite Community Edition benar-benar gratis?**
> Ya, sepenuhnya gratis. Versi Community sudah cukup untuk seluruh kebutuhan praktikum ini. Versi Professional (berbayar) memiliki fitur tambahan seperti scanner otomatis yang tidak diperlukan di sini.

**T: Apakah aman menginstal sertifikat Burp Suite di komputer saya?**
> Ya, aman untuk keperluan praktikum. Sertifikat ini hanya digunakan agar Burp Suite bisa membaca lalu lintas HTTPS lokal. Setelah praktikum selesai, Anda bisa menghapus sertifikat tersebut dari browser jika diinginkan.

**T: Apa bedanya Intercept "on" dan "off" di Burp Suite?**
> **Intercept on** = Burp Suite **menahan** setiap request dan menunggu Anda menekan tombol Forward secara manual. Cocok untuk memodifikasi request.
> **Intercept off** = Burp Suite membiarkan semua request lewat secara otomatis dan hanya **mencatatnya** di HTTP history. Untuk praktikum ini, gunakan mode **off**.
