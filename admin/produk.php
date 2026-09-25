<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/koneksi.php";

$nama_admin = $_SESSION['nama_admin'] ?? 'Administrator';

$currentPage = basename($_SERVER['PHP_SELF']);


/* =========================
   AMBIL DATA PRODUK
========================= */

$query_produk = mysqli_query(
    $conn,
    "SELECT 
        produk.id_produk,
        produk.nama_produk,
        produk.deskripsi,
        produk.harga,
        produk.stok,
        produk.lokasi_rak,
        produk.gambar,
        kategori.nama_kategori
     FROM produk
     LEFT JOIN kategori
        ON produk.id_kategori = kategori.id_kategori
     ORDER BY produk.id_produk DESC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Produk - Admin</title>

    <link rel="stylesheet"
          href="assets/css/admin.css">

</head>

<body>

<div class="admin-layout">


    <?php require_once "includes/sidebar.php"; ?>


    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <h1>Produk</h1>

            <div class="admin-user">

                <strong>
                    <?= htmlspecialchars($nama_admin); ?>
                </strong>

                <span>Administrator</span>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="admin-content">


            <div class="page-title">

                <h1>Kelola Produk</h1>

                <p>
                    Kelola data produk ATK dan alat jahit.
                </p>

            </div>


            <!-- DAFTAR PRODUK -->

            <div class="content-card">


                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:25px;
                    gap:20px;
                ">

                    <div>

                        <h2 style="margin:0 0 6px 0;">
                            Daftar Produk
                        </h2>

                        <p style="
                            margin:0;
                            color:#7890a4;
                        ">
                            Produk yang tersedia di toko.
                        </p>

                    </div>


                    <a
                        href="produk_tambah.php"
                        style="
                            background:#123451;
                            color:white;
                            padding:13px 22px;
                            border-radius:8px;
                            text-decoration:none;
                            font-weight:600;
                            white-space:nowrap;
                        "
                    >
                        + Tambah Produk
                    </a>

                </div>


                <?php if (mysqli_num_rows($query_produk) > 0) : ?>


                    <div style="
                        overflow-x:auto;
                    ">

                        <table style="
                            width:100%;
                            border-collapse:collapse;
                            min-width:950px;
                        ">

                            <thead>

                                <tr style="
                                    background:#f4f7fa;
                                ">

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        ID
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Foto
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Produk
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Kategori
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Harga
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Stok
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Lokasi / Rak
                                    </th>

                                    <th style="
                                        padding:15px;
                                        text-align:left;
                                        color:#456;
                                    ">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php while ($produk = mysqli_fetch_assoc($query_produk)) : ?>

                                    <tr style="
                                        border-bottom:1px solid #e5ebf0;
                                    ">


                                        <!-- ID -->

                                        <td style="
                                            padding:15px;
                                        ">

                                            <?= $produk['id_produk']; ?>

                                        </td>


                                        <!-- FOTO -->

                                        <td style="padding:15px;">

    <?php if (!empty($produk['gambar'])): ?>

        <img
            src="assets/images/products/<?= htmlspecialchars($produk['gambar']); ?>"
            alt="<?= htmlspecialchars($produk['nama_produk']); ?>"
            style="
                width:90px;
                height:70px;
                object-fit:cover;
                border-radius:8px;
                border:1px solid #e5ebf0;
            "
        >

    <?php else: ?>

        <div style="
            width:90px;
            height:70px;
            display:flex;
            align-items:center;
            justify-content:center;
            text-align:center;
            background:#f4f7fa;
            border:1px solid #e5ebf0;
            border-radius:8px;
            color:#7890a4;
            font-size:13px;
        ">
            Belum ada foto
        </div>

    <?php endif; ?>

</td>


                                        <!-- PRODUK -->

                                        <td style="
                                            padding:15px;
                                            font-weight:600;
                                            color:#123451;
                                        ">

                                            <?= htmlspecialchars(
                                                $produk['nama_produk']
                                            ); ?>

                                        </td>


                                        <!-- KATEGORI -->

                                        <td style="
                                            padding:15px;
                                        ">

                                            <?= htmlspecialchars(
                                                $produk['nama_kategori'] ?? '-'
                                            ); ?>

                                        </td>


                                        <!-- HARGA -->

                                        <td style="
                                            padding:15px;
                                            white-space:nowrap;
                                        ">

                                            Rp <?= number_format(
                                                $produk['harga'],
                                                0,
                                                ',',
                                                '.'
                                            ); ?>

                                        </td>


                                        <!-- STOK -->

                                        <td style="
                                            padding:15px;
                                        ">

                                            <?= $produk['stok']; ?>

                                        </td>


                                        <!-- LOKASI -->

                                        <td style="
                                            padding:15px;
                                        ">

                                            <?= htmlspecialchars(
                                                $produk['lokasi_rak'] ?: '-'
                                            ); ?>

                                        </td>


                                        <!-- AKSI -->

                                        <td style="
                                            padding:15px;
                                            white-space:nowrap;
                                        ">

                                            <div style="
                                                display:flex;
                                                gap:7px;
                                                flex-wrap:wrap;
                                            ">


                            


                                                <!-- EDIT -->

                                                <a
                                                    href="produk_edit.php?id=<?= $produk['id_produk']; ?>"
                                                    style="
                                                        background:#123451;
                                                        color:white;
                                                        padding:8px 11px;
                                                        border-radius:6px;
                                                        text-decoration:none;
                                                        font-size:13px;
                                                        font-weight:600;
                                                    "
                                                >
                                                    Edit
                                                </a>


                                                <!-- HAPUS -->

                                                <a
                                                    href="produk_hapus.php?id=<?= $produk['id_produk']; ?>"
                                                    onclick="return confirm('Yakin ingin menghapus produk ini?');"
                                                    style="
                                                        background:#f8e8e8;
                                                        color:#a33;
                                                        padding:8px 11px;
                                                        border-radius:6px;
                                                        text-decoration:none;
                                                        font-size:13px;
                                                        font-weight:600;
                                                    "
                                                >
                                                    Hapus
                                                </a>


                                            </div>

                                        </td>


                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>


                <?php else : ?>


                    <div style="
                        padding:45px 20px;
                        text-align:center;
                        border:1px dashed #d7e0e8;
                        border-radius:12px;
                        color:#7890a4;
                    ">

                        Belum ada data produk.

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>

</div>

</body>

</html>