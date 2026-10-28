<?php
/**
 * OPAC InlisLite portabel (PHP 7.4+ / MySQL 5.7+).
 * Upload file ini ke hosting, lalu ubah konfigurasi di bawah.
 */
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'inlislite';
const DB_USER = 'pustaka';
const DB_PASS = 'GANTI_PASSWORD_DATABASE';
const DB_CHARSET = 'utf8mb4';
const OPAC_SOURCE = 'v_opaconline2'; // nama view/tabel katalog yang tersedia di InlisLite
const PAGE_SIZE = 20;

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function db() {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(10, (int)($_GET['per_page'] ?? PAGE_SIZE)));
$offset = ($page - 1) * $perPage;
$rows = [];
$total = 0;
$error = null;

try {
    // Kolom tetap dan sumber divalidasi di kode; nilai pengguna hanya masuk sebagai parameter.
    $where = '';
    $params = [];
    if ($q !== '') {
        // Satu keyword mencari judul, pengarang, ISBN, subjek, barcode, dan nomor panggil.
        $where = ' WHERE (Title LIKE :q1 OR Author LIKE :q2 OR ISBN LIKE :q3 OR Subject LIKE :q4 OR NomorBarcode LIKE :q5 OR CallNumber LIKE :q6)';
        $needle = '%' . $q . '%';
        for ($i = 1; $i <= 6; $i++) $params[':q' . $i] = $needle;
    }
    $pdo = db();
    $count = $pdo->prepare('SELECT COUNT(*) FROM `' . OPAC_SOURCE . '`' . $where);
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $sql = 'SELECT ID, NomorBarcode, Title, Author, Publikasi, ISBN, PhysicalDescription, Subject, CallNumber, Lokasi, Status FROM `' . OPAC_SOURCE . '`' . $where . ' ORDER BY Title ASC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    // Detail koneksi tidak ditampilkan ke publik.
    $error = 'Katalog belum dapat diakses. Periksa konfigurasi database atau hubungi administrator.';
    error_log('OPAC InlisLite: ' . $e->getMessage());
}
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages && $total > 0) $page = $totalPages;
function pageUrl($page) { return '?' . http_build_query(['q' => $_GET['q'] ?? '', 'per_page' => $_GET['per_page'] ?? PAGE_SIZE, 'page' => $page]); }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Katalog Online</title>
<style>
:root{--primary:#1261a0;--ink:#203040;--muted:#667788;--line:#dbe3ea;--bg:#f4f7fa}*{box-sizing:border-box}body{margin:0;background:var(--bg);font:15px/1.5 Arial,sans-serif;color:var(--ink)}.wrap{max-width:1180px;margin:0 auto;padding:24px 16px}.hero{background:linear-gradient(135deg,#125d96,#168b9e);color:#fff;padding:28px;border-radius:12px 12px 0 0}.hero h1{margin:0 0 5px;font-size:clamp(24px,4vw,36px)}.hero p{margin:0;opacity:.9}.search{background:#fff;padding:18px;border:1px solid var(--line);border-top:0;box-shadow:0 3px 10px #16324d12}.search form{display:flex;gap:10px}.search input{flex:1;padding:12px;border:1px solid #bccbd6;border-radius:6px;font-size:16px}.btn{border:0;border-radius:6px;padding:11px 20px;background:var(--primary);color:#fff;font-weight:bold;cursor:pointer}.meta{color:var(--muted);margin:20px 0 10px}.notice{padding:14px;background:#fff2d9;border:1px solid #e6c77b;border-radius:6px}.tablebox{overflow:auto;background:#fff;border:1px solid var(--line);border-radius:7px}.results{width:100%;border-collapse:collapse;min-width:900px}.results th,.results td{padding:11px 12px;text-align:left;border-bottom:1px solid var(--line);vertical-align:top}.results th{background:#edf3f7;color:#38536a;font-size:13px;white-space:nowrap}.results tr:hover{background:#f8fbfd}.title{font-weight:bold;color:#164e7c}.muted{color:var(--muted);font-size:13px}.pager{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:16px 0}.pager a{padding:8px 12px;background:#fff;border:1px solid var(--line);border-radius:5px;color:var(--primary);text-decoration:none}.pager a.disabled{pointer-events:none;opacity:.45}@media(max-width:600px){.wrap{padding:10px}.hero{padding:20px}.search form{flex-direction:column}.btn{width:100%}}
</style>
</head>
<body><main class="wrap"><section class="hero"><h1>Katalog Online</h1><p>Cari koleksi buku dan bahan pustaka</p></section>
<section class="search"><form method="get"><input name="q" value="<?=h($q)?>" placeholder="Cari judul, pengarang, ISBN, subjek, atau barcode…" autocomplete="off"><input type="hidden" name="per_page" value="<?=h($perPage)?>"><button class="btn" type="submit">Cari</button><?php if ($q !== ''): ?><a class="btn" href="?" style="text-decoration:none;background:#64748b">Reset</a><?php endif; ?></form></section>
<?php if ($error): ?><p class="notice"><?=h($error)?></p><?php else: ?><div class="meta"><?php if ($q !== ''): ?>Hasil untuk <strong><?=h($q)?></strong>: <?php endif; ?><strong><?=number_format($total,0,',','.')?></strong> koleksi</div>
<div class="tablebox"><table class="results"><thead><tr><th>Judul</th><th>Pengarang</th><th>Publikasi</th><th>ISBN</th><th>Deskripsi Fisik</th><th>Subjek</th><th>No. Panggil</th><th>Lokasi</th><th>Status</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><div class="title"><?=h($row['Title'])?></div><div class="muted">Barcode: <?=h($row['NomorBarcode'])?></div></td><td><?=h($row['Author'])?></td><td><?=h($row['Publikasi'])?></td><td><?=h($row['ISBN'])?></td><td><?=h($row['PhysicalDescription'])?></td><td><?=h($row['Subject'])?></td><td><?=h($row['CallNumber'])?></td><td><?=h($row['Lokasi'])?></td><td><?=h($row['Status'])?></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="9" style="text-align:center;padding:28px">Tidak ada koleksi yang cocok.</td></tr><?php endif; ?></tbody></table></div>
<?php if ($totalPages > 1): ?><nav class="pager"><a class="<?=($page<=1?'disabled':'')?>" href="<?=h(pageUrl(max(1,$page-1)))?>">&laquo; Sebelumnya</a><span>Halaman <?=$page?> dari <?=$totalPages?></span><a class="<?=($page>=$totalPages?'disabled':'')?>" href="<?=h(pageUrl(min($totalPages,$page+1)))?>">Berikutnya &raquo;</a></nav><?php endif; ?><?php endif; ?></main></body></html>
