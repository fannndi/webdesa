CREATE DATABASE IF NOT EXISTS webdesa-secure;
USE webdesa-secure;

CREATE TABLE users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(50)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  role         ENUM('admin','petugas') DEFAULT 'petugas',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE warga (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  nik               CHAR(16)     NOT NULL UNIQUE,
  nama              VARCHAR(100) NOT NULL,
  tempat_lahir      VARCHAR(100) NOT NULL,
  tanggal_lahir     DATE         NOT NULL,
  jenis_kelamin     ENUM('L','P') NOT NULL,
  alamat            VARCHAR(255) NOT NULL,
  rt                VARCHAR(5)   NOT NULL,
  rw                VARCHAR(5)   NOT NULL,
  dusun             VARCHAR(100) NOT NULL,
  pekerjaan         VARCHAR(100) NOT NULL,
  status_perkawinan ENUM('Belum Kawin','Kawin','Cerai Hidup','Cerai Mati') NOT NULL,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE surat_pengajuan (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  warga_id        INT  NOT NULL,
  jenis_surat     ENUM('domisili','usaha','tidak_mampu','pengantar_nikah') NOT NULL,
  keperluan       TEXT NOT NULL,
  nama_usaha      VARCHAR(150) NULL,
  alamat_usaha    VARCHAR(255) NULL,
  nama_pasangan   VARCHAR(100) NULL,
  status          ENUM('menunggu','diproses','selesai','ditolak') DEFAULT 'menunggu',
  catatan_admin   TEXT NULL,
  tanggal_ajuan   DATETIME DEFAULT CURRENT_TIMESTAMP,
  tanggal_selesai DATETIME NULL,
  diproses_oleh   INT NULL,
  FOREIGN KEY (warga_id) REFERENCES warga(id),
  FOREIGN KEY (diproses_oleh) REFERENCES users(id)
);

CREATE TABLE berita (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  judul       VARCHAR(255) NOT NULL,
  isi         TEXT         NOT NULL,
  penulis     VARCHAR(100) NOT NULL,
  diterbitkan TINYINT(1)   DEFAULT 0,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip_address   VARCHAR(45) NOT NULL,
  attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at)
);
