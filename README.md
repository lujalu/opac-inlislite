# OPAC InlisLite Portabel

Halaman PHP mandiri untuk menampilkan dan mencari katalog InlisLite melalui view atau tabel `v_opaconline2`. Aplikasi tidak membutuhkan framework, Composer, atau build step.

## Persyaratan

- PHP 7.4 atau lebih baru dengan ekstensi `pdo` dan `pdo_mysql`.
- MySQL 5.7+/MariaDB dengan akses baca ke sumber katalog.
- Web server Apache atau Nginx yang menjalankan PHP-FPM.
- View/tabel katalog bernama `v_opaconline2` dengan kolom berikut: `ID`, `NomorBarcode`, `Title`, `Author`, `Publikasi`, `ISBN`, `PhysicalDescription`, `Subject`, `CallNumber`, `Lokasi`, dan `Status`.

## Instalasi cepat

1. Salin `opac-inlislite.php` ke document root situs, atau ke subdirektori seperti `public_html/opac/`.
2. Edit konstanta konfigurasi di bagian atas file:

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_PORT = 3306;
   const DB_NAME = 'nama_database';
   const DB_USER = 'user_baca';
   const DB_PASS = 'password_database';
   ```

   Gunakan user database khusus baca dan batasi host sesuai kebutuhan. Jangan menyimpan password yang sama dengan password SSH atau panel.

3. Pastikan user database dapat membaca sumber katalog:

   ```sql
   GRANT SELECT ON nama_database.v_opaconline2 TO 'user_baca'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. Buka `https://domain-anda.example/opac-inlislite.php`. Uji pencarian judul, pengarang, ISBN, barcode, subjek, dan nomor panggil.

## Instalasi pada HestiaCP

1. Masuk ke HestiaCP, pilih **WEB**, lalu buka domain tujuan.
2. Upload file ke document root domain, misalnya:
   `/home/USER/web/DOMAIN/public_html/opac-inlislite.php`.
3. Pastikan versi PHP domain menyediakan `pdo_mysql` (PHP-FPM 8.2/8.3 umum dipakai pada Snowi).
4. Buat database dan user melalui menu **DB**, atau gunakan database InlisLite yang sudah ada. Jika database berada di host lain, ubah `DB_HOST` dan izinkan koneksi dari server web.
5. Uji URL melalui HTTPS. Jika halaman menampilkan pesan katalog belum dapat diakses, periksa kredensial, nama database, nama view, ekstensi `pdo_mysql`, dan log PHP-FPM/Apache.

## Instalasi dengan SCP

Dari komputer yang memiliki akses SSH ke server:

```bash
scp opac-inlislite.php pus@192.168.1.247:/tmp/
ssh pus@192.168.1.247
sudo install -o USER -g USER -m 0644 /tmp/opac-inlislite.php \
  /home/USER/web/DOMAIN/public_html/opac-inlislite.php
```

Ganti `USER`, `DOMAIN`, dan path sesuai domain HestiaCP. Jangan menaruh file pada direktori backup atau direktori konfigurasi.

## Perilaku aplikasi

- Semua parameter pencarian memakai prepared statement.
- Output katalog di-escape dengan `htmlspecialchars`.
- Pagination dibatasi 10–50 baris per halaman; default 20.
- Detail error koneksi tidak ditampilkan kepada pengunjung, tetapi dicatat melalui `error_log`.
- Nilai `OPAC_SOURCE` adalah konstanta kode, sehingga tidak dapat diubah melalui query string.

## Verifikasi setelah pemasangan

1. `GET /opac-inlislite.php` menampilkan halaman tanpa error PHP.
2. `GET /opac-inlislite.php?q=kata+kunci` menampilkan hasil yang sesuai.
3. Uji halaman berikutnya dan tombol reset pencarian.
4. Periksa bahwa HTML tidak memantulkan karakter `<`, `>`, atau tanda kutip mentah dari data katalog.
5. Jalankan pemeriksaan lokal:

   ```bash
   git diff --check
   php -l opac-inlislite.php
   ```

   Perintah lint membutuhkan binary PHP pada mesin penguji.

## Pemeliharaan

- Simpan backup file sebelum mengubah konstanta konfigurasi.
- Jika struktur kolom view berubah, sesuaikan query `SELECT`, filter pencarian, dan tabel hasil secara bersamaan.
- Gunakan HTTPS dan batasi akses database; file ini tidak menyediakan login admin.
