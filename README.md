# OPAC InlisLite Portabel

Halaman PHP mandiri (`opac-inlislite.php`) untuk menampilkan dan mencari katalog InlisLite di InlisLite Versi 3.2 , langsung dari tabel database standar (`collections`, `catalogs`, `locations`, dan `collectionstatus`). Aplikasi tidak membutuhkan framework, Composer, database view tambahan (`v_opaconline2`), atau build step.

## Persyaratan

- PHP 5.6 atau lebih baru dengan ekstensi `pdo` dan `pdo_mysql`.
- MySQL 5.7+/MariaDB dengan akses baca (`SELECT`) ke database InlisLite.
- Web server Apache atau Nginx yang menjalankan PHP-FPM.
- Database InlisLite standar dengan tabel `collections`, `catalogs`, `locations`, dan `collectionstatus`.

## Relasi Tabel & Query Langsung

Aplikasi melakukan `INNER JOIN` langsung ke empat tabel dasar InlisLite sehingga menghasilkan jumlah baris yang identik dengan jumlah koleksi pustaka:

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

## Unduhan menurut versi PHP

File `opac-inlislite.php` yang sama dapat digunakan pada PHP 5.6, PHP 7, dan PHP 8; versi terpisah tidak diperlukan. Pilih baris sesuai versi PHP domain hosting:

