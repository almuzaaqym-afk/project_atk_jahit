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
function validTanggalProduk($tanggal)
{
    if (!is_string($tanggal)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $tanggal);

    return $date && $date->format('Y-m-d') === $tanggal;
}

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

if (!validTanggalProduk($tanggal_mulai)) {
    $tanggal_mulai = date('Y-m-01');
}

if (!validTanggalProduk($tanggal_akhir)) {
    $tanggal_akhir = date('Y-m-d');
}

if ($tanggal_mulai > $tanggal_akhir) {
    [$tanggal_mulai, $tanggal_akhir] = [
        $tanggal_akhir,
        $tanggal_mulai
    ];
}

/* DATA PRODUK TERLARIS */
$query_produk = "
    SELECT
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.stok,
        k.nama_kategori,
        COALESCE(SUM(dp.jumlah), 0) AS total_terjual,
        COALESCE(SUM(dp.subtotal), 0) AS total_pendapatan
    FROM detail_pesanan dp
    INNER JOIN pesanan ps
        ON dp.id_pesanan = ps.id_pesanan
    INNER JOIN produk p
        ON dp.id_produk = p.id_produk
    LEFT JOIN kategori k
        ON p.id_kategori = k.id_kategori
    WHERE DATE(ps.tanggal_pesanan) BETWEEN ? AND ?
      AND ps.status_pesanan <> 'Dibatalkan'
    GROUP BY
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.stok,
        k.nama_kategori
    ORDER BY
        total_terjual DESC,
        total_pendapatan DESC
";

$stmt = mysqli_prepare($conn, $query_produk);

$data_produk = [];
$total_produk_terjual = 0;
$total_unit_terjual = 0;
$total_pendapatan = 0;

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $tanggal_mulai,
        $tanggal_akhir
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $data_produk[] = $row;
        $total_produk_terjual++;
        $total_unit_terjual += (int) $row['total_terjual'];
        $total_pendapatan += (float) $row['total_pendapatan'];
    }

    mysqli_stmt_close($stmt);
} else {
    error_log("Query laporan produk gagal: " . mysqli_error($conn));
}

/* PRODUK TERLARIS */
$produk_terlaris = $data_produk[0] ?? null;

/* PENDAPATAN PRODUK TERBESAR */
$produk_pendapatan_terbesar = null;

