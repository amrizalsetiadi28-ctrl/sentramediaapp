# Sistem Informasi Persediaan dan Penjualan Buku — CV. Sentra Media

Aplikasi web sesuai Bab III Proposal Capstone Project (PHP + MySQL, metode Waterfall).
Mengimplementasikan seluruh kebutuhan fungsional KF-01 s.d. KF-08.

## Teknologi
- Frontend: HTML, CSS (Bootstrap 5), JavaScript
- Backend: PHP (native, PDO)
- Database: MySQL
- Tools: XAMPP, VS Code

## Cara Menjalankan (XAMPP)

1. **Salin folder proyek**
   Salin seluruh folder `sentra-media` ini ke dalam `C:\xampp\htdocs\` (Windows)
   atau `/Applications/XAMPP/htdocs/` (Mac). Boleh diganti namanya, aplikasi
   akan tetap berjalan normal.

2. **Jalankan Apache dan MySQL**
   Buka XAMPP Control Panel, klik **Start** pada Apache dan MySQL.

3. **Buat database**
   Buka `http://localhost/phpmyadmin`, buat tab **Import**, lalu import file
   `database.sql` yang ada di folder ini. Database `sentra_media` beserta
   7 tabel dan beberapa data contoh (supplier & buku) akan otomatis dibuat.

4. **Buat akun admin**
   Buka `http://localhost/sentra-media/setup_admin.php` di browser, lalu klik
   tombol **Buat Akun Admin Default**. Ini akan membuat akun:
   - Username: `admin`
   - Password: `admin123`

   Password disimpan dalam bentuk hash (bukan teks biasa) sesuai kebutuhan
   non-fungsional pada proposal. **Setelah berhasil, hapus file
   `setup_admin.php` dari server** untuk keamanan.

5. **Login**
   Buka `http://localhost/sentra-media/login.php` dan masuk menggunakan
   akun di atas.

   Jika nama folder proyek Anda bukan `sentra-media`, sesuaikan URL-nya
   dengan nama folder yang Anda pakai — seluruh link internal aplikasi
   sudah otomatis menyesuaikan diri (tidak perlu mengubah kode apa pun).

6. **(Opsional) Sesuaikan koneksi database**
   Jika username/password MySQL Anda berbeda dari bawaan XAMPP, ubah di
   `config/database.php`.

## Struktur Fitur

| Halaman | Kebutuhan Fungsional |
|---|---|
| Login (`login.php`) | KF-01 |
| Dashboard (`dashboard.php`) | KF-06, KF-08 |
| Data Buku (`buku/`) | KF-02 |
| Data Supplier (`supplier/`) | KF-03 |
| Stok Masuk (`stok_masuk/`) | KF-04 |
| Penjualan (`penjualan/`) | KF-05 |
| Laporan (`laporan/`) | KF-07 |

## Poin Penting Implementasi

- **Stok otomatis**: stok buku bertambah otomatis saat stok masuk disimpan,
  dan berkurang otomatis saat penjualan disimpan. Kedua proses ini dan
  penyimpanan transaksinya dibungkus dalam satu **database transaction**
  (`beginTransaction` / `commit` / `rollBack`) sehingga data stok selalu
  konsisten dengan data transaksi, sesuai kebutuhan non-fungsional pada
  proposal.
- **Validasi stok penjualan**: sebelum transaksi penjualan disimpan, sistem
  mengunci dan memeriksa stok setiap buku (`SELECT ... FOR UPDATE`).
  Transaksi otomatis ditolak dengan pesan yang jelas apabila jumlah yang
  diminta melebihi stok yang tersedia — sesuai skenario pengujian pada
  Tabel 3.12.
- **Harga tidak bisa dimanipulasi dari sisi pengguna**: harga jual pada
  transaksi penjualan selalu diambil ulang dari database di sisi server,
  bukan dari input formulir, sehingga tidak bisa diubah lewat developer
  tools di browser.
- **Keamanan dasar**: password di-hash dengan `password_hash` bawaan PHP,
  seluruh query menggunakan prepared statement (PDO) untuk mencegah SQL
  Injection, dan semua output ditampilkan dengan `htmlspecialchars` untuk
  mencegah XSS.
- **Indikator stok minimum** (KF-06): buku dengan stok ≤ stok minimum
  otomatis ditandai "Perlu Dipesan Ulang" di Data Buku, Dashboard, dan
  Laporan Persediaan.
- **Dashboard** (KF-08): menampilkan buku terlaris, grafik perkembangan
  penjualan 7 hari terakhir, dan daftar buku yang perlu dipesan ulang.
- **Laporan** (KF-07): laporan penjualan per periode (dengan ringkasan per
  buku) dan laporan persediaan (stok terkini + riwayat stok masuk), kedua
  laporan bisa dicetak langsung dari browser (`Ctrl+P` / tombol Cetak).

## Pengujian (Black Box)

Seluruh skenario pada Tabel 3.12 proposal sudah diakomodasi oleh validasi
di aplikasi ini, antara lain:
- Login gagal saat password salah / kolom kosong.
- Akses halaman tanpa login otomatis diarahkan ke halaman login.
- Tambah buku gagal jika kode buku sudah ada atau kolom wajib kosong.
- Stok masuk menambah stok sesuai jumlah yang dicatat.
- Penjualan ditolak jika jumlah melebihi stok, dan berhasil (stok berkurang,
  total benar) jika jumlah mencukupi.
- Buku yang stoknya mencapai batas minimum otomatis tampil di daftar
  "perlu dipesan ulang".
- Laporan penjualan menampilkan transaksi sesuai periode yang dipilih.

Silakan lakukan pengujian ini secara manual dan catat hasilnya pada Tabel
3.12/Bab IV proposal Anda (kolom Hasil Aktual dan Status).
