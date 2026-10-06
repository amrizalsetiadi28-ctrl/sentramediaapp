-- =========================================================
-- Database: sentra_media
-- Sistem Informasi Persediaan dan Penjualan Buku
-- CV. Sentra Media
-- =========================================================

CREATE DATABASE IF NOT EXISTS sentra_media
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sentra_media;

-- ---------------------------------------------------------
-- Tabel admin
-- ---------------------------------------------------------
CREATE TABLE admin (
  id_admin INT AUTO_INCREMENT PRIMARY KEY,
  nama_lengkap VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel supplier
-- ---------------------------------------------------------
CREATE TABLE supplier (
  id_supplier INT AUTO_INCREMENT PRIMARY KEY,
  nama_supplier VARCHAR(100) NOT NULL,
  alamat TEXT,
  no_telepon VARCHAR(20)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel buku
-- ---------------------------------------------------------
CREATE TABLE buku (
  id_buku INT AUTO_INCREMENT PRIMARY KEY,
  kode_buku VARCHAR(20) NOT NULL UNIQUE,
  judul VARCHAR(200) NOT NULL,
  penulis VARCHAR(100),
  penerbit VARCHAR(100),
  harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  stok_minimum INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel stok_masuk (header)
-- ---------------------------------------------------------
CREATE TABLE stok_masuk (
  id_stok_masuk INT AUTO_INCREMENT PRIMARY KEY,
  no_faktur VARCHAR(30),
  tanggal_masuk DATE NOT NULL,
  id_supplier INT NOT NULL,
  id_admin INT NOT NULL,
  keterangan TEXT,
  FOREIGN KEY (id_supplier) REFERENCES supplier(id_supplier),
  FOREIGN KEY (id_admin) REFERENCES admin(id_admin)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel detail_stok_masuk
-- ---------------------------------------------------------
CREATE TABLE detail_stok_masuk (
  id_detail_masuk INT AUTO_INCREMENT PRIMARY KEY,
  id_stok_masuk INT NOT NULL,
  id_buku INT NOT NULL,
  jumlah INT NOT NULL,
  harga_beli DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (id_stok_masuk) REFERENCES stok_masuk(id_stok_masuk) ON DELETE CASCADE,
  FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel penjualan (header)
-- ---------------------------------------------------------
CREATE TABLE penjualan (
  id_penjualan INT AUTO_INCREMENT PRIMARY KEY,
  no_transaksi VARCHAR(20) NOT NULL UNIQUE,
  tanggal_transaksi DATETIME NOT NULL,
  nama_pembeli VARCHAR(100),
  metode_pembayaran VARCHAR(20),
  total_harga DECIMAL(14,2) NOT NULL DEFAULT 0,
  id_admin INT NOT NULL,
  FOREIGN KEY (id_admin) REFERENCES admin(id_admin)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel detail_penjualan
-- ---------------------------------------------------------
CREATE TABLE detail_penjualan (
  id_detail_jual INT AUTO_INCREMENT PRIMARY KEY,
  id_penjualan INT NOT NULL,
  id_buku INT NOT NULL,
  jumlah INT NOT NULL,
  harga_satuan DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON DELETE CASCADE,
  FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Data contoh (opsional, boleh dihapus)
-- ---------------------------------------------------------
INSERT INTO supplier (nama_supplier, alamat, no_telepon) VALUES
('Penerbit Erlangga', 'Jl. H. Baping Raya No. 100, Jakarta Timur', '0215678901'),
('Penerbit Gramedia', 'Jl. Palmerah Barat No. 33-37, Jakarta', '0215483008');

INSERT INTO buku (kode_buku, judul, penulis, penerbit, harga_jual, stok, stok_minimum) VALUES
('BK-001', 'Belajar PHP dan MySQL untuk Pemula', 'Andi Wijaya', 'Penerbit Erlangga', 85000, 12, 5),
('BK-002', 'Struktur Data dan Algoritma', 'Budi Santoso', 'Penerbit Gramedia', 95000, 8, 5),
('BK-003', 'Basis Data Relasional', 'Citra Dewi', 'Penerbit Erlangga', 78000, 3, 5);

-- Catatan: akun admin TIDAK dibuat lewat file ini karena kolom password
-- harus berisi hash bcrypt yang dihasilkan oleh PHP (password_hash), bukan
-- teks biasa. Setelah database ini diimpor, buka setup_admin.php sekali
-- lewat browser untuk membuat akun admin default secara otomatis.
