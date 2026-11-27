<?php
session_start();
if (!isset($_SESSION['id_admin'])) { header("Location: ../login.php"); exit; }
require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
cekAkses('voucher');

$success = '';
$error = '';
$search = trim($_GET['search'] ?? '');

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'tambah') $success = "Voucher berhasil ditambahkan.";
    if ($_GET['status'] === 'edit') $success = "Voucher berhasil diperbarui.";
    if ($_GET['status'] === 'hapus') $success = "Voucher berhasil dihapus.";
    if ($_GET['status'] === 'gagal') $error = "Terjadi kesalahan.";
}

$query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM voucher");
$total_voucher = (int)(mysqli_fetch_assoc($query)['total'] ?? 0);

$query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM voucher WHERE status = 'Aktif'");
$total_aktif = (int)(mysqli_fetch_assoc($query)['total'] ?? 0);

$query = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah_digunakan), 0) AS total FROM voucher");
$total_digunakan = (int)(mysqli_fetch_assoc($query)['total'] ?? 0);

$where = '';
if ($search !== '') {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where = "WHERE kode_voucher LIKE '%$search_escaped%' OR nama_voucher LIKE '%$search_escaped%' OR jenis_diskon LIKE '%$search_escaped%' OR status LIKE '%$search_escaped%' OR DATE_FORMAT(tanggal_mulai, '%d/%m/%Y') LIKE '%$search_escaped%' OR DATE_FORMAT(tanggal_berakhir, '%d/%m/%Y') LIKE '%$search_escaped%' OR DATE_FORMAT(tanggal_mulai, '%d-%m-%Y') LIKE '%$search_escaped%' OR DATE_FORMAT(tanggal_berakhir, '%d-%m-%Y') LIKE '%$search_escaped%' OR DATE_FORMAT(tanggal_mulai, '%Y-%m-%d') LIKE '%$search_escaped%' OR DATE_FORMAT(tanggal_berakhir, '%Y-%m-%d') LIKE '%$search_escaped%'";
}

