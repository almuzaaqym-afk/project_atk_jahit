<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";

/* =========================================================
   AMBIL ID PESANAN
========================================================= */

$id_pesanan = (int)($_GET['id'] ?? 0);

if ($id_pesanan <= 0) {
    header("Location: index.php?status=gagal");
    exit;
}

$success = '';
$error = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'berhasil') {
        $success = "Status pesanan berhasil diperbarui.";
    }

    if ($_GET['status'] === 'gagal') {
        $error = "Status pesanan gagal diperbarui.";
    }
}

/* =========================================================
   AMBIL DATA PESANAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        p.bukti_pembayaran,
        p.id_pelanggan,
        pl.nama
     FROM pesanan p
     LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
     WHERE p.id_pesanan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   AMBIL DETAIL PRODUK PESANAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        dp.id_produk,
        dp.id_varian,
        dp.nama_varian,
        dp.jumlah,
        COALESCE(dp.harga, pv.harga, pr.harga) AS harga,
        pr.nama_produk,
        pr.harga AS harga_produk,
        pr.gambar,
        k.nama_kategori,
        pv.nama_varian AS nama_varian_data,
        (
            SELECT pvg.nama_gambar
            FROM produk_varian_gambar pvg
            WHERE pvg.id_varian = pv.id_varian
            ORDER BY pvg.urutan ASC, pvg.id_gambar_varian ASC
            LIMIT 1
        ) AS gambar_varian,
        lv.nama_lokasi AS nama_lokasi_varian,
        lv.keterangan AS keterangan_lokasi_varian,
        lp.nama_lokasi AS nama_lokasi_produk,
        lp.keterangan AS keterangan_lokasi_produk
     FROM detail_pesanan dp
     LEFT JOIN produk pr ON dp.id_produk = pr.id_produk
     LEFT JOIN kategori k ON pr.id_kategori = k.id_kategori
     LEFT JOIN produk_varian pv ON dp.id_varian = pv.id_varian
     LEFT JOIN lokasi_rak lv ON pv.lokasi_rak = lv.kode_rak
     LEFT JOIN lokasi_rak lp ON pr.lokasi_rak = lp.kode_rak
     WHERE dp.id_pesanan = ?
     ORDER BY dp.id_produk ASC, dp.id_varian ASC"
);

mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
mysqli_stmt_execute($stmt);

$result_detail = mysqli_stmt_get_result($stmt);

$detail_produk = [];

while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_produk[] = $row;
}

mysqli_stmt_close($stmt);

/* =========================================================
   HITUNG TOTAL PRODUK
========================================================= */

$total_detail = 0;

foreach ($detail_produk as $item) {
    $total_detail +=
        (float)($item['harga'] ?? 0)
        * (int)($item['jumlah'] ?? 0);
}

/* =========================================================
   STATUS PESANAN
========================================================= */

