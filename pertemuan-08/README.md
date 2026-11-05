# pertemuan-08
PANDUAN FITUR MANAJEMEN PENYEWA

URL: http://localhost/dpwl-2522500031/admin/penyewa

Fitur:

Menu Manajemen Penyewa pada sidebar admin.
Daftar data penyewa (tanpa menampilkan kolom password).
Tambah, ubah, dan hapus dengan pola MVC.
Query MySQLi menggunakan prepared statement.
Password baru disimpan dengan password_hash(PASSWORD_DEFAULT).
Validasi username, NIK 16 digit, nama, nomor HP, dan alamat.
Upload foto KTP dan SIM A: JPEG/PNG/WEBP, maksimal 5 MB.
Nama file upload dibuat acak; MIME diperiksa dengan Fileinfo dan getimagesize.
Foto lama dihapus setelah data berhasil diperbarui/dihapus.
Pemeriksaan sesi admin, token CSRF, dan redirect setelah aksi berhasil.
Folder upload memiliki aturan .htaccess untuk menolak eksekusi skrip.
PENGUJIAN DI LARAGON:

Ekstrak folder proyek ke C:\laragon\www\dpwl-2522500031.
Pastikan database db_dpwl_seldelio026 dan tabel t_penyewa sudah tersedia.
Jalankan Apache dan MySQL, lalu login sebagai admin.
Buka /admin/penyewa.
Uji tambah data dengan foto dan tanpa foto.
Periksa kolom sandi di database: nilainya harus berupa hash password, bukan teks asli.
Uji ubah data tanpa mengganti foto, lalu dengan mengganti foto.
Uji NIK tidak valid, username duplikat, password terlalu pendek, dan file bukan gambar.
Uji hapus data yang tidak memiliki transaksi terkait.
Periksa kembali Manajemen Kendaraan, login penyewa, profil, dan transaksi.
CATATAN: Pengujian sintaks PHP dilakukan pada berkas proyek. Pengujian integrasi MySQL dan alur browser tetap perlu dijalankan pada lingkungan Laragon dengan database milik pengguna.