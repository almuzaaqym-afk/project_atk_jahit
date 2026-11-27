<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('pembelian');

/* =====================================================
   FUNGSI BANTU
===================================================== */

function escapePembelian($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =====================================================
   PARAMETER DAN NOTIFIKASI
===================================================== */

$pesan = "";
$error = "";
$search = trim($_GET['search'] ?? '');

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'hapus':
            $pesan = "Pembelian berhasil dihapus.";
            break;

        case 'gagal_hapus':
            $error = "Pembelian gagal dihapus.";
            break;

        case 'tidak_bisa_hapus':
            $error = "Pembelian yang sudah selesai atau dibatalkan tidak dapat dihapus.";
            break;

        case 'tidak_ditemukan':
            $error = "Data pembelian tidak ditemukan.";
            break;

        case 'berhasil':
            $pesan = "Pembelian berhasil dibuat.";
            break;

        case 'selesai':
            $pesan = "Pembelian berhasil diselesaikan.";
            break;
    }
}

/* =====================================================
   AMBIL DATA PEMBELIAN
===================================================== */

function ambilDataPembelian(mysqli $conn, string $search)
{
    $sql = "SELECT
                p.id_pembelian,
                p.nomor_pembelian,
                p.tanggal_pembelian,
                p.total_harga,
                p.status,
                p.keterangan,
                s.kode_supplier,
                s.nama_supplier
            FROM pembelian p
            INNER JOIN supplier s
                ON p.id_supplier = s.id_supplier";

    if ($search !== '') {
        $sql .= " WHERE (
                    p.nomor_pembelian LIKE ?
                    OR s.kode_supplier LIKE ?
                    OR s.nama_supplier LIKE ?
                    OR p.status LIKE ?
                    OR p.keterangan LIKE ?
                    OR DATE_FORMAT(
                        p.tanggal_pembelian,
                        '%d-%m-%Y'
                    ) LIKE ?
                    OR EXISTS (
                        SELECT 1
                        FROM detail_pembelian dp_search
                        LEFT JOIN produk pr_search
                            ON dp_search.id_produk = pr_search.id_produk
                        LEFT JOIN produk_varian pv_search
                            ON dp_search.id_varian = pv_search.id_varian
                        WHERE dp_search.id_pembelian = p.id_pembelian
                        AND (
                            pr_search.nama_produk LIKE ?
                            OR pv_search.nama_varian LIKE ?
                        )
                    )
                )";

        $sql .= " ORDER BY p.id_pembelian DESC";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return false;
        }

        $keyword = '%' . $search . '%';

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssss",
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword
        );

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $data;
    }

    $sql .= " ORDER BY p.id_pembelian DESC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return false;
    }

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

/* =====================================================
   STATISTIK PEMBELIAN
===================================================== */

function ambilStatistikPembelian(mysqli $conn)
{
    $sql = "SELECT
                COUNT(*) AS total,
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'Draft' THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS draft,
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'Selesai' THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS selesai,
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'Selesai'
                            THEN total_harga
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_nilai
            FROM pembelian";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return false;
    }

    $data = mysqli_fetch_assoc($result);

    return [
        'total' => (int) ($data['total'] ?? 0),
        'draft' => (int) ($data['draft'] ?? 0),
        'selesai' => (int) ($data['selesai'] ?? 0),
        'total_nilai' => (float) ($data['total_nilai'] ?? 0)
    ];
}

/* =====================================================
   RENDER BARIS TABEL
===================================================== */

