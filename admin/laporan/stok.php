<?php
session_start();

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";

cekAkses('laporan');

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

/* FILTER STOK */
$filter = $_GET['filter'] ?? 'semua';

$filter_valid = ['semua', 'aman', 'menipis', 'habis'];

if (!in_array($filter, $filter_valid, true)) {
    $filter = 'semua';
}

/* FUNGSI MENGAMBIL ANGKA */
function ambilAngkaStok($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        error_log("Query statistik stok gagal: " . mysqli_error($conn));
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    mysqli_free_result($result);

    return (int) ($row['total'] ?? 0);
}

/* STATISTIK STOK */
$total_produk = ambilAngkaStok(
    $conn,
    "SELECT COUNT(*) AS total FROM produk"
);

$total_stok = ambilAngkaStok(
    $conn,
    "SELECT COALESCE(SUM(stok), 0) AS total FROM produk"
);

$total_habis = ambilAngkaStok(
    $conn,
    "SELECT COUNT(*) AS total FROM produk WHERE stok <= 0"
);

$total_menipis = ambilAngkaStok(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produk
     WHERE stok > 0 AND stok <= stok_minimum"
);

$total_aman = max(
    0,
    $total_produk - $total_habis - $total_menipis
);

/* DATA STOK PRODUK */
$sql = "
    SELECT
        p.id_produk,
        p.nama_produk,
        p.stok,
        p.stok_minimum,
        p.lokasi_rak,
        k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k
        ON p.id_kategori = k.id_kategori
";

if ($filter === 'aman') {
    $sql .= " WHERE p.stok > p.stok_minimum";
} elseif ($filter === 'menipis') {
    $sql .= " WHERE p.stok > 0 AND p.stok <= p.stok_minimum";
} elseif ($filter === 'habis') {
    $sql .= " WHERE p.stok <= 0";
}

$sql .= " ORDER BY p.stok ASC, p.nama_produk ASC";

$query_produk = mysqli_query($conn, $sql);

if (!$query_produk) {
    error_log("Query daftar stok gagal: " . mysqli_error($conn));
}

