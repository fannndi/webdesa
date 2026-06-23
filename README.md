## Instalasi
1. Copy folder webdesa ke htdocs/ (XAMPP) atau www/ (Laragon)
2. Buat database: CREATE DATABASE webdesa;
3. Import schema: mysql -u root webdesa < database/schema.sql
4. Import data:   mysql -u root webdesa < database/dummy_data.sql
5. Akses: http://localhost/webdesa/

## Akun Default
| Role    | Username | Password   | URL Login           |
|---------|----------|------------|---------------------|
| Admin   | admin    | admin123   | /admin/login.php    |
| Petugas | petugas1 | petugas123 | /admin/login.php    |

## Titik-Titik Injeksi

| File                    | Parameter | Method | Keterangan                         |
|-------------------------|-----------|--------|------------------------------------|
| cek_warga.php           | nik       | POST   | Pencarian warga berdasarkan NIK    |
| ajukan_surat.php        | nik       | POST   | Step 1 verifikasi warga            |
| riwayat.php             | nik       | POST   | Riwayat pengajuan warga            |
| admin/login.php         | username  | POST   | Login bypass                       |
| admin/login.php         | password  | POST   | Login bypass                       |
| admin/warga.php         | q         | GET    | Search warga                       |
| admin/surat.php         | status    | GET    | Filter pengajuan                   |
| berita_detail.php       | id        | GET    | Detail berita (query utama)        |
| berita_detail.php       | id        | GET    | Detail berita (sidebar "Lainnya")  |

## Pengujian dengan Burp Suite
1. Set Burp Suite sebagai proxy (127.0.0.1:8080)
2. Gunakan browser yang sudah dikonfigurasi ke proxy Burp
3. Kirim request ke salah satu titik injeksi di atas
4. Intersep via Proxy tab, kirim ke Repeater
5. Modifikasi parameter dan amati respons
