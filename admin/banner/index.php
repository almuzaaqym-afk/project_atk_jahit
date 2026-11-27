<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('banner');


/* =========================================================
   ALERT
========================================================= */

$success = '';
$error = '';

if (isset($_GET['status'])) {

    if ($_GET['status'] === 'tambah') {
        $success = "Banner berhasil ditambahkan.";
    }

    if ($_GET['status'] === 'edit') {
        $success = "Banner berhasil diperbarui.";
    }

    if ($_GET['status'] === 'hapus') {
        $success = "Banner berhasil dihapus.";
    }

    if ($_GET['status'] === 'gagal') {
        $error = "Terjadi kesalahan.";
    }
}


/* =========================================================
   DATA BANNER
========================================================= */

$query_banner = mysqli_query(
    $conn,
    "SELECT
        id_banner,
        judul,
        deskripsi,
        gambar,
        teks_tombol,
        link_tombol,
        urutan,
        status,
        created_at
     FROM banner
     ORDER BY urutan ASC, id_banner DESC"
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

    <title>
        Banner - Toko ATK & Alat Jahit
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <style>

        .banner-page {
            max-width:1500px;
        }


        /* ALERT */

        .banner-alert {
            padding:14px 18px;
            border-radius:10px;
            margin-bottom:20px;
            font-size:14px;
            font-weight:600;
        }

        .banner-alert.success {
            background:#e8f7ee;
            color:#237a45;
            border:1px solid #c9ead6;
        }

        .banner-alert.error {
            background:#fce8e8;
            color:#a33;
            border:1px solid #f2caca;
        }


        /* HEADER */

        .banner-header {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            margin-bottom:25px;
        }


        .banner-header-text h1 {
            margin:0 0 7px;
            color:#102f4b;
            font-size:28px;
        }


        .banner-header-text p {
            margin:0;
            color:#7890a4;
            font-size:14px;
        }


        .btn-tambah {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:11px 17px;
            border-radius:8px;
            background:#102f4b;
            color:#fff;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
        }


        .btn-tambah:hover {
            background:#1c496b;
        }


        /* CARD */

        .banner-card {
            background:#fff;
            border:1px solid #e0e7ed;
            border-radius:14px;
            box-shadow:0 4px 15px rgba(16,47,75,.04);
            overflow:hidden;
        }


        .banner-card-header {
            padding:22px 24px;
            border-bottom:1px solid #e6edf2;
        }


        .banner-card-header h2 {
            margin:0 0 6px;
            color:#102f4b;
            font-size:20px;
        }


        .banner-card-header p {
            margin:0;
            color:#7890a4;
            font-size:14px;
        }


        /* TABLE */

        .banner-table-wrapper {
            width:100%;
            overflow-x:auto;
        }


        .banner-table {
            width:100%;
            border-collapse:collapse;
            min-width:1100px;
        }


        .banner-table th {
            background:#f4f7fa;
            color:#506d84;
            text-align:left;
            padding:14px;
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
        }


        .banner-table td {
            padding:15px 14px;
            border-top:1px solid #e5ebf0;
            color:#334f66;
            font-size:14px;
            vertical-align:middle;
        }


        .banner-table tbody tr:hover {
            background:#fafcfd;
        }


        /* GAMBAR */

        .banner-preview {
            width:180px;
            height:85px;
            object-fit:cover;
            border-radius:9px;
            border:1px solid #dfe7ed;
            background:#f4f7fa;
            display:block;
        }


        .banner-no-image {
            width:180px;
            height:85px;
            border-radius:9px;
            background:#f4f7fa;
            border:1px solid #dfe7ed;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#9aafbf;
            font-size:12px;
        }


        /* DATA */

        .banner-title {
            color:#102f4b;
            font-weight:700;
            display:block;
            margin-bottom:5px;
        }


        .banner-description {
            color:#7890a4;
            font-size:12px;
            line-height:1.5;
            max-width:280px;
        }


        .banner-button-text {
            color:#506d84;
            font-size:12px;
        }


        .banner-order {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:32px;
            height:30px;
            padding:0 8px;
            border-radius:7px;
            background:#eef3f7;
            color:#334f66;
            font-weight:700;
        }


        /* STATUS */

        .banner-status {
            display:inline-flex;
            align-items:center;
            padding:7px 11px;
            border-radius:20px;
            font-size:12px;
            font-weight:700;
        }


        .status-aktif {
            background:#e3f5e9;
            color:#27804a;
        }


        .status-nonaktif {
            background:#fde7e7;
            color:#b23b3b;
        }


        /* AKSI */

        .banner-actions {
            display:flex;
            align-items:center;
            gap:7px;
            flex-wrap:wrap;
        }


        .btn-edit {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:9px 13px;
            border-radius:8px;
            background:#eef3f7;
            border:1px solid #dce5ec;
            color:#334f66;
            text-decoration:none;
            font-size:12px;
            font-weight:700;
        }


        .btn-edit:hover {
            background:#e1e9ef;
        }


        .btn-hapus {
            border:none;
            padding:9px 13px;
            border-radius:8px;
            background:#fde7e7;
            color:#b23b3b;
            font-family:inherit;
            font-size:12px;
            font-weight:700;
            cursor:pointer;
        }


        .btn-hapus:hover {
            background:#f8d4d4;
        }


        /* EMPTY */

        .banner-kosong {
            text-align:center;
            padding:70px 20px;
        }


        .banner-kosong-icon {
            font-size:42px;
            margin-bottom:12px;
        }


        .banner-kosong h3 {
            color:#334f66;
            font-size:17px;
            margin:0 0 7px;
        }


        .banner-kosong p {
            color:#7890a4;
            font-size:14px;
            margin:0;
        }


        @media (max-width:700px) {

            .banner-header {
                flex-direction:column;
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


            <div class="banner-page">


                <div class="banner-header">


                    <div class="banner-header-text">

                        <h1>
                            Banner Homepage
                        </h1>

                        <p>
                            Kelola banner berjalan yang tampil di halaman utama pelanggan.
                        </p>

                    </div>


                    <a
                        href="tambah.php"
                        class="btn-tambah"
                    >
                        + Tambah Banner
                    </a>


                </div>


                <?php if ($success !== '') : ?>

                    <div class="banner-alert success">

                        ✓ <?= htmlspecialchars($success); ?>

                    </div>

                <?php endif; ?>


                <?php if ($error !== '') : ?>

                    <div class="banner-alert error">

                        ⚠ <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <div class="banner-card">


                    <div class="banner-card-header">

                        <h2>
                            Daftar Banner
                        </h2>

                        <p>
                            Banner dengan status Aktif akan ditampilkan pada homepage pelanggan.
                        </p>

                    </div>


                    <div class="banner-table-wrapper">


                        <table class="banner-table">


                            <thead>

                                <tr>

                                    <th>No</th>

                                    <th>Banner</th>

                                    <th>Informasi</th>

                                    <th>Tombol</th>

                                    <th>Urutan</th>

                                    <th>Status</th>

                                    <th>Aksi</th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (
                                $query_banner &&
                                mysqli_num_rows($query_banner) > 0
                            ) : ?>


                                <?php $no = 1; ?>


                                <?php while (
                                    $banner =
                                    mysqli_fetch_assoc($query_banner)
                                ) : ?>


                                    <tr>


                                        <td>
                                            <?= $no++; ?>
                                        </td>


                                        <td>


                                            <?php if (
                                                !empty(
                                                    $banner['gambar']
                                                )
                                            ) : ?>

                                                <img
                                                    src="../assets/images/banners/<?= htmlspecialchars(
                                                        $banner['gambar']
                                                    ); ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $banner['judul']
                                                    ); ?>"
                                                    class="banner-preview"
                                                >

                                            <?php else : ?>

                                                <div class="banner-no-image">
                                                    Tidak ada gambar
                                                </div>

                                            <?php endif; ?>


                                        </td>


                                        <td>

                                            <span class="banner-title">

                                                <?= htmlspecialchars(
                                                    $banner['judul']
                                                ); ?>

                                            </span>


                                            <div class="banner-description">

                                                <?= htmlspecialchars(
                                                    $banner['deskripsi']
                                                    ?? ''
                                                ); ?>

                                            </div>

                                        </td>


                                        <td>

                                            <?php if (
                                                !empty(
                                                    $banner['teks_tombol']
                                                )
                                            ) : ?>

                                                <span class="banner-button-text">

                                                    <?= htmlspecialchars(
                                                        $banner['teks_tombol']
                                                    ); ?>

                                                </span>

                                            <?php else : ?>

                                                <span class="banner-button-text">
                                                    -
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span class="banner-order">

                                                <?= (int)$banner['urutan']; ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php if (
                                                $banner['status'] === 'Aktif'
                                            ) : ?>

                                                <span class="banner-status status-aktif">
                                                    Aktif
                                                </span>

                                            <?php else : ?>

                                                <span class="banner-status status-nonaktif">
                                                    Tidak Aktif
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>


                                            <div class="banner-actions">


                                                <a
                                                    href="edit.php?id=<?= (int)$banner['id_banner']; ?>"
                                                    class="btn-edit"
                                                >
                                                    Edit
                                                </a>


                                                <form
                                                    method="POST"
                                                    action="hapus.php"
                                                    onsubmit="return confirm('Yakin ingin menghapus banner ini?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="id_banner"
                                                        value="<?= (int)$banner['id_banner']; ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        name="hapus_banner"
                                                        class="btn-hapus"
                                                    >
                                                        Hapus
                                                    </button>

                                                </form>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else : ?>


                                <tr>

                                    <td colspan="7">


                                        <div class="banner-kosong">

                                            <div class="banner-kosong-icon">
                                                🖼️
                                            </div>

                                            <h3>
                                                Belum Ada Banner
                                            </h3>

                                            <p>
                                                Tambahkan banner pertama untuk homepage pelanggan.
                                            </p>

                                        </div>


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


<script src="../assets/js/admin.js"></script>

</body>

</html>