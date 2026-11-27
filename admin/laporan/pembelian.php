<?php
session_start();

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('laporan');

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

/* FILTER TANGGAL */
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

function validTanggalPembelian($tanggal)
{
    if (!is_string($tanggal)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $tanggal);

    return $date && $date->format('Y-m-d') === $tanggal;
}

if (!validTanggalPembelian($tanggal_mulai)) {
    $tanggal_mulai = date('Y-m-01');
}

if (!validTanggalPembelian($tanggal_selesai)) {
    $tanggal_selesai = date('Y-m-d');
}

if ($tanggal_mulai > $tanggal_selesai) {
    [$tanggal_mulai, $tanggal_selesai] = [
        $tanggal_selesai,
        $tanggal_mulai
    ];
}

/* TOTAL TRANSAKSI */
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pembelian
     WHERE DATE(tanggal_pembelian) BETWEEN ? AND ?"
);

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_transaksi = (int)(mysqli_fetch_assoc($result)['total'] ?? 0);
mysqli_stmt_close($stmt);

/* TOTAL PEMBELIAN SELESAI */
$stmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(total_harga), 0) AS total
     FROM pembelian
     WHERE DATE(tanggal_pembelian) BETWEEN ? AND ?
       AND status = 'Selesai'"
);

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_pembelian = (float)(mysqli_fetch_assoc($result)['total'] ?? 0);
mysqli_stmt_close($stmt);

/* JUMLAH SUPPLIER */
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(DISTINCT id_supplier) AS total
     FROM pembelian
     WHERE DATE(tanggal_pembelian) BETWEEN ? AND ?"
);

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_supplier = (int)(mysqli_fetch_assoc($result)['total'] ?? 0);
mysqli_stmt_close($stmt);

/* TOTAL ITEM PEMBELIAN SELESAI */
$stmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(dp.jumlah), 0) AS total
     FROM detail_pembelian dp
     INNER JOIN pembelian p
        ON dp.id_pembelian = p.id_pembelian
     WHERE DATE(p.tanggal_pembelian) BETWEEN ? AND ?
       AND p.status = 'Selesai'"
);

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_item = (int)(mysqli_fetch_assoc($result)['total'] ?? 0);
mysqli_stmt_close($stmt);

/* DATA LAPORAN PEMBELIAN */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        pb.id_pembelian,
        pb.nomor_pembelian,
        pb.tanggal_pembelian,
        pb.total_harga,
        pb.status,
        pb.id_supplier,
        s.nama_supplier
     FROM pembelian pb
     LEFT JOIN supplier s
        ON pb.id_supplier = s.id_supplier
     WHERE DATE(pb.tanggal_pembelian) BETWEEN ? AND ?
     ORDER BY pb.tanggal_pembelian DESC, pb.id_pembelian DESC"
);

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$laporan_pembelian = [];

while ($row = mysqli_fetch_assoc($result)) {
    $laporan_pembelian[] = $row;
}

mysqli_stmt_close($stmt);

/* FORMAT STATUS */
function classStatusPembelian($status)
{
    switch ($status) {
        case 'Selesai':
            return 'status-selesai';

        case 'Draft':
            return 'status-draft';

        case 'Dibatalkan':
            return 'status-batal';

        default:
            return 'status-default';
    }
}

/* FORMAT RUPIAH */
function rupiahPembelian($angka)
{
    return number_format((float)$angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Laporan Pembelian - Toko ATK & Alat Jahit</title>

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
            display: inline-block;
            padding: 10px 15px;
            background: #102a43;
            color: #fff;
            font-size: 13px;
        }

        .btn-filter:hover,
        .btn-pdf:hover,
        .btn-detail:hover {
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
            min-width: 950px;
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

        .status-selesai {
            background: #e3f5e9;
            color: #27804a;
        }

        .status-draft {
            background: #fff4d6;
            color: #996900;
        }

        .status-batal {
            background: #fde7e7;
            color: #b23b3b;
        }

        .status-default {
            background: #edf0f2;
            color: #687681;
        }

        .btn-detail {
            display: inline-block;
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

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="admin-main">

        <?php require_once "../includes/navbar.php"; ?>

        <section class="admin-content">
            <div class="laporan-page">

                <div class="page-title">
                    <h1>Laporan Pembelian</h1>
                    <p>
                        Ringkasan pembelian barang dari supplier berdasarkan periode.
                    </p>
                </div>

                <div style="display:flex;justify-content:flex-end;margin:0 0 18px;">
                    <a
                        class="btn-pdf"
                        href="export_pdf.php?jenis=pembelian&amp;tanggal_mulai=<?= urlencode($tanggal_mulai); ?>&amp;tanggal_selesai=<?= urlencode($tanggal_selesai); ?>"
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
                        <span class="laporan-stat-label">Total Pembelian</span>
                        <span class="laporan-stat-number">
                            Rp <?= rupiahPembelian($total_pembelian); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Supplier</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_supplier, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="laporan-stat-card">
                        <span class="laporan-stat-label">Item Dibeli</span>
                        <span class="laporan-stat-number">
                            <?= number_format($total_item, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <div class="laporan-card">
                    <div class="laporan-card-header">
                        <h2>Daftar Pembelian</h2>
                        <p>
                            Data pembelian berdasarkan periode yang dipilih.
                        </p>
                    </div>

                    <div class="laporan-table-wrapper">
                        <table class="laporan-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Pembelian</th>
                                    <th>Supplier</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($laporan_pembelian)) : ?>
                                    <?php $no = 1; ?>

                                    <?php foreach ($laporan_pembelian as $row) : ?>
                                        <tr>
                                            <td><?= $no++; ?></td>

                                            <td>
                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $row['nomor_pembelian'] ?? '-',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $row['nama_supplier'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </td>

                                            <td>
                                                <?= !empty($row['tanggal_pembelian'])
                                                    ? date(
                                                        'd/m/Y',
                                                        strtotime($row['tanggal_pembelian'])
                                                    )
                                                    : '-'; ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    Rp <?= rupiahPembelian(
                                                        $row['total_harga'] ?? 0
                                                    ); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="status <?= classStatusPembelian($row['status'] ?? ''); ?>">
                                                    <?= htmlspecialchars(
                                                        $row['status'] ?? '-',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <a
                                                    href="../pembelian/detail.php?id=<?= (int)$row['id_pembelian']; ?>"
                                                    class="btn-detail"
                                                >
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="laporan-empty">
                                            Tidak ada data pembelian pada periode ini.
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

<!-- JavaScript utama admin tetap dipanggil agar navbar/sidebar berfungsi -->
<script src="../assets/js/admin.js"></script>

<!-- Auto-refresh hanya untuk statistik dan tabel laporan -->
<script>
(function () {
    let sedangRefresh = false;

    async function refreshLaporan() {
        if (sedangRefresh) {
            return;
        }

        const formFilter = document.querySelector('.filter-form');

        if (formFilter && formFilter.contains(document.activeElement)) {
            return;
        }

        sedangRefresh = true;

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('_refresh', Date.now());

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
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

            if (statistikLama && statistikBaru) {
                statistikLama.innerHTML = statistikBaru.innerHTML;
            }

            if (tabelLama && tabelBaru) {
                tabelLama.innerHTML = tabelBaru.innerHTML;
            }

        } catch (error) {
            console.error('Gagal memperbarui laporan pembelian:', error);
        } finally {
            sedangRefresh = false;
        }
    }

    setInterval(refreshLaporan, 10000);
})();
</script>

</body>
</html>