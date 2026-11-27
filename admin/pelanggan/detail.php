<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";


/* =========================================================
   ID PELANGGAN
========================================================= */

$id_pelanggan = (int)($_GET['id'] ?? 0);

if ($id_pelanggan <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}


/* =========================================================
   DATA PELANGGAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_pelanggan,
        nama,
        email,
        created_at
     FROM pelanggan
     WHERE id_pelanggan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pelanggan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$pelanggan) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}


/* =========================================================
   STATISTIK PELANGGAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS jumlah_pesanan,
        COALESCE(SUM(
            CASE
                WHEN status_pesanan != 'Dibatalkan'
                THEN total_harga
                ELSE 0
            END
        ), 0) AS total_belanja
     FROM pesanan
     WHERE id_pelanggan = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$statistik = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   RIWAYAT PESANAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_pesanan,
        nomor_pesanan,
        tanggal_pesanan,
        total_harga,
        status_pesanan
     FROM pesanan
     WHERE id_pelanggan = ?
     ORDER BY id_pesanan DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pesanan = [];

while ($row = mysqli_fetch_assoc($result)) {
    $pesanan[] = $row;
}

mysqli_stmt_close($stmt);


/* =========================================================
   STATUS
========================================================= */

function classStatusPelanggan($status)
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Detail Pelanggan - Toko ATK & Alat Jahit
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <style>

        .detail-pelanggan-page {
            max-width:1200px;
        }


        .detail-header {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            margin-bottom:25px;
        }

        .detail-header h1 {
            margin:0 0 7px;
            color:#102f4b;
            font-size:28px;
        }

        .detail-header p {
            margin:0;
            color:#7890a4;
            font-size:14px;
        }


        .btn-kembali {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:10px 16px;
            border-radius:8px;
            background:#eef3f7;
            border:1px solid #dce5ec;
            color:#334f66;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
        }

        .btn-kembali:hover {
            background:#e1e9ef;
        }


        .detail-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
            margin-bottom:20px;
        }


        .detail-card {
            background:#fff;
            border:1px solid #e0e7ed;
            border-radius:14px;
            box-shadow:0 4px 15px rgba(16,47,75,.04);
            overflow:hidden;
        }


        .detail-card-header {
            padding:20px 22px;
            border-bottom:1px solid #e6edf2;
        }

        .detail-card-header h2 {
            margin:0;
            color:#102f4b;
            font-size:18px;
        }


        .detail-card-body {
            padding:22px;
        }


        .info-row {
            display:flex;
            justify-content:space-between;
            gap:20px;
            padding:12px 0;
            border-bottom:1px solid #edf1f4;
        }

        .info-row:last-child {
            border-bottom:none;
        }

        .info-label {
            color:#7890a4;
            font-size:13px;
        }

        .info-value {
            color:#334f66;
            font-size:14px;
            font-weight:600;
            text-align:right;
        }


        .stat-number {
            color:#102f4b;
            font-size:22px;
            font-weight:800;
        }


        /* TABLE */

        .pesanan-wrapper {
            overflow-x:auto;
        }

        .pesanan-table {
            width:100%;
            border-collapse:collapse;
            min-width:750px;
        }

        .pesanan-table th {
            background:#f4f7fa;
            color:#506d84;
            text-align:left;
            padding:14px;
            font-size:13px;
        }

        .pesanan-table td {
            padding:14px;
            border-top:1px solid #e5ebf0;
            color:#334f66;
            font-size:13px;
        }

        .nomor-pesanan {
            color:#102f4b;
            font-weight:700;
        }


        /* STATUS */

        .pesanan-status {
            display:inline-flex;
            align-items:center;
            padding:7px 11px;
            border-radius:20px;
            font-size:11px;
            font-weight:700;
            white-space:nowrap;
        }

        .status-menunggu {
            background:#fff4d6;
            color:#996900;
        }

        .status-verifikasi {
            background:#e4efff;
            color:#315fa5;
        }

        .status-proses {
            background:#eee8ff;
            color:#6847a6;
        }

        .status-pickup {
            background:#e2f5f5;
            color:#247579;
        }

        .status-selesai {
            background:#e3f5e9;
            color:#27804a;
        }

        .status-batal {
            background:#fde7e7;
            color:#b23b3b;
        }

        .status-default {
            background:#edf0f2;
            color:#687681;
        }


        .btn-detail {
            display:inline-flex;
            padding:8px 12px;
            border-radius:7px;
            background:#102f4b;
            color:#fff;
            text-decoration:none;
            font-size:11px;
            font-weight:700;
        }


        .kosong {
            text-align:center;
            padding:60px 20px;
            color:#7890a4;
            font-size:14px;
        }


        @media (max-width:850px) {

            .detail-grid {
                grid-template-columns:1fr;
            }

        }


        @media (max-width:600px) {

            .detail-header {
                flex-direction:column;
            }

            .detail-header h1 {
                font-size:24px;
            }

            .info-row {
                flex-direction:column;
                gap:4px;
            }

            .info-value {
                text-align:left;
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


            <div class="detail-pelanggan-page">


                <div class="detail-header">


                    <div>

                        <h1>
                            Detail Pelanggan
                        </h1>

                        <p>
                            Informasi pelanggan dan riwayat pesanannya.
                        </p>

                    </div>


                    <a
                        href="index.php"
                        class="btn-kembali"
                    >
                        ← Kembali
                    </a>


                </div>


                <!-- INFORMASI -->

                <div class="detail-grid">


                    <div class="detail-card">


                        <div class="detail-card-header">

                            <h2>
                                Informasi Pelanggan
                            </h2>

                        </div>


                        <div class="detail-card-body">


                            <div class="info-row">

                                <span class="info-label">
                                    ID Pelanggan
                                </span>

                                <span class="info-value">
                                    #<?= (int)$pelanggan['id_pelanggan']; ?>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Nama
                                </span>

                                <span class="info-value">

                                    <?= htmlspecialchars(
                                        $pelanggan['nama']
                                    ); ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Email
                                </span>

                                <span class="info-value">

                                    <?= htmlspecialchars(
                                        $pelanggan['email']
                                    ); ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Terdaftar
                                </span>

                                <span class="info-value">

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $pelanggan['created_at']
                                        )
                                    ); ?>

                                </span>

                            </div>


                        </div>


                    </div>


                    <!-- STATISTIK -->

                    <div class="detail-card">


                        <div class="detail-card-header">

                            <h2>
                                Statistik Pelanggan
                            </h2>

                        </div>


                        <div class="detail-card-body">


                            <div class="info-row">

                                <span class="info-label">
                                    Jumlah Pesanan
                                </span>

                                <span class="info-value stat-number">

                                    <?= (int)$statistik[
                                        'jumlah_pesanan'
                                    ]; ?>

                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Total Belanja
                                </span>

                                <span class="info-value stat-number">

                                    Rp
                                    <?= number_format(
                                        (float)$statistik[
                                            'total_belanja'
                                        ],
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </span>

                            </div>


                        </div>


                    </div>


                </div>


                <!-- RIWAYAT -->

                <div class="detail-card">


                    <div class="detail-card-header">

                        <h2>
                            Riwayat Pesanan
                        </h2>

                    </div>


                    <div class="pesanan-wrapper">


                    <?php if (count($pesanan) > 0) : ?>


                        <table class="pesanan-table">


                            <thead>

                                <tr>

                                    <th>No</th>

                                    <th>Nomor Pesanan</th>

                                    <th>Tanggal</th>

                                    <th>Total</th>

                                    <th>Status</th>

                                    <th>Aksi</th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php $no = 1; ?>


                            <?php foreach ($pesanan as $item) : ?>


                                <tr>

                                    <td>
                                        <?= $no++; ?>
                                    </td>


                                    <td>

                                        <span class="nomor-pesanan">

                                            <?= htmlspecialchars(
                                                $item['nomor_pesanan']
                                            ); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $item['tanggal_pesanan']
                                            )
                                        ); ?>

                                    </td>


                                    <td>

                                        Rp
                                        <?= number_format(
                                            (float)$item['total_harga'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                pesanan-status
                                                <?= classStatusPelanggan(
                                                    $item['status_pesanan']
                                                ); ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $item['status_pesanan']
                                            ); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="../pesanan/detail.php?id=<?= (int)$item['id_pesanan']; ?>"
                                            class="btn-detail"
                                        >
                                            Detail
                                        </a>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    <?php else : ?>


                        <div class="kosong">

                            👤<br><br>

                            Pelanggan ini belum memiliki pesanan.

                        </div>


                    <?php endif; ?>


                    </div>


                </div>


            </div>


        </section>


        <?php require_once "../includes/footer.php"; ?>


    </main>


</div>


<script src="../assets/js/admin.js"></script>


</body>

</html>