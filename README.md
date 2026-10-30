# OPAC InlisLite Portabel

Halaman PHP mandiri (`opac-inlislite.php`) untuk menampilkan dan mencari katalog InlisLite langsung dari tabel database standar (`collections`, `catalogs`, `locations`, dan `collectionstatus`). Aplikasi tidak membutuhkan framework, Composer, database view tambahan (`v_opaconline2`), atau build step.

## Persyaratan

- PHP 5.6 atau lebih baru dengan ekstensi `pdo` dan `pdo_mysql`.
- MySQL 5.7+/MariaDB dengan akses baca (`SELECT`) ke database InlisLite.
- Web server Apache atau Nginx yang menjalankan PHP-FPM.
- Database InlisLite standar dengan tabel `collections`, `catalogs`, `locations`, dan `collectionstatus`.

## Relasi Tabel & Query Langsung

Aplikasi melakukan `INNER JOIN` langsung ke empat tabel dasar InlisLite sehingga menghasilkan jumlah baris yang identik dengan view `v_opaconline2` (terverifikasi **239.391 baris** pada database production `inlis20117`):

```d2
direction: right
collections: "collections (col)\nID, NomorBarcode, CallNumber,\nCatalog_id, Location_id, Status_id"
catalogs: "catalogs (cat)\nID, Title, Author, Publikasi,\nISBN, PhysicalDescription, Subject"
locations: "locations (loc)\nID, Name (Lokasi)"
collectionstatus: "collectionstatus (stat)\nID, Name (Status)"

collections -> catalogs: "col.Catalog_id = cat.ID"
collections -> locations: "col.Location_id = loc.ID"
collections -> collectionstatus: "col.Status_id = stat.ID"
```

```sql
SELECT
  col.ID,
  col.NomorBarcode,
  cat.Title,
  cat.Author,
  cat.Publikasi,
  cat.ISBN,
  cat.PhysicalDescription,
  cat.Subject,
  col.CallNumber,
  loc.Name AS Lokasi,
  stat.Name AS Status
FROM collections AS col
INNER JOIN catalogs AS cat ON cat.ID = col.Catalog_id
INNER JOIN locations AS loc ON loc.ID = col.Location_id
INNER JOIN collectionstatus AS stat ON stat.ID = col.Status_id
WHERE 1=1
  -- filter opsional (:q1 s.d. :q6) untuk Title, Author, ISBN, Subject, NomorBarcode, CallNumber
ORDER BY cat.Title ASC
LIMIT :limit OFFSET :offset;
```

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

3. Pastikan user database dapat membaca keempat tabel katalog:

   ```sql
   GRANT SELECT ON nama_database.collections TO 'user_baca'@'localhost';
   GRANT SELECT ON nama_database.catalogs TO 'user_baca'@'localhost';
   GRANT SELECT ON nama_database.locations TO 'user_baca'@'localhost';
   GRANT SELECT ON nama_database.collectionstatus TO 'user_baca'@'localhost';
   FLUSH PRIVILEGES;
   ```

4. Buka `https://domain-anda.example/opac-inlislite.php`. Uji pencarian judul, pengarang, ISBN, barcode, subjek, dan nomor panggil.

## Instalasi pada CPANEL

1. Masuk ke Control Panel
2. Upload file ke document root domain, misalnya:
   `/home/USER/DOMAIN/public_html/opac-inlislite.php`.
3. Pastikan versi PHP domain menyediakan `pdo_mysql` (PHP-FPM 8.2/8.3 umum dipakai pada Snowi).
4. Buat database dan user melalui menu **DB**, atau gunakan database InlisLite yang sudah ada. Jika database berada di host lain, ubah `DB_HOST` dan izinkan koneksi dari server web.
5. Uji URL melalui HTTPS. Jika halaman menampilkan pesan katalog belum dapat diakses, periksa kredensial, nama database, izin `SELECT` pada keempat tabel, ekstensi `pdo_mysql`, dan log PHP-FPM/Apache.

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

- Semua parameter pencarian memakai prepared statement (`PDO::ATTR_EMULATE_PREPARES => false`).
- Output katalog di-escape dengan `htmlspecialchars`.
- Pagination dibatasi 10–50 baris per halaman; default 20.
- Detail error koneksi tidak ditampilkan kepada pengunjung, tetapi dicatat melalui `error_log`.
- Query memakai tabel standar InlisLite (`collections`, `catalogs`, `locations`, dan `collectionstatus`) tanpa membutuhkan database view.

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
- Jika skema tabel InlisLite berubah, sesuaikan query `SELECT`, relasi `INNER JOIN`, filter pencarian, dan tabel hasil secara bersamaan.
- Gunakan HTTPS dan batasi akses database (hanya `SELECT`); file ini tidak menyediakan login admin.
