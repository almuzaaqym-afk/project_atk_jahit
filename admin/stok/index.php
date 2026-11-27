<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}
require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
cekAkses('stok');
$pesan = "";
$error = "";
$search = trim($_GET['search'] ?? '');
$search_escaped = mysqli_real_escape_string($conn, $search);
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'berhasil':
            $pesan = "Stok berhasil diperbarui.";
            break;
        case 'tidak_valid':
            $error = "Data stok tidak valid.";
            break;
        case 'gagal':
            $error = "Gagal memperbarui stok.";
            break;
        case 'tidak_ditemukan':
            $error = "Produk atau varian tidak ditemukan.";
            break;
    }
}
$where_search = "";
if ($search !== '') {
    $where_search = "WHERE (
        produk.nama_produk LIKE '%$search_escaped%'
        OR kategori.nama_kategori LIKE '%$search_escaped%'
        OR lokasi_rak.nama_lokasi LIKE '%$search_escaped%'
        OR produk.lokasi_rak LIKE '%$search_escaped%'
        OR EXISTS (
            SELECT 1
            FROM produk_varian pv_search
            LEFT JOIN lokasi_rak lv_search
                ON lv_search.kode_rak = pv_search.lokasi_rak
            WHERE pv_search.id_produk = produk.id_produk
            AND (
                pv_search.nama_varian LIKE '%$search_escaped%'
                OR pv_search.lokasi_rak LIKE '%$search_escaped%'
                OR lv_search.nama_lokasi LIKE '%$search_escaped%'
            )
        )
    )";
}
$query = mysqli_query(
    $conn,
    "SELECT
        produk.id_produk,
        produk.nama_produk,
        produk.gambar,
        produk.stok,
        produk.stok_minimum,
        produk.lokasi_rak,
        lokasi_rak.nama_lokasi,
        kategori.nama_kategori
    FROM produk
    LEFT JOIN kategori
        ON produk.id_kategori = kategori.id_kategori
    LEFT JOIN lokasi_rak
        ON lokasi_rak.kode_rak = produk.lokasi_rak
    $where_search
    ORDER BY produk.nama_produk ASC"
);
$data_varian_produk = [];
$query_varian = mysqli_query(
    $conn,
    "SELECT
        v.id_varian,
        v.id_produk,
        v.nama_varian,
        v.harga,
        v.stok,
        v.stok_minimum,
        v.lokasi_rak,
        v.status,
        l.nama_lokasi,
        (
            SELECT pvg.nama_gambar
            FROM produk_varian_gambar pvg
            WHERE pvg.id_varian = v.id_varian
            ORDER BY pvg.urutan ASC, pvg.id_gambar_varian ASC
            LIMIT 1
        ) AS gambar_varian
    FROM produk_varian v
    LEFT JOIN lokasi_rak l
        ON l.kode_rak = v.lokasi_rak
    ORDER BY v.id_produk ASC, v.id_varian ASC"
);
if ($query_varian) {
    while ($variant = mysqli_fetch_assoc($query_varian)) {
        $id_produk_varian = (int)$variant['id_produk'];
        if (!isset($data_varian_produk[$id_produk_varian])) {
            $data_varian_produk[$id_produk_varian] = [];
        }
        $data_varian_produk[$id_produk_varian][] = [
            'id_varian' => (int)$variant['id_varian'],
            'nama_varian' => $variant['nama_varian'],
            'harga' => (float)$variant['harga'],
            'stok' => (int)$variant['stok'],
            'stok_minimum' => (int)$variant['stok_minimum'],
            'lokasi_rak' => $variant['lokasi_rak'] ?? '',
            'nama_lokasi' => $variant['nama_lokasi'] ?? '',
            'status' => $variant['status'] ?? 'Aktif',
            'gambar' => $variant['gambar_varian'] ?? ''
        ];
    }
}
$total_produk = 0;
$total_stok = 0;
$total_menipis = 0;
$total_habis = 0;
$data_produk = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $id_produk_row = (int)$row['id_produk'];
        $varian_produk = $data_varian_produk[$id_produk_row] ?? [];
        $memiliki_varian = count($varian_produk) > 0;
        if ($memiliki_varian) {
            $stok = 0;
            $stok_minimum = 0;
            foreach ($varian_produk as $varian) {
                if ($varian['status'] === 'Aktif') {
                    $stok += (int)$varian['stok'];
                    $stok_minimum += (int)$varian['stok_minimum'];
                }
            }
        } else {
            $stok = (int)$row['stok'];
            $stok_minimum = (int)$row['stok_minimum'];
        }
        $total_produk++;
        $total_stok += $stok;
        if ($stok <= 0) {
            $status = "Habis";
            $status_class = "stok-habis";
            $total_habis++;
        } elseif ($stok <= $stok_minimum) {
            $status = "Menipis";
            $status_class = "stok-menipis";
            $total_menipis++;
        } else {
            $status = "Aman";
            $status_class = "stok-aman";
        }
        $row['stok_tampil'] = $stok;
        $row['stok_minimum_tampil'] = $stok_minimum;
        $row['memiliki_varian'] = $memiliki_varian;
        $row['varian'] = $varian_produk;
        $row['status'] = $status;
        $row['status_class'] = $status_class;
        $data_produk[] = $row;
    }
}
/* URUTAN: HABIS → MENIPIS → AMAN */
usort($data_produk, function ($a, $b) {
    $urutan_status = [
        'Habis' => 1,
        'Menipis' => 2,
        'Aman' => 3
    ];
    $status_a = $urutan_status[$a['status']] ?? 99;
    $status_b = $urutan_status[$b['status']] ?? 99;
    if ($status_a !== $status_b) {
        return $status_a <=> $status_b;
    }
    return strcasecmp(
        $a['nama_produk'] ?? '',
        $b['nama_produk'] ?? ''
    );
});
$rak_stok_result = mysqli_query(
    $conn,
    "SELECT
        kode_rak,
        nama_lokasi
     FROM lokasi_rak
     ORDER BY kode_rak ASC"
);
$rak_js_data = [];
if ($rak_stok_result) {
    while ($rak_js = mysqli_fetch_assoc($rak_stok_result)) {
        $rak_js_data[] = [
            'kode_rak' => $rak_js['kode_rak'],
            'nama_lokasi' => $rak_js['nama_lokasi']
        ];
    }
}
/* BARIS TABEL STOK UNTUK RENDER AWAL DAN SINKRONISASI */
ob_start();
if (empty($data_produk)):
?>
<tr>
    <td colspan="8" class="stok-empty">
        <?= $search !== '' ? 'Produk yang dicari tidak ditemukan.' : 'Belum ada data produk.'; ?>
    </td>
