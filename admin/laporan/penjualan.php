<?php
session_start();

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";

cekAkses('laporan');

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

/* VALIDASI TANGGAL */
function validTanggalPenjualan($tanggal)
{
    if (!is_string($tanggal)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $tanggal);

    return $date && $date->format('Y-m-d') === $tanggal;
}

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

if (!validTanggalPenjualan($tanggal_mulai)) {
    $tanggal_mulai = date('Y-m-01');
}

if (!validTanggalPenjualan($tanggal_selesai)) {
    $tanggal_selesai = date('Y-m-d');
}

if ($tanggal_mulai > $tanggal_selesai) {
    [$tanggal_mulai, $tanggal_selesai] = [
        $tanggal_selesai,
        $tanggal_mulai
    ];
}

/* FUNGSI STATISTIK */
function ambilNilaiPenjualan($conn, $sql, $mulai, $selesai)
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log("Query laporan penjualan gagal: " . mysqli_error($conn));
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "ss", $mulai, $selesai);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : [];

    mysqli_stmt_close($stmt);

    return $row['total'] ?? 0;
}

/* TOTAL TRANSAKSI */
$total_transaksi = (int) ambilNilaiPenjualan(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE DATE(tanggal_pesanan) BETWEEN ? AND ?",
    $tanggal_mulai,
    $tanggal_selesai
);

/* TOTAL PENJUALAN */
$total_penjualan = (float) ambilNilaiPenjualan(
    $conn,
    "SELECT COALESCE(SUM(total_harga), 0) AS total
     FROM pesanan
     WHERE DATE(tanggal_pesanan) BETWEEN ? AND ?
       AND status_pesanan <> 'Dibatalkan'",
    $tanggal_mulai,
    $tanggal_selesai
);

/* TOTAL DISKON VOUCHER */
$total_diskon = (float) ambilNilaiPenjualan(
    $conn,
    "SELECT COALESCE(SUM(diskon_voucher), 0) AS total
     FROM pesanan
     WHERE DATE(tanggal_pesanan) BETWEEN ? AND ?
       AND status_pesanan <> 'Dibatalkan'",
    $tanggal_mulai,
    $tanggal_selesai
);

/* TOTAL PRODUK TERJUAL */
$total_produk = (int) ambilNilaiPenjualan(
    $conn,
    "SELECT COALESCE(SUM(dp.jumlah), 0) AS total
     FROM detail_pesanan dp
     INNER JOIN pesanan p
        ON dp.id_pesanan = p.id_pesanan
     WHERE DATE(p.tanggal_pesanan) BETWEEN ? AND ?
       AND p.status_pesanan <> 'Dibatalkan'",
    $tanggal_mulai,
    $tanggal_selesai
);

/* DATA LAPORAN PENJUALAN */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        p.kode_voucher,
        p.diskon_voucher,
        pl.nama
     FROM pesanan p
     LEFT JOIN pelanggan pl
        ON p.id_pelanggan = pl.id_pelanggan
     WHERE DATE(p.tanggal_pesanan) BETWEEN ? AND ?
     ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC"
);

$laporan_penjualan = [];

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $tanggal_mulai,
        $tanggal_selesai
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $laporan_penjualan[] = $row;
    }

    mysqli_stmt_close($stmt);
} else {
    error_log("Query data laporan penjualan gagal: " . mysqli_error($conn));
}

/* STATUS PESANAN */
function classStatusPenjualan($status)
{
    switch ($status) {
        case 'Menunggu Pembayaran':
            return 'status-menunggu';

        case 'Menunggu Verifikasi':
            return 'status-verifikasi';

        case 'Diproses':
            return 'status-proses';

        case 'Siap Diambil':
            return 'status-pickup';

        case 'Selesai':
            return 'status-selesai';

        case 'Dibatalkan':
            return 'status-batal';

        default:
            return 'status-default';
    }
}

