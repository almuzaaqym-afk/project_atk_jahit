<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/koneksi.php';
require_once '../includes/hak_akses.php';
require_once __DIR__ . '/../includes/log_aktivitas.php';

cekAkses('pembelian');

$error = '';
$id_supplier = 0;
$tanggal = date('Y-m-d');
$keterangan = '';

/* =====================================================
   DATA SUPPLIER AKTIF
===================================================== */

$supplier_list = [];

$query_supplier = mysqli_query(
    $conn,
    "SELECT id_supplier, kode_supplier, nama_supplier
     FROM supplier
     WHERE status = 'Aktif'
     ORDER BY nama_supplier ASC"
);

if ($query_supplier) {
    while ($row = mysqli_fetch_assoc($query_supplier)) {
        $supplier_list[] = $row;
    }
}

/* =====================================================
   DATA PRODUK
===================================================== */

$data_produk = [];

$query_produk = mysqli_query(
    $conn,
    "SELECT
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.stok,
        p.lokasi_rak,
        k.nama_kategori
     FROM produk p
     LEFT JOIN kategori k
        ON p.id_kategori = k.id_kategori
     ORDER BY p.nama_produk ASC"
);

if ($query_produk) {
    while ($row = mysqli_fetch_assoc($query_produk)) {
        $data_produk[] = $row;
    }
}

/* =====================================================
   DATA VARIAN PRODUK AKTIF
===================================================== */

$data_varian_produk = [];

$query_varian = mysqli_query(
    $conn,
    "SELECT
        id_varian,
        id_produk,
        nama_varian,
        harga,
        stok,
        lokasi_rak
     FROM produk_varian
     WHERE status = 'Aktif'
     ORDER BY id_produk ASC, id_varian ASC"
);

if ($query_varian) {
    while ($varian = mysqli_fetch_assoc($query_varian)) {
        $id_produk_varian = (int) $varian['id_produk'];

        $data_varian_produk[$id_produk_varian][] = [
            'id_varian' => (int) $varian['id_varian'],
            'nama_varian' => $varian['nama_varian'],
            'harga' => (float) $varian['harga'],
            'stok' => (int) $varian['stok'],
            'lokasi_rak' => $varian['lokasi_rak'] ?? ''
        ];
    }
}

