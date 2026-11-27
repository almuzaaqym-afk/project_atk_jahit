<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('pesanan');

$success = '';
$error = '';
$search = trim($_GET['search'] ?? '');
$search_escaped = mysqli_real_escape_string($conn, $search);

/* =========================================================
   PESAN STATUS
========================================================= */

if (isset($_GET['status']) && $_GET['status'] === 'berhasil') {
    $success = "Status pesanan berhasil diperbarui.";
}

if (isset($_GET['status']) && $_GET['status'] === 'gagal') {
    $error = "Status pesanan gagal diperbarui.";
}

/* =========================================================
   STATISTIK PESANAN
========================================================= */

$query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM pesanan"
);

$total_pesanan = (int)(
    mysqli_fetch_assoc($query)['total'] ?? 0
);

$query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE status_pesanan IN ('Diproses', 'Siap Diambil')"
);

$total_proses = (int)(
    mysqli_fetch_assoc($query)['total'] ?? 0
);

$query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE status_pesanan = 'Selesai'"
);

$total_selesai = (int)(
    mysqli_fetch_assoc($query)['total'] ?? 0
);

$query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE status_pesanan = 'Dibatalkan'"
);

$total_dibatalkan = (int)(
    mysqli_fetch_assoc($query)['total'] ?? 0
);

/* =========================================================
   SEARCH PESANAN
========================================================= */

$where_search = '';

if ($search !== '') {
    $where_search = "WHERE (
        p.nomor_pesanan LIKE '%$search_escaped%'
        OR pl.nama LIKE '%$search_escaped%'
        OR p.status_pesanan LIKE '%$search_escaped%'
        OR DATE_FORMAT(p.tanggal_pesanan, '%d/%m/%Y') LIKE '%$search_escaped%'
        OR DATE_FORMAT(p.tanggal_pesanan, '%d-%m-%Y') LIKE '%$search_escaped%'
        OR EXISTS (
            SELECT 1
            FROM detail_pesanan dp_search
            LEFT JOIN produk pr_search
                ON dp_search.id_produk = pr_search.id_produk
            LEFT JOIN produk_varian pv_search
                ON dp_search.id_varian = pv_search.id_varian
            WHERE dp_search.id_pesanan = p.id_pesanan
            AND (
                pr_search.nama_produk LIKE '%$search_escaped%'
                OR pv_search.nama_varian LIKE '%$search_escaped%'
                OR dp_search.nama_varian LIKE '%$search_escaped%'
            )
        )
    )";
}

/* =========================================================
   DATA PESANAN
   PRIORITAS STATUS:
   1. Siap Diambil
   2. Diproses
   3. Selesai
   4. Dibatalkan
   Status lain ditampilkan setelahnya.
   Dalam status yang sama, terbaru lebih dahulu.
========================================================= */

$query_pesanan = mysqli_query(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        pl.nama
     FROM pesanan p
     LEFT JOIN pelanggan pl
        ON p.id_pelanggan = pl.id_pelanggan
     $where_search
     ORDER BY
        CASE p.status_pesanan
            WHEN 'Siap Diambil' THEN 1
            WHEN 'Diproses' THEN 2
            WHEN 'Selesai' THEN 3
            WHEN 'Dibatalkan' THEN 4
            ELSE 5
        END ASC,
        p.id_pesanan DESC"
);

/* =========================================================
   STATUS CLASS
========================================================= */