$query_voucher = mysqli_query($conn, "SELECT id_voucher, kode_voucher, nama_voucher, jenis_diskon, nilai_diskon, minimal_belanja, maksimal_diskon, tanggal_mulai, tanggal_berakhir, kuota, jumlah_digunakan, status FROM voucher $where ORDER BY id_voucher DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voucher & Promo - Toko ATK & Alat Jahit</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
.voucher-page{max-width:1500px}.voucher-header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:25px}.voucher-header-text h1{margin:0 0 7px;color:#102f4b;font-size:28px}.voucher-header-text p{margin:0;color:#7890a4;font-size:14px}.btn-tambah{display:inline-flex;align-items:center;justify-content:center;padding:11px 17px;border-radius:9px;background:#102f4b;color:#fff;text-decoration:none;font-size:13px;font-weight:700;white-space:nowrap}.btn-tambah:hover{background:#1c496b}.voucher-alert{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:600}.voucher-alert.success{background:#e8f7ee;color:#237a45;border:1px solid #c9ead6}.voucher-alert.error{background:#fce8e8;color:#a33;border:1px solid #f2caca}.voucher-stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:25px}.voucher-stat-card{background:#fff;border:1px solid #e0e7ed;border-radius:14px;padding:22px;box-shadow:0 4px 15px rgba(16,47,75,.04)}.voucher-stat-label{display:block;color:#7890a4;font-size:13px;margin-bottom:10px;text-transform:uppercase;font-weight:600}.voucher-stat-number{display:block;color:#102f4b;font-size:30px;font-weight:800}.voucher-card{background:#fff;border:1px solid #e0e7ed;border-radius:14px;box-shadow:0 4px 15px rgba(16,47,75,.04);overflow:hidden}.voucher-card-header{padding:22px 24px;border-bottom:1px solid #e6edf2}.voucher-card-header h2{margin:0 0 6px;color:#102f4b;font-size:20px}.voucher-card-header p{margin:0;color:#7890a4;font-size:14px}.voucher-search{margin-top:18px}.voucher-search-form{display:flex;align-items:center;gap:8px;width:100%;max-width:620px}.voucher-search-input{flex:1;min-width:0;height:42px;padding:0 13px;border:1px solid #d5e0e8;border-radius:9px;outline:none;font-family:inherit;font-size:14px;color:#334f66;background:#fff;box-sizing:border-box}.voucher-search-input:focus{border-color:#102f4b;box-shadow:0 0 0 3px rgba(16,47,75,.08)}.voucher-search-btn,.voucher-search-reset{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 14px;border-radius:9px;font-family:inherit;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;box-sizing:border-box}.voucher-search-btn{border:none;background:#102f4b;color:#fff;cursor:pointer}.voucher-search-btn:hover{background:#1c496b}.voucher-search-reset{background:#eef3f7;color:#506d84}.voucher-search-reset:hover{background:#e3ebf1}.voucher-table-wrapper{width:100%;overflow-x:auto}.voucher-table{width:100%;border-collapse:collapse;min-width:1250px}.voucher-table th{background:#f4f7fa;color:#506d84;text-align:left;padding:14px;font-size:13px;font-weight:700;white-space:nowrap}.voucher-table td{padding:15px 14px;border-top:1px solid #e5ebf0;color:#334f66;font-size:13px;vertical-align:middle}.voucher-table tbody tr:hover{background:#fafcfd}.kode-voucher{display:inline-block;padding:7px 10px;background:#eef3f7;border:1px solid #dce5ec;border-radius:7px;color:#102f4b;font-size:12px;font-weight:800;letter-spacing:.5px}.nama-voucher{display:block;margin-top:7px;color:#506d84;font-size:12px}.nilai-diskon{color:#102f4b;font-weight:800;white-space:nowrap}.minimal-belanja,.maksimal-diskon{color:#506d84;white-space:nowrap}.periode{color:#506d84;line-height:1.6;white-space:nowrap}.kuota{color:#102f4b;font-weight:700;white-space:nowrap}.kuota-terpakai{display:block;margin-top:4px;color:#9aafbf;font-size:11px}.voucher-status{display:inline-flex;align-items:center;justify-content:center;padding:7px 11px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}.status-aktif{background:#e3f5e9;color:#27804a}.status-nonaktif{background:#fde7e7;color:#b23b3b}.aksi-wrapper{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.btn-edit{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:7px;background:#eef3f7;border:1px solid #dce5ec;color:#334f66;text-decoration:none;font-size:11px;font-weight:700;white-space:nowrap}.btn-edit:hover{background:#e1e9ef}.btn-hapus{border:none;padding:8px 12px;border-radius:7px;background:#fde7e7;color:#b23b3b;font-family:inherit;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap}.btn-hapus:hover{background:#f8d4d4}.voucher-kosong{text-align:center;padding:70px 20px}.voucher-kosong-icon{font-size:40px;margin-bottom:12px}.voucher-kosong h3{color:#334f66;font-size:17px;margin:0 0 7px}.voucher-kosong p{color:#7890a4;font-size:14px;margin:0}@media(max-width:900px){.voucher-stat-grid{grid-template-columns:1fr}}@media(max-width:650px){.voucher-header{flex-direction:column}.btn-tambah{width:100%}.voucher-search-form{max-width:none;flex-wrap:wrap}.voucher-search-input{width:100%;flex-basis:100%}.voucher-search-btn,.voucher-search-reset{flex:1}}
</style>
</head>
<body>
<div class="admin-layout">
<?php require_once "../includes/sidebar.php"; ?>
<main class="admin-main">
<?php require_once "../includes/navbar.php"; ?>
<section class="admin-content">
<div class="voucher-page">
<div class="voucher-header">
<div class="voucher-header-text"><h1>Voucher & Promo</h1><p>Kelola kode voucher dan promo untuk pelanggan.</p></div>
<a href="tambah.php" class="btn-tambah">+ Tambah Voucher</a>
</div>

<?php if ($success !== '') : ?>
<div class="voucher-alert success">✓ <?= htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error !== '') : ?>
<div class="voucher-alert error">⚠ <?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="voucher-stat-grid">
<div class="voucher-stat-card"><span class="voucher-stat-label">Total Voucher</span><span class="voucher-stat-number"><?= $total_voucher; ?></span></div>
<div class="voucher-stat-card"><span class="voucher-stat-label">Voucher Aktif</span><span class="voucher-stat-number"><?= $total_aktif; ?></span></div>
<div class="voucher-stat-card"><span class="voucher-stat-label">Total Digunakan</span><span class="voucher-stat-number"><?= $total_digunakan; ?></span></div>
</div>