| PHP hosting | Unduhan |
| --- | --- |
| PHP 5.6 | [Unduh OPAC untuk PHP 5.6](https://raw.githubusercontent.com/lujalu/opac-inlislite/main/opac-inlislite.php) |
| PHP 7.0–7.4 | [Unduh OPAC untuk PHP 7](https://raw.githubusercontent.com/lujalu/opac-inlislite/main/opac-inlislite.php) |
| PHP 8 | [Unduh OPAC untuk PHP 8](https://raw.githubusercontent.com/lujalu/opac-inlislite/main/opac-inlislite.php) |

Semua tautan mengarah ke file universal yang sama. Jika browser menampilkan kode, gunakan **Save link as / Simpan tautan sebagai** dan simpan dengan nama `opac-inlislite.php`. Edit konfigurasi database, lalu upload sesuai panduan instalasi.

Sintaks `isset()`, `Exception`, array, serta API PDO yang dipakai tersedia pada ketiga generasi PHP. Penggantian `??` untuk PHP 5.6 tetap kompatibel dengan PHP 7/8. `Exception` tetap menangkap kegagalan PDO pada PHP 7/8; objek `Error` tidak ditangkap. Nilai untuk `htmlspecialchars()` dikonversi ke string, termasuk nilai database `NULL`.

Pemeriksaan ulang grammar menggunakan `php-parser` 3.7.0 pada 2026-10-30 lolos untuk PHP 5.6, seluruh PHP 7.0–7.4, dan PHP 8.0–8.3 pada file kedua repositori. Ini verifikasi sintaks dan tinjauan API; uji eksekusi HTTP/database pada masing-masing versi PHP belum dilakukan. Pastikan `PDO`/`pdo_mysql` tersedia dan jalankan checklist setelah pemasangan memakai versi PHP domain yang dipilih.

## Instalasi cepat

1. Salin `opac-inlislite.php` ke document root situs, atau ke subdirektori seperti `public_html/opac/`.
2. Edit konstanta konfigurasi di bagian atas file:

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_PORT = 3306;
   const DB_NAME = 'nama_database';
   const DB_USER = 'opac_read';
   const DB_PASS = 'password_database';
   ```

   Gunakan user database khusus baca dan batasi host sesuai kebutuhan. Jangan menyimpan password yang sama dengan password SSH atau panel.

3. Buat user khusus dengan izin `SELECT` pada empat tabel sesuai [panduan user read-only](#user-database-read-only).

4. Buka `https://domain-anda.example/opac-inlislite.php`. Uji pencarian judul, pengarang, ISBN, barcode, subjek, dan nomor panggil.

## User database read-only

> **Sebaiknya buat user database khusus OPAC dengan akses Read (baca/`SELECT`) saja. Jangan memakai user database untuk layanan utama InlisLite, akun administrator, atau `root`.** Akun layanan utama biasanya membutuhkan izin menambah, mengubah, dan menghapus data; OPAC publik hanya membutuhkan pembacaan katalog. Pisahkan username dan password agar pembatasan akses OPAC tidak mengganggu layanan utama.

### Membuat user read-only melalui MySQL/MariaDB

1. Minta administrator database menjalankan langkah ini pada **server database**, melalui klien MySQL/MariaDB atau tab SQL phpMyAdmin dengan akun yang berwenang membuat user dan memberi izin. Contoh masuk melalui terminal:

   ```bash
   mysql -h HOST_DATABASE -P 3306 -u ADMIN_DATABASE -p
   ```

   Opsi `-p` meminta password secara interaktif. Jangan menuliskan password langsung pada perintah terminal.

2. Tentukan nama database dan host asal koneksi. Contoh berikut memakai database `inlislite`, user baru `opac_read`, serta server web `192.168.1.247`. Ganti sesuai instalasi Anda. Host pada `'opac_read'@'192.168.1.247'` adalah **alamat asal server web yang dilihat database**, bukan alamat server database atau browser pengunjung. Jika memakai NAT, gunakan alamat sumber yang terlihat setelah NAT. Hindari host `%` karena mengizinkan asal koneksi dari mana saja. Untuk database dan PHP pada mesin yang sama, gunakan akun host lokal yang sesuai dengan koneksi Anda (`localhost` atau `127.0.0.1`); keduanya tidak selalu cocok dengan akun yang sama. Seluruh perintah berikut harus memakai pasangan user dan host yang konsisten.

3. Buat akun **baru** dengan password kuat dan unik; ganti placeholder password sebelum menjalankan SQL:

   ```sql
   CREATE USER 'opac_read'@'192.168.1.247'
     IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_ACAK_UNIK';

   GRANT SELECT ON `inlislite`.`collections`
     TO 'opac_read'@'192.168.1.247';
   GRANT SELECT ON `inlislite`.`catalogs`
     TO 'opac_read'@'192.168.1.247';
   GRANT SELECT ON `inlislite`.`locations`
     TO 'opac_read'@'192.168.1.247';
   GRANT SELECT ON `inlislite`.`collectionstatus`
     TO 'opac_read'@'192.168.1.247';

   SHOW GRANTS FOR 'opac_read'@'192.168.1.247';
   ```

   Berikan `SELECT` hanya untuk empat tabel tersebut, bukan `ALL PRIVILEGES`, izin global `*.*`, atau seluruh database. Jangan berikan `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `DROP`, `ALTER`, `FILE`, maupun `GRANT OPTION`. `USAGE ON *.*` yang mungkin muncul pada hasil `SHOW GRANTS` berarti akun dapat dikenali, bukan izin membaca semua database. Akun baru seharusnya hanya memiliki `USAGE` dan empat grant `SELECT` di atas, tanpa role tambahan. Jika `CREATE USER` gagal karena akun sudah ada, jangan langsung melanjutkan: gunakan nama akun baru atau minta DBA mengaudit izin langsung dan role akun tersebut; menambahkan `SELECT` tidak mencabut izin lama. `FLUSH PRIVILEGES` tidak diperlukan setelah `CREATE USER` dan `GRANT`.

4. Dari **server web**, uji koneksi memakai akun baru dan password yang sama:

   ```bash
   mysql -h HOST_DATABASE -P 3306 -u opac_read -p inlislite
   ```

   Kemudian jalankan:

   ```sql
   SELECT CURRENT_USER();
   SHOW GRANTS;
   SELECT col.ID, cat.Title, loc.Name AS Lokasi, stat.Name AS Status
   FROM collections AS col
   INNER JOIN catalogs AS cat ON cat.ID = col.Catalog_id
   INNER JOIN locations AS loc ON loc.ID = col.Location_id
   INNER JOIN collectionstatus AS stat ON stat.ID = col.Status_id
   LIMIT 1;
   ```

   `CURRENT_USER()` menunjukkan akun user/host yang benar-benar dipilih MySQL/MariaDB. Query harus berhasil (hasil kosong tetap valid bila katalog belum berisi data). Periksa bahwa grant hanya mencakup empat tabel dan tidak ada role yang memberi izin lebih luas. Tidak perlu mencoba `INSERT`, `UPDATE`, atau `DELETE` pada database production untuk menguji pembatasan ini. Jika koneksi gagal, periksa kecocokan host akun, password, jaringan/firewall, dan konfigurasi koneksi database. Untuk database remote, batasi firewall port database hanya bagi server web yang memerlukan akses.

5. Isi konfigurasi OPAC dengan `DB_USER = 'opac_read'`, password baru pada `DB_PASS`, nama database pada `DB_NAME`, dan alamat **server database** pada `DB_HOST`. Simpan kredensial hanya pada konfigurasi server; jangan commit password asli ke repositori. Setelah pemasangan, uji daftar katalog, pencarian, dan pagination melalui browser. Akun layanan utama InlisLite tetap memakai kredensialnya sendiri.

### Membuat user melalui panel hosting/phpMyAdmin

Jika tidak memiliki hak `CREATE USER`, gunakan menu **MySQL Databases / Database Users** pada panel hosting atau minta penyedia hosting/DBA membuat akun khusus. Pada cPanel, buat user baru, hubungkan ke database InlisLite yang sudah ada, lalu pilih hanya **SELECT** pada **Manage User Privileges**; jangan pilih **ALL PRIVILEGES**. Nama database/user dapat diberi prefix akun hosting: gunakan nama lengkap dari panel pada konfigurasi PHP.

Panel yang hanya menyediakan izin tingkat database akan memberi `SELECT` pada seluruh tabel database tersebut, sehingga cakupannya lebih luas daripada kebutuhan empat tabel OPAC. Untuk pembatasan empat tabel, minta DBA menjalankan grant per tabel di atas atau gunakan pengaturan izin tingkat tabel jika tersedia. Di phpMyAdmin dengan hak administrator, gunakan **User accounts → Add user account**, tentukan host, jangan centang hak global, lalu atur izin `SELECT` per tabel. Nama menu dapat berbeda menurut versi panel. Akhiri dengan pemeriksaan `SHOW GRANTS` dan uji koneksi dari server web seperti langkah di atas; jangan mengubah hak akun layanan utama.

## Instalasi pada CPANEL

1. Masuk ke Control Panel
2. Upload file ke document root domain, misalnya:
   `/home/USER/DOMAIN/public_html/opac-inlislite.php`.
3. Pastikan versi PHP domain menyediakan `pdo_mysql` (PHP-FPM 8.2/8.3).
4. Buat database dan user melalui menu **DB**, atau gunakan database InlisLite yang sudah ada. Jika database berada di host lain, ubah `DB_HOST` dan izinkan koneksi dari server web.
5. Uji URL melalui HTTPS. Jika halaman menampilkan pesan katalog belum dapat diakses, periksa kredensial, nama database, izin `SELECT` pada keempat tabel, ekstensi `pdo_mysql`, dan log PHP-FPM/Apache.

## Instalasi dengan SCP

Dari komputer yang memiliki akses SSH ke server:

```bash
scp opac-inlislite.php user@ipserver:/tmp/
ssh user@ipserver
sudo install -o USER -g USER -m 0644 /tmp/opac-inlislite.php \
  /home/USER/web/DOMAIN/public_html/opac-inlislite.php
```

Ganti `USER`, `DOMAIN`, dan path sesuai domain dan aturan di webserver. Jangan menaruh file pada direktori backup atau direktori konfigurasi.

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

## Kompatibilitas PHP 5.6 dan Status Verifikasi

Pembaruan 2026-10-30 (commit `1d1939b`) menyesuaikan file utama untuk PHP 5.6:

- Operator `??` diganti dengan ternary `isset()` pada parameter GET dan tautan pagination.
- `catch (Throwable $e)` diganti dengan `catch (Exception $e)` untuk menangani exception PDO. Pada PHP 7+, objek `Error` tidak ditangkap oleh blok ini.

Pemeriksaan grammar menggunakan `php-parser` 3.7.0 berhasil untuk target PHP 5.6, 7.4, dan 8.3; script sebelum perbaikan gagal pada grammar 5.6. `git diff --check` pada perubahan kompatibilitas juga berhasil. Pemeriksaan grammar tidak menjalankan script: runtime/database PHP 5.6 belum diuji karena interpreter tersebut tidak tersedia. Perbaikan sudah dipush ke `origin/main`, tetapi belum dideploy ke server pada tindak lanjut ini.

Untuk verifikasi di hosting, jalankan `php -v`, `php -m`, dan `php -l opac-inlislite.php` dengan binary PHP 5.6. Pastikan `PDO` dan `pdo_mysql` aktif serta versi PHP-FPM domain sesuai; versi CLI dapat berbeda dari domain. Kemudian jalankan checklist HTTP pada bagian verifikasi setelah pemasangan, termasuk pencarian dan pagination.