foreach ($data_produk as $produk) {
    if (
        $produk_pendapatan_terbesar === null ||
        (float) $produk['total_pendapatan'] >
        (float) $produk_pendapatan_terbesar['total_pendapatan']
    ) {
        $produk_pendapatan_terbesar = $produk;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produk Terlaris - Toko ATK &amp; Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .laporan-page {
            max-width: 1500px;
        }

        .produk-filter,
        .laporan-stat-card,
        .produk-highlight-card,
        .laporan-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        .produk-filter {
            padding: 20px 22px;
            margin-bottom: 25px;
        }

        .produk-filter-form {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .produk-filter-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .produk-filter-group label {
            color: #506d84;
            font-size: 12px;
            font-weight: 700;
        }

        .produk-filter-group input {
            width: 190px;
            height: 40px;
            box-sizing: border-box;
            padding: 0 11px;
            border: 1px solid #d8e0e6;
            border-radius: 7px;
            background: #fff;
            color: #334f66;
            font-size: 13px;
        }

        .produk-filter-group input:focus {
            border-color: #102f4b;
            outline: none;
        }

        .produk-filter-button,
        .produk-reset-button {
            height: 40px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            border: none;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .produk-filter-button {
            background: #102f4b;
            color: #fff;
        }

        .produk-filter-button:hover {
            background: #1c496b;
        }

        .produk-reset-button {
            background: #f1f4f7;
            color: #506d84;
        }

        .produk-reset-button:hover {
            background: #e5ebf0;
        }

        /* TOMBOL PDF - DISAMAKAN DENGAN LAPORAN LAIN */
        .btn-pdf {
            display: inline-block;
            background: #102a43;
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(16, 42, 67, .12);
        }

        .btn-pdf:hover {
            background: #1c496b;
        }

        .laporan-stat {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
            font-size: 27px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .produk-highlight {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .produk-highlight-card {
            padding: 18px 20px;
        }

        .produk-highlight-card span {
            display: block;
            color: #7890a4;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .produk-highlight-card strong {
            display: block;
            color: #102f4b;
            font-size: 19px;
            margin-bottom: 5px;
            overflow-wrap: anywhere;
        }

        .produk-highlight-info {
            color: #506d84;
            font-size: 13px;
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
            border-collapse: collapse;
            min-width: 950px;
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

        .rank-badge {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef3f7;
            color: #102f4b;
            font-size: 12px;
            font-weight: 800;
        }

        .rank-1 {
            background: #fff1c7;
            color: #946d00;
        }

        .rank-2 {
            background: #e9eef2;
            color: #536b7d;
        }

        .rank-3 {
            background: #f8e9dc;
            color: #925f38;
        }

        .kategori-badge,
        .stok-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .kategori-badge {
            background: #eef3f7;
            color: #506d84;
        }

        .stok-badge {
            background: #f1f4f7;
            color: #506d84;
        }

        .terjual-number {
            color: #27804a;
            font-weight: 800;
        }

        .pendapatan-number {
            color: #102f4b;
            font-weight: 700;
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

            .produk-highlight {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .laporan-stat {
                grid-template-columns: 1fr;
            }

            .produk-filter-form {
                align-items: stretch;
                flex-direction: column;
            }

            .produk-filter-group input,
            .produk-filter-button,
            .produk-reset-button {
                width: 100%;
            }
        }

        @media (max-width: 500px) {
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
                    <h1>Produk Terlaris</h1>
                    <p>
                        Melihat produk yang paling banyak terjual berdasarkan periode tanggal.
                    </p>
                </div>

                <!-- TOMBOL PDF -->
                <div style="display:flex; justify-content:flex-end; margin:0 0 18px;">
                    <a
                        class="btn-pdf"
                        href="export_pdf.php?jenis=produk&amp;tanggal_mulai=<?= urlencode($tanggal_mulai); ?>&amp;tanggal_akhir=<?= urlencode($tanggal_akhir); ?>"
                    >
                        ⬇ Unduh PDF
                    </a>
                </div>

                <!-- FILTER TANGGAL -->
                <div class="produk-filter">
                    <form method="GET" class="produk-filter-form">

                        <div class="produk-filter-group">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input
                                type="date"
                                id="tanggal_mulai"
                                name="tanggal_mulai"
                                value="<?= htmlspecialchars($tanggal_mulai, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>

                        <div class="produk-filter-group">
                            <label for="tanggal_akhir">Tanggal Akhir</label>
                            <input
                                type="date"
                                id="tanggal_akhir"
                                name="tanggal_akhir"
                                value="<?= htmlspecialchars($tanggal_akhir, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="produk-filter-button">
                            Tampilkan
                        </button>

                        <a href="produk.php" class="produk-reset-button">
                            Reset
                        </a>

                    </form>
                </div>

                <!-- STATISTIK -->
                <div class="laporan-stat">
                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Produk Terjual</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_produk_terjual, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Total Unit Terjual</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_unit_terjual, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Total Pendapatan</span>
                        <span class="laporan-stat-number">
                            Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <!-- HIGHLIGHT PRODUK -->
                <div id="produk-highlight-container">
                    <?php if ($produk_terlaris || $produk_pendapatan_terbesar): ?>
                        <div class="produk-highlight">

                            <?php if ($produk_terlaris): ?>
                                <div class="produk-highlight-card">
                                    <span>Produk Paling Banyak Terjual</span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $produk_terlaris['nama_produk'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                    <div class="produk-highlight-info">
                                        <?= number_format(
                                            $produk_terlaris['total_terjual'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                        unit terjual
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($produk_pendapatan_terbesar): ?>
                                <div class="produk-highlight-card">
                                    <span>Pendapatan Produk Terbesar</span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $produk_pendapatan_terbesar['nama_produk'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                    <div class="produk-highlight-info">
                                        Rp <?= number_format(
                                            $produk_pendapatan_terbesar['total_pendapatan'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>
                </div>

                <!-- TABEL PRODUK -->
                <div class="laporan-card">
                    <div class="laporan-card-header">
                        <h2>Ranking Produk Terlaris</h2>
                        <p>
                            Produk berdasarkan jumlah penjualan pada periode yang dipilih.
                        </p>
                    </div>

                    <div class="laporan-table-wrapper">
                        <table class="laporan-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Terjual</th>
                                    <th>Pendapatan</th>
                                    <th>Stok Saat Ini</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($data_produk)): ?>
                                    <?php $no = 1; ?>

                                    <?php foreach ($data_produk as $produk): ?>
                                        <?php
                                        $rank_class = '';

                                        if ($no === 1) {
                                            $rank_class = 'rank-1';
                                        } elseif ($no === 2) {
                                            $rank_class = 'rank-2';
                                        } elseif ($no === 3) {
                                            $rank_class = 'rank-3';
                                        }
                                        ?>

                                        <tr>
                                            <td>
                                                <span class="rank-badge <?= $rank_class; ?>">
                                                    <?= $no; ?>
                                                </span>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $produk['nama_produk'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="kategori-badge">
                                                    <?= htmlspecialchars(
                                                        $produk['nama_kategori'] ?? 'Tanpa Kategori',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </span>
                                            </td>

                                            <td>
                                                Rp <?= number_format(
                                                    $produk['harga'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </td>

                                            <td>
                                                <span class="terjual-number">
                                                    <?= number_format(
                                                        $produk['total_terjual'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ); ?>
                                                </span>
                                                unit
                                            </td>

                                            <td>
                                                <span class="pendapatan-number">
                                                    Rp <?= number_format(
                                                        $produk['total_pendapatan'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="stok-badge">
                                                    <?= number_format(
                                                        $produk['stok'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ); ?>
                                                    stok
                                                </span>
                                            </td>
                                        </tr>

                                        <?php $no++; ?>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="laporan-empty">
                                            Belum ada produk yang terjual pada periode
                                            <?= date('d/m/Y', strtotime($tanggal_mulai)); ?>
                                            sampai
                                            <?= date('d/m/Y', strtotime($tanggal_akhir)); ?>.
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

<script src="../assets/js/admin.js"></script>

<!-- AUTO-REFRESH TANPA INDIKATOR -->
<script>
(function () {
    let sedangRefresh = false;

    async function refreshLaporanProduk() {
        if (sedangRefresh) {
            return;
        }

        const filterForm = document.querySelector('.produk-filter-form');

        if (filterForm && filterForm.contains(document.activeElement)) {
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

            const highlightLama = document.querySelector(
                '#produk-highlight-container'
            );

            const highlightBaru = halamanBaru.querySelector(
                '#produk-highlight-container'
            );

            const tabelLama = document.querySelector(
                '.laporan-table-wrapper'
            );

            const tabelBaru = halamanBaru.querySelector(
                '.laporan-table-wrapper'
            );

            // Navbar, sidebar, tombol PDF, dan filter tidak diganti.
            if (statistikLama && statistikBaru) {
                statistikLama.innerHTML = statistikBaru.innerHTML;
            }

            if (highlightLama && highlightBaru) {
                highlightLama.innerHTML = highlightBaru.innerHTML;
            }

            if (tabelLama && tabelBaru) {
                tabelLama.innerHTML = tabelBaru.innerHTML;
            }

        } catch (error) {
            console.error('Gagal memperbarui laporan produk:', error);
        } finally {
            sedangRefresh = false;
        }
    }

    setInterval(refreshLaporanProduk, 10000);
})();
</script>

</body>
</html>