/* FORMAT RUPIAH */
function rupiahPenjualan($angka)
{
    return number_format((float) $angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laporan Penjualan - Toko ATK &amp; Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .laporan-page {
            max-width: 1500px;
        }

        .laporan-filter,
        .laporan-card,
        .laporan-stat-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        .laporan-filter {
            padding: 22px 25px;
            margin-bottom: 25px;
        }

        .laporan-filter h2 {
            color: #102f4b;
            font-size: 18px;
            margin: 0 0 18px;
        }

        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .filter-item label {
            color: #506d84;
            font-size: 13px;
            font-weight: 700;
        }

        .filter-item input {
            height: 42px;
            min-width: 180px;
            padding: 0 12px;
            border: 1px solid #d6e0e8;
            border-radius: 8px;
            color: #334f66;
            background: #fff;
            box-sizing: border-box;
        }

        .btn-filter,
        .btn-pdf,
        .btn-detail {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-filter {
            height: 42px;
            padding: 0 18px;
            background: #102f4b;
            color: #fff;
        }

        .btn-pdf {
            padding: 10px 15px;
            background: #102a43;
            color: #fff;
            font-size: 13px;
        }

        .btn-filter:hover,
        .btn-detail:hover,
        .btn-pdf:hover {
            background: #1c496b;
        }

        .laporan-stat {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .laporan-stat-card {
            padding: 23px;
        }

        .laporan-stat-label {
            display: block;
            color: #7890a4;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .laporan-stat-number {
            display: block;
            color: #102f4b;
            font-size: 26px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .laporan-card {
            margin-bottom: 25px;
            overflow: hidden;
        }

        .laporan-card-header {
            padding: 22px 25px;
            border-bottom: 1px solid #e6edf2;
        }

        .laporan-card-header h2 {
            color: #102f4b;
            font-size: 20px;
            margin: 0 0 5px;
        }

        .laporan-card-header p {
            color: #7890a4;
            font-size: 13px;
            margin: 0;
        }

        .laporan-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .laporan-table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
        }

        .laporan-table th {
            background: #f4f7fa;
            color: #506d84;
            text-align: left;
            padding: 14px 15px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .laporan-table td {
            padding: 14px 15px;
            border-top: 1px solid #e5ebf0;
            color: #334f66;
            font-size: 13px;
            vertical-align: middle;
        }

        .laporan-table tbody tr:hover {
            background: #fafcfd;
        }

        .laporan-table strong {
            color: #102f4b;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-menunggu {
            background: #fff4d6;
            color: #996900;
        }

        .status-verifikasi {
            background: #e4efff;
            color: #315fa5;
        }

        .status-proses {
            background: #eee8ff;
            color: #6847a6;
        }

        .status-pickup {
            background: #e2f5f5;
            color: #247579;
        }

        .status-selesai {
            background: #e3f5e9;
            color: #27804a;
        }

        .status-batal {
            background: #fde7e7;
            color: #b23b3b;
        }

        .status-default {
            background: #edf0f2;
            color: #687681;
        }

        .voucher-badge {
            display: inline-block;
            padding: 5px 8px;
            background: #eef5fb;
            color: #20567d;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .tanpa-voucher {
            color: #9aa9b5;
            font-size: 12px;
        }

        .diskon {
            color: #27804a;
            font-weight: 700;
        }

        .btn-detail {
            padding: 7px 11px;
            background: #102f4b;
            color: #fff;
            font-size: 11px;
            white-space: nowrap;
        }

        .laporan-empty {
            text-align: center;
            padding: 45px 20px !important;
            color: #7890a4 !important;
        }

        @media (max-width: 1100px) {
            .laporan-stat {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .laporan-stat {
                grid-template-columns: 1fr;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-item,
            .filter-item input,
            .btn-filter {
                width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }
        }

        @media (max-width: 500px) {
            .laporan-filter,
            .laporan-stat-card {
                padding: 18px;
            }

            .laporan-stat-number {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>
<div class="admin-layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="admin-main">

        <?php require_once __DIR__ . "/../includes/navbar.php"; ?>

        <section class="admin-content">
            <div class="laporan-page">

                <div class="page-title">
                    <h1>Laporan Penjualan</h1>
                    <p>
                        Ringkasan transaksi penjualan dan pesanan pelanggan
                        berdasarkan periode.
                    </p>
                </div>

                <div style="display:flex;justify-content:flex-end;margin:0 0 18px;">
                    <a
                        class="btn-pdf"
                        href="export_pdf.php?jenis=penjualan&amp;tanggal_mulai=<?= urlencode($tanggal_mulai); ?>&amp;tanggal_selesai=<?= urlencode($tanggal_selesai); ?>"
                    >
                        ⬇ Unduh PDF
                    </a>
                </div>

                <div class="laporan-filter">
                    <h2>Filter Periode Laporan</h2>

                    <form method="GET" class="filter-form">
                        <div class="filter-item">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input
                                type="date"
                                id="tanggal_mulai"
                                name="tanggal_mulai"
                                value="<?= htmlspecialchars($tanggal_mulai, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>

                        <div class="filter-item">
                            <label for="tanggal_selesai">Tanggal Selesai</label>
                            <input
                                type="date"
                                id="tanggal_selesai"
                                name="tanggal_selesai"
                                value="<?= htmlspecialchars($tanggal_selesai, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="btn-filter">
                            Tampilkan Laporan
                        </button>
                    </form>
                </div>

                <div class="laporan-stat">
                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Total Transaksi</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_transaksi, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Total Penjualan</span>
                        <span class="laporan-stat-number">
                            Rp <?= rupiahPenjualan($total_penjualan); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Diskon Voucher</span>
                        <span class="laporan-stat-number">
                            Rp <?= rupiahPenjualan($total_diskon); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Produk Terjual</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_produk, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <div class="laporan-card">
                    <div class="laporan-card-header">
                        <h2>Daftar Penjualan</h2>
                        <p>
                            Data pesanan pelanggan berdasarkan periode yang dipilih.
                        </p>
                    </div>

                    <div class="laporan-table-wrapper">
                        <table class="laporan-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Voucher</th>
                                    <th>Diskon</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($laporan_penjualan)): ?>
                                    <?php $no = 1; ?>

                                    <?php foreach ($laporan_penjualan as $row): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $row['nomor_pesanan'] ?? '-',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $row['nama'] ?? 'Pelanggan dihapus',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </td>

                                            <td>
                                                <?= !empty($row['tanggal_pesanan'])
                                                    ? date(
                                                        'd/m/Y',
                                                        strtotime($row['tanggal_pesanan'])
                                                    )
                                                    : '-'; ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($row['kode_voucher'])): ?>
                                                    <span class="voucher-badge">
                                                        <?= htmlspecialchars(
                                                            $row['kode_voucher'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="tanpa-voucher">
                                                        Tidak ada
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="diskon">
                                                Rp <?= rupiahPenjualan(
                                                    $row['diskon_voucher'] ?? 0
                                                ); ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    Rp <?= rupiahPenjualan(
                                                        $row['total_harga'] ?? 0
                                                    ); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="status <?= classStatusPenjualan($row['status_pesanan'] ?? ''); ?>">
                                                    <?= htmlspecialchars(
                                                        $row['status_pesanan'] ?? '-',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <a
                                                    class="btn-detail"
                                                    href="../pesanan/detail.php?id=<?= (int)$row['id_pesanan']; ?>"
                                                >
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="laporan-empty">
                                            Tidak ada data penjualan pada periode ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </main>
</div>

<!-- JavaScript utama admin tetap aktif -->
<script src="../assets/js/admin.js"></script>

<!-- Auto-refresh hanya pada statistik dan tabel -->
<script>
(function () {
    let sedangRefresh = false;

    async function refreshLaporan() {
        if (sedangRefresh) {
            return;
        }

        const formFilter = document.querySelector('.filter-form');

        // Hindari gangguan saat tanggal sedang dipilih.
        if (formFilter && formFilter.contains(document.activeElement)) {
            return;
        }

        sedangRefresh = true;

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('_refresh', Date.now());

            const response = await fetch(url.toString(), {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                return;
            }

            const html = await response.text();

            const halamanBaru = new DOMParser().parseFromString(
                html,
                'text/html'
            );

            const statistikLama = document.querySelector('.laporan-stat');
            const statistikBaru = halamanBaru.querySelector('.laporan-stat');

            const tabelLama = document.querySelector('.laporan-table-wrapper');
            const tabelBaru = halamanBaru.querySelector('.laporan-table-wrapper');

            // Tidak mengganti navbar, sidebar, footer, atau seluruh halaman.
            if (statistikLama && statistikBaru) {
                statistikLama.innerHTML = statistikBaru.innerHTML;
            }

            if (tabelLama && tabelBaru) {
                tabelLama.innerHTML = tabelBaru.innerHTML;
            }

        } catch (error) {
            console.error('Gagal memperbarui laporan penjualan:', error);
        } finally {
            sedangRefresh = false;
        }
    }

    setInterval(refreshLaporan, 10000);
})();
</script>

</body>
</html>