<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('supplier');

/* =====================================================
   FUNGSI BANTU
===================================================== */

function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =====================================================
   PENCARIAN
===================================================== */

$search = trim($_GET['search'] ?? '');

$pesan = '';
$error = '';

/* =====================================================
   NOTIFIKASI
===================================================== */

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'tambah':
            $pesan = "Supplier berhasil ditambahkan.";
            break;

        case 'edit':
            $pesan = "Supplier berhasil diperbarui.";
            break;

        case 'hapus':
            $pesan = "Supplier berhasil dihapus.";
            break;

        case 'digunakan':
            $error = "Supplier tidak dapat dihapus karena masih digunakan pada data pembelian.";
            break;

        case 'tidak_ditemukan':
            $error = "Supplier tidak ditemukan.";
            break;

        case 'gagal':
            $error = "Supplier gagal diproses.";
            break;
    }
}

/* =====================================================
   AMBIL DATA SUPPLIER
===================================================== */

function ambilSupplier(mysqli $conn, string $search)
{
    if ($search !== '') {
        $sql = "SELECT
                    id_supplier,
                    kode_supplier,
                    nama_supplier,
                    nama_kontak,
                    telepon,
                    email,
                    status
                FROM supplier
                WHERE kode_supplier LIKE ?
                   OR nama_supplier LIKE ?
                   OR nama_kontak LIKE ?
                   OR telepon LIKE ?
                   OR email LIKE ?
                   OR status LIKE ?
                ORDER BY id_supplier DESC";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return false;
        }

        $keyword = '%' . $search . '%';

        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
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

    $sql = "SELECT
                id_supplier,
                kode_supplier,
                nama_supplier,
                nama_kontak,
                telepon,
                email,
                status
            FROM supplier
            ORDER BY id_supplier DESC";

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
   STATISTIK SUPPLIER
===================================================== */

function ambilStatistikSupplier(mysqli $conn): array
{
    $sql = "SELECT
                COUNT(*) AS total,
                COALESCE(
                    SUM(CASE WHEN status = 'Aktif' THEN 1 ELSE 0 END),
                    0
                ) AS aktif,
                COALESCE(
                    SUM(CASE WHEN status = 'Tidak Aktif' THEN 1 ELSE 0 END),
                    0
                ) AS nonaktif
            FROM supplier";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return [
            'total' => 0,
            'aktif' => 0,
            'nonaktif' => 0
        ];
    }

    $row = mysqli_fetch_assoc($result);

    return [
        'total' => (int) ($row['total'] ?? 0),
        'aktif' => (int) ($row['aktif'] ?? 0),
        'nonaktif' => (int) ($row['nonaktif'] ?? 0)
    ];
}

/* =====================================================
   RENDER TABEL
===================================================== */

function renderBarisSupplier(array $data, string $search): string
{
    ob_start();

    if (!empty($data)):
        $no = 1;

        foreach ($data as $supplier):
?>
            <tr>
                <td><?= $no++; ?></td>

                <td class="supplier-code">
                    <?= h($supplier['kode_supplier']); ?>
                </td>

                <td class="supplier-name">
                    <?= h($supplier['nama_supplier']); ?>
                </td>

                <td>
                    <?= h($supplier['nama_kontak'] ?: '-'); ?>
                </td>

                <td>
                    <?= h($supplier['telepon'] ?: '-'); ?>
                </td>

                <td>
                    <?= h($supplier['email'] ?: '-'); ?>
                </td>

                <td>
                    <?php if ($supplier['status'] === 'Aktif'): ?>
                        <span class="supplier-status active">
                            Aktif
                        </span>
                    <?php else: ?>
                        <span class="supplier-status inactive">
                            <?= h($supplier['status'] ?: 'Tidak Aktif'); ?>
                        </span>
                    <?php endif; ?>
                </td>

                <td>
                    <div class="supplier-actions">
                        <a
                            href="edit.php?id=<?= (int) $supplier['id_supplier']; ?>"
                            class="supplier-btn-edit"
                        >
                            Edit
                        </a>

                        <form
                            method="POST"
                            action="hapus.php"
                            class="supplier-delete-form"
                            onsubmit="return confirm('Yakin ingin menghapus supplier ini?');"
                        >
                            <input
                                type="hidden"
                                name="id_supplier"
                                value="<?= (int) $supplier['id_supplier']; ?>"
                            >

                            <button
                                type="submit"
                                name="hapus_supplier"
                                class="supplier-btn-delete"
                            >
                                Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
<?php
        endforeach;

    else:
?>
        <tr>
            <td colspan="8" class="supplier-empty">
                <?= $search !== ''
                    ? 'Supplier yang dicari tidak ditemukan.'
                    : 'Belum ada data supplier.'; ?>
            </td>
        </tr>
<?php
    endif;

    return ob_get_clean();
}

