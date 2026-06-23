# Web Desa Secure — v2.0.0-secure

Versi aman dari Sistem Informasi Desa untuk demonstrasi pengujian keamanan.

## Instalasi
1. Copy folder webdesa ke htdocs/ (XAMPP)
2. Buat database: `CREATE DATABASE webdesa_secure;`
3. Import schema: `mysql -u root webdesa_secure < database/schema.sql`
4. Import data: `mysql -u root webdesa_secure < database/dummy_data.sql`
5. Akses: `http://localhost/webdesa/`

## Akun Default
| Role    | Username | Password   |
|---------|----------|------------|
| Admin   | admin    | admin123   |
| Petugas | petugas1 | petugas123 |

(password disimpan sebagai bcrypt hash di database)

## Perlindungan yang Diimplementasikan

| Kerentanan | Perlindungan |
|---|---|
| SQL Injection | Prepared Statements (semua query) |
| Password plaintext | bcrypt hash cost factor 12 |
| Brute Force | Rate limiting 5x/15 menit per IP |
| XSS | htmlspecialchars() semua output |
| CSRF | Token per sesi di semua form POST |
| Info Disclosure | Pesan error generik |
| Clickjacking | X-Frame-Options: DENY |

## Perbedaan dengan Versi Non-Secure

| Aspek | Non-Secure (master) | Secure (secure-v2) |
|---|---|---|
| Query DB | String concatenation raw | Prepared statement mysqli |
| Password | Plaintext di DB | bcrypt hash |
| Error | `mysqli_error($conn)` ditampilkan | Pesan generik |
| Output | `echo $row['x']` | `echo e($row['x'])` = htmlspecialchars |
| CSRF | Tidak ada | Token per sesi |
| Rate Limit | Tidak ada | 5x / 15 menit |
| Session | `session_start()` biasa | httponly + samesite strict + regenerate |

## Switch Branch

```bash
# Versi non-secure (SQLi Lab)
git checkout master

# Versi secure (hardened)
git checkout secure-v2
```