function classStatus($status)
{
    switch ($status) {
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

/* =========================================================
   SIMPAN DATA PESANAN KE ARRAY
========================================================= */

$pesanan_rows = [];

if ($query_pesanan) {
    while ($row = mysqli_fetch_assoc($query_pesanan)) {
        $pesanan_rows[] = $row;
    }
}

/* =========================================================
   BANGUN HTML BARIS TABEL
========================================================= */

ob_start();

if (!empty($pesanan_rows)):
    $no = 1;

    foreach ($pesanan_rows as $pesanan):
?>

<tr>
    <td><?= $no++; ?></td>

    <td>
        <span class="nomor-pesanan">
            <?= htmlspecialchars(
                $pesanan['nomor_pesanan'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </span>

        <span class="id-pesanan">
            ID #<?= (int)$pesanan['id_pesanan']; ?>
        </span>
    </td>

    <td>
        <span class="nama-pelanggan">
            <?= htmlspecialchars(
                $pesanan['nama'] ?? '-',
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </span>
    </td>

    <td>
        <?= !empty($pesanan['tanggal_pesanan'])
            ? date('d/m/Y', strtotime($pesanan['tanggal_pesanan']))
            : '-'; ?>

        <br>

        <small style="color:#9aafbf;">
            <?= !empty($pesanan['tanggal_pesanan'])
                ? date('H:i', strtotime($pesanan['tanggal_pesanan']))
                : '-'; ?>
        </small>
    </td>

    <td>
        <span class="total-harga">
            Rp <?= number_format(
                (float)$pesanan['total_harga'],
                0,
                ',',
                '.'
            ); ?>
        </span>
    </td>

    <td>
        <span class="metode-qris">QRIS</span>
    </td>

    <td>
        <span class="pesanan-status <?= classStatus($pesanan['status_pesanan']); ?>">
            <?= htmlspecialchars(
                $pesanan['status_pesanan'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </span>
    </td>

    <td>
        <a
            href="detail.php?id=<?= (int)$pesanan['id_pesanan']; ?>"
            class="btn-detail"
        >
            Detail
        </a>
    </td>
</tr>

<?php
    endforeach;
else:
?>

<tr>
    <td colspan="8">
        <div class="pesanan-kosong">
            <div class="pesanan-kosong-icon">🛒</div>

            <h3>
                <?= $search !== ''
                    ? 'Pesanan Tidak Ditemukan'
                    : 'Belum Ada Pesanan'; ?>
            </h3>

            <p>
                <?= $search !== ''
                    ? 'Tidak ada pesanan yang sesuai dengan kata pencarian.'
                    : 'Pesanan pelanggan akan muncul di sini.'; ?>
            </p>
        </div>
    </td>
</tr>

<?php
endif;

$rows_html = ob_get_clean();
$rows_hash = hash('sha256', $rows_html);

/* =========================================================
   MODE SINKRONISASI
   Mengembalikan JSON saja, tanpa navbar, sidebar, atau footer.
========================================================= */

if (isset($_GET['_live_admin_orders'])) {
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => $total_pesanan,
            'proses' => $total_proses,
            'selesai' => $total_selesai,
            'dibatalkan' => $total_dibatalkan
        ],
        'rows_html' => $rows_html,
        'rows_hash' => $rows_hash
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pesanan - Toko ATK &amp; Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>
        .pesanan-page {
            max-width: 1500px;
        }

        .pesanan-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .pesanan-alert.success {
            background: #e8f7ee;
            color: #237a45;
            border: 1px solid #c9ead6;
        }

        .pesanan-alert.error {
            background: #fce8e8;
            color: #a33;
            border: 1px solid #f2caca;
        }

        .pesanan-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .pesanan-stat-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        .pesanan-stat-label {
            display: block;
            color: #7890a4;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .pesanan-stat-number {
            display: block;
            color: #102f4b;
            font-size: 30px;
            font-weight: 800;
        }

        .pesanan-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
            overflow: hidden;
        }

        .pesanan-card-header {
            padding: 22px 24px;
            border-bottom: 1px solid #e6edf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .pesanan-card-header h2 {
            font-size: 20px;
            color: #102f4b;
            margin: 0 0 6px;
        }

        .pesanan-card-header p {
            color: #7890a4;
            font-size: 14px;
            margin: 0;
        }

        .pesanan-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pesanan-search-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .pesanan-search-input {
            width: 275px;
            height: 40px;
            padding: 0 13px;
            border: 1px solid #dce5ec;
            border-radius: 8px;
            outline: none;
            color: #334f66;
            font-size: 13px;
            background: #fff;
            box-sizing: border-box;
        }

        .pesanan-search-input:focus {
            border-color: #315b78;
            box-shadow: 0 0 0 3px rgba(49, 91, 120, .08);
        }

        .btn-search-pesanan,
        .btn-clear-pesanan {
            height: 40px;
            padding: 0 13px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
        }

        .btn-search-pesanan {
            background: #102f4b;
            color: #fff;
        }

        .btn-search-pesanan:hover {
            background: #1c496b;
        }

        .btn-clear-pesanan {
            background: #eef3f7;
            color: #506d84;
        }

        .btn-clear-pesanan:hover {
            background: #e3ebf0;
        }

        .pesanan-search-info {
            padding: 13px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e6edf2;
            color: #7890a4;
            font-size: 12px;
        }

        .pesanan-search-info strong {
            color: #102f4b;
        }

        .pesanan-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .pesanan-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        .pesanan-table th {
            background: #f4f7fa;
            color: #506d84;
            text-align: left;
            padding: 14px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pesanan-table td {
            padding: 15px 14px;
            border-top: 1px solid #e5ebf0;
            color: #334f66;
            font-size: 14px;
            vertical-align: middle;
        }

        .pesanan-table tbody tr:hover {
            background: #fafcfd;
        }

        .nomor-pesanan {
            color: #102f4b;
            font-weight: 700;
        }

        .id-pesanan {
            display: block;
            margin-top: 4px;
            color: #9aafbf;
            font-size: 12px;
        }

        .nama-pelanggan {
            color: #334f66;
            font-weight: 600;
        }

        .total-harga {
            color: #102f4b;
            font-weight: 700;
            white-space: nowrap;
        }

        .metode-qris {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 20px;
            background: #e4efff;
            color: #315fa5;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pesanan-status {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
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

        .btn-detail {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 8px;
            background: #102f4b;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-detail:hover {
            background: #1c496b;
        }

        .pesanan-kosong {
            text-align: center;
            padding: 70px 20px;
        }

        .pesanan-kosong-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .pesanan-kosong h3 {
            color: #334f66;
            font-size: 17px;
            margin: 0 0 7px;
        }

        .pesanan-kosong p {
            color: #7890a4;
            font-size: 14px;
            margin: 0;
        }

        @media (max-width: 1100px) {
            .pesanan-stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pesanan-card-header {
                align-items: stretch;
            }

            .pesanan-header-actions {
                width: 100%;
            }

            .pesanan-search-form {
                width: 100%;
            }

            .pesanan-search-input {
                flex: 1;
                width: auto;
            }
        }

        @media (max-width: 800px) {
            .pesanan-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .pesanan-header-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .pesanan-search-form {
                flex-wrap: wrap;
            }

            .pesanan-search-input {
                width: 100%;
                flex: none;
            }

            .btn-search-pesanan,
            .btn-clear-pesanan {
                flex: 1;
            }
        }

        @media (max-width: 500px) {
            .pesanan-stat-card {
                padding: 18px;
            }

            .pesanan-stat-number {
                font-size: 26px;
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

            <div class="pesanan-page">

                <div class="page-title">
                    <h1>Pesanan</h1>
                    <p>Kelola pesanan pelanggan dan proses pengambilan di toko.</p>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="pesanan-alert success">
                        ✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="pesanan-alert error">
                        ⚠ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="pesanan-stat-grid" id="pesananStatGrid">

                    <div class="pesanan-stat-card">
                        <span class="pesanan-stat-label">Total Pesanan</span>
                        <span class="pesanan-stat-number">
                            <?= $total_pesanan; ?>
                        </span>
                    </div>

                    <div class="pesanan-stat-card">
                        <span class="pesanan-stat-label">Dalam Proses</span>
                        <span class="pesanan-stat-number">
                            <?= $total_proses; ?>
                        </span>
                    </div>

                    <div class="pesanan-stat-card">
                        <span class="pesanan-stat-label">Selesai</span>
                        <span class="pesanan-stat-number">
                            <?= $total_selesai; ?>
                        </span>
                    </div>

                    <div class="pesanan-stat-card">
                        <span class="pesanan-stat-label">Dibatalkan</span>
                        <span class="pesanan-stat-number">
                            <?= $total_dibatalkan; ?>
                        </span>
                    </div>

                </div>

                <div class="pesanan-card">

                    <div class="pesanan-card-header">

                        <div>
                            <h2>Daftar Pesanan</h2>
                            <p>Semua pesanan pelanggan yang masuk ke sistem.</p>
                        </div>

                        <div class="pesanan-header-actions">

                            <form
                                method="GET"
                                action="index.php"
                                class="pesanan-search-form"
                            >

                                <input
                                    type="text"
                                    name="search"
                                    class="pesanan-search-input"
                                    placeholder="Cari no pesanan, pelanggan, produk..."
                                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-search-pesanan"
                                >
                                    Cari
                                </button>

                                <?php if ($search !== ''): ?>
                                    <a
                                        href="index.php"
                                        class="btn-clear-pesanan"
                                    >
                                        Reset
                                    </a>
                                <?php endif; ?>

                            </form>

                        </div>

                    </div>

                    <?php if ($search !== ''): ?>
                        <div class="pesanan-search-info">
                            Hasil pencarian untuk
                            <strong>
                                "<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            </strong>
                        </div>
                    <?php endif; ?>

                    <div class="pesanan-table-wrapper">

                        <table class="pesanan-table">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="pesananTableBody">
                                <?= $rows_html; ?>
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

<script>
/* =========================================================
   SINKRONISASI DATA TANPA MEMUAT ULANG NAVBAR
========================================================= */

(function () {
    const statGrid = document.getElementById("pesananStatGrid");
    const tableBody = document.getElementById("pesananTableBody");

    if (!statGrid || !tableBody) {
        return;
    }

    let sedangMemuat = false;
    let hashBarisTerakhir = <?= json_encode($rows_hash); ?>;

    async function sinkronPesananAdmin() {
        if (
            sedangMemuat ||
            document.visibilityState !== "visible"
        ) {
            return;
        }

        sedangMemuat = true;

        try {
            const url = new URL(window.location.href);

            url.searchParams.set("_live_admin_orders", "1");
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

            if (!response.ok) {
                return;
            }

            const contentType = response.headers.get("content-type") || "";

            if (!contentType.includes("application/json")) {
                return;
            }

            const data = await response.json();

            if (
                !data.success ||
                !data.stats ||
                typeof data.rows_html !== "string"
            ) {
                return;
            }

            /* UPDATE ANGKA STATISTIK */
            const angkaStatistik = statGrid.querySelectorAll(
                ".pesanan-stat-number"
            );

            const nilaiStatistik = [
                data.stats.total,
                data.stats.proses,
                data.stats.selesai,
                data.stats.dibatalkan
            ];

            angkaStatistik.forEach(function (elemen, index) {
                const nilaiBaru = String(
                    nilaiStatistik[index] ?? 0
                );

                if (elemen.textContent.trim() !== nilaiBaru) {
                    elemen.textContent = nilaiBaru;
                }
            });

            /* UPDATE TABEL JIKA DATA BERUBAH */
            if (
                data.rows_hash &&
                data.rows_hash !== hashBarisTerakhir
            ) {
                const tableWrapper = tableBody.closest(
                    ".pesanan-table-wrapper"
                );

                const posisiScroll = tableWrapper
                    ? tableWrapper.scrollLeft
                    : 0;

                tableBody.innerHTML = data.rows_html;
                hashBarisTerakhir = data.rows_hash;

                if (tableWrapper) {
                    tableWrapper.scrollLeft = posisiScroll;
                }
            }

        } catch (error) {
            console.error(
                "Gagal menyinkronkan pesanan:",
                error
            );

        } finally {
            sedangMemuat = false;
        }
    }

    setInterval(sinkronPesananAdmin, 4000);

})();
</script>

</body>
</html>