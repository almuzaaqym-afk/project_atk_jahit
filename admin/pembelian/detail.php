<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('pembelian');

/* =====================================================
   FUNGSI BANTU
===================================================== */

function escapePembelianDetail($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =====================================================
   ID PEMBELIAN
===================================================== */

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   TOKEN FORM
===================================================== */

if (empty($_SESSION['csrf_pembelian_detail'])) {
    $_SESSION['csrf_pembelian_detail'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

/* =====================================================
   PROSES SELESAIKAN PEMBELIAN
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'selesaikan'
) {
    $id_pembelian = (int) ($_POST['id_pembelian'] ?? 0);
    $token_post = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token_post) ||
        !hash_equals(
            $_SESSION['csrf_pembelian_detail'],
            $token_post
        )
    ) {
        $error = "Permintaan tidak valid. Muat ulang halaman lalu coba kembali.";
    } elseif ($id_pembelian <= 0 || $id_pembelian !== $id) {
        $error = "Data pembelian tidak valid.";
    } else {
        $transaction_started = false;

        $stmt = null;
        $stmt_detail = null;
        $stmt_produk = null;
        $stmt_varian = null;
        $stmt_sync = null;
        $stmt_status = null;
        $stmt_cek_produk = null;
        $stmt_cek_varian = null;

        try {
            if (!mysqli_begin_transaction($conn)) {
                throw new Exception("Gagal memulai transaksi database.");
            }

            $transaction_started = true;

            /* =============================================
               KUNCI HEADER PEMBELIAN
            ============================================= */

            $stmt = mysqli_prepare(
                $conn,
                "SELECT id_pembelian, nomor_pembelian, status
                 FROM pembelian
                 WHERE id_pembelian = ?
                 LIMIT 1
                 FOR UPDATE"
            );

            if (!$stmt) {
                throw new Exception("Gagal menyiapkan data pembelian.");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_pembelian
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal memeriksa status pembelian.");
            }

            $result = mysqli_stmt_get_result($stmt);
            $pembelian_lock = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);
            $stmt = null;

            if (!$pembelian_lock) {
                throw new Exception("Pembelian tidak ditemukan.");
            }

            if ($pembelian_lock['status'] !== 'Draft') {
                throw new Exception("Pembelian ini sudah diproses.");
            }

            /* =============================================
               AMBIL DETAIL PEMBELIAN
            ============================================= */

            $stmt_detail = mysqli_prepare(
                $conn,
                "SELECT
                    id_detail,
                    id_produk,
                    id_varian,
                    jumlah
                 FROM detail_pembelian
                 WHERE id_pembelian = ?
                 ORDER BY id_detail ASC"
            );

            if (!$stmt_detail) {
                throw new Exception("Gagal mengambil detail pembelian.");
            }

            mysqli_stmt_bind_param(
                $stmt_detail,
                "i",
                $id_pembelian
            );

            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal membaca detail pembelian.");
            }

            $detail_result = mysqli_stmt_get_result($stmt_detail);
            $detail_pembelian = [];

            while ($row = mysqli_fetch_assoc($detail_result)) {
                $detail_pembelian[] = $row;
            }

            mysqli_stmt_close($stmt_detail);
            $stmt_detail = null;

            if (empty($detail_pembelian)) {
                throw new Exception("Pembelian belum memiliki produk.");
            }

            /* =============================================
               SIAPKAN QUERY STOK PRODUK DAN VARIAN
            ============================================= */

            $stmt_produk = mysqli_prepare(
                $conn,
                "UPDATE produk
                 SET stok = stok + ?
                 WHERE id_produk = ?"
            );

            $stmt_varian = mysqli_prepare(
                $conn,
                "UPDATE produk_varian
                 SET stok = stok + ?
                 WHERE id_varian = ?
                   AND id_produk = ?
                   AND status = 'Aktif'"
            );

            $stmt_sync = mysqli_prepare(
                $conn,
                "UPDATE produk
                 SET
                    stok = (
                        SELECT COALESCE(SUM(v.stok), 0)
                        FROM produk_varian v
                        WHERE v.id_produk = produk.id_produk
                          AND v.status = 'Aktif'
                    ),
                    stok_minimum = (
                        SELECT COALESCE(SUM(v.stok_minimum), 0)
                        FROM produk_varian v
                        WHERE v.id_produk = produk.id_produk
                          AND v.status = 'Aktif'
                    ),
                    lokasi_rak = NULL
                 WHERE id_produk = ?"
            );

            $stmt_cek_produk = mysqli_prepare(
                $conn,
                "SELECT id_produk
                 FROM produk
                 WHERE id_produk = ?
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt_cek_varian = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS jumlah
                 FROM produk_varian
                 WHERE id_produk = ?
                   AND status = 'Aktif'"
            );

            if (
                !$stmt_produk ||
                !$stmt_varian ||
                !$stmt_sync ||
                !$stmt_cek_produk ||
                !$stmt_cek_varian
            ) {
                throw new Exception("Gagal menyiapkan proses pembaruan stok.");
            }

            /* =============================================
               PROSES DETAIL PEMBELIAN
            ============================================= */

            $produk_varian = [];

            foreach ($detail_pembelian as $detail) {
                $jumlah = (int) $detail['jumlah'];
                $id_produk = (int) $detail['id_produk'];
                $id_varian = (int) ($detail['id_varian'] ?? 0);

                if ($jumlah <= 0) {
                    throw new Exception("Jumlah produk tidak valid.");
                }

                if ($id_produk <= 0) {
                    throw new Exception("Produk pada detail pembelian tidak valid.");
                }

                /* =========================================
                   PRODUK BERVARIAN
                ========================================= */

                if ($id_varian > 0) {
                    mysqli_stmt_bind_param(
                        $stmt_varian,
                        "iii",
                        $jumlah,
                        $id_varian,
                        $id_produk
                    );

                    if (!mysqli_stmt_execute($stmt_varian)) {
                        throw new Exception("Gagal memperbarui stok varian.");
                    }

                    if (mysqli_stmt_affected_rows($stmt_varian) <= 0) {
                        throw new Exception(
                            "Varian tidak ditemukan, tidak aktif, atau tidak sesuai dengan produk."
                        );
                    }

                    $produk_varian[$id_produk] = true;
                } else {
                    /* =====================================
                       CEK PRODUK MASIH ADA
                    ===================================== */

                    mysqli_stmt_bind_param(
                        $stmt_cek_produk,
                        "i",
                        $id_produk
                    );

                    if (!mysqli_stmt_execute($stmt_cek_produk)) {
                        throw new Exception("Gagal memeriksa produk.");
                    }

                    $result_produk = mysqli_stmt_get_result(
                        $stmt_cek_produk
                    );

                    $produk_ada = mysqli_fetch_assoc($result_produk);

                    if (!$produk_ada) {
                        throw new Exception(
                            "Produk tidak ditemukan saat memperbarui stok."
                        );
                    }

                    /* =====================================
                       PASTIKAN PRODUK TIDAK MEMILIKI
                       VARIAN AKTIF
                    ===================================== */

                    mysqli_stmt_bind_param(
                        $stmt_cek_varian,
                        "i",
                        $id_produk
                    );

                    if (!mysqli_stmt_execute($stmt_cek_varian)) {
                        throw new Exception("Gagal memeriksa varian produk.");
                    }

                    $result_cek_varian = mysqli_stmt_get_result(
                        $stmt_cek_varian
                    );

                    $data_cek_varian = mysqli_fetch_assoc(
                        $result_cek_varian
                    );

                    $jumlah_varian = (int) (
                        $data_cek_varian['jumlah'] ?? 0
                    );

                    if ($jumlah_varian > 0) {
                        throw new Exception(
                            "Produk ini memiliki varian aktif. Pembelian harus menggunakan varian yang sesuai."
                        );
                    }

                    /* =====================================
                       UPDATE STOK PRODUK BIASA
                    ===================================== */

                    mysqli_stmt_bind_param(
                        $stmt_produk,
                        "ii",
                        $jumlah,
                        $id_produk
                    );

                    if (!mysqli_stmt_execute($stmt_produk)) {
                        throw new Exception("Gagal memperbarui stok produk.");
                    }

                    if (mysqli_stmt_affected_rows($stmt_produk) <= 0) {
                        throw new Exception(
                            "Stok produk gagal diperbarui."
                        );
                    }
                }
            }

            /* =============================================
               SINKRON TOTAL STOK PRODUK BERVARIAN
            ============================================= */

            foreach (array_keys($produk_varian) as $id_produk_sync) {
                mysqli_stmt_bind_param(
                    $stmt_sync,
                    "i",
                    $id_produk_sync
                );

                if (!mysqli_stmt_execute($stmt_sync)) {
                    throw new Exception(
                        "Gagal menyinkronkan total stok produk bervarian."
                    );
                }

                if (mysqli_stmt_affected_rows($stmt_sync) <= 0) {
                    throw new Exception(
                        "Total stok produk bervarian gagal disinkronkan."
                    );
                }
            }

            /* =============================================
               TUTUP QUERY STOK
            ============================================= */

            mysqli_stmt_close($stmt_produk);
            $stmt_produk = null;

            mysqli_stmt_close($stmt_varian);
            $stmt_varian = null;

            mysqli_stmt_close($stmt_sync);
            $stmt_sync = null;

            mysqli_stmt_close($stmt_cek_produk);
            $stmt_cek_produk = null;

            mysqli_stmt_close($stmt_cek_varian);
            $stmt_cek_varian = null;

            /* =============================================
               UBAH STATUS PEMBELIAN
            ============================================= */

            $stmt_status = mysqli_prepare(
                $conn,
                "UPDATE pembelian
                 SET status = 'Selesai'
                 WHERE id_pembelian = ?
                   AND status = 'Draft'"
            );

            if (!$stmt_status) {
                throw new Exception(
                    "Gagal menyiapkan perubahan status pembelian."
                );
            }

            mysqli_stmt_bind_param(
                $stmt_status,
                "i",
                $id_pembelian
            );

            if (!mysqli_stmt_execute($stmt_status)) {
                throw new Exception(
                    "Gagal memperbarui status pembelian."
                );
            }

            if (mysqli_stmt_affected_rows($stmt_status) !== 1) {
                throw new Exception(
                    "Status pembelian gagal diperbarui."
                );
            }

            mysqli_stmt_close($stmt_status);
            $stmt_status = null;

            /* =============================================
               COMMIT TRANSAKSI
            ============================================= */

            if (!mysqli_commit($conn)) {
                throw new Exception(
                    "Gagal menyimpan transaksi pembelian."
                );
            }

            $transaction_started = false;

            /* =============================================
               CATAT LOG PENYELESAIAN PEMBELIAN
            ============================================= */

            $nomorPembelianLog = $pembelian_lock['nomor_pembelian']
                ?? ('ID ' . $id_pembelian);

            $detailLog = 'Menyelesaikan pembelian "' .
                $nomorPembelianLog .
                '" (ID: ' . $id_pembelian .
                ', status: Draft menjadi Selesai). Stok produk/varian telah diperbarui.';

            try {
                $logBerhasil = catatLogAktivitas(
                    $conn,
                    'pembelian',
                    'Selesaikan Pembelian',
                    $detailLog
                );

                if (!$logBerhasil) {
                    error_log(
                        'Gagal mencatat log penyelesaian pembelian: ID ' .
                        $id_pembelian
                    );
                }
            } catch (Throwable $logError) {
                error_log(
                    'Kesalahan saat mencatat log penyelesaian pembelian ID ' .
                    $id_pembelian . ': ' . $logError->getMessage()
                );
            }

            header(
                "Location: detail.php?id=" .
                $id_pembelian .
                "&status=selesai"
            );

            exit;

        } catch (Throwable $e) {
            if ($transaction_started) {
                mysqli_rollback($conn);
            }

            foreach ([
                $stmt,
                $stmt_detail,
                $stmt_produk,
                $stmt_varian,
                $stmt_sync,
                $stmt_status,
                $stmt_cek_produk,
                $stmt_cek_varian
            ] as $statement) {
                if ($statement instanceof mysqli_stmt) {
                    @mysqli_stmt_close($statement);
                }
            }

            error_log(
                "Penyelesaian pembelian gagal: " .
                $e->getMessage()
            );

            $error = $e->getMessage();
        }
    }
}