/* FUNGSI STATUS STOK */
function statusStok($stok, $stok_minimum)
{
    $stok = (int) $stok;
    $stok_minimum = (int) $stok_minimum;

    if ($stok <= 0) {
        return [
            'label' => 'Habis',
            'class' => 'status-habis',
            'number_class' => 'stok-habis'
        ];
    }

    if ($stok <= $stok_minimum) {
        return [
            'label' => 'Menipis',
            'class' => 'status-menipis',
            'number_class' => 'stok-menipis'
        ];
    }

    return [
        'label' => 'Aman',
        'class' => 'status-aman',
        'number_class' => 'stok-aman'
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laporan Stok - Toko ATK &amp; Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .laporan-page {
            max-width: 1500px;
        }

        .laporan-stat-card,
        .stok-summary-card,
        .laporan-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        /* TOMBOL PDF - SAMA DENGAN PEMBELIAN DAN PENJUALAN */
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

        /* STATISTIK */
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

        .stat-danger {
            color: #b23b3b !important;
        }

        .stat-warning {
            color: #a66b00 !important;
        }

        .stat-success {
            color: #27804a !important;
        }

        /* RINGKASAN STOK */
        .stok-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stok-summary-card {
            padding: 18px 20px;
        }

        .stok-summary-card span {
            display: block;
            color: #7890a4;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .stok-summary-card strong {
            color: #102f4b;
            font-size: 20px;
        }

        /* TABEL */
        .laporan-card {
            margin-bottom: 25px;
            overflow: hidden;
        }

        .laporan-card-header {
            padding: 22px 25px;
            border-bottom: 1px solid #e6edf2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
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

        .stok-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .stok-filter a {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 7px;
            background: #f1f4f7;
            color: #506d84;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
        }

        .stok-filter a.active {
            background: #102f4b;
            color: #fff;
        }

        .stok-filter a:hover {
            opacity: .88;
        }

        .laporan-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .laporan-table {
            width: 100%;
            min-width: 900px;
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

        .kategori-badge {
            display: inline-block;
            padding: 5px 9px;
            background: #eef3f7;
            color: #506d84;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .stok-number {
            font-size: 16px;
            font-weight: 800;
        }

        .stok-habis {
            color: #b23b3b;
        }

        .stok-menipis {
            color: #a66b00;
        }

        .stok-aman {
            color: #27804a;
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

        .status-aman {
            background: #e3f5e9;
            color: #27804a;
        }

        .status-menipis {
            background: #fff4d6;
            color: #996900;
        }

        .status-habis {
            background: #fde7e7;
            color: #b23b3b;
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

            .stok-summary {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .laporan-stat {
                grid-template-columns: 1fr;
            }

            .laporan-card-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .stok-filter {
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
                    <h1>Laporan Stok</h1>
                    <p>
                        Informasi persediaan produk dan kondisi stok saat ini.
                    </p>
                </div>

                <!-- TOMBOL PDF -->
                <div style="display:flex; justify-content:flex-end; margin:0 0 18px;">
                    <a
                        class="btn-pdf"
                        href="export_pdf.php?jenis=stok&amp;filter=<?= urlencode($filter); ?>"
                    >
                        ⬇ Unduh PDF
                    </a>
                </div>

                <!-- STATISTIK -->
                <div class="laporan-stat">
                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Total Produk</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_produk, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Total Stok</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_stok, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Stok Menipis</span>
                        <span class="laporan-stat-number stat-warning">
                            <?= number_format($total_menipis, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Produk Habis</span>
                        <span class="laporan-stat-number stat-danger">
                            <?= number_format($total_habis, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <!-- RINGKASAN KONDISI STOK -->
                <div class="stok-summary">
                    <div class="stok-summary-card">
                        <span>Produk dengan Stok Aman</span>
                        <strong>
                            <?= number_format($total_aman, 0, ',', '.'); ?> produk
                        </strong>
                    </div>

                    <div class="stok-summary-card">
                        <span>Produk dengan Stok Menipis</span>
                        <strong>
                            <?= number_format($total_menipis, 0, ',', '.'); ?> produk
                        </strong>
                    </div>

                    <div class="stok-summary-card">
                        <span>Produk dengan Stok Habis</span>
                        <strong>
                            <?= number_format($total_habis, 0, ',', '.'); ?> produk
                        </strong>
                    </div>
                </div>

                <!-- DAFTAR STOK -->
                <div class="laporan-card">

                    <div class="laporan-card-header">
                        <div>
                            <h2>Daftar Persediaan Produk</h2>
                            <p>
                                Data stok saat ini berdasarkan kondisi dan batas minimum stok.
                            </p>
                        </div>

                        <!-- FILTER -->
                        <div class="stok-filter">
                            <a
                                class="<?= $filter === 'semua' ? 'active' : ''; ?>"
                                href="stok.php?filter=semua"
                            >
                                Semua
                            </a>

                            <a
                                class="<?= $filter === 'aman' ? 'active' : ''; ?>"
                                href="stok.php?filter=aman"
                            >
                                Aman
                            </a>

                            <a
                                class="<?= $filter === 'menipis' ? 'active' : ''; ?>"
                                href="stok.php?filter=menipis"
                            >
                                Menipis
                            </a>

                            <a
                                class="<?= $filter === 'habis' ? 'active' : ''; ?>"
                                href="stok.php?filter=habis"
                            >
                                Habis
                            </a>
                        </div>
                    </div>

                    <div class="laporan-table-wrapper">
                        <table class="laporan-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Lokasi Rak</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Stok Minimum</th>
                                    <th>Kondisi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if ($query_produk && mysqli_num_rows($query_produk) > 0): ?>
                                    <?php $no = 1; ?>

                                    <?php while ($row = mysqli_fetch_assoc($query_produk)): ?>
                                        <?php
                                        $status = statusStok(
                                            $row['stok'] ?? 0,
                                            $row['stok_minimum'] ?? 0
                                        );
                                        ?>

                                        <tr>
                                            <td><?= $no++; ?></td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $row['nama_produk'] ?? '-',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="kategori-badge">
                                                    <?= htmlspecialchars(
                                                        $row['nama_kategori'] ?? 'Tanpa kategori',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $row['lokasi_rak'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </td>

                                            <td>
                                                <span class="stok-number <?= $status['number_class']; ?>">
                                                    <?= number_format(
                                                        (int) ($row['stok'] ?? 0),
                                                        0,
                                                        ',',
                                                        '.'
                                                    ); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= number_format(
                                                    (int) ($row['stok_minimum'] ?? 0),
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </td>

                                            <td>
                                                <span class="status <?= $status['class']; ?>">
                                                    <?= $status['label']; ?>
                                                </span>
                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="laporan-empty">
                                            Tidak ada data stok untuk filter ini.
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

    async function refreshLaporanStok() {
        if (sedangRefresh) {
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

            const ringkasanLama = document.querySelector('.stok-summary');
            const ringkasanBaru = halamanBaru.querySelector('.stok-summary');

            const tabelLama = document.querySelector('.laporan-table-wrapper');
            const tabelBaru = halamanBaru.querySelector('.laporan-table-wrapper');

            // Hanya isi laporan yang diperbarui.
            // Navbar, sidebar, footer, tombol PDF, dan filter tetap utuh.
            if (statistikLama && statistikBaru) {
                statistikLama.innerHTML = statistikBaru.innerHTML;
            }

            if (ringkasanLama && ringkasanBaru) {
                ringkasanLama.innerHTML = ringkasanBaru.innerHTML;
            }

            if (tabelLama && tabelBaru) {
                tabelLama.innerHTML = tabelBaru.innerHTML;
            }

        } catch (error) {
            console.error('Gagal memperbarui laporan stok:', error);
        } finally {
            sedangRefresh = false;
        }
    }

    setInterval(refreshLaporanStok, 10000);
})();
</script>

</body>
</html>