</tr>
<?php else: ?>
    <?php $no = 1; ?>
    <?php foreach ($data_produk as $produk): ?>
        <?php
        $stok_modal_data = [
            'id_produk' => (int)$produk['id_produk'],
            'nama_produk' => $produk['nama_produk'],
            'gambar' => $produk['gambar'] ?? '',
            'nama_kategori' => $produk['nama_kategori'] ?? '',
            'memiliki_varian' => $produk['memiliki_varian'],
            'stok' => (int)$produk['stok_tampil'],
            'stok_minimum' => (int)$produk['stok_minimum_tampil'],
            'lokasi_rak' => $produk['lokasi_rak'] ?? '',
            'nama_lokasi' => $produk['nama_lokasi'] ?? '',
            'varian' => $produk['varian']
        ];
        ?>
        <tr>
            <td>
                <span class="stok-no"><?= $no++; ?></span>
            </td>
            <td>
                <span class="stok-product-name">
                    <?= htmlspecialchars($produk['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <?php if ($produk['memiliki_varian']): ?>
                    <div style="margin-top:4px;color:#7890a4;font-size:11px">
                        <?= count($produk['varian']); ?> varian
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <span class="stok-category">
                    <?= htmlspecialchars($produk['nama_kategori'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </td>
            <td>
                <?php if ($produk['memiliki_varian']): ?>
                    <span class="stok-rack">Per varian</span>
                <?php elseif (!empty($produk['nama_lokasi'])): ?>
                    <span class="stok-rack">
                        <?= htmlspecialchars($produk['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php elseif (!empty($produk['lokasi_rak'])): ?>
                    <span class="stok-rack">
                        <?= htmlspecialchars($produk['lokasi_rak'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php else: ?>
                                        <span class="stok-rack">-</span>
                <?php endif; ?>
            </td>
            <td>
                <span class="stok-number">
                    <?= number_format((int)$produk['stok_tampil']); ?>
                </span>
            </td>
            <td>
                <span class="stok-minimum">
                    <?= number_format((int)$produk['stok_minimum_tampil']); ?>
                </span>
            </td>
            <td>
                <span class="<?= htmlspecialchars($produk['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?= htmlspecialchars($produk['status'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </td>
            <td>
                <button
                    type="button"
                    class="btn-kelola-stok"
                    onclick='bukaModalStok(<?= json_encode($stok_modal_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'
                >
                    Kelola
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
<?php
$stok_tbody_html = ob_get_clean();
$stok_tbody_hash = hash('sha256', $stok_tbody_html);
/* MODE SINKRONISASI JSON: TIDAK MEMUAT NAVBAR / SIDEBAR */
if (isset($_GET['_live_stok'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_produk' => number_format($total_produk),
            'total_stok' => number_format($total_stok),
            'total_menipis' => number_format($total_menipis),
            'total_habis' => number_format($total_habis)
        ],
        'rows_html' => $stok_tbody_html,
        'rows_hash' => $stok_tbody_hash
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stok - Toko ATK &amp; Alat Jahit</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
.stok-page{max-width:1500px}
.stok-page-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:28px}
.stok-page-header h1{margin:0 0 7px;color:#102f4b;font-size:28px;font-weight:700}
.stok-page-header p{margin:0;color:#7890a4;font-size:14px}
.stok-alert{padding:13px 16px;border-radius:9px;margin-bottom:22px;font-size:13px;opacity:1;transform:translateY(0);transition:opacity .35s ease,transform .35s ease}
.stok-alert.is-hiding{opacity:0;transform:translateY(-8px)}
.stok-alert.success{background:#eaf7ee;color:#218739;border:1px solid #c9e8d1}
.stok-alert.error{background:#fff1f2;color:#b42318;border:1px solid #fecdd3}
.stok-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:25px}
.stok-summary-card{background:#fff;border:1px solid #e0e7ed;border-radius:14px;padding:22px;box-shadow:0 4px 15px rgba(16,47,75,.04)}
.stok-summary-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:15px}
.stok-summary-label{color:#7890a4;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.3px}
.stok-summary-icon{width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:9px;background:#edf3f7;color:#102f4b;font-size:14px;font-weight:bold}
.stok-summary-card strong{display:block;color:#102f4b;font-size:28px;line-height:1}
.stok-table-card{background:#fff;border:1px solid #e0e7ed;border-radius:14px;overflow:hidden;box-shadow:0 4px 15px rgba(16,47,75,.04)}
.stok-table-header{padding:20px 24px;border-bottom:1px solid #e6edf2;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.stok-table-header h2{margin:0 0 5px;color:#102f4b;font-size:18px;font-weight:600}
.stok-table-header p{margin:0;color:#7890a4;font-size:13px}
.stok-search-form{display:flex;align-items:center;gap:7px}
.stok-search-input{width:260px;height:40px;padding:0 13px;border:1px solid #dce5ec;border-radius:8px;outline:none;color:#334f66;font-size:13px;background:#fff;box-sizing:border-box}
.stok-search-input:focus{border-color:#315b78;box-shadow:0 0 0 3px rgba(49,91,120,.08)}
.btn-search-stok,.btn-clear-stok{height:40px;padding:0 13px;border-radius:8px;border:none;font-size:12px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer;box-sizing:border-box}
.btn-search-stok{background:#102f4b;color:#fff}
.btn-search-stok:hover{background:#245b83}
.btn-clear-stok{background:#eef3f7;color:#506d84}
.btn-clear-stok:hover{background:#e3ebf0}
.search-result-info{padding:13px 24px;background:#f8fafc;border-bottom:1px solid #e6edf2;color:#7890a4;font-size:12px}
.search-result-info strong{color:#102f4b}
.stok-table-wrapper{width:100%;overflow-x:auto}
.stok-table{width:100%;min-width:1000px;border-collapse:collapse}
.stok-table th{padding:13px 18px;background:#f7f9fb;border-bottom:1px solid #e3e9ee;color:#7890a4;text-align:left;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap}
.stok-table td{padding:15px 18px;border-bottom:1px solid #edf1f4;color:#405b70;font-size:13px;vertical-align:middle}
.stok-table tbody tr:last-child td{border-bottom:none}
.stok-table tbody tr:hover{background:#fafcfd}
.stok-no{color:#7890a4;font-weight:600}
.stok-product-name{color:#102f4b;font-weight:600}
.stok-category{display:inline-block;padding:5px 9px;background:#edf3f7;color:#315b78;border-radius:6px;font-size:11px;font-weight:600}
.stok-rack{color:#506d84;font-weight:600}
.stok-number{color:#102f4b;font-size:14px;font-weight:bold}
.stok-minimum{color:#506d84;font-weight:600}
.stok-aman,.stok-menipis,.stok-habis{display:inline-flex;align-items:center;justify-content:center;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:bold;white-space:nowrap}
.stok-aman{background:#e3f5e9;color:#27804a}
.stok-menipis{background:#fff4d6;color:#996900}
.stok-habis{background:#fde7e7;color:#b23b3b}
.btn-kelola-stok{display:inline-flex;align-items:center;justify-content:center;height:32px;padding:0 12px;background:#102f4b;color:#fff;border:none;border-radius:7px;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;transition:.2s ease}
.btn-kelola-stok:hover{background:#245b83}
.stok-empty{padding:55px 20px!important;text-align:center;color:#7890a4!important;font-size:13px}
.stok-modal{display:none;position:fixed;z-index:9999;inset:0;align-items:center;justify-content:center;padding:20px;background:rgba(16,47,75,.45)}
.stok-modal-box{width:100%;max-width:520px;background:#fff;border-radius:14px;padding:25px;box-shadow:0 15px 40px rgba(0,0,0,.15)}
.stok-modal-box.variant-mode{max-width:820px;max-height:90vh;overflow-y:auto}
.stok-modal-header{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #e6edf2}
.stok-modal-header h2{margin:0;color:#102f4b;font-size:19px}
.stok-modal-close{width:32px;height:32px;border:none;border-radius:7px;background:#f1f4f6;color:#687681;font-size:22px;line-height:1;cursor:pointer}
.stok-modal-close:hover{background:#e5eaee}
.stok-product-preview{display:flex;align-items:center;gap:14px;margin-bottom:20px;padding:13px;background:#f7f9fb;border:1px solid #e3e9ee;border-radius:10px}
.stok-product-preview-image{width:72px;height:72px;border-radius:10px;object-fit:cover;border:1px solid #dce5ec;background:#fff;flex-shrink:0}
.stok-product-preview-no-image{width:72px;height:72px;border-radius:10px;border:1px solid #dce5ec;background:#eef2f5;display:flex;align-items:center;justify-content:center;text-align:center;color:#8a9dac;font-size:10px;line-height:1.3;flex-shrink:0}
.stok-product-preview-info{min-width:0}
.stok-product-preview-info strong{display:block;color:#102f4b;font-size:14px;line-height:1.4}
.stok-product-preview-info span{display:block;margin-top:4px;color:#7890a4;font-size:11px}
.stok-form-group{margin-bottom:18px}
.stok-form-group label{display:block;margin-bottom:8px;color:#29485f;font-size:13px;font-weight:600}
.stok-form-group input{width:100%;box-sizing:border-box;padding:12px 13px;border:1px solid #d9e2ea;border-radius:8px;background:#fff;color:#29485f;font-family:inherit;font-size:13px;outline:none}
.stok-form-group input:focus{border-color:#5f86a3;box-shadow:0 0 0 3px rgba(36,91,131,.08)}
.stok-form-group input[readonly]{background:#f4f7fa;color:#7890a4}
.stok-control{display:flex;align-items:center;gap:8px}
.stok-control input{flex:1;text-align:center;font-weight:600}
.btn-stok-minus,.btn-stok-plus{width:43px;height:43px;border:none;border-radius:8px;font-size:22px;font-weight:bold;cursor:pointer}
.btn-stok-minus{background:#fdeaea;color:#ad3d3d}
.btn-stok-plus{background:#e3f5e9;color:#27804a}
.btn-stok-minus:hover{background:#f8dada}
.btn-stok-plus:hover{background:#d5f0dc}
.stok-form-actions{display:flex;gap:10px;margin-top:22px}
.btn-simpan-stok,.btn-batal-stok{flex:1;padding:12px;border:none;border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer}
.btn-simpan-stok{background:#102f4b;color:#fff}
.btn-simpan-stok:hover{background:#245b83}
.btn-batal-stok{background:#eef2f6;color:#102f4b}
.btn-batal-stok:hover{background:#e2e9ef}
.stok-variant-info{margin-bottom:18px;padding:11px 13px;background:#f1f5f9;border-radius:8px;color:#475569;font-size:12px}
.stok-variant-list{display:flex;flex-direction:column;gap:14px;margin-bottom:18px}
.stok-variant-item{border:1px solid #dfe7ed;border-radius:10px;padding:15px;background:#fbfcfd}
.stok-variant-top{display:flex;align-items:center;gap:14px;margin-bottom:14px}
.stok-variant-image{width:62px;height:62px;border-radius:9px;object-fit:cover;border:1px solid #dce5ec;background:#fff;flex-shrink:0}
.stok-variant-no-image{width:62px;height:62px;border-radius:9px;border:1px solid #dce5ec;background:#eef2f5;display:flex;align-items:center;justify-content:center;text-align:center;color:#8a9dac;font-size:9px;line-height:1.2;flex-shrink:0}
.stok-variant-title{min-width:0;flex:1}
.stok-variant-title strong{display:block;color:#102f4b;font-size:14px;line-height:1.4}
.stok-variant-location{display:block;margin-top:4px;color:#7890a4;font-size:11px}
.stok-variant-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:12px}
.stok-variant-grid .stok-form-group{margin-bottom:0}
.stok-location-select{width:100%;box-sizing:border-box;padding:12px 13px;border:1px solid #d9e2ea;border-radius:8px;background:#fff;color:#29485f;font-family:inherit;font-size:13px;outline:none}
.stok-location-select:focus{border-color:#5f86a3;box-shadow:0 0 0 3px rgba(36,91,131,.08)}
.stok-mode-simple,.stok-mode-variant{display:none}
.stok-mode-simple.active,.stok-mode-variant.active{display:block}
@media(max-width:1100px){
    .stok-summary{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:900px){
    .stok-table-header{align-items:stretch}
    .stok-search-form{width:100%}
    .stok-search-input{flex:1;width:auto}
}
@media(max-width:700px){
    .stok-variant-grid{grid-template-columns:1fr}
    .stok-modal-box.variant-mode{max-height:92vh}
    .stok-search-form{flex-wrap:wrap}
    .stok-search-input{width:100%;flex:none}
    .btn-search-stok,.btn-clear-stok{flex:1}
}
@media(max-width:600px){
    .stok-page-header{align-items:stretch;flex-direction:column}
    .stok-summary{grid-template-columns:1fr}
    .stok-summary-card{padding:18px}
    .stok-modal-box{padding:20px}
    .stok-product-preview{align-items:flex-start}
}
</style>
</head>
<body>
<div class="admin-layout">
    <?php include "../includes/sidebar.php"; ?>
    <div class="admin-main">
        <?php include "../includes/navbar.php"; ?>
        <main class="admin-content stok-page">
            <div class="stok-page-header">
                <div>
                    <h1>Stok Produk</h1>
                    <p>Kelola stok dan batas minimum stok setiap produk.</p>
                </div>
            </div>
            <?php if ($pesan !== ""): ?>
                <div class="stok-alert success"><?= htmlspecialchars($pesan); ?></div>
            <?php endif; ?>
            <?php if ($error !== ""): ?>
                <div class="stok-alert error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="stok-summary">
                <div class="stok-summary-card">
                    <div class="stok-summary-top">
                        <span class="stok-summary-label">Total Produk</span>
                        <div class="stok-summary-icon">P</div>
                    </div>
                    <strong id="stokStatTotalProduk"><?= number_format($total_produk); ?></strong>
                </div>
                <div class="stok-summary-card">
                    <div class="stok-summary-top">
                        <span class="stok-summary-label">Total Stok</span>
                        <div class="stok-summary-icon">S</div>
                    </div>
                    <strong id="stokStatTotalStok"><?= number_format($total_stok); ?></strong>
                </div>
                <div class="stok-summary-card">
                    <div class="stok-summary-top">
                        <span class="stok-summary-label">Stok Menipis</span>
                        <div class="stok-summary-icon">!</div>
                    </div>
                    <strong id="stokStatMenipis"><?= number_format($total_menipis); ?></strong>
                </div>
                <div class="stok-summary-card">
                    <div class="stok-summary-top">
                        <span class="stok-summary-label">Stok Habis</span>
                        <div class="stok-summary-icon">0</div>
                    </div>
                    <strong id="stokStatHabis"><?= number_format($total_habis); ?></strong>
                </div>
            </div>
            <div class="stok-table-card">
                <div class="stok-table-header">
                    <div>
                        <h2>Daftar Stok Produk</h2>
                        <p>Atur jumlah stok dan batas minimum stok produk.</p>
                    </div>
                    <form method="GET" action="index.php" class="stok-search-form">
                        <input
                            type="text"
                            name="search"
                            class="stok-search-input"
                            placeholder="Cari produk, kategori, varian..."
                            value="<?= htmlspecialchars($search); ?>"
                        >
                        <button type="submit" class="btn-search-stok">Cari</button>
                        <?php if ($search !== ''): ?>
                            <a href="index.php" class="btn-clear-stok">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                <?php if ($search !== ''): ?>
                    <div class="search-result-info">
                        Hasil pencarian untuk
                        <strong>"<?= htmlspecialchars($search); ?>"</strong>
                    </div>
                <?php endif; ?>
                <div class="stok-table-wrapper">
                    <table class="stok-table">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Lokasi / Rak</th>
                                <th>Stok</th>
                                <th>Stok Minimum</th>
                                <th>Status</th>
                                <th width="110">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="stokTableBody"><?= $stok_tbody_html; ?></tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php include "../includes/footer.php"; ?>
    </div>
</div>
<div id="stokModal" class="stok-modal">
    <div class="stok-modal-box" id="stokModalBox">
        <div class="stok-modal-header">
            <h2>Kelola Stok</h2>
            <button type="button" class="stok-modal-close" onclick="tutupModalStok()">×</button>
        </div>
        <form method="POST" action="update.php" id="form-stok">
            <input type="hidden" name="id_produk" id="id_produk">
            <input type="hidden" name="mode" id="stok_mode" value="produk">
            <div class="stok-mode-simple" id="stokModeSimple">
                <div class="stok-product-preview">
                    <div id="stokProdukImageWrap">
                        <div class="stok-product-preview-no-image">Belum ada foto</div>
                    </div>
                    <div class="stok-product-preview-info">
                        <strong id="stokProdukPreviewNama">-</strong>
                        <span id="stokProdukPreviewKategori">-</span>
                    </div>
                </div>
                <div class="stok-form-group">
                    <label for="nama_produk">Nama Produk</label>
                    <input type="text" id="nama_produk" readonly>
                </div>
                <div class="stok-form-group">
                    <label for="stok_baru">Stok Saat Ini</label>
                    <div class="stok-control">
                        <button type="button" class="btn-stok-minus" onclick="kurangiStok()">−</button>
                        <input type="number" name="stok_baru" id="stok_baru" min="0">
                        <button type="button" class="btn-stok-plus" onclick="tambahStok()">+</button>
                    </div>
                </div>
                <div class="stok-form-group">
                    <label for="stok_minimum">Stok Minimum</label>
                    <input type="number" name="stok_minimum" id="stok_minimum" min="0">
                </div>
                <div class="stok-form-group">
                    <label for="lokasi_rak_produk">Lokasi / Rak</label>
                    <select name="lokasi_rak" id="lokasi_rak_produk" class="stok-location-select">
                        <option value="">-- Pilih Lokasi Rak --</option>
                        <?php
                        if ($rak_stok_result) {
                            mysqli_data_seek($rak_stok_result, 0);
                            while ($rak = mysqli_fetch_assoc($rak_stok_result)):
                        ?>
                            <option value="<?= htmlspecialchars($rak['kode_rak']); ?>">
                                <?= htmlspecialchars($rak['kode_rak']); ?> - <?= htmlspecialchars($rak['nama_lokasi']); ?>
                            </option>
                        <?php
                            endwhile;
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="stok-mode-variant" id="stokModeVariant">
                <div class="stok-product-preview">
                    <div id="stokProdukVarianImageWrap">
                        <div class="stok-product-preview-no-image">Belum ada foto</div>
                    </div>
                    <div class="stok-product-preview-info">
                        <strong id="nama_produk_varian_preview">-</strong>
                        <span id="stokProdukVarianKategori">Produk dengan varian</span>
                    </div>
                </div>
                <div class="stok-form-group">
                    <label>Nama Produk</label>
                    <input type="text" id="nama_produk_varian" readonly>
                </div>
                <div class="stok-variant-info">
                    Atur stok masing-masing varian di bawah ini. Stok produk utama otomatis mengikuti total stok varian aktif.
                </div>
                <div class="stok-variant-list" id="stokVariantList"></div>
            </div>
            <div class="stok-form-actions">
                <button type="button" class="btn-batal-stok" onclick="tutupModalStok()">Batal</button>
                <button type="submit" name="simpan_stok" class="btn-simpan-stok">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const daftarLokasiRak = <?= json_encode($rak_js_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
function bukaModalStok(data) {
    const modal = document.getElementById("stokModal");
    const modalBox = document.getElementById("stokModalBox");
    const form = document.getElementById("form-stok");
    const mode = document.getElementById("stok_mode");
    const simple = document.getElementById("stokModeSimple");
    const variant = document.getElementById("stokModeVariant");
    const namaProduk = document.getElementById("nama_produk");
    const namaProdukVarian = document.getElementById("nama_produk_varian");
    const stokBaru = document.getElementById("stok_baru");
    const stokMinimum = document.getElementById("stok_minimum");
    const stokVariantList = document.getElementById("stokVariantList");
    form.reset();
    document.getElementById("id_produk").value = data.id_produk;
    mode.value = data.memiliki_varian ? "varian" : "produk";
    stokVariantList.innerHTML = "";
    simple.classList.remove("active");
    variant.classList.remove("active");
    modalBox.classList.remove("variant-mode");
    const imageWrapSimple = document.getElementById("stokProdukImageWrap");
    const imageWrapVariant = document.getElementById("stokProdukVarianImageWrap");
    document.getElementById("stokProdukPreviewNama").textContent = data.nama_produk || "-";
    document.getElementById("stokProdukPreviewKategori").textContent = data.nama_kategori || "-";
    document.getElementById("nama_produk_varian_preview").textContent = data.nama_produk || "-";
    document.getElementById("stokProdukVarianKategori").textContent = data.nama_kategori || "Produk dengan varian";
    if (data.gambar) {
        imageWrapSimple.innerHTML = `
            <img
                src="../assets/images/products/${escapeHtml(data.gambar)}"
                alt="${escapeHtml(data.nama_produk || 'Produk')}"
                class="stok-product-preview-image"
            >
        `;
        imageWrapVariant.innerHTML = `
            <img
                src="../assets/images/products/${escapeHtml(data.gambar)}"
                alt="${escapeHtml(data.nama_produk || 'Produk')}"
                class="stok-product-preview-image"
            >
        `;
    } else {
        imageWrapSimple.innerHTML = '<div class="stok-product-preview-no-image">Belum ada foto</div>';
        imageWrapVariant.innerHTML = '<div class="stok-product-preview-no-image">Belum ada foto</div>';
    }
    if (!data.memiliki_varian) {
        simple.classList.add("active");
        namaProduk.value = data.nama_produk || "";
        stokBaru.value = Number(data.stok) || 0;
        stokMinimum.value = Number(data.stok_minimum) || 0;
        document.getElementById("lokasi_rak_produk").value = data.lokasi_rak || "";
        stokBaru.disabled = false;
        stokBaru.required = true;
        stokMinimum.disabled = false;
        stokMinimum.required = true;
    } else {
        variant.classList.add("active");
        modalBox.classList.add("variant-mode");
        stokBaru.disabled = true;
        stokBaru.required = false;
        stokMinimum.disabled = true;
        stokMinimum.required = false;
        namaProdukVarian.value = data.nama_produk || "";
        if (!Array.isArray(data.varian) || data.varian.length === 0) {
            stokVariantList.innerHTML = '<div class="stok-variant-info">Produk ini memiliki data varian, tetapi belum ada varian yang dapat dikelola.</div>';
        } else {
            data.varian.forEach(function(varian) {
                const item = document.createElement("div");
                item.className = "stok-variant-item";
                const gambarVarian = varian.gambar || data.gambar || "";
                let imageHtml = '<div class="stok-variant-no-image">Belum ada foto</div>';
                if (gambarVarian) {
                    const imageFolder = varian.gambar ? "variants/" : "";
                    imageHtml = `
                        <img
                            src="../assets/images/products/${imageFolder}${escapeHtml(gambarVarian)}"
                            alt="${escapeHtml(varian.nama_varian || 'Varian')}"
                            class="stok-variant-image"
                        >
                    `;
                }
                item.innerHTML = `
                    <input
                        type="hidden"
                        name="varian[${varian.id_varian}][id_varian]"
                        value="${varian.id_varian}"
                    >
                    <div class="stok-variant-top">
                        ${imageHtml}
                        <div class="stok-variant-title">
                            <strong>${escapeHtml(varian.nama_varian)}</strong>
                            <span class="stok-variant-location">
                                ${escapeHtml(varian.nama_lokasi || varian.lokasi_rak || "Belum ada lokasi")}
                            </span>
                        </div>
                    </div>
                    <div class="stok-variant-grid">
                        <div class="stok-form-group">
                            <label>Stok Saat Ini</label>
                            <div class="stok-control">
                                <button type="button" class="btn-stok-minus" onclick="kurangiStokVarian(this)">−</button>
                                <input
                                    type="number"
                                    name="varian[${varian.id_varian}][stok]"
                                    value="${Number(varian.stok) || 0}"
                                    min="0"
                                    required
                                >
                                <button type="button" class="btn-stok-plus" onclick="tambahStokVarian(this)">+</button>
                            </div>
                        </div>
                        <div class="stok-form-group">
                            <label>Stok Minimum</label>
                            <input
                                type="number"
                                name="varian[${varian.id_varian}][stok_minimum]"
                                value="${Number(varian.stok_minimum) || 0}"
                                min="0"
                                required
                            >
                        </div>
                        <div class="stok-form-group">
                            <label>Lokasi / Rak</label>
                            <select
                                name="varian[${varian.id_varian}][lokasi_rak]"
                                class="stok-location-select"
                            >
                                ${buatOpsiRak(varian.lokasi_rak || "")}
                            </select>
                        </div>
                    </div>
                `;
                stokVariantList.appendChild(item);
            });
        }
    }
    modal.style.display = "flex";
}
function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
function buatOpsiRak(nilaiTerpilih) {
    let html = '<option value="">-- Pilih Lokasi Rak --</option>';
    daftarLokasiRak.forEach(function(rak) {
        const selected = String(rak.kode_rak) === String(nilaiTerpilih) ? "selected" : "";
        html += `
            <option value="${escapeHtml(rak.kode_rak)}" ${selected}>
                ${escapeHtml(rak.kode_rak)} - ${escapeHtml(rak.nama_lokasi)}
            </option>
        `;
    });
    return html;
}
function tutupModalStok() {
    document.getElementById("stokModal").style.display = "none";
    document.getElementById("form-stok").reset();
}
function tambahStok() {
    const input = document.getElementById("stok_baru");
    let stok = parseInt(input.value) || 0;
    input.value = stok + 1;
}
function kurangiStok() {
    const input = document.getElementById("stok_baru");
    let stok = parseInt(input.value) || 0;
    input.value = stok > 0 ? stok - 1 : 0;
}
function tambahStokVarian(button) {
    const control = button.closest(".stok-control");
    if (!control) return;
    const input = control.querySelector('input[type="number"]');
    if (!input) return;
    let stok = parseInt(input.value) || 0;
    input.value = stok + 1;
}
function kurangiStokVarian(button) {
    const control = button.closest(".stok-control");
    if (!control) return;
    const input = control.querySelector('input[type="number"]');
    if (!input) return;
    let stok = parseInt(input.value) || 0;
    input.value = stok > 0 ? stok - 1 : 0;
}
window.addEventListener("click", function(event) {
    const modal = document.getElementById("stokModal");
    if (event.target === modal) {
        tutupModalStok();
    }
});
window.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        tutupModalStok();
    }
});
</script>
<script>
/* SIMPAN STOK TANPA RELOAD HALAMAN */
(function () {
    const form = document.getElementById("form-stok");
    const tombolSimpan = form ? form.querySelector(".btn-simpan-stok") : null;
    if (!form) return;

    let sedangMenyimpan = false;
    let timerNotifikasi = null;
    const halamanStok = document.querySelector(".stok-page");

    function dapatkanNotifikasi() {
        if (!halamanStok) return null;

        let notifikasi = document.getElementById("stokAjaxAlert") ||
            halamanStok.querySelector(".stok-alert");

        const semuaNotifikasi = halamanStok.querySelectorAll(".stok-alert");
        semuaNotifikasi.forEach(function (item) {
            if (item !== notifikasi) item.remove();
        });

        if (notifikasi) notifikasi.id = "stokAjaxAlert";
        return notifikasi;
    }

    function jadwalkanNotifikasiHilang(notifikasi) {
        if (!notifikasi) return;

        if (timerNotifikasi) clearTimeout(timerNotifikasi);

        notifikasi.classList.remove("is-hiding");
        notifikasi.style.display = "block";

        timerNotifikasi = setTimeout(function () {
            notifikasi.classList.add("is-hiding");

            setTimeout(function () {
                if (notifikasi.classList.contains("is-hiding")) {
                    notifikasi.style.display = "none";
                }
            }, 400);
        }, 10000);
    }

    function tampilkanPesanStok(teks, tipe) {
        if (!halamanStok) return;

        let pesan = dapatkanNotifikasi();

        if (!pesan) {
            pesan = document.createElement("div");
            pesan.id = "stokAjaxAlert";
            pesan.className = "stok-alert";

            const ringkasan = halamanStok.querySelector(".stok-summary");

            if (ringkasan) {
                halamanStok.insertBefore(pesan, ringkasan);
            } else {
                halamanStok.prepend(pesan);
            }
        }

        pesan.className = "stok-alert " + (tipe === "success" ? "success" : "error");
        pesan.textContent = teks;

        jadwalkanNotifikasiHilang(pesan);
    }

    // Satukan notifikasi PHP dan AJAX agar tidak muncul ganda.
    const notifikasiAwal = dapatkanNotifikasi();

    if (notifikasiAwal) {
        jadwalkanNotifikasiHilang(notifikasiAwal);
    }

    // Bersihkan status dari URL agar pesan lama tidak muncul kembali.
    try {
        const urlBersih = new URL(window.location.href);

        urlBersih.searchParams.delete("status");
        urlBersih.searchParams.delete("pesan");

        window.history.replaceState(
            {},
            document.title,
            urlBersih.pathname + urlBersih.search + urlBersih.hash
        );
    } catch (error) {
        console.warn("URL status stok tidak dapat dibersihkan:", error);
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (sedangMenyimpan) return;
        if (!form.reportValidity()) return;

        sedangMenyimpan = true;

        const teksAwal = tombolSimpan ? tombolSimpan.textContent : "Simpan";

        if (tombolSimpan) {
            tombolSimpan.disabled = true;
            tombolSimpan.textContent = "Menyimpan...";
        }

        try {
            const formData = new FormData(form);
            formData.set("simpan_stok", "1");

            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                }
            });

            let hasil;

            try {
                hasil = await response.json();
            } catch (e) {
                throw new Error("Respons server tidak valid. Periksa file update.php.");
            }

            if (!response.ok || !hasil.success) {
                tampilkanPesanStok(
                    hasil.message || "Stok gagal disimpan. Periksa kembali data yang diisi.",
                    "error"
                );
                return;
            }

            tutupModalStok();

            tampilkanPesanStok(
                hasil.message || "Stok berhasil diperbarui.",
                "success"
            );

            document.dispatchEvent(new Event("stok:updated"));
        } catch (error) {
            console.error("Gagal menyimpan stok:", error);

            tampilkanPesanStok(
                error.message || "Tidak dapat menyimpan stok. Coba lagi.",
                "error"
            );
        } finally {
            sedangMenyimpan = false;

            if (tombolSimpan) {
                tombolSimpan.disabled = false;
                tombolSimpan.textContent = teksAwal;
            }
        }
    });
})();
</script>

<script src="../assets/js/admin.js"></script>
<script>
/* SINKRONISASI STOK TANPA MEMUAT ULANG NAVBAR */
(function() {
    const tbody = document.getElementById("stokTableBody");
    const modal = document.getElementById("stokModal");

    if (!tbody) return;

    const stats = {
        produk: document.getElementById("stokStatTotalProduk"),
        stok: document.getElementById("stokStatTotalStok"),
        menipis: document.getElementById("stokStatMenipis"),
        habis: document.getElementById("stokStatHabis")
    };

    let sedangMemuat = false;
    let hashSaatIni = <?= json_encode($stok_tbody_hash); ?>;

    function ubahAngkaJikaBerbeda(elemen, nilai) {
        if (elemen && elemen.textContent.trim() !== String(nilai)) {
            elemen.textContent = String(nilai);
        }
    }

    async function sinkronStok() {
        if (sedangMemuat || document.visibilityState !== "visible") {
            return;
        }

        sedangMemuat = true;

        try {
            const url = new URL(window.location.href);

            url.searchParams.set("_live_stok", "1");
            url.searchParams.set("_", Date.now().toString());

            const response = await fetch(url.toString(), {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) return;

            const contentType = response.headers.get("content-type") || "";

            if (!contentType.includes("application/json")) return;

            const data = await response.json();

            if (!data.success || !data.stats || typeof data.rows_html !== "string") {
                return;
            }

            ubahAngkaJikaBerbeda(stats.produk, data.stats.total_produk);
            ubahAngkaJikaBerbeda(stats.stok, data.stats.total_stok);
            ubahAngkaJikaBerbeda(stats.menipis, data.stats.total_menipis);
            ubahAngkaJikaBerbeda(stats.habis, data.stats.total_habis);

            const modalTerbuka = modal &&
                window.getComputedStyle(modal).display !== "none";

            if (data.rows_hash !== hashSaatIni && !modalTerbuka) {
                const wrapper = tbody.closest(".stok-table-wrapper");
                const posisiScroll = wrapper ? wrapper.scrollLeft : 0;

                tbody.innerHTML = data.rows_html;
                hashSaatIni = data.rows_hash;

                if (wrapper) {
                    wrapper.scrollLeft = posisiScroll;
                }
            }
        } catch (error) {
            console.error("Gagal menyinkronkan stok:", error);
        } finally {
            sedangMemuat = false;
        }
    }

    document.addEventListener("stok:updated", sinkronStok);
    setInterval(sinkronStok, 4000);
})();
</script>
</body>
</html>