function renderBarisPembelian(array $data, string $search): string
{
    ob_start();

    if (!empty($data)) {
        $no = 1;

        foreach ($data as $pembelian) {
            $status = $pembelian['status'] ?? '';

            if ($status === 'Draft') {
                $status_class = 'draft';
            } elseif ($status === 'Selesai') {
                $status_class = 'selesai';
            } else {
                $status_class = 'dibatalkan';
            }

            $tanggal = '-';

            if (!empty($pembelian['tanggal_pembelian'])) {
                $timestamp = strtotime(
                    $pembelian['tanggal_pembelian']
                );

                if ($timestamp !== false) {
                    $tanggal = date('d-m-Y', $timestamp);
                }
            }
            ?>

            <tr>
                <td><?= $no++; ?></td>

                <td>
                    <span class="nomor-pembelian">
                        <?= escapePembelian(
                            $pembelian['nomor_pembelian']
                        ); ?>
                    </span>
                </td>

                <td>
                    <?= escapePembelian($tanggal); ?>
                </td>

                <td>
                    <span class="supplier-name">
                        <?= escapePembelian(
                            $pembelian['nama_supplier']
                        ); ?>
                    </span>

                    <span class="supplier-code">
                        <?= escapePembelian(
                            $pembelian['kode_supplier']
                        ); ?>
                    </span>
                </td>

                <td class="nilai-pembelian">
                    Rp <?= number_format(
                        (float) $pembelian['total_harga'],
                        0,
                        ',',
                        '.'
                    ); ?>
                </td>

                <td>
                    <span class="pembelian-status <?= $status_class; ?>">
                        <?= escapePembelian($status); ?>
                    </span>
                </td>

                <td>
                    <div class="pembelian-actions">

                        <a
                            href="detail.php?id=<?= (int) $pembelian['id_pembelian']; ?>"
                            class="btn-detail-pembelian"
                        >
                            Detail
                        </a>

                        <?php if ($status === 'Draft'): ?>
                            <form
                                method="POST"
                                action="hapus.php"
                                class="form-hapus-pembelian"
                                onsubmit="return confirm('Yakin ingin menghapus pembelian ini?');"
                            >
                                <input
                                    type="hidden"
                                    name="id_pembelian"
                                    value="<?= (int) $pembelian['id_pembelian']; ?>"
                                >

                                <button
                                    type="submit"
                                    name="hapus_pembelian"
                                    class="btn-hapus-pembelian"
                                >
                                    Hapus
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>

            <?php
        }
    } else {
        ?>

        <tr>
            <td colspan="7" class="pembelian-empty">
                <?= $search !== ''
                    ? 'Data pembelian yang dicari tidak ditemukan.'
                    : 'Belum ada transaksi pembelian.'; ?>
            </td>
        </tr>

        <?php
    }

    return ob_get_clean();
}

/* =====================================================
   MODE SINKRONISASI OTOMATIS
===================================================== */

if (
    isset($_GET['_live_pembelian']) &&
    $_GET['_live_pembelian'] === '1'
) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $data_pembelian = ambilDataPembelian($conn, $search);
    $statistik = ambilStatistikPembelian($conn);

    if (
        $data_pembelian === false ||
        $statistik === false
    ) {
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data pembelian.'
        ]);

        exit;
    }

    $rows_html = renderBarisPembelian(
        $data_pembelian,
        $search
    );

    $rows_hash = hash(
        'sha256',
        $rows_html . json_encode($statistik)
    );

    echo json_encode([
        'success' => true,
        'stats' => $statistik,
        'rows_html' => $rows_html,
        'rows_hash' => $rows_hash
    ]);

    exit;
}

/* =====================================================
   DATA HALAMAN UTAMA
===================================================== */

$data_pembelian = ambilDataPembelian($conn, $search);
$statistik = ambilStatistikPembelian($conn);

if (
    $data_pembelian === false ||
    $statistik === false
) {
    $error = "Data pembelian gagal dimuat. Periksa koneksi database.";

    $data_pembelian = $data_pembelian === false
        ? []
        : $data_pembelian;

    $statistik = $statistik === false
        ? [
            'total' => 0,
            'draft' => 0,
            'selesai' => 0,
            'total_nilai' => 0
        ]
        : $statistik;
}

$total_pembelian = $statistik['total'];
$total_draft = $statistik['draft'];
$total_selesai = $statistik['selesai'];
$total_nilai = $statistik['total_nilai'];

$rows_html = renderBarisPembelian(
    $data_pembelian,
    $search
);