/* =====================================================
   MODE SINKRONISASI AJAX
===================================================== */

if (isset($_GET['_live_supplier']) && $_GET['_live_supplier'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $data_supplier = ambilSupplier($conn, $search);

    if ($data_supplier === false) {
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data supplier.'
        ]);

        exit;
    }

    $statistik = ambilStatistikSupplier($conn);
    $rows_html = renderBarisSupplier($data_supplier, $search);

    echo json_encode([
        'success' => true,
        'stats' => $statistik,
        'rows_html' => $rows_html,
        'rows_hash' => hash(
            'sha256',
            $rows_html . json_encode($statistik)
        )
    ]);

    exit;
}

/* =====================================================
   DATA HALAMAN UTAMA
===================================================== */

$data_supplier = ambilSupplier($conn, $search);
$statistik = ambilStatistikSupplier($conn);

if ($data_supplier === false) {
    $data_supplier = [];
    $error = "Data supplier gagal dimuat. Periksa koneksi database.";
}

$total_supplier = $statistik['total'];
$total_aktif = $statistik['aktif'];
$total_nonaktif = $statistik['nonaktif'];

$rows_html = renderBarisSupplier($data_supplier, $search);

$rows_hash = hash(
    'sha256',
    $rows_html . json_encode($statistik)
);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Supplier - Toko ATK &amp; Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .supplier-page {
            max-width: 1500px;
        }

        .supplier-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .supplier-page-header h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 28px;
        }

        .supplier-page-header p {
            margin: 0;
            color: #7890a4;
            font-size: 14px;
        }

        .supplier-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .supplier-search-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .supplier-search-input {
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

        .supplier-search-input:focus {
            border-color: #315b78;
            box-shadow: 0 0 0 3px rgba(49, 91, 120, .08);
        }

        .btn-search-supplier,
        .btn-clear-supplier {
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

        .btn-search-supplier {
            background: #102f4b;
            color: #fff;
        }

        .btn-search-supplier:hover {
            background: #1c496b;
        }

        .btn-clear-supplier {
            background: #eef3f7;
            color: #506d84;
        }

        .btn-clear-supplier:hover {
            background: #e3ebf0;
        }

        .supplier-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 17px;
            border-radius: 8px;
            background: #102f4b;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .supplier-add-btn:hover {
            background: #1c496b;
        }

        .supplier-alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            opacity: 1;
            transition: opacity .35s ease, transform .35s ease;
        }

        .supplier-alert.success {
            background: #e3f5e9;
            color: #27804a;
            border: 1px solid #c9e8d1;
        }

        .supplier-alert.error {
            background: #fde7e7;
            color: #b23b3b;
            border: 1px solid #f1caca;
        }

        .supplier-alert.is-hiding {
            opacity: 0;
            transform: translateY(-5px);
        }

        .supplier-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .supplier-stat {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        .supplier-stat-label {
            display: block;
            color: #7890a4;
            font-size: 13px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .supplier-stat-number {
            color: #102f4b;
            font-size: 28px;
            font-weight: bold;
        }

        .supplier-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
        }

        .supplier-card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e6edf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .supplier-card-header h2 {
            margin: 0;
            color: #102f4b;
            font-size: 18px;
        }

        .supplier-card-header p {
            margin: 5px 0 0;
            color: #7890a4;
            font-size: 13px;
        }

        .supplier-search-info {
            padding: 13px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #e6edf2;
            color: #7890a4;
            font-size: 12px;
        }

        .supplier-search-info strong {
            color: #102f4b;
        }

        .supplier-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .supplier-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        .supplier-table th {
            padding: 13px 16px;
            background: #f7f9fb;
            color: #7890a4;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .supplier-table td {
            padding: 14px 16px;
            border-top: 1px solid #edf1f4;
            color: #506d84;
            font-size: 13px;
            vertical-align: middle;
        }

        .supplier-table tr:hover {
            background: #fafcfd;
        }

        .supplier-code {
            color: #102f4b;
            font-weight: bold;
        }

        .supplier-name {
            color: #102f4b;
            font-weight: 600;
        }

        .supplier-status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .supplier-status.active {
            background: #e3f5e9;
            color: #27804a;
        }

        .supplier-status.inactive {
            background: #fde7e7;
            color: #b23b3b;
        }

        .supplier-actions {
            display: flex;
            gap: 7px;
            align-items: center;
            white-space: nowrap;
        }

        .supplier-delete-form {
            display: inline;
            margin: 0;
        }

        .supplier-btn-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 7px;
            background: #edf3f7;
            color: #102f4b;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .supplier-btn-edit:hover {
            background: #dfe8ef;
        }

        .supplier-btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border: none;
            border-radius: 7px;
            background: #fde7e7;
            color: #b23b3b;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .supplier-btn-delete:hover {
            background: #f7dada;
        }

        .supplier-empty {
            padding: 45px 20px !important;
            text-align: center;
            color: #7890a4 !important;
        }

        @media (max-width: 1000px) {
            .supplier-page-header {
                align-items: stretch;
            }

            .supplier-header-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .supplier-search-form {
                width: 100%;
            }

            .supplier-search-input {
                flex: 1;
                width: auto;
            }
        }

        @media (max-width: 900px) {
            .supplier-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .supplier-page-header {
                flex-direction: column;
            }

            .supplier-search-form {
                flex-wrap: wrap;
            }

            .supplier-search-input {
                width: 100%;
                flex: none;
            }

            .btn-search-supplier,
            .btn-clear-supplier {
                flex: 1;
            }

            .supplier-add-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <?php include "../includes/sidebar.php"; ?>

    <div class="admin-main">

        <?php include "../includes/navbar.php"; ?>

        <main class="admin-content">

            <div class="supplier-page">

                <div class="supplier-page-header">

                    <div>
                        <h1>Supplier</h1>
                        <p>Kelola data supplier untuk kebutuhan pembelian barang.</p>
                    </div>

                    <div class="supplier-header-actions">

                        <form
                            method="GET"
                            action="index.php"
                            class="supplier-search-form"
                        >
                            <input
                                type="text"
                                name="search"
                                class="supplier-search-input"
                                placeholder="Cari kode, nama, kontak..."
                                value="<?= h($search); ?>"
                            >

                            <button type="submit" class="btn-search-supplier">
                                Cari
                            </button>

                            <?php if ($search !== ''): ?>
                                <a href="index.php" class="btn-clear-supplier">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </form>

                        <a href="tambah.php" class="supplier-add-btn">
                            + Tambah Supplier
                        </a>

                    </div>
                </div>

                <?php if ($pesan !== ''): ?>
                    <div class="supplier-alert success" role="status">
                        <?= h($pesan); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="supplier-alert error" role="alert">
                        <?= h($error); ?>
                    </div>
                <?php endif; ?>

                <div class="supplier-stats">

                    <div class="supplier-stat">
                        <span class="supplier-stat-label">Total Supplier</span>
                        <span
                            class="supplier-stat-number"
                            id="supplierTotal"
                        ><?= $total_supplier; ?></span>
                    </div>

                    <div class="supplier-stat">
                        <span class="supplier-stat-label">Supplier Aktif</span>
                        <span
                            class="supplier-stat-number"
                            id="supplierAktif"
                        ><?= $total_aktif; ?></span>
                    </div>

                    <div class="supplier-stat">
                        <span class="supplier-stat-label">Supplier Tidak Aktif</span>
                        <span
                            class="supplier-stat-number"
                            id="supplierNonaktif"
                        ><?= $total_nonaktif; ?></span>
                    </div>

                </div>

                <div class="supplier-card">

                    <div class="supplier-card-header">
                        <div>
                            <h2>Daftar Supplier</h2>
                            <p>Data supplier yang terdaftar pada sistem.</p>
                        </div>
                    </div>

                    <?php if ($search !== ''): ?>
                        <div class="supplier-search-info">
                            Hasil pencarian untuk
                            <strong>"<?= h($search); ?>"</strong>
                        </div>
                    <?php endif; ?>

                    <div class="supplier-table-wrapper">

                        <table class="supplier-table">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Supplier</th>
                                    <th>Kontak</th>
                                    <th>Telepon</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody
                                id="supplierTableBody"
                                data-hash="<?= h($rows_hash); ?>"
                            >
                                <?= $rows_html; ?>
                            </tbody>

                        </table>

                    </div>
                </div>

            </div>
        </main>

        <?php include "../includes/footer.php"; ?>

    </div>
</div>

<script src="../assets/js/admin.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('supplierTableBody');
    const tableWrapper = document.querySelector('.supplier-table-wrapper');

    const search = <?= json_encode(
        $search,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ); ?>;

    let isFetching = false;

    /*
     * Hilangkan notifikasi setelah 10 detik.
     */
    document.querySelectorAll('.supplier-alert').forEach(function (alert) {
        setTimeout(function () {
            alert.classList.add('is-hiding');

            setTimeout(function () {
                alert.remove();
            }, 400);
        }, 10000);
    });

    /*
     * Bersihkan status dari URL setelah notifikasi tampil.
     * Parameter pencarian tetap dipertahankan.
     */
    const currentUrl = new URL(window.location.href);

    if (currentUrl.searchParams.has('status')) {
        currentUrl.searchParams.delete('status');

        window.history.replaceState(
            {},
            document.title,
            currentUrl.pathname + currentUrl.search + currentUrl.hash
        );
    }

    /*
     * Sinkronisasi data supplier dan statistik.
     * Navbar dan sidebar tidak dimuat ulang.
     */
    async function syncSupplier() {
        if (isFetching || document.visibilityState === 'hidden') {
            return;
        }

        isFetching = true;

        try {
            const liveUrl = new URL(window.location.pathname, window.location.origin);
            liveUrl.searchParams.set('_live_supplier', '1');

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
                throw new Error('Gagal mengambil data supplier.');
            }

            const data = await response.json();

            if (!data.success) {
                return;
            }

            document.getElementById('supplierTotal').textContent =
                data.stats.total;

            document.getElementById('supplierAktif').textContent =
                data.stats.aktif;

            document.getElementById('supplierNonaktif').textContent =
                data.stats.nonaktif;

            if (data.rows_hash !== tableBody.dataset.hash) {
                const scrollLeft = tableWrapper.scrollLeft;

                tableBody.innerHTML = data.rows_html;
                tableBody.dataset.hash = data.rows_hash;

                tableWrapper.scrollLeft = scrollLeft;
            }

        } catch (error) {
            console.warn('Sinkronisasi supplier gagal:', error.message);
        } finally {
            isFetching = false;
        }
    }

    syncSupplier();
    setInterval(syncSupplier, 4000);
});
</script>

</body>
</html>