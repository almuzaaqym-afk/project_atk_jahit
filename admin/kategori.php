<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/koneksi.php";

$nama_admin = $_SESSION['nama_admin'] ?? 'Administrator';

$pesan = "";
$error = "";


/* =========================================================
   TAMBAH KATEGORI
========================================================= */

if (isset($_POST['tambah'])) {

    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if ($nama_kategori === '') {

        $error = "Nama kategori wajib diisi.";

    } else {

        $nama_safe = mysqli_real_escape_string(
            $conn,
            $nama_kategori
        );

        $cek = mysqli_query(
            $conn,
            "SELECT id_kategori
             FROM kategori
             WHERE nama_kategori = '$nama_safe'
             LIMIT 1"
        );

        if (mysqli_num_rows($cek) > 0) {

            $error = "Kategori tersebut sudah ada.";

        } else {

            $query = mysqli_query(
                $conn,
                "INSERT INTO kategori (nama_kategori)
                 VALUES ('$nama_safe')"
            );

            if ($query) {

                header("Location: kategori.php?status=berhasil");
                exit;

            } else {

                $error = "Kategori gagal ditambahkan.";
            }
        }
    }
}


/* =========================================================
   HAPUS KATEGORI
========================================================= */

if (isset($_GET['hapus'])) {

    $id_kategori = (int) $_GET['hapus'];

    if ($id_kategori > 0) {

        $hapus = mysqli_query(
            $conn,
            "DELETE FROM kategori
             WHERE id_kategori = $id_kategori"
        );

        if ($hapus) {

            header("Location: kategori.php?status=hapus");
            exit;

        } else {

            $error = "Kategori tidak dapat dihapus.";
        }
    }
}


/* =========================================================
   PESAN
========================================================= */

if (isset($_GET['status'])) {

    if ($_GET['status'] === 'berhasil') {
        $pesan = "Kategori berhasil ditambahkan.";
    }

    if ($_GET['status'] === 'hapus') {
        $pesan = "Kategori berhasil dihapus.";
    }
}


/* =========================================================
   AMBIL DATA KATEGORI
========================================================= */

$query_kategori = mysqli_query(
    $conn,
    "SELECT *
     FROM kategori
     ORDER BY id_kategori DESC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Kategori - Admin</title>

    <link rel="stylesheet"
          href="assets/css/admin.css">

</head>

<body>

<div class="admin-layout">


    <!-- SIDEBAR -->

    <?php require_once "includes/sidebar.php"; ?>


    <!-- MAIN -->

    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <h1>
                Kelola Kategori
            </h1>

            <div class="admin-user">

                <strong>
                    <?= htmlspecialchars($nama_admin); ?>
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="admin-content">


            <!-- TITLE -->

            <div class="page-title">

                <h1>
                    Kategori Produk
                </h1>

                <p>
                    Kelola kategori produk ATK dan alat jahit.
                </p>

            </div>


            <!-- PESAN BERHASIL -->

            <?php if ($pesan !== "") : ?>

                <div style="
                    background:#eaf7ee;
                    border:1px solid #b9dfc5;
                    color:#26733d;
                    padding:14px 18px;
                    border-radius:9px;
                    margin-bottom:20px;
                ">

                    <?= htmlspecialchars($pesan); ?>

                </div>

            <?php endif; ?>


            <!-- PESAN ERROR -->

            <?php if ($error !== "") : ?>

                <div style="
                    background:#fff0f0;
                    border:1px solid #f0c2c2;
                    color:#b52b2b;
                    padding:14px 18px;
                    border-radius:9px;
                    margin-bottom:20px;
                ">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <!-- FORM TAMBAH -->

            <div class="admin-card"
                 style="margin-bottom:25px;">

                <div class="admin-card-header">

                    <h2>
                        Tambah Kategori
                    </h2>

                    <p>
                        Tambahkan kategori baru untuk produk toko.
                    </p>

                </div>


                <div style="padding:25px;">

                    <form method="POST">

                        <div style="
                            display:flex;
                            gap:15px;
                            align-items:end;
                        ">

                            <div class="form-group"
                                 style="
                                    flex:1;
                                    margin-bottom:0;
                                 ">

                                <label>
                                    Nama Kategori
                                </label>

                                <input
                                    type="text"
                                    name="nama_kategori"
                                    class="form-control"
                                    placeholder="Contoh: ATK"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                name="tambah"
                                class="btn btn-primary"
                            >
                                + Tambah Kategori
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- DAFTAR KATEGORI -->

            <div class="admin-card">

                <div class="admin-card-header">

                    <h2>
                        Daftar Kategori
                    </h2>

                    <p>
                        Daftar kategori produk yang tersedia.
                    </p>

                </div>


                <div style="padding:25px;">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th width="80">
                                    ID
                                </th>

                                <th>
                                    Nama Kategori
                                </th>

                                <th width="130">
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
                                $kategori = mysqli_fetch_assoc(
                                    $query_kategori
                                )
                            ) : ?>

                                <tr>

                                    <td>
                                        <?= (int) $kategori['id_kategori']; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $kategori['nama_kategori']
                                        ); ?>
                                    </td>

                                    <td>

                                        <a
                                            href="kategori.php?hapus=<?= (int) $kategori['id_kategori']; ?>"
                                            onclick="return confirm('Yakin ingin menghapus kategori ini?');"
                                            style="
                                                display:inline-block;
                                                background:#fff0f0;
                                                color:#c62828;
                                                padding:8px 12px;
                                                border-radius:7px;
                                                font-size:14px;
                                            "
                                        >
                                            Hapus
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else : ?>

                            <tr>

                                <td
                                    colspan="3"
                                    style="
                                        text-align:center;
                                        color:#7890a4;
                                        padding:30px;
                                    "
                                >
                                    Belum ada kategori.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


        </section>

    </main>

</div>

</body>

</html>