/* =====================================================
   PROSES SIMPAN PEMBELIAN
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_supplier_raw = $_POST['id_supplier'] ?? '';

    $id_supplier = (
        is_scalar($id_supplier_raw) &&
        ctype_digit((string) $id_supplier_raw)
    ) ? (int) $id_supplier_raw : 0;

    $tanggal_raw = $_POST['tanggal_pembelian'] ?? '';

    $tanggal = is_string($tanggal_raw)
        ? trim($tanggal_raw)
        : '';

    $keterangan_raw = $_POST['keterangan'] ?? '';

    $keterangan = is_string($keterangan_raw)
        ? trim($keterangan_raw)
        : '';

    $produk_id = $_POST['produk_id'] ?? [];
    $id_varian = $_POST['id_varian'] ?? [];
    $jumlah = $_POST['jumlah'] ?? [];
    $harga_beli = $_POST['harga_beli'] ?? [];

    /* =============================================
       VALIDASI FORMAT FORM
    ============================================= */

    if (
        !is_array($produk_id) ||
        !is_array($id_varian) ||
        !is_array($jumlah) ||
        !is_array($harga_beli)
    ) {
        $error = 'Format data produk tidak valid. Silakan isi ulang formulir.';

        $produk_id = [];
        $id_varian = [];
        $jumlah = [];
        $harga_beli = [];
    }

    /* =============================================
       VALIDASI TANGGAL
    ============================================= */

    if ($error === '') {
        $tanggal_obj = DateTime::createFromFormat(
            '!Y-m-d',
            $tanggal
        );

        $tanggal_errors = DateTime::getLastErrors();

        if (
            !$tanggal_obj ||
            $tanggal_obj->format('Y-m-d') !== $tanggal ||
            (
                is_array($tanggal_errors) &&
                (
                    $tanggal_errors['warning_count'] > 0 ||
                    $tanggal_errors['error_count'] > 0
                )
            )
        ) {
            $error = 'Tanggal pembelian tidak valid.';
        }
    }

    /* =============================================
       VALIDASI SUPPLIER
    ============================================= */

    if ($error === '') {
        if ($id_supplier <= 0) {
            $error = 'Supplier wajib dipilih.';
        } elseif (empty($produk_id)) {
            $error = 'Minimal harus ada satu produk.';
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_supplier
             FROM supplier
             WHERE id_supplier = ?
               AND status = 'Aktif'
             LIMIT 1"
        );

        if (!$stmt) {
            $error = 'Gagal memeriksa supplier.';
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                'i',
                $id_supplier
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Gagal memeriksa supplier.';
                mysqli_stmt_close($stmt);
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $supplier_valid = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if (!$supplier_valid) {
                    $error = 'Supplier tidak ditemukan atau tidak aktif.';
                }
            }
        }
    }

    /* =============================================
       VALIDASI PRODUK DAN VARIAN
    ============================================= */

    $detail_valid = [];
    $total_harga = 0;

    if ($error === '') {
        foreach ($produk_id as $i => $id_produk_raw) {
            // Lewati baris yang belum memilih produk.
            if (
                is_scalar($id_produk_raw) &&
                trim((string) $id_produk_raw) === ''
            ) {
                continue;
            }

            if (
                !is_scalar($id_produk_raw) ||
                !ctype_digit((string) $id_produk_raw) ||
                (int) $id_produk_raw <= 0
            ) {
                $error = 'Produk yang dipilih tidak valid.';
                break;
            }

            $id_produk = (int) $id_produk_raw;

            /* -------------------------------------
               VALIDASI VARIAN
            ------------------------------------- */

            $id_varian_raw = $id_varian[$i] ?? '';

            if (!is_scalar($id_varian_raw)) {
                $error = 'Varian produk tidak valid.';
                break;
            }

            $id_varian_raw = trim((string) $id_varian_raw);

            if (
                $id_varian_raw !== '' &&
                (
                    !ctype_digit($id_varian_raw) ||
                    (int) $id_varian_raw < 0
                )
            ) {
                $error = 'Varian produk tidak valid.';
                break;
            }

            $id_varian_item = $id_varian_raw === ''
                ? 0
                : (int) $id_varian_raw;

            /* -------------------------------------
               VALIDASI JUMLAH
            ------------------------------------- */

            $qty_raw = $jumlah[$i] ?? '';

            if (
                !is_scalar($qty_raw) ||
                !ctype_digit((string) $qty_raw) ||
                (int) $qty_raw <= 0
            ) {
                $error = 'Jumlah produk harus berupa bilangan bulat lebih dari 0.';
                break;
            }

            $qty = (int) $qty_raw;

            /* -------------------------------------
               VALIDASI HARGA BELI
            ------------------------------------- */

            $harga_raw = $harga_beli[$i] ?? '';

            if (
                !is_scalar($harga_raw) ||
                !is_numeric((string) $harga_raw)
            ) {
                $error = 'Harga beli harus berupa angka yang valid.';
                break;
            }

            $harga = (float) $harga_raw;

            if (!is_finite($harga) || $harga < 0) {
                $error = 'Harga beli tidak boleh negatif atau tidak valid.';
                break;
            }

            /* -------------------------------------
               CEK PRODUK
            ------------------------------------- */

            $stmt_produk = mysqli_prepare(
                $conn,
                "SELECT id_produk, nama_produk
                 FROM produk
                 WHERE id_produk = ?
                 LIMIT 1"
            );

            if (!$stmt_produk) {
                $error = 'Gagal memeriksa produk.';
                break;
            }

            mysqli_stmt_bind_param(
                $stmt_produk,
                'i',
                $id_produk
            );

            if (!mysqli_stmt_execute($stmt_produk)) {
                mysqli_stmt_close($stmt_produk);
                $error = 'Gagal memeriksa produk.';
                break;
            }

            $result_produk = mysqli_stmt_get_result($stmt_produk);
            $produk = mysqli_fetch_assoc($result_produk);

            mysqli_stmt_close($stmt_produk);

            if (!$produk) {
                $error = 'Produk tidak ditemukan.';
                break;
            }

            /* -------------------------------------
               CEK PRODUK MEMILIKI VARIAN AKTIF
            ------------------------------------- */

            $stmt_cek_varian = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS jumlah
                 FROM produk_varian
                 WHERE id_produk = ?
                   AND status = 'Aktif'"
            );

            if (!$stmt_cek_varian) {
                $error = 'Gagal memeriksa varian produk.';
                break;
            }

            mysqli_stmt_bind_param(
                $stmt_cek_varian,
                'i',
                $id_produk
            );

            if (!mysqli_stmt_execute($stmt_cek_varian)) {
                mysqli_stmt_close($stmt_cek_varian);
                $error = 'Gagal memeriksa varian produk.';
                break;
            }

            $result_cek_varian = mysqli_stmt_get_result(
                $stmt_cek_varian
            );

            $data_cek_varian = mysqli_fetch_assoc(
                $result_cek_varian
            );

            mysqli_stmt_close($stmt_cek_varian);

            $jumlah_varian = (int) (
                $data_cek_varian['jumlah'] ?? 0
            );

            $memiliki_varian = $jumlah_varian > 0;

            if ($memiliki_varian && $id_varian_item <= 0) {
                $error = 'Produk "' .
                    $produk['nama_produk'] .
                    '" memiliki varian. Pilih variannya terlebih dahulu.';

                break;
            }

            if (!$memiliki_varian && $id_varian_item > 0) {
                $error = 'Varian tidak valid untuk produk "' .
                    $produk['nama_produk'] .
                    '".';

                break;
            }

            /* -------------------------------------
               CEK KESESUAIAN VARIAN
            ------------------------------------- */

            if ($id_varian_item > 0) {
                $stmt_varian = mysqli_prepare(
                    $conn,
                    "SELECT id_varian, id_produk, nama_varian
                     FROM produk_varian
                     WHERE id_varian = ?
                       AND id_produk = ?
                       AND status = 'Aktif'
                     LIMIT 1"
                );

                if (!$stmt_varian) {
                    $error = 'Gagal memeriksa varian.';
                    break;
                }

                mysqli_stmt_bind_param(
                    $stmt_varian,
                    'ii',
                    $id_varian_item,
                    $id_produk
                );

                if (!mysqli_stmt_execute($stmt_varian)) {
                    mysqli_stmt_close($stmt_varian);
                    $error = 'Gagal memeriksa varian.';
                    break;
                }

                $result_varian = mysqli_stmt_get_result(
                    $stmt_varian
                );

                $varian = mysqli_fetch_assoc($result_varian);

                mysqli_stmt_close($stmt_varian);

                if (!$varian) {
                    $error = 'Varian produk tidak ditemukan atau tidak aktif.';
                    break;
                }
            }

            /* -------------------------------------
               HITUNG SUBTOTAL
            ------------------------------------- */

            $subtotal = $qty * $harga;

            if (
                !is_finite($subtotal) ||
                !is_finite($total_harga + $subtotal)
            ) {
                $error = 'Total pembelian terlalu besar atau tidak valid.';
                break;
            }

            $total_harga += $subtotal;

            $detail_valid[] = [
                'id_produk' => $id_produk,
                'id_varian' => $id_varian_item > 0
                    ? $id_varian_item
                    : null,
                'jumlah' => $qty,
                'harga_beli' => $harga,
                'subtotal' => $subtotal
            ];
        }

        if ($error === '' && empty($detail_valid)) {
            $error = 'Minimal harus ada satu produk yang valid.';
        }
    }

    /* =============================================
       SIMPAN TRANSAKSI
    ============================================= */

    if ($error === '') {
        if (!mysqli_begin_transaction($conn)) {
            $error = 'Gagal memulai transaksi pembelian.';
        } else {
            try {
                /* ---------------------------------
                   BUAT NOMOR PEMBELIAN
                --------------------------------- */

                $stmt_cek_nomor = mysqli_prepare(
                    $conn,
                    "SELECT id_pembelian
                     FROM pembelian
                     WHERE nomor_pembelian = ?
                     LIMIT 1"
                );

                if (!$stmt_cek_nomor) {
                    throw new Exception(
                        'Gagal memeriksa nomor pembelian.'
                    );
                }

                $nomor_pembelian = '';
                $nomor_unik = false;

                for ($percobaan = 0; $percobaan < 5; $percobaan++) {
                    $nomor_pembelian = 'PB-' .
                        date('YmdHis') .
                        '-' .
                        random_int(1000, 9999);

                    mysqli_stmt_bind_param(
                        $stmt_cek_nomor,
                        's',
                        $nomor_pembelian
                    );

                    if (!mysqli_stmt_execute($stmt_cek_nomor)) {
                        throw new Exception(
                            'Gagal memeriksa nomor pembelian.'
                        );
                    }

                    $result_nomor = mysqli_stmt_get_result(
                        $stmt_cek_nomor
                    );

                    if (
                        $result_nomor &&
                        mysqli_num_rows($result_nomor) === 0
                    ) {
                        $nomor_unik = true;
                        break;
                    }
                }

                mysqli_stmt_close($stmt_cek_nomor);

                if (!$nomor_unik) {
                    throw new Exception(
                        'Nomor pembelian gagal dibuat. Silakan coba kembali.'
                    );
                }

                /* ---------------------------------
                   SIMPAN HEADER PEMBELIAN
                --------------------------------- */

                $stmt_header = mysqli_prepare(
                    $conn,
                    "INSERT INTO pembelian
                        (
                            nomor_pembelian,
                            id_supplier,
                            tanggal_pembelian,
                            total_harga,
                            status,
                            keterangan
                        )
                     VALUES (?, ?, ?, ?, 'Draft', ?)"
                );

                if (!$stmt_header) {
                    throw new Exception(
                        'Gagal menyiapkan data pembelian.'
                    );
                }

                mysqli_stmt_bind_param(
                    $stmt_header,
                    'sisds',
                    $nomor_pembelian,
                    $id_supplier,
                    $tanggal,
                    $total_harga,
                    $keterangan
                );

                if (!mysqli_stmt_execute($stmt_header)) {
                    $pesan_db = mysqli_stmt_error($stmt_header);
                    mysqli_stmt_close($stmt_header);

                    throw new Exception(
                        'Gagal menyimpan header pembelian: ' . $pesan_db
                    );
                }

                $id_pembelian = mysqli_insert_id($conn);

                mysqli_stmt_close($stmt_header);

                /* ---------------------------------
                   SIMPAN DETAIL PEMBELIAN
                --------------------------------- */

                $stmt_detail = mysqli_prepare(
                    $conn,
                    "INSERT INTO detail_pembelian
                        (
                            id_pembelian,
                            id_produk,
                            id_varian,
                            jumlah,
                            harga_beli,
                            subtotal
                        )
                     VALUES (?, ?, NULLIF(?, 0), ?, ?, ?)"
                );

                if (!$stmt_detail) {
                    throw new Exception(
                        'Gagal menyiapkan detail pembelian.'
                    );
                }

                foreach ($detail_valid as $detail) {
                    $id_varian_simpan = (int) (
                        $detail['id_varian'] ?? 0
                    );

                    mysqli_stmt_bind_param(
                        $stmt_detail,
                        'iiiidd',
                        $id_pembelian,
                        $detail['id_produk'],
                        $id_varian_simpan,
                        $detail['jumlah'],
                        $detail['harga_beli'],
                        $detail['subtotal']
                    );

                    if (!mysqli_stmt_execute($stmt_detail)) {
                        $pesan_db = mysqli_stmt_error($stmt_detail);
                        mysqli_stmt_close($stmt_detail);

                        throw new Exception(
                            'Gagal menyimpan detail pembelian: ' .
                            $pesan_db
                        );
                    }
                }

                mysqli_stmt_close($stmt_detail);

                /* ---------------------------------
                   COMMIT TRANSAKSI
                --------------------------------- */

                if (!mysqli_commit($conn)) {
                    throw new Exception(
                        'Gagal menyelesaikan transaksi pembelian.'
                    );
                }

                /* ---------------------------------
                   CATAT LOG TAMBAH PEMBELIAN
                --------------------------------- */

                $namaSupplierLog = 'ID ' . $id_supplier;

                foreach ($supplier_list as $supplierLog) {
                    if ((int) $supplierLog['id_supplier'] === $id_supplier) {
                        $namaSupplierLog = $supplierLog['nama_supplier'];
                        break;
                    }
                }

                $detailLog = 'Membuat pembelian "' .
                    $nomor_pembelian .
                    '" (ID: ' . $id_pembelian .
                    ', supplier: ' . $namaSupplierLog .
                    ', tanggal: ' . $tanggal .
                    ', jumlah baris produk: ' . count($detail_valid) .
                    ', total: Rp ' . number_format($total_harga, 2, ',', '.') .
                    ', status: Draft).';

                try {
                    $logBerhasil = catatLogAktivitas(
                        $conn,
                        'pembelian',
                        'Tambah Pembelian',
                        $detailLog
                    );

                    if (!$logBerhasil) {
                        error_log(
                            'Gagal mencatat log tambah pembelian: ID ' .
                            $id_pembelian
                        );
                    }
                } catch (Throwable $logError) {
                    error_log(
                        'Terjadi kesalahan saat mencatat log tambah pembelian ID ' .
                        $id_pembelian . ': ' . $logError->getMessage()
                    );
                }

                header(
                    'Location: detail.php?id=' .
                    $id_pembelian .
                    '&status=berhasil'
                );

                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);

                error_log(
                    'Gagal membuat pembelian: ' .
                    $e->getMessage()
                );

                $error = 'Pembelian gagal dibuat. Periksa kembali data dan koneksi database.';
            }
        }
    }
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

    <title>Tambah Pembelian - Toko ATK &amp; Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>
        .pembelian-page {
            width: 100%;
        }

        .card {
            background: #fff;
            border: 1px solid #e3eaf0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .page-title {
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0 0 6px;
            color: #123451;
        }

        .page-title p {
            margin: 0;
            color: #7890a4;
        }

        .grid-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: #123451;
            font-size: 13px;
            font-weight: 600;
        }

        .field input,
        .field select,
        .field textarea,
        .produk-table input,
        .produk-table select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #d7e0e8;
            border-radius: 8px;
            background: #fff;
            color: #123451;
            font-family: inherit;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus,
        .produk-table input:focus,
        .produk-table select:focus {
            outline: none;
            border-color: #245b83;
            box-shadow: 0 0 0 3px rgba(36, 91, 131, 0.08);
        }

        .field textarea {
            min-height: 90px;
            resize: vertical;
        }

        .produk-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .produk-head h2 {
            margin: 0;
            color: #123451;
            font-size: 18px;
        }

        .btn-add,
        .btn-save,
        .btn-back,
        .btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            border: 0;
            border-radius: 8px;
            padding: 10px 15px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-add {
            background: #eef4f8;
            color: #123451;
        }

        .btn-add:hover {
            background: #dfe8ef;
        }

        .btn-save {
            background: #123451;
            color: #fff;
        }

        .btn-save:hover {
            background: #245b83;
        }

        .btn-back {
            background: #eef2f5;
            color: #123451;
        }

        .btn-back:hover {
            background: #e0e7ed;
        }

        .btn-delete {
            background: #fce8e8;
            color: #a33;
            font-size: 12px;
        }

        .btn-delete:hover {
            background: #f7dada;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .produk-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .produk-table th {
            padding: 12px 10px;
            background: #f4f7fa;
            color: #123451;
            text-align: left;
            font-size: 12px;
            white-space: nowrap;
        }

        .produk-table td {
            padding: 10px;
            border-bottom: 1px solid #edf1f4;
            vertical-align: top;
        }

        .info,
        .info-varian {
            display: block;
            margin-top: 5px;
            color: #7890a4;
            font-size: 11px;
            line-height: 1.5;
        }

        .total {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
            padding: 16px;
            background: #f4f7fa;
            border-radius: 10px;
        }

        .total strong {
            font-size: 24px;
            color: #123451;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 8px;
            background: #fce8e8;
            border: 1px solid #f2caca;
            color: #a33;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .select-varian:disabled {
            background: #f3f6f8;
            color: #8aa0b2;
            cursor: not-allowed;
        }

        @media (max-width: 750px) {
            .grid-info {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .field.full {
                grid-column: auto;
            }

            .card {
                padding: 18px;
            }

            .produk-head {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-add {
                width: 100%;
            }

            .actions {
                flex-direction: column;
            }

            .btn-save,
            .btn-back {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <?php require_once '../includes/sidebar.php'; ?>

    <main class="admin-main">

        <?php require_once '../includes/navbar.php'; ?>

        <section class="admin-content">

            <div class="pembelian-page">

                <div class="page-title">
                    <h1>Tambah Pembelian</h1>
                    <p>Buat transaksi pembelian barang dari supplier.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formPembelian">

                    <div class="card">

                        <h2>Informasi Pembelian</h2>

                        <div class="grid-info">

                            <div class="field">
                                <label for="id_supplier">Supplier *</label>

                                <select
                                    name="id_supplier"
                                    id="id_supplier"
                                    required
                                >
                                    <option value="">
                                        -- Pilih Supplier --
                                    </option>

                                    <?php foreach ($supplier_list as $supplier): ?>
                                        <option
                                            value="<?= (int) $supplier['id_supplier']; ?>"
                                            <?= $id_supplier === (int) $supplier['id_supplier']
                                                ? 'selected'
                                                : ''; ?>
                                        >
                                            <?= htmlspecialchars(
                                                $supplier['kode_supplier'] .
                                                ' - ' .
                                                $supplier['nama_supplier'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="field">
                                <label for="tanggal_pembelian">
                                    Tanggal Pembelian *
                                </label>

                                <input
                                    type="date"
                                    name="tanggal_pembelian"
                                    id="tanggal_pembelian"
                                    value="<?= htmlspecialchars(
                                        $tanggal,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    required
                                >
                            </div>

                            <div class="field full">
                                <label for="keterangan">Keterangan</label>

                                <textarea
                                    name="keterangan"
                                    id="keterangan"
                                    placeholder="Keterangan tambahan jika diperlukan"
                                ><?= htmlspecialchars(
                                    $keterangan,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card">

                        <div class="produk-head">
                            <h2>Produk yang Dibeli</h2>

                            <button
                                type="button"
                                class="btn-add"
                                onclick="tambahProduk()"
                            >
                                + Tambah Produk
                            </button>
                        </div>

                        <div class="table-wrap">

                            <table class="produk-table">

                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Varian</th>
                                        <th>Jumlah</th>
                                        <th>Harga Beli</th>
                                        <th>Subtotal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <tbody id="produkBody"></tbody>

                            </table>

                        </div>

                        <div class="total">
                            <div>
                                <div style="color:#7890a4;font-size:13px;">
                                    Total Pembelian
                                </div>

                                <strong id="totalPembelian">Rp 0</strong>
                            </div>
                        </div>

                    </div>

                    <div class="actions">

                        <button
                            type="submit"
                            class="btn-save"
                        >
                            Simpan Pembelian
                        </button>

                        <a href="index.php" class="btn-back">
                            Batal
                        </a>

                    </div>

                </form>

            </div>
        </section>

        <?php require_once '../includes/footer.php'; ?>

    </main>
</div>

<script>
const daftarProduk = <?= json_encode(
    array_map(
        function ($produk) use ($data_varian_produk) {
            return [
                'id' => (int) $produk['id_produk'],
                'nama' => $produk['nama_produk'],
                'harga' => (float) $produk['harga'],
                'stok' => (int) $produk['stok'],
                'kategori' => $produk['nama_kategori'] ?? '-',
                'rak' => $produk['lokasi_rak'] ?? '-',
                'varian' => $data_varian_produk[
                    (int) $produk['id_produk']
                ] ?? []
            ];
        },
        $data_produk
    ),
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
); ?>;

function esc(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function tambahProduk() {
    const tbody = document.getElementById('produkBody');
    const tr = document.createElement('tr');

    let options = '<option value="">-- Pilih Produk --</option>';

    daftarProduk.forEach(function (produk) {
        options += `
            <option value="${produk.id}">
                ${esc(produk.nama)} - Stok: ${produk.stok}
            </option>
        `;
    });

    tr.innerHTML = `
        <td>
            <select
                name="produk_id[]"
                onchange="pilihProduk(this)"
                required
            >
                ${options}
            </select>
            <small class="info"></small>
        </td>

        <td>
            <select
                name="id_varian[]"
                class="select-varian"
                onchange="pilihVarian(this)"
            >
                <option value="">-- Pilih Produk Dahulu --</option>
            </select>
            <small class="info-varian"></small>
        </td>

        <td>
            <input
                type="number"
                name="jumlah[]"
                value="1"
                min="1"
                step="1"
                oninput="hitungTotal()"
                required
            >
        </td>

        <td>
            <input
                type="number"
                name="harga_beli[]"
                value="0"
                min="0"
                step="0.01"
                oninput="hitungTotal()"
                required
            >
        </td>

        <td>
            <strong class="subtotal">Rp 0</strong>
        </td>

        <td>
            <button
                type="button"
                class="btn-delete"
                onclick="hapusProduk(this)"
            >
                Hapus
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    hitungTotal();
}

function pilihProduk(selectElement) {
    const tr = selectElement.closest('tr');
    const productId = parseInt(selectElement.value, 10);

    const produk = daftarProduk.find(function (item) {
        return item.id === productId;
    });

    const selectVarian = tr.querySelector('.select-varian');
    const hargaInput = tr.querySelector('input[name="harga_beli[]"]');
    const infoProduk = tr.querySelector('.info');
    const infoVarian = tr.querySelector('.info-varian');

    selectVarian.innerHTML = '';
    infoProduk.textContent = '';
    infoVarian.textContent = '';

    if (!produk) {
        selectVarian.innerHTML =
            '<option value="">-- Pilih Produk Dahulu --</option>';

        selectVarian.disabled = false;
        selectVarian.required = false;
        hargaInput.value = 0;

        hitungTotal();
        return;
    }

    if (Array.isArray(produk.varian) && produk.varian.length > 0) {
        selectVarian.innerHTML =
            '<option value="">-- Pilih Varian --</option>';

        selectVarian.disabled = false;
        selectVarian.required = true;

        produk.varian.forEach(function (varian) {
            const option = document.createElement('option');

            option.value = varian.id_varian;
            option.textContent =
                varian.nama_varian + ' - Stok: ' + varian.stok;

            selectVarian.appendChild(option);
        });

        hargaInput.value = 0;

        infoProduk.textContent =
            'Kategori: ' + produk.kategori +
            ' • Produk bervarian • Total stok: ' + produk.stok;

        infoVarian.textContent =
            'Pilih varian untuk menentukan detail stok.';
    } else {
        selectVarian.innerHTML =
            '<option value="">-- Tidak Ada Varian --</option>';

        selectVarian.disabled = true;
        selectVarian.required = false;

        hargaInput.value = produk.harga;

        infoProduk.textContent =
            'Kategori: ' + produk.kategori +
            ' • Rak: ' + produk.rak +
            ' • Stok saat ini: ' + produk.stok;
    }

    hitungTotal();
}

function pilihVarian(selectElement) {
    const tr = selectElement.closest('tr');

    const productId = parseInt(
        tr.querySelector('select[name="produk_id[]"]').value,
        10
    );

    const variantId = parseInt(selectElement.value, 10);

    const produk = daftarProduk.find(function (item) {
        return item.id === productId;
    });

    const hargaInput = tr.querySelector('input[name="harga_beli[]"]');
    const infoVarian = tr.querySelector('.info-varian');

    const varian = produk && Array.isArray(produk.varian)
        ? produk.varian.find(function (item) {
            return item.id_varian === variantId;
        })
        : null;

    if (!varian) {
        hargaInput.value = 0;

        infoVarian.textContent =
            produk && produk.varian.length
                ? 'Silakan pilih varian.'
                : '';

        hitungTotal();
        return;
    }

    hargaInput.value = varian.harga;

    infoVarian.textContent =
        'Harga dasar: Rp ' +
        Number(varian.harga).toLocaleString('id-ID') +
        ' • Stok: ' + varian.stok +
        ' • Rak: ' + (varian.lokasi_rak || '-');

    hitungTotal();
}

function hitungTotal() {
    let total = 0;

    document.querySelectorAll('#produkBody tr').forEach(function (row) {
        const qty = parseFloat(
            row.querySelector('input[name="jumlah[]"]').value
        ) || 0;

        const harga = parseFloat(
            row.querySelector('input[name="harga_beli[]"]').value
        ) || 0;

        const subtotal = qty * harga;

        total += subtotal;

        row.querySelector('.subtotal').textContent = formatRp(subtotal);
    });

    document.getElementById('totalPembelian').textContent = formatRp(total);
}

function hapusProduk(button) {
    const row = button.closest('tr');

    if (row) {
        row.remove();
    }

    hitungTotal();
}

function formatRp(number) {
    return 'Rp ' + Number(number).toLocaleString('id-ID', {
        maximumFractionDigits: 2
    });
}

document.addEventListener('DOMContentLoaded', function () {
    tambahProduk();
});
</script>

<script src="../assets/js/admin.js"></script>

</body>
</html>