function classStatusDetail($status)
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

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pesanan - Toko ATK & Alat Jahit</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .detail-pesanan-page {
            max-width: 1200px;
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

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .detail-header-left h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 28px;
        }

        .detail-header-left p {
            margin: 0;
            color: #7890a4;
            font-size: 14px;
        }

        .btn-kembali {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            background: #eef3f7;
            border: 1px solid #dce5ec;
            color: #334f66;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .btn-kembali:hover {
            background: #e1e9ef;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04);
            overflow: hidden;
        }

        .detail-card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #e6edf2;
        }

        .detail-card-header h2 {
            margin: 0;
            color: #102f4b;
            font-size: 18px;
        }

        .detail-card-body {
            padding: 22px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 11px 0;
            border-bottom: 1px solid #edf1f4;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #7890a4;
            font-size: 13px;
        }

        .info-value {
            color: #334f66;
            font-size: 14px;
            font-weight: 600;
            text-align: right;
        }

        .nomor-order {
            color: #102f4b;
            font-weight: 800;
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

        .bukti-area {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #edf1f4;
        }

        .bukti-area h3 {
            margin: 0 0 12px;
            color: #334f66;
            font-size: 14px;
        }

        .btn-lihat-bukti {
            display: inline-flex;
            margin-top: 10px;
            padding: 9px 14px;
            border-radius: 8px;
            background: #102f4b;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-lihat-bukti:hover {
            background: #1c496b;
        }

        .belum-ada {
            color: #9aafbf;
            font-size: 13px;
        }

        .produk-table-wrapper {
            overflow-x: auto;
        }

        .produk-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .produk-table th {
            background: #f4f7fa;
            color: #506d84;
            text-align: left;
            padding: 13px;
            font-size: 12px;
            font-weight: 700;
        }

        .produk-table td {
            padding: 14px 13px;
            border-top: 1px solid #e5ebf0;
            color: #334f66;
            font-size: 13px;
            vertical-align: middle;
        }

        .produk-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .produk-gambar {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e1e8ed;
            background: #f4f7fa;
        }

        .produk-nama {
            color: #102f4b;
            font-weight: 700;
        }

        .produk-kategori {
            display: block;
            margin-top: 4px;
            color: #9aafbf;
            font-size: 11px;
        }

        .harga-produk {
            white-space: nowrap;
        }

        .jumlah-produk {
            font-weight: 700;
            text-align: center;
        }

        .varian-produk {
            display: block;
            margin-top: 4px;
            color: #5f7890;
            font-size: 11px;
            font-weight: 600;
        }

        .lokasi-produk {
            min-width: 180px;
            line-height: 1.45;
        }

        .lokasi-produk strong {
            display: block;
            color: #102f4b;
            font-size: 12px;
        }

        .lokasi-produk small {
            display: block;
            margin-top: 3px;
            color: #7890a4;
            font-size: 11px;
        }

        .subtotal-produk {
            color: #102f4b;
            font-weight: 700;
            white-space: nowrap;
        }

        .total-area {
            display: flex;
            justify-content: flex-end;
            padding: 20px 22px;
            border-top: 1px solid #e6edf2;
        }

        .total-box {
            min-width: 280px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            padding: 6px 0;
            color: #506d84;
            font-size: 13px;
        }

        .total-row.grand-total {
            margin-top: 8px;
            padding-top: 14px;
            border-top: 1px solid #dfe7ed;
            color: #102f4b;
            font-size: 18px;
            font-weight: 800;
        }

        .status-action-card {
            margin-top: 20px;
        }

        .status-action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-action-form {
            margin: 0;
        }

        .btn-status-action {
            border: none;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-siap {
            background: #102f4b;
            color: #fff;
        }

        .btn-siap:hover {
            background: #1c496b;
        }

        .btn-selesai {
            background: #27804a;
            color: #fff;
        }

        .btn-selesai:hover {
            background: #1f6b3e;
        }

        .btn-batal {
            background: #fde7e7;
            color: #b23b3b;
            border: 1px solid #f2caca;
        }

        .btn-batal:hover {
            background: #f9d5d5;
        }

        .status-final {
            margin-top: 20px;
            padding: 15px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-final.selesai {
            background: #e3f5e9;
            color: #27804a;
            border: 1px solid #c8e8d3;
        }

        .status-final.dibatalkan {
            background: #fde7e7;
            color: #b23b3b;
            border: 1px solid #f2caca;
        }

        .produk-kosong {
            text-align: center;
            padding: 50px 20px;
            color: #7890a4;
            font-size: 14px;
        }

        .btn-chat-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 15px;
            background: #102f4b;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            transition: .2s;
            border: none;
        }

        .btn-chat-admin:hover {
            background: #1c496b;
        }

        @media(max-width: 900px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {
            .detail-header {
                flex-direction: column;
            }

            .detail-header-left h1 {
                font-size: 24px;
            }

            .info-row {
                flex-direction: column;
                gap: 4px;
            }

            .info-value {
                text-align: left;
            }

            .total-area {
                justify-content: flex-start;
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

                <div class="detail-pesanan-page">

                    <div class="detail-header">
                        <div class="detail-header-left">
                            <h1>Detail Pesanan</h1>
                            <p>Informasi lengkap pesanan pelanggan.</p>
                        </div>

                        <a href="index.php" class="btn-kembali">
                            ← Kembali
                        </a>
                    </div>

                    <?php if ($success !== ''): ?>
                        <div class="pesanan-alert success">
                            ✓ <?= htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="pesanan-alert error">
                            ⚠ <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- INFORMASI PESANAN DAN PELANGGAN -->

                    <div class="detail-grid">

                        <div class="detail-card">

                            <div class="detail-card-header">
                                <h2>Informasi Pesanan</h2>
                            </div>

                            <div class="detail-card-body">

                                <div class="info-row">
                                    <span class="info-label">Nomor Pesanan</span>
                                    <span class="info-value nomor-order">
                                        <?= htmlspecialchars($pesanan['nomor_pesanan']); ?>
                                    </span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">ID Pesanan</span>
                                    <span class="info-value">
                                        #<?= (int)$pesanan['id_pesanan']; ?>
                                    </span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Tanggal</span>
                                    <span class="info-value">
                                        <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])); ?>
                                    </span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Status</span>
                                    <span class="info-value">
                                        <span class="pesanan-status <?= classStatusDetail($pesanan['status_pesanan']); ?>">
                                            <?= htmlspecialchars($pesanan['status_pesanan']); ?>
                                        </span>
                                    </span>
                                </div>

                            </div>
                        </div>

                        <div class="detail-card">

                            <div class="detail-card-header">
                                <h2>Informasi Pelanggan</h2>
                            </div>

                            <div class="detail-card-body">

                                <div class="info-row">
                                    <span class="info-label">ID Pelanggan</span>
                                    <span class="info-value">
                                        #<?= (int)$pesanan['id_pelanggan']; ?>
                                    </span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Nama Pelanggan</span>
                                    <span class="info-value">
                                        <?= htmlspecialchars($pesanan['nama'] ?? '-'); ?>
                                    </span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Total Pesanan</span>
                                    <span class="info-value">
                                        Rp <?= number_format((float)$pesanan['total_harga'], 0, ',', '.'); ?>
                                    </span>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- PRODUK PESANAN -->

                    <div class="detail-card">

                        <div class="detail-card-header">
                            <h2>Produk Pesanan</h2>
                        </div>

                        <div class="produk-table-wrapper">

                            <?php if (count($detail_produk) > 0): ?>

                                <table class="produk-table">

                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Jumlah</th>
                                            <th>Lokasi Barang</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($detail_produk as $item): ?>

                                            <?php
                                            $harga = (float)($item['harga'] ?? $item['harga_produk'] ?? 0);
                                            $jumlah = (int)($item['jumlah'] ?? 0);
                                            $subtotal = $harga * $jumlah;

                                            $isVarian = !empty($item['id_varian']);

                                            $namaVarian = trim(
                                                (string)($item['nama_varian'] ?? $item['nama_varian_data'] ?? '')
                                            );

                                            $namaLokasi = $isVarian
                                                ? trim((string)($item['nama_lokasi_varian'] ?? ''))
                                                : trim((string)($item['nama_lokasi_produk'] ?? ''));

                                            $keteranganLokasi = $isVarian
                                                ? trim((string)($item['keterangan_lokasi_varian'] ?? ''))
                                                : trim((string)($item['keterangan_lokasi_produk'] ?? ''));

                                            $gambarProduk = $isVarian && !empty($item['gambar_varian'])
                                                ? $item['gambar_varian']
                                                : ($item['gambar'] ?? '');

                                            $gambarPath = $isVarian && !empty($item['gambar_varian'])
                                                ? '../assets/images/products/variants/'
                                                : '../assets/images/products/';
                                            ?>

                                            <tr>

                                                <td>

                                                    <div class="produk-info">

                                                        <?php if (!empty($gambarProduk)): ?>

                                                            <img
                                                                src="<?= $gambarPath . htmlspecialchars($gambarProduk); ?>"
                                                                alt="<?= htmlspecialchars($item['nama_produk']); ?>"
                                                                class="produk-gambar"
                                                            >

                                                        <?php else: ?>

                                                            <div class="produk-gambar"></div>

                                                        <?php endif; ?>

                                                        <div>

                                                            <span class="produk-nama">
                                                                <?= htmlspecialchars($item['nama_produk']); ?>
                                                            </span>

                                                            <span class="produk-kategori">
                                                                <?= htmlspecialchars($item['nama_kategori'] ?? '-'); ?>
                                                            </span>

                                                            <?php if ($isVarian && $namaVarian !== ''): ?>

                                                                <span class="varian-produk">
                                                                    Varian: <?= htmlspecialchars($namaVarian); ?>
                                                                </span>

                                                            <?php endif; ?>

                                                        </div>

                                                    </div>

                                                </td>

                                                <td>
                                                    <span class="harga-produk">
                                                        Rp <?= number_format($harga, 0, ',', '.'); ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <div class="jumlah-produk">
                                                        <?= $jumlah; ?>
                                                    </div>
                                                </td>

                                                <td>

                                                    <div class="lokasi-produk">

                                                        <?php if ($namaLokasi !== ''): ?>

                                                            <strong>
                                                                <?= htmlspecialchars($namaLokasi); ?>
                                                            </strong>

                                                            <?php if ($keteranganLokasi !== ''): ?>

                                                                <small>
                                                                    <?= htmlspecialchars($keteranganLokasi); ?>
                                                                </small>

                                                            <?php endif; ?>

                                                        <?php else: ?>

                                                            <span class="belum-ada">
                                                                Lokasi belum ditentukan.
                                                            </span>

                                                        <?php endif; ?>

                                                    </div>

                                                </td>

                                                <td>
                                                    <span class="subtotal-produk">
                                                        Rp <?= number_format($subtotal, 0, ',', '.'); ?>
                                                    </span>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                                <div class="total-area">

                                    <div class="total-box">

                                        <div class="total-row">
                                            <span>Total Produk</span>
                                            <span>
                                                Rp <?= number_format($total_detail, 0, ',', '.'); ?>
                                            </span>
                                        </div>

                                        <div class="total-row grand-total">
                                            <span>Total Pesanan</span>
                                            <span>
                                                Rp <?= number_format((float)$pesanan['total_harga'], 0, ',', '.'); ?>
                                            </span>
                                        </div>

                                    </div>

                                </div>

                            <?php else: ?>

                                <div class="produk-kosong">
                                    Belum ada detail produk pada pesanan ini.
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- AKSI PESANAN: DIPROSES -->

                    <?php if ($pesanan['status_pesanan'] === 'Diproses'): ?>

                        <div class="detail-card status-action-card">

                            <div class="detail-card-header">
                                <h2>Aksi Pesanan</h2>
                            </div>

                            <div class="detail-card-body">

                                <div class="status-action-buttons">

                                    <form
                                        method="POST"
                                        action="update_status.php"
                                        class="status-action-form"
                                    >
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="id_pesanan" value="<?= (int)$pesanan['id_pesanan']; ?>">
                                        <input type="hidden" name="status_pesanan" value="Siap Diambil">

                                        <button
                                            type="submit"
                                            class="btn-status-action btn-siap"
                                            onclick="return confirm('Apakah pesanan sudah siap diambil pelanggan?');"
                                        >
                                            Pesanan Siap Diambil
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="update_status.php"
                                        class="status-action-form"
                                    >
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="id_pesanan" value="<?= (int)$pesanan['id_pesanan']; ?>">
                                        <input type="hidden" name="status_pesanan" value="Dibatalkan">

                                        <button
                                            type="submit"
                                            class="btn-status-action btn-batal"
                                            onclick="return confirm('Yakin ingin membatalkan pesanan ini?');"
                                        >
                                            Batalkan Pesanan
                                        </button>
                                    </form>

                                    <a
                                        href="chat.php?id=<?= $id_pesanan; ?>"
                                        class="btn-chat-admin"
                                    >
                                        💬 Live Chat Pelanggan
                                    </a>

                                </div>

                            </div>

                        </div>

                    <!-- AKSI PESANAN: SIAP DIAMBIL -->

                    <?php elseif ($pesanan['status_pesanan'] === 'Siap Diambil'): ?>

                        <div class="detail-card status-action-card">

                            <div class="detail-card-header">
                                <h2>Aksi Pesanan</h2>
                            </div>

                            <div class="detail-card-body">

                                <div class="status-action-buttons">

                                    <form
                                        method="POST"
                                        action="update_status.php"
                                        class="status-action-form"
                                    >
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="id_pesanan" value="<?= (int)$pesanan['id_pesanan']; ?>">
                                        <input type="hidden" name="status_pesanan" value="Selesai">

                                        <button
                                            type="submit"
                                            class="btn-status-action btn-selesai"
                                            onclick="return confirm('Apakah pesanan sudah selesai diambil pelanggan?');"
                                        >
                                            Pesanan Selesai
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="update_status.php"
                                        class="status-action-form"
                                    >
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="id_pesanan" value="<?= (int)$pesanan['id_pesanan']; ?>">
                                        <input type="hidden" name="status_pesanan" value="Dibatalkan">

                                        <button
                                            type="submit"
                                            class="btn-status-action btn-batal"
                                            onclick="return confirm('Yakin ingin membatalkan pesanan ini?');"
                                        >
                                            Batalkan Pesanan
                                        </button>
                                    </form>

                                    <!-- LIVE CHAT TETAP MUNCUL SAAT SIAP DIAMBIL -->
                                    <a
                                        href="chat.php?id=<?= $id_pesanan; ?>"
                                        class="btn-chat-admin"
                                    >
                                        💬 Live Chat Pelanggan
                                    </a>

                                </div>

                            </div>

                        </div>

                    <!-- STATUS SELESAI -->

                    <?php elseif ($pesanan['status_pesanan'] === 'Selesai'): ?>

                        <div class="status-final selesai">
                            ✓ Pesanan ini sudah selesai. Tidak ada aksi yang dapat dilakukan.
                        </div>

                    <!-- STATUS DIBATALKAN -->

                    <?php elseif ($pesanan['status_pesanan'] === 'Dibatalkan'): ?>

                        <div class="status-final dibatalkan">
                            Pesanan ini sudah dibatalkan. Tidak ada aksi yang dapat dilakukan.
                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <?php require_once "../includes/footer.php"; ?>

        </main>

    </div>

    <script src="../assets/js/admin.js"></script>

</body>
</html>