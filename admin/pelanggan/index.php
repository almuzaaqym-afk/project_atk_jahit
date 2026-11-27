<?php
session_start();
if (!isset($_SESSION['id_admin'])) { header("Location: ../login.php"); exit; }
require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
cekAkses('pelanggan');

$success = '';
$error = '';
$search = trim($_GET['search'] ?? '');

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'hapus') $success = "Pelanggan berhasil dihapus.";
    if ($_GET['status'] === 'tidak_bisa_hapus') $error = "Pelanggan tidak dapat dihapus karena sudah memiliki pesanan.";
    if ($_GET['status'] === 'tidak_ditemukan') $error = "Data pelanggan tidak ditemukan.";
    if ($_GET['status'] === 'gagal') $error = "Terjadi kesalahan.";
}

$query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pelanggan");
$total_pelanggan = (int)(mysqli_fetch_assoc($query)['total'] ?? 0);

$query = mysqli_query($conn, "SELECT COUNT(DISTINCT id_pelanggan) AS total FROM pesanan WHERE id_pelanggan IS NOT NULL");
$total_pelanggan_aktif = (int)(mysqli_fetch_assoc($query)['total'] ?? 0);

$query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan");
$total_pesanan = (int)(mysqli_fetch_assoc($query)['total'] ?? 0);

$search_condition = '';

if ($search !== '') {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $search_condition = "WHERE CAST(pl.id_pelanggan AS CHAR) LIKE '%$search_escaped%' OR pl.nama LIKE '%$search_escaped%' OR pl.email LIKE '%$search_escaped%' OR DATE_FORMAT(pl.created_at, '%d/%m/%Y') LIKE '%$search_escaped%' OR DATE_FORMAT(pl.created_at, '%d-%m-%Y') LIKE '%$search_escaped%' OR DATE_FORMAT(pl.created_at, '%Y-%m-%d') LIKE '%$search_escaped%'";
}

