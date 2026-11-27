<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";

cekAkses('kategori');

$pesan = "";
$error = "";


/* =========================================================
   PESAN STATUS
========================================================= */

if (isset($_GET['status'])) {

    switch ($_GET['status']) {

        case 'berhasil':
            $pesan = "Kategori berhasil ditambahkan.";
            break;

        case 'edit':
            $pesan = "Kategori berhasil diperbarui.";
            break;

        case 'hapus':
            $pesan = "Kategori berhasil dihapus.";
            break;

        case 'digunakan':
            $error = "Kategori tidak dapat dihapus karena masih digunakan oleh produk.";
            break;

        case 'tidak_ditemukan':
            $error = "Kategori tidak ditemukan.";
            break;

        case 'gagal':
            $error = "Kategori gagal diproses.";
            break;
    }
}


/* =========================================================
   AMBIL DATA KATEGORI
========================================================= */

$query_kategori = mysqli_query(
    $conn,
    "SELECT
        k.id_kategori,
        k.nama_kategori,
        COUNT(p.id_produk) AS jumlah_produk
     FROM kategori k
     LEFT JOIN produk p
        ON p.id_kategori = k.id_kategori
     GROUP BY
        k.id_kategori,
        k.nama_kategori
     ORDER BY k.id_kategori DESC"
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

    <title>Kategori - Toko ATK & Alat Jahit</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>

        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .category-page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;

        }


        .category-page-header-left h1 {

            color: #102f4b;

            font-size: 28px;

            font-weight: 700;

            margin: 0 0 7px;

        }


        .category-page-header-left p {

            color: #7890a4;

            font-size: 14px;

            margin: 0;

        }


        /* =====================================================
           BUTTON TAMBAH
        ===================================================== */

        .btn-add-category {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            height: 42px;

            padding: 0 17px;

            border-radius: 8px;

            background: #102f4b;

            color: #ffffff;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

            white-space: nowrap;

            transition:
                background 0.2s ease,
                transform 0.2s ease;

        }


        .btn-add-category:hover {

            background: #1c496b;

            transform: translateY(-1px);

        }


        /* =====================================================
           ALERT
        ===================================================== */

        .category-alert {

            padding: 13px 16px;

            border-radius: 9px;

            margin-bottom: 22px;

            font-size: 13px;

        }


        .category-alert.success {

            background: #eaf7ee;

            border: 1px solid #b9dfc5;

            color: #26733d;

        }


        .category-alert.error {

            background: #fff0f0;

            border: 1px solid #f0c2c2;

            color: #b52b2b;

        }


        /* =====================================================
           CARD
        ===================================================== */

        .category-card {

            background: #ffffff;

            border: 1px solid #e0e7ed;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(16,47,75,0.04);

            overflow: hidden;

        }


        .category-card-header {

            padding: 20px 24px;

            border-bottom: 1px solid #e6edf2;

        }


        .category-card-header h2 {

            margin: 0 0 5px;

            color: #102f4b;

            font-size: 18px;

            font-weight: 600;

        }


        .category-card-header p {

            margin: 0;

            color: #7890a4;

            font-size: 13px;

        }


        /* =====================================================
           TABLE WRAPPER
        ===================================================== */

        .category-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .category-table {

            width: 100%;

            border-collapse: collapse;

        }


        .category-table thead th {

            background: #f7f9fb;

            color: #7890a4;

            text-align: left;

            padding: 13px 20px;

            border-bottom: 1px solid #e6edf2;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .5px;

            white-space: nowrap;

        }


        .category-table tbody td {

            padding: 16px 20px;

            border-bottom: 1px solid #edf1f4;

            color: #334f66;

            font-size: 13px;

            vertical-align: middle;

        }


        .category-table tbody tr:last-child td {

            border-bottom: none;

        }


        .category-table tbody tr:hover {

            background: #fafcfd;

        }


        .category-id {

            color: #7890a4;

            font-weight: 600;

        }


        .category-name {

            color: #102f4b;

            font-weight: 600;

        }


        /* =====================================================
           JUMLAH PRODUK
        ===================================================== */

        .category-count {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 72px;

            padding: 5px 10px;

            border-radius: 20px;

            background: #edf3f7;

            color: #245b83;

            font-size: 12px;

            font-weight: 600;

        }


        /* =====================================================
           AKSI
        ===================================================== */

        .category-actions {

            display: flex;

            align-items: center;

            gap: 7px;

            white-space: nowrap;

        }


        .btn-edit-category {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            height: 32px;

            padding: 0 11px;

            border-radius: 7px;

            background: #eef4f8;

            border: 1px solid #d6e2ea;

            color: #245b83;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            transition: 0.2s;

        }


        .btn-edit-category:hover {

            background: #e1ebf1;

        }


        .btn-delete-category {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            height: 32px;

            padding: 0 11px;

            border-radius: 7px;

            background: #fff0f0;

            border: 1px solid #f3d0d0;

            color: #c62828;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            transition: 0.2s;

        }


        .btn-delete-category:hover {

            background: #ffe1e1;

        }


        .category-used {

            color: #9aa9b7;

            font-size: 12px;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .category-empty {

            padding: 55px 20px !important;

            text-align: center;

            color: #8a9dac !important;

        }


        .category-empty-icon {

            font-size: 30px;

            margin-bottom: 10px;

            color: #b5c4cf;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 700px) {

            .category-page-header {

                align-items: stretch;

                flex-direction: column;

            }


            .btn-add-category {

                width: 100%;

            }


            .category-card-header {

                padding: 18px;

            }


            .category-table {

                min-width: 650px;

            }

        }

    </style>

</head>


<body>

<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ===================================================== -->

    <?php include "../includes/sidebar.php"; ?>


    <!-- =====================================================
         MAIN
    ===================================================== -->

    <div class="admin-main">


        <!-- =================================================
             NAVBAR
        ================================================= -->

        <?php include "../includes/navbar.php"; ?>


        <!-- =================================================
             CONTENT
        ================================================= -->

        <main class="admin-content">


            <!-- =================================================
                 HEADER
            ================================================= -->

            <div class="category-page-header">

                <div class="category-page-header-left">

                    <h1>
                        Kategori Produk
                    </h1>

                    <p>
                        Kelola kategori produk ATK dan alat jahit.
                    </p>

                </div>


                <a
                    href="tambah.php"
                    class="btn-add-category"
                >

                    <span>+</span>

                    Tambah Kategori

                </a>

            </div>


            <!-- =================================================
                 SUCCESS
            ================================================= -->

            <?php if ($pesan !== "") : ?>

                <div class="category-alert success">

                    <?= htmlspecialchars($pesan); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR
            ================================================= -->

            <?php if ($error !== "") : ?>

                <div class="category-alert error">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 CARD KATEGORI
            ================================================= -->

            <div class="category-card">


                <div class="category-card-header">

                    <h2>
                        Daftar Kategori
                    </h2>

                    <p>
                        Daftar kategori produk yang tersedia di toko.
                    </p>

                </div>


                <div class="category-table-wrapper">

                    <table class="category-table">

                        <thead>

                            <tr>

                                <th width="80">
                                    ID
                                </th>

                                <th>
                                    Nama Kategori
                                </th>

                                <th width="180">
                                    Jumlah Produk
                                </th>

                                <th width="200">
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (
                            $query_kategori &&
                            mysqli_num_rows($query_kategori) > 0
                        ) : ?>


                            <?php while (
                                $kategori =
                                mysqli_fetch_assoc($query_kategori)
                            ) : ?>

                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <span class="category-id">

                                            #<?= (int)$kategori['id_kategori']; ?>

                                        </span>

                                    </td>


                                    <!-- NAMA -->

                                    <td>

                                        <span class="category-name">

                                            <?= htmlspecialchars(
                                                $kategori['nama_kategori']
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- JUMLAH PRODUK -->

                                    <td>

                                        <span class="category-count">

                                            <?= (int)$kategori['jumlah_produk']; ?>

                                            Produk

                                        </span>

                                    </td>


                                    <!-- AKSI -->

                                    <td>

                                        <div class="category-actions">


                                            <a
                                                href="edit.php?id=<?= (int)$kategori['id_kategori']; ?>"
                                                class="btn-edit-category"
                                            >
                                                Edit
                                            </a>


                                            <?php if (
                                                (int)$kategori['jumlah_produk'] === 0
                                            ) : ?>

                                                <a
                                                    href="hapus.php?id=<?= (int)$kategori['id_kategori']; ?>"
                                                    class="btn-delete-category"
                                                    onclick="return confirm('Yakin ingin menghapus kategori ini?');"
                                                >
                                                    Hapus
                                                </a>

                                            <?php else : ?>

                                                <span class="category-used">
                                                    Digunakan
                                                </span>

                                            <?php endif; ?>


                                        </div>

                                    </td>


                                </tr>

                            <?php endwhile; ?>


                        <?php else : ?>


                            <tr>

                                <td
                                    colspan="4"
                                    class="category-empty"
                                >

                                    <div class="category-empty-icon">
                                        ▦
                                    </div>

                                    Belum ada kategori produk.

                                </td>

                            </tr>


                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


        </main>


        <!-- =================================================
             FOOTER
        ================================================= -->

        <?php include "../includes/footer.php"; ?>


    </div>

</div>


<script src="../assets/js/admin.js"></script>

</body>

</html>