$rows_hash = hash(
    'sha256',
    $rows_html . json_encode($statistik)
);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pembelian - Toko ATK &amp; Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .pembelian-page {
            width: 100%;
        }

        .pembelian-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .pembelian-stat {
            background: #fff;
            border: 1px solid #e3eaf0;
            border-radius: 12px;
            padding: 20px;
        }

        .pembelian-stat span {
            display: block;
            color: #7890a4;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .pembelian-stat strong {
            color: #123451;
            font-size: 25px;
        }

        .pembelian-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .pembelian-header h2 {
            margin: 0;
            color: #123451;
            font-size: 20px;
        }

        .pembelian-header p {
            margin: 5px 0 0;
            color: #7890a4;
            font-size: 13px;
        }

        .pembelian-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pembelian-search-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .pembelian-search-input {
            width: 270px;
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

        .pembelian-search-input:focus {
            border-color: #315b78;
            box-shadow: 0 0 0 3px rgba(49, 91, 120, .08);
        }

        .btn-search-pembelian,
        .btn-clear-pembelian {
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

        .btn-search-pembelian {
            background: #123451;
            color: #fff;
        }

        .btn-search-pembelian:hover {
            background: #245b83;
        }

        .btn-clear-pembelian {
            background: #eef3f7;
            color: #506d84;
        }

        .btn-clear-pembelian:hover {
            background: #e3ebf0;
        }

        .btn-tambah-pembelian {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #123451;
            color: #fff;
            padding: 11px 17px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-tambah-pembelian:hover {
            background: #0d2942;
        }

        .pembelian-card {
            background: #fff;
            border: 1px solid #e3eaf0;
            border-radius: 12px;
            overflow: hidden;
        }

        .pembelian-search-info {
            padding: 13px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e6edf2;
            color: #7890a4;
            font-size: 12px;
        }

        .pembelian-search-info strong {
            color: #123451;
        }

        .pembelian-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .pembelian-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        .pembelian-table th {
            padding: 14px 12px;
            background: #f4f7fa;
            color: #123451;
            text-align: left;
            font-size: 13px;
            border-bottom: 1px solid #dfe6ec;
            white-space: nowrap;
        }

        .pembelian-table td {
            padding: 14px 12px;
            color: #456;
            border-bottom: 1px solid #edf1f4;
            vertical-align: middle;
        }

        .pembelian-table tbody tr:hover {
            background: #fafcfd;
        }

        .pembelian-table tbody tr:last-child td {
            border-bottom: none;
        }

        .nomor-pembelian {
            color: #123451;
            font-weight: 700;
        }

        .supplier-name {
            display: block;
            color: #123451;
            font-weight: 600;
        }

        .supplier-code {
            display: block;
            margin-top: 4px;
            color: #7890a4;
            font-size: 12px;
        }

        .nilai-pembelian {
            color: #123451;
            font-weight: 700;
            white-space: nowrap;
        }

        .pembelian-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .pembelian-status.draft {
            background: #fff4df;
            color: #a66a00;
        }

        .pembelian-status.selesai {
            background: #e8f7ee;
            color: #237a45;
        }

        .pembelian-status.dibatalkan {
            background: #fce8e8;
            color: #a33;
        }

        .pembelian-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .form-hapus-pembelian {
            display: inline;
            margin: 0;
        }

        .btn-detail-pembelian,
        .btn-hapus-pembelian {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 11px;
            border-radius: 7px;
            text-decoration: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-detail-pembelian {
            background: #eef4f8;
            color: #123451;
        }

        .btn-detail-pembelian:hover {
            background: #dfe8ef;
        }

        .btn-hapus-pembelian {
            border: none;
            cursor: pointer;
            background: #fce8e8;
            color: #a33;
        }

        .btn-hapus-pembelian:hover {
            background: #f7dada;
        }

        .pembelian-alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            transition: opacity .35s ease, transform .35s ease;
        }

        .pembelian-alert.success {
            background: #eaf7ee;
            color: #218739;
            border: 1px solid #c9e8d1;
        }

        .pembelian-alert.error {
            background: #fce8e8;
            color: #a33;
            border: 1px solid #f1caca;
        }

        .pembelian-alert.is-hiding {
            opacity: 0;
            transform: translateY(-5px);
        }

        .pembelian-empty {
            text-align: center;
            padding: 45px !important;
            color: #7890a4 !important;
        }

        @media (max-width: 1050px) {
            .pembelian-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .pembelian-header {
                align-items: stretch;
            }

            .pembelian-header-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .pembelian-search-form {
                width: 100%;
            }

            .pembelian-search-input {
                flex: 1;
                width: auto;
            }
        }

        @media (max-width: 650px) {
            .pembelian-stats {
                grid-template-columns: 1fr;
            }

            .pembelian-header {
                flex-direction: column;
                align-items: stretch;
            }

            .pembelian-search-form {
                flex-wrap: wrap;
            }

            .pembelian-search-input {
                width: 100%;
                flex: none;
            }

            .btn-search-pembelian,
            .btn-clear-pembelian {
                flex: 1;
            }

            .btn-tambah-pembelian {
                width: 100%;
                box-sizing: border-box;
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

            <div class="pembelian-page">

                <div class="page-title">
                    <h1>Pembelian</h1>
                    <p>Kelola pembelian barang dari supplier.</p>
                </div>

                <?php if ($pesan !== ""): ?>
                    <div class="pembelian-alert success" role="status">
                        <?= escapePembelian($pesan); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="pembelian-alert error" role="alert">
                        <?= escapePembelian($error); ?>
                    </div>
                <?php endif; ?>

                <div class="pembelian-stats">

                    <div class="pembelian-stat">
                        <span>Total Pembelian</span>
                        <strong id="totalPembelian">
                            <?= $total_pembelian; ?>
                        </strong>
                    </div>

                    <div class="pembelian-stat">
                        <span>Draft</span>
                        <strong id="totalDraft">
                            <?= $total_draft; ?>
                        </strong>
                    </div>

                    <div class="pembelian-stat">
                        <span>Selesai</span>
                        <strong id="totalSelesai">
                            <?= $total_selesai; ?>
                        </strong>
                    </div>

                    <div class="pembelian-stat">
                        <span>Total Nilai Pembelian</span>
                        <strong
                            id="totalNilaiPembelian"
                            style="font-size:20px;"
                        >
                            Rp <?= number_format(
                                $total_nilai,
                                0,
                                ',',
                                '.'
                            ); ?>
                        </strong>
                    </div>

                </div>

                <div class="pembelian-card">

                    <div class="pembelian-header">

                        <div>
                            <h2>Daftar Pembelian</h2>
                            <p>Riwayat transaksi pembelian barang.</p>
                        </div>

                        <div class="pembelian-header-actions">

                            <form
                                method="GET"
                                action="index.php"
                                class="pembelian-search-form"
                            >
                                <input
                                    type="text"
                                    name="search"
                                    class="pembelian-search-input"
                                    placeholder="Cari no pembelian, supplier, produk..."
                                    value="<?= escapePembelian($search); ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-search-pembelian"
                                >
                                    Cari
                                </button>

                                <?php if ($search !== ''): ?>
                                    <a
                                        href="index.php"
                                        class="btn-clear-pembelian"
                                    >
                                        Reset
                                    </a>
                                <?php endif; ?>

                            </form>

                            <a
                                href="tambah.php"
                                class="btn-tambah-pembelian"
                            >
                                + Tambah Pembelian
                            </a>

                        </div>
                    </div>

                    <?php if ($search !== ''): ?>
                        <div class="pembelian-search-info">
                            Hasil pencarian untuk
                            <strong>"<?= escapePembelian($search); ?>"</strong>
                        </div>
                    <?php endif; ?>

                    <div class="pembelian-table-wrapper">

                        <table class="pembelian-table">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Pembelian</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody
                                id="pembelianTableBody"
                                data-hash="<?= escapePembelian($rows_hash); ?>"
                            >
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
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('pembelianTableBody');
    const tableWrapper = document.querySelector('.pembelian-table-wrapper');

    const search = <?= json_encode(
        $search,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ); ?>;

    let isFetching = false;

    /* =================================================
       NOTIFIKASI OTOMATIS HILANG
    ================================================= */

    document.querySelectorAll('.pembelian-alert').forEach(function (alert) {
        setTimeout(function () {
            alert.classList.add('is-hiding');

            setTimeout(function () {
                alert.remove();
            }, 400);
        }, 10000);
    });

    /* =================================================
       BERSIHKAN STATUS DARI URL
    ================================================= */

    const currentUrl = new URL(window.location.href);

    if (currentUrl.searchParams.has('status')) {
        currentUrl.searchParams.delete('status');

        window.history.replaceState(
            {},
            document.title,
            currentUrl.pathname +
            currentUrl.search +
            currentUrl.hash
        );
    }

    /* =================================================
       FORMAT RUPIAH
    ================================================= */

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {
            maximumFractionDigits: 0
        });
    }

    /* =================================================
       SINKRONISASI PEMBELIAN
    ================================================= */

    async function syncPembelian() {
        if (
            isFetching ||
            document.visibilityState === 'hidden'
        ) {
            return;
        }

        isFetching = true;

        try {
            const liveUrl = new URL(
                window.location.pathname,
                window.location.origin
            );

            liveUrl.searchParams.set('_live_pembelian', '1');

            if (search !== '') {
                liveUrl.searchParams.set('search', search);
            }

            const response = await fetch(liveUrl.toString(), {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Gagal mengambil data pembelian.');
            }

            const data = await response.json();

            if (!data.success) {
                return;
            }

            document.getElementById('totalPembelian').textContent =
                data.stats.total;

            document.getElementById('totalDraft').textContent =
                data.stats.draft;

            document.getElementById('totalSelesai').textContent =
                data.stats.selesai;

            document.getElementById('totalNilaiPembelian').textContent =
                formatRupiah(data.stats.total_nilai);

            /*
             * Perbarui tabel hanya jika data berubah.
             * Posisi scroll tabel dipertahankan.
             */
            if (data.rows_hash !== tableBody.dataset.hash) {
                const scrollLeft = tableWrapper.scrollLeft;

                tableBody.innerHTML = data.rows_html;
                tableBody.dataset.hash = data.rows_hash;

                tableWrapper.scrollLeft = scrollLeft;
            }

        } catch (error) {
            console.warn(
                'Sinkronisasi pembelian gagal:',
                error.message
            );
        } finally {
            isFetching = false;
        }
    }

    syncPembelian();
    setInterval(syncPembelian, 4000);
});
</script>

</body>
</html>