/* =====================================================
   NOTIFIKASI
===================================================== */

if (($_GET['status'] ?? '') === 'selesai') {
    $success = "Pembelian berhasil diselesaikan dan stok produk/varian telah ditambahkan.";
} elseif (($_GET['status'] ?? '') === 'berhasil') {
    $success = "Pembelian berhasil dibuat sebagai Draft.";
}

/* =====================================================
   DATA PEMBELIAN
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.*,
        s.kode_supplier,
        s.nama_supplier,
        s.nama_kontak,
        s.telepon,
        s.email,
        s.alamat
     FROM pembelian p
     INNER JOIN supplier s
        ON p.id_supplier = s.id_supplier
     WHERE p.id_pembelian = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$pembelian = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pembelian) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   DATA DETAIL PRODUK
===================================================== */

$detail_produk = [];
$jumlah_item = 0;
$detail_error = '';

$stmt_detail = mysqli_prepare(
    $conn,
    "SELECT
        d.*,
        p.nama_produk,
        p.gambar,
        p.stok,
        k.nama_kategori,
        p.lokasi_rak AS lokasi_rak_produk,
        lr_produk.nama_lokasi AS nama_lokasi_produk,
        v.nama_varian,
        v.stok AS stok_varian,
        v.lokasi_rak AS lokasi_rak_varian,
        lr_varian.nama_lokasi AS nama_lokasi_varian
     FROM detail_pembelian d
     INNER JOIN produk p
        ON d.id_produk = p.id_produk
     LEFT JOIN kategori k
        ON p.id_kategori = k.id_kategori
     LEFT JOIN lokasi_rak lr_produk
        ON p.lokasi_rak = lr_produk.kode_rak
     LEFT JOIN produk_varian v
        ON d.id_varian = v.id_varian
        AND v.id_produk = d.id_produk
     LEFT JOIN lokasi_rak lr_varian
        ON v.lokasi_rak = lr_varian.kode_rak
     WHERE d.id_pembelian = ?
     ORDER BY d.id_detail ASC"
);