<div class="voucher-card">
<div class="voucher-card-header">
<h2>Daftar Voucher</h2>
<p>Semua voucher dan promo yang tersedia dalam sistem.</p>
<div class="voucher-search">
<form method="GET" class="voucher-search-form">
<input type="search" name="search" value="<?= htmlspecialchars($search); ?>" class="voucher-search-input" placeholder="Cari kode, nama, jenis diskon, status, atau periode..." autocomplete="off">
<button type="submit" class="voucher-search-btn">Cari</button>
<?php if ($search !== '') : ?>
<a href="index.php" class="voucher-search-reset">Reset</a>
<?php endif; ?>
</form>
</div>
</div>

<div class="voucher-table-wrapper">
<table class="voucher-table">
<thead>
<tr>
<th>No</th>
<th>Voucher</th>
<th>Diskon</th>
<th>Min. Belanja</th>
<th>Max. Diskon</th>
<th>Periode</th>
<th>Kuota</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php if ($query_voucher && mysqli_num_rows($query_voucher) > 0) : ?>
<?php $no = 1; ?>
<?php while ($voucher = mysqli_fetch_assoc($query_voucher)) : ?>
<tr>
<td><?= $no++; ?></td>
<td><span class="kode-voucher"><?= htmlspecialchars($voucher['kode_voucher']); ?></span><span class="nama-voucher"><?= htmlspecialchars($voucher['nama_voucher']); ?></span></td>
<td><span class="nilai-diskon"><?php if ($voucher['jenis_diskon'] === 'Persen') : ?><?= number_format((float)$voucher['nilai_diskon'],0,',','.'); ?>%<?php else : ?>Rp <?= number_format((float)$voucher['nilai_diskon'],0,',','.'); ?><?php endif; ?></span></td>
<td><span class="minimal-belanja">Rp <?= number_format((float)$voucher['minimal_belanja'],0,',','.'); ?></span></td>
<td><span class="maksimal-diskon"><?php if ($voucher['maksimal_diskon'] !== null && $voucher['maksimal_diskon'] !== '') : ?>Rp <?= number_format((float)$voucher['maksimal_diskon'],0,',','.'); ?><?php else : ?>Tidak ada<?php endif; ?></span></td>
<td><span class="periode"><?= date('d/m/Y',strtotime($voucher['tanggal_mulai'])); ?><br>s/d <?= date('d/m/Y',strtotime($voucher['tanggal_berakhir'])); ?></span></td>
<td><span class="kuota"><?php if ((int)$voucher['kuota'] === 0) : ?>Tidak Terbatas<?php else : ?><?= (int)$voucher['kuota']; ?><?php endif; ?></span><span class="kuota-terpakai">Digunakan: <?= (int)$voucher['jumlah_digunakan']; ?></span></td>
<td><?php if ($voucher['status'] === 'Aktif') : ?><span class="voucher-status status-aktif">Aktif</span><?php else : ?><span class="voucher-status status-nonaktif">Tidak Aktif</span><?php endif; ?></td>
<td><div class="aksi-wrapper"><a href="edit.php?id=<?= (int)$voucher['id_voucher']; ?>" class="btn-edit">Edit</a><form method="POST" action="hapus.php" onsubmit="return confirm('Yakin ingin menghapus voucher ini?');"><input type="hidden" name="id_voucher" value="<?= (int)$voucher['id_voucher']; ?>"><button type="submit" name="hapus_voucher" class="btn-hapus">Hapus</button></form></div></td>
</tr>
<?php endwhile; ?>
<?php else : ?>
<tr>
<td colspan="9">
<div class="voucher-kosong">
<div class="voucher-kosong-icon">🎟️</div>
<h3><?= $search !== '' ? 'Voucher Tidak Ditemukan' : 'Belum Ada Voucher'; ?></h3>
<p><?= $search !== '' ? 'Coba gunakan kata kunci pencarian yang lain.' : 'Tambahkan voucher atau promo untuk pelanggan.'; ?></p>
</div>
</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</section>
<?php require_once "../includes/footer.php"; ?>
</main>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>