$query_pelanggan = mysqli_query($conn, "SELECT pl.id_pelanggan, pl.nama, pl.email, pl.created_at, COUNT(p.id_pesanan) AS jumlah_pesanan, COALESCE(SUM(CASE WHEN p.status_pesanan != 'Dibatalkan' THEN p.total_harga ELSE 0 END), 0) AS total_belanja FROM pelanggan pl LEFT JOIN pesanan p ON pl.id_pelanggan = p.id_pelanggan $search_condition GROUP BY pl.id_pelanggan, pl.nama, pl.email, pl.created_at ORDER BY pl.id_pelanggan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pelanggan - Toko ATK & Alat Jahit</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
.pelanggan-page{max-width:1500px}.pelanggan-alert{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:600}.pelanggan-alert.success{background:#e8f7ee;color:#237a45;border:1px solid #c9ead6}.pelanggan-alert.error{background:#fce8e8;color:#a33;border:1px solid #f2caca}.pelanggan-stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:25px}.pelanggan-stat-card{background:#fff;border:1px solid #e0e7ed;border-radius:14px;padding:22px;box-shadow:0 4px 15px rgba(16,47,75,.04)}.pelanggan-stat-label{display:block;color:#7890a4;font-size:13px;margin-bottom:10px;text-transform:uppercase;font-weight:600}.pelanggan-stat-number{display:block;color:#102f4b;font-size:30px;font-weight:800}.pelanggan-card{background:#fff;border:1px solid #e0e7ed;border-radius:14px;box-shadow:0 4px 15px rgba(16,47,75,.04);overflow:hidden}.pelanggan-card-header{padding:22px 24px;border-bottom:1px solid #e6edf2}.pelanggan-card-header h2{margin:0 0 6px;color:#102f4b;font-size:20px}.pelanggan-card-header p{margin:0;color:#7890a4;font-size:14px}.pelanggan-search{margin-top:18px}.pelanggan-search-form{display:flex;align-items:center;gap:8px;width:100%;max-width:560px}.pelanggan-search-input{flex:1;min-width:0;height:42px;padding:0 13px;border:1px solid #d5e0e8;border-radius:9px;outline:none;font-family:inherit;font-size:14px;color:#334f66;background:#fff;box-sizing:border-box}.pelanggan-search-input:focus{border-color:#102f4b;box-shadow:0 0 0 3px rgba(16,47,75,.08)}.pelanggan-search-btn,.pelanggan-search-reset{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 14px;border-radius:9px;font-family:inherit;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;box-sizing:border-box}.pelanggan-search-btn{border:none;background:#102f4b;color:#fff;cursor:pointer}.pelanggan-search-btn:hover{background:#1c496b}.pelanggan-search-reset{background:#eef3f7;color:#506d84}.pelanggan-search-reset:hover{background:#e3ebf1}.pelanggan-table-wrapper{width:100%;overflow-x:auto}.pelanggan-table{width:100%;border-collapse:collapse;min-width:1000px}.pelanggan-table th{background:#f4f7fa;color:#506d84;text-align:left;padding:14px;font-size:13px;font-weight:700;white-space:nowrap}.pelanggan-table td{padding:15px 14px;border-top:1px solid #e5ebf0;color:#334f66;font-size:14px;vertical-align:middle}.pelanggan-table tbody tr:hover{background:#fafcfd}.nama-pelanggan{color:#102f4b;font-weight:700}.id-pelanggan{display:block;margin-top:4px;color:#9aafbf;font-size:12px}.email-pelanggan{color:#506d84}.jumlah-pesanan,.total-belanja{color:#102f4b;font-weight:700}.total-belanja{white-space:nowrap}.tanggal-daftar{color:#506d84;white-space:nowrap}.aksi-wrapper{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.btn-detail{display:inline-flex;align-items:center;justify-content:center;padding:9px 13px;border-radius:8px;background:#102f4b;color:#fff;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap}.btn-detail:hover{background:#1c496b}.btn-hapus{border:none;padding:9px 13px;border-radius:8px;background:#fde7e7;color:#b23b3b;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap}.btn-hapus:hover{background:#f8d4d4}.pelanggan-kosong{text-align:center;padding:70px 20px}.pelanggan-kosong-icon{font-size:40px;margin-bottom:12px}.pelanggan-kosong h3{color:#334f66;font-size:17px;margin:0 0 7px}.pelanggan-kosong p{color:#7890a4;font-size:14px;margin:0}
@media(max-width:900px){.pelanggan-stat-grid{grid-template-columns:1fr}}@media(max-width:500px){.pelanggan-search-form{max-width:none;flex-wrap:wrap}.pelanggan-search-input{width:100%;flex-basis:100%}.pelanggan-search-btn,.pelanggan-search-reset{flex:1}.pelanggan-stat-card{padding:18px}.pelanggan-stat-number{font-size:26px}}
</style>
</head>
<body>
<div class="admin-layout">
<?php require_once "../includes/sidebar.php"; ?>
<main class="admin-main">
<?php require_once "../includes/navbar.php"; ?>
<section class="admin-content">
<div class="pelanggan-page">
<div class="page-title"><h1>Pelanggan</h1><p>Kelola data pelanggan dan riwayat transaksi pelanggan.</p></div>

<?php if ($success !== '') : ?>
<div class="pelanggan-alert success">✓ <?= htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error !== '') : ?>
<div class="pelanggan-alert error">⚠ <?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="pelanggan-stat-grid">
<div class="pelanggan-stat-card"><span class="pelanggan-stat-label">Total Pelanggan</span><span class="pelanggan-stat-number"><?= $total_pelanggan; ?></span></div>
<div class="pelanggan-stat-card"><span class="pelanggan-stat-label">Pelanggan Bertransaksi</span><span class="pelanggan-stat-number"><?= $total_pelanggan_aktif; ?></span></div>
<div class="pelanggan-stat-card"><span class="pelanggan-stat-label">Total Pesanan</span><span class="pelanggan-stat-number"><?= $total_pesanan; ?></span></div>
</div>

<div class="pelanggan-card">
<div class="pelanggan-card-header">
<h2>Daftar Pelanggan</h2>
<p>Semua pelanggan yang terdaftar pada sistem.</p>
<div class="pelanggan-search">
<form method="GET" class="pelanggan-search-form">
<input type="search" name="search" value="<?= htmlspecialchars($search); ?>" class="pelanggan-search-input" placeholder="Cari nama, email, ID, atau tanggal..." autocomplete="off">
<button type="submit" class="pelanggan-search-btn">Cari</button>
<?php if ($search !== '') : ?>
<a href="index.php" class="pelanggan-search-reset">Reset</a>
<?php endif; ?>
</form>
</div>
</div>

<div class="pelanggan-table-wrapper">
<table class="pelanggan-table">
<thead>
<tr>
<th>No</th>
<th>Pelanggan</th>
<th>Email</th>
<th>Tanggal Daftar</th>
<th>Pesanan</th>
<th>Total Belanja</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>

<?php if ($query_pelanggan && mysqli_num_rows($query_pelanggan) > 0) : ?>
<?php $no = 1; ?>
<?php while ($pelanggan = mysqli_fetch_assoc($query_pelanggan)) : ?>
<tr>
<td><?= $no++; ?></td>
<td>
<span class="nama-pelanggan"><?= htmlspecialchars($pelanggan['nama']); ?></span>
<span class="id-pelanggan">ID #<?= (int)$pelanggan['id_pelanggan']; ?></span>
</td>
<td><span class="email-pelanggan"><?= htmlspecialchars($pelanggan['email']); ?></span></td>
<td><span class="tanggal-daftar"><?= date('d/m/Y H:i', strtotime($pelanggan['created_at'])); ?></span></td>
<td><span class="jumlah-pesanan"><?= (int)$pelanggan['jumlah_pesanan']; ?></span></td>
<td><span class="total-belanja">Rp <?= number_format((float)$pelanggan['total_belanja'],0,',','.'); ?></span></td>
<td>
<div class="aksi-wrapper">
<a href="detail.php?id=<?= (int)$pelanggan['id_pelanggan']; ?>" class="btn-detail">Detail</a>

<?php if ((int)$pelanggan['jumlah_pesanan'] === 0) : ?>
<form method="POST" action="hapus.php" onsubmit="return confirm('Yakin ingin menghapus pelanggan ini?');">
<input type="hidden" name="id_pelanggan" value="<?= (int)$pelanggan['id_pelanggan']; ?>">
<button type="submit" name="hapus_pelanggan" class="btn-hapus">Hapus</button>
</form>
<?php endif; ?>

</div>
</td>
</tr>
<?php endwhile; ?>

<?php else : ?>
<tr>
<td colspan="7">
<div class="pelanggan-kosong">
<div class="pelanggan-kosong-icon">👤</div>
<h3><?= $search !== '' ? 'Pelanggan Tidak Ditemukan' : 'Belum Ada Pelanggan'; ?></h3>
<p><?= $search !== '' ? 'Coba gunakan kata kunci pencarian yang lain.' : 'Data pelanggan akan muncul di sini.'; ?></p>
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