if (!$stmt_detail) {
    $detail_error = "Detail produk gagal dimuat.";
} else {
    mysqli_stmt_bind_param($stmt_detail, "i", $id);

    if (!mysqli_stmt_execute($stmt_detail)) {
        $detail_error = "Detail produk gagal dimuat.";
    } else {
        $detail_result = mysqli_stmt_get_result($stmt_detail);

        while ($detail = mysqli_fetch_assoc($detail_result)) {
            $detail_produk[] = $detail;
            $jumlah_item += (int) $detail['jumlah'];
        }
    }

    mysqli_stmt_close($stmt_detail);
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

    <title>Detail Pembelian - Toko ATK &amp; Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>
        .pembelian-detail-page {
            width: 100%;
        }

        .detail-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .detail-alert.success {
            background: #e8f7ee;
            color: #237a45;
            border: 1px solid #c9ead6;
        }

        .detail-alert.error {
            background: #fce8e8;
            color: #a33;
            border: 1px solid #f2caca;
        }

        .detail-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-info-card {
            background: #fff;
            border: 1px solid #e3eaf0;
            border-radius: 12px;
            padding: 22px;
        }

        .detail-info-card h2 {
            margin: 0 0 18px;
            color: #123451;
            font-size: 18px;
        }

        .detail-info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f3f6;
        }

        .detail-info-row:last-child {
            border-bottom: none;
        }

        .detail-info-label {
            color: #7890a4;
            font-size: 13px;
        }

        .detail-info-value {
            color: #123451;
            font-size: 14px;
            font-weight: 600;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .detail-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .detail-status.draft {
            background: #fff4df;
            color: #a66a00;
        }

        .detail-status.selesai {
            background: #e8f7ee;
            color: #237a45;
        }

        .detail-status.dibatalkan {
            background: #fce8e8;
            color: #a33;
        }

        .detail-product-card {
            background: #fff;
            border: 1px solid #e3eaf0;
            border-radius: 12px;
            overflow: hidden;
        }

        .detail-product-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e8edf1;
        }

        .detail-product-header h2 {
            margin: 0;
            color: #123451;
            font-size: 20px;
        }

        .detail-product-header p {
            margin: 6px 0 0;
            color: #7890a4;
            font-size: 13px;
        }

        .detail-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .detail-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .detail-table th {
            padding: 14px 12px;
            background: #f4f7fa;
            color: #123451;
            text-align: left;
            border-bottom: 1px solid #dfe6ec;
            font-size: 12px;
            white-space: nowrap;
        }

        .detail-table td {
            padding: 14px 12px;
            color: #456;
            border-bottom: 1px solid #edf1f4;
            vertical-align: middle;
            font-size: 13px;
        }

        .detail-product-name {
            color: #123451;
            font-weight: 700;
        }

        .detail-product-info {
            margin-top: 5px;
            color: #7890a4;
            font-size: 11px;
        }

        .detail-rak {
            display: inline-flex;
            background: #eef3f7;
            color: #123451;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        .detail-subtotal {
            color: #123451;
            font-weight: 700;
            white-space: nowrap;
        }

        .detail-total {
            display: flex;
            justify-content: flex-end;
            padding: 20px 22px;
            background: #f4f7fa;
            border-top: 1px solid #e3eaf0;
        }

        .detail-total-content {
            text-align: right;
        }

        .detail-total-content span {
            display: block;
            color: #7890a4;
            font-size: 13px;
        }

        .detail-total-content strong {
            display: block;
            margin-top: 4px;
            color: #123451;
            font-size: 26px;
        }

        .detail-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-selesaikan,
        .btn-kembali {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .btn-selesaikan {
            border: none;
            background: #123451;
            color: #fff;
            cursor: pointer;
        }

        .btn-selesaikan:hover {
            background: #0d2942;
        }

        .btn-kembali {
            background: #eef2f5;
            color: #123451;
        }

        .btn-kembali:hover {
            background: #e1e7ec;
        }

        .detail-empty {
            padding: 40px 20px !important;
            text-align: center;
            color: #7890a4 !important;
        }

        @media (max-width: 800px) {
            .detail-info-grid {
                grid-template-columns: 1fr;
            }

            .detail-info-row {
                flex-direction: column;
                gap: 5px;
            }

            .detail-info-value {
                text-align: left;
            }
        }

        @media (max-width: 550px) {
            .detail-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .detail-buttons form,
            .btn-selesaikan,
            .btn-kembali {
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

            <div class="pembelian-detail-page">

                <div class="page-title">
                    <h1>Detail Pembelian</h1>
                    <p>Informasi lengkap transaksi pembelian.</p>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="detail-alert success" role="status">
                        ✓ <?= escapePembelianDetail($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="detail-alert error" role="alert">
                        <?= escapePembelianDetail($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($detail_error !== ''): ?>
                    <div class="detail-alert error" role="alert">
                        <?= escapePembelianDetail($detail_error); ?>
                    </div>
                <?php endif; ?>

                <div class="detail-info-grid">

                    <div class="detail-info-card">

                        <h2>Informasi Pembelian</h2>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Nomor Pembelian
                            </span>

                            <span class="detail-info-value">
                                <?= escapePembelianDetail(
                                    $pembelian['nomor_pembelian']
                                ); ?>
                            </span>
                        </div>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Tanggal
                            </span>

                            <span class="detail-info-value">
                                <?php
                                $tanggal_pembelian = strtotime(
                                    $pembelian['tanggal_pembelian']
                                );

                                echo $tanggal_pembelian !== false
                                    ? date('d-m-Y', $tanggal_pembelian)
                                    : '-';
                                ?>
                            </span>
                        </div>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Status
                            </span>

                            <span class="detail-info-value">
                                <?php if ($pembelian['status'] === 'Draft'): ?>
                                    <span class="detail-status draft">
                                        Draft
                                    </span>
                                <?php elseif ($pembelian['status'] === 'Selesai'): ?>
                                    <span class="detail-status selesai">
                                        Selesai
                                    </span>
                                <?php else: ?>
                                    <span class="detail-status dibatalkan">
                                        <?= escapePembelianDetail(
                                            $pembelian['status']
                                        ); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Keterangan
                            </span>

                            <span class="detail-info-value">
                                <?= escapePembelianDetail(
                                    $pembelian['keterangan'] ?: '-'
                                ); ?>
                            </span>
                        </div>

                    </div>

                    <div class="detail-info-card">

                        <h2>Informasi Supplier</h2>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Kode Supplier
                            </span>

                            <span class="detail-info-value">
                                <?= escapePembelianDetail(
                                    $pembelian['kode_supplier']
                                ); ?>
                            </span>
                        </div>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Nama Supplier
                            </span>

                            <span class="detail-info-value">
                                <?= escapePembelianDetail(
                                    $pembelian['nama_supplier']
                                ); ?>
                            </span>
                        </div>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Kontak
                            </span>

                            <span class="detail-info-value">
                                <?= escapePembelianDetail(
                                    $pembelian['nama_kontak'] ?: '-'
                                ); ?>
                            </span>
                        </div>

                        <div class="detail-info-row">
                            <span class="detail-info-label">
                                Telepon
                            </span>

                            <span class="detail-info-value">
                                <?= escapePembelianDetail(
                                    $pembelian['telepon'] ?: '-'
                                ); ?>
                            </span>
                        </div>

                    </div>

                </div>

                <div class="detail-product-card">

                    <div class="detail-product-header">
                        <h2>Produk yang Dibeli</h2>
                        <p>Daftar produk dan varian pada transaksi ini.</p>
                    </div>

                    <div class="detail-table-wrapper">

                        <table class="detail-table">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Produk</th>
                                    <th>Varian</th>
                                    <th>Kategori</th>
                                    <th>Rak</th>
                                    <th>Jumlah</th>
                                    <th>Harga Beli</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (!empty($detail_produk)): ?>

                                    <?php foreach ($detail_produk as $index => $detail): ?>

                                        <?php
                                        $memakai_varian = !empty(
                                            $detail['id_varian']
                                        );

                                        $lokasi_rak = $memakai_varian
                                            ? ($detail['lokasi_rak_varian'] ?? '')
                                            : ($detail['lokasi_rak_produk'] ?? '');

                                        $nama_lokasi = $memakai_varian
                                            ? ($detail['nama_lokasi_varian'] ?? '')
                                            : ($detail['nama_lokasi_produk'] ?? '');

                                        $stok_sekarang = $memakai_varian
                                            ? ($detail['stok_varian'] ?? 0)
                                            : ($detail['stok'] ?? 0);
                                        ?>

                                        <tr>

                                            <td><?= $index + 1; ?></td>

                                            <td>
                                                <div class="detail-product-name">
                                                    <?= escapePembelianDetail(
                                                        $detail['nama_produk']
                                                    ); ?>
                                                </div>

                                                <div class="detail-product-info">
                                                    Stok sekarang:
                                                    <?= number_format(
                                                        (int) $stok_sekarang,
                                                        0,
                                                        ',',
                                                        '.'
                                                    ); ?>
                                                </div>
                                            </td>

                                            <td>
                                                <?php if (
                                                    $memakai_varian &&
                                                    !empty($detail['nama_varian'])
                                                ): ?>
                                                    <span class="detail-rak">
                                                        <?= escapePembelianDetail(
                                                            $detail['nama_varian']
                                                        ); ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= escapePembelianDetail(
                                                    $detail['nama_kategori'] ?? '-'
                                                ); ?>
                                            </td>

                                            <td>
                                                <?php if ($lokasi_rak !== ''): ?>
                                                    <span class="detail-rak">
                                                        <?= escapePembelianDetail(
                                                            $lokasi_rak
                                                        ); ?>
                                                    </span>

                                                    <?php if ($nama_lokasi !== ''): ?>
                                                        <div class="detail-product-info">
                                                            <?= escapePembelianDetail(
                                                                $nama_lokasi
                                                            ); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= number_format(
                                                    (int) $detail['jumlah'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </td>

                                            <td>
                                                Rp <?= number_format(
                                                    (float) $detail['harga_beli'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </td>

                                            <td class="detail-subtotal">
                                                Rp <?= number_format(
                                                    (float) $detail['subtotal'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ); ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="8" class="detail-empty">
                                            Belum ada produk pada pembelian ini.
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="detail-total">

                        <div class="detail-total-content">
                            <span>
                                Total <?= number_format(
                                    $jumlah_item,
                                    0,
                                    ',',
                                    '.'
                                ); ?> barang
                            </span>

                            <strong>
                                Rp <?= number_format(
                                    (float) $pembelian['total_harga'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </strong>
                        </div>

                    </div>

                </div>

                <div class="detail-buttons">

                    <?php if (
                        $pembelian['status'] === 'Draft' &&
                        $detail_error === '' &&
                        !empty($detail_produk)
                    ): ?>

                        <form
                            method="POST"
                            onsubmit="return confirm('Yakin ingin menyelesaikan pembelian ini? Stok produk dan varian yang dibeli akan otomatis bertambah.');"
                        >
                            <input
                                type="hidden"
                                name="aksi"
                                value="selesaikan"
                            >

                            <input
                                type="hidden"
                                name="id_pembelian"
                                value="<?= $id; ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= escapePembelianDetail(
                                    $_SESSION['csrf_pembelian_detail']
                                ); ?>"
                            >

                            <button
                                type="submit"
                                class="btn-selesaikan"
                            >
                                ✓ Selesaikan Pembelian
                            </button>
                        </form>

                    <?php endif; ?>

                    <a
                        href="index.php"
                        class="btn-kembali"
                    >
                        &larr; Kembali ke Pembelian
                    </a>

                </div>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>

<script src="../assets/js/admin.js"></script>

</body>
</html>