<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";

cekLoginAdmin();
cekAkses('cs');

$total_menunggu = 0;
$total_dilayani = 0;
$total_selesai = 0;


/* =========================================================
   STATISTIK AWAL
========================================================= */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cs_chat
     WHERE status_chat = 'Menunggu'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_menunggu =
        (int) ($row['total'] ?? 0);

}


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cs_chat
     WHERE status_chat = 'Dilayani'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_dilayani =
        (int) ($row['total'] ?? 0);

}


$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cs_chat
     WHERE status_chat = 'Selesai'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_selesai =
        (int) ($row['total'] ?? 0);

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
        Customer Service - Toko ATK & Alat Jahit
    </title>

    <link
        rel="stylesheet"
        href="/project_atk_jahit/admin/assets/css/admin.css"
    >

    <link
        rel="stylesheet"
        href="/project_atk_jahit/admin/assets/css/customer-service.css"
    >

</head>


<body>

<div class="admin-layout">


    <!-- SIDEBAR -->

    <?php
    require_once __DIR__ . "/../includes/sidebar.php";
    ?>


    <!-- MAIN -->

    <main class="admin-main">


        <!-- NAVBAR -->

        <?php
        require_once __DIR__ . "/../includes/navbar.php";
        ?>


        <!-- CONTENT -->

        <section class="admin-content">


            <div
                class="cs-admin-page"
                id="csPage"
                data-endpoint="notifikasi.php"
            >


                <!-- HEADER -->

                <div class="cs-admin-header">

                    <div>

                        <h1>
                            💬 Customer Service
                        </h1>

                        <p>
                            Kelola antrian dan percakapan pelanggan.
                        </p>

                    </div>

                </div>


                <!-- STATISTIK -->

                <div class="cs-stats">


                    <!-- MENUNGGU -->

                    <div class="cs-stat-card cs-stat-waiting">

                        <div class="cs-stat-top">

                            <span class="cs-stat-title">
                                Menunggu CS
                            </span>

                            <span class="cs-stat-icon">
                                ⏳
                            </span>

                        </div>

                        <div
                            class="cs-stat-number"
                            id="csWaitingNumber"
                        >
                            <?= $total_menunggu; ?>
                        </div>

                    </div>


                    <!-- DILAYANI -->

                    <div class="cs-stat-card cs-stat-serving">

                        <div class="cs-stat-top">

                            <span class="cs-stat-title">
                                Sedang Dilayani
                            </span>

                            <span class="cs-stat-icon">
                                🟢
                            </span>

                        </div>

                        <div
                            class="cs-stat-number"
                            id="csServingNumber"
                        >
                            <?= $total_dilayani; ?>
                        </div>

                    </div>


                    <!-- SELESAI -->

                    <div class="cs-stat-card cs-stat-done">

                        <div class="cs-stat-top">

                            <span class="cs-stat-title">
                                Chat Selesai
                            </span>

                            <span class="cs-stat-icon">
                                ✓
                            </span>

                        </div>

                        <div
                            class="cs-stat-number"
                            id="csDoneNumber"
                        >
                            <?= $total_selesai; ?>
                        </div>

                    </div>


                </div>


                <!-- DAFTAR CHAT -->

                <div class="cs-admin-card">


                    <div class="cs-admin-card-header">

                        <h2>
                            Daftar Customer Service
                        </h2>

                        <span>
                            Antrian terbaru berada di atas
                        </span>

                    </div>


                    <div class="cs-table-wrap">

                        <table class="cs-table">

                            <thead>

                                <tr>

                                    <th>
                                        No
                                    </th>

                                    <th>
                                        Pelanggan
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Pesan Terakhir
                                    </th>

                                    <th>
                                        Waktu
                                    </th>

                                    <th>
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="csTableBody">

                                <tr>

                                    <td
                                        colspan="6"
                                    >

                                        <div class="cs-empty">

                                            <div class="cs-empty-icon">
                                                💬
                                            </div>

                                            <div class="cs-empty-title">
                                                Memuat percakapan...
                                            </div>

                                            <div class="cs-empty-text">
                                                Data Customer Service sedang dimuat.
                                            </div>

                                        </div>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


            </div>

        </section>

    </main>

</div>


<!-- ADMIN JS -->

<script src="/project_atk_jahit/admin/assets/js/admin.js"></script>


<!-- CUSTOMER SERVICE JS -->

<script src="/project_atk_jahit/admin/assets/js/customer-service.js"></script>


</body>

</html>