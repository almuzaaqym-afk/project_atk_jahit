<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/koneksi.php";

$nama_admin = $_SESSION['nama_admin'] ?? 'Administrator';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - Toko ATK & Alat Jahit</title>

    <link rel="stylesheet"
          href="assets/css/admin.css">

</head>

<body>

<div class="admin-layout">

    <?php require_once "includes/sidebar.php"; ?>


    <main class="admin-main">

        <!-- TOPBAR -->
        <header class="admin-topbar">

            <h1>Dashboard</h1>

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

                <h1>
                    Selamat Datang, Administrator
                </h1>

                <p>
                    Kelola aktivitas Toko ATK & Alat Jahit melalui sistem ERP.
                </p>

            </div>


            <!-- STATISTIK -->
            <div class="dashboard-stats">

                <div class="stat-card">

                    <span class="stat-label">
                        Total Produk
                    </span>

                    <span class="stat-number">
                        0
                    </span>

                    <span class="stat-description">
                        Produk dalam katalog
                    </span>

                </div>


                <div class="stat-card">

                    <span class="stat-label">
                        Total Stok
                    </span>

                    <span class="stat-number">
                        0
                    </span>

                    <span class="stat-description">
                        Jumlah stok produk
                    </span>

                </div>


                <div class="stat-card">

                    <span class="stat-label">
                        Kategori
                    </span>

                    <span class="stat-number">
                        2
                    </span>

                    <span class="stat-description">
                        Kategori produk
                    </span>

                </div>


                <div class="stat-card">

                    <span class="stat-label">
                        Pelanggan
                    </span>

                    <span class="stat-number">
                        1
                    </span>

                    <span class="stat-description">
                        Data pelanggan
                    </span>

                </div>

            </div>


            <!-- DASHBOARD GRID -->
            <div class="dashboard-grid">


                <!-- MODUL ERP -->
                <div class="admin-card">

                    <div class="admin-card-header">

                        <h2>
                            Modul ERP
                        </h2>

                        <p>
                            Modul yang digunakan dalam pengelolaan toko.
                        </p>

                    </div>


                    <div class="module-grid">


                        <a href="produk.php"
                           class="module-item">

                            <h3>
                                Produk
                            </h3>

                            <p>
                                Mengelola data dan katalog produk.
                            </p>

                        </a>


                        <a href="stok.php"
                           class="module-item">

                            <h3>
                                Stok
                            </h3>

                            <p>
                                Mengelola jumlah dan pergerakan stok.
                            </p>

                        </a>


                        <a href="lokasi.php"
                           class="module-item">

                            <h3>
                                Lokasi / Rak
                            </h3>

                            <p>
                                Menentukan lokasi produk di toko.
                            </p>

                        </a>


                        <a href="supplier.php"
                           class="module-item">

                            <h3>
                                Supplier
                            </h3>

                            <p>
                                Mengelola data pemasok barang.
                            </p>

                        </a>


                        <a href="pembelian.php"
                           class="module-item">

                            <h3>
                                Pembelian
                            </h3>

                            <p>
                                Mengelola pengadaan barang.
                            </p>

                        </a>


                        <a href="pesanan.php"
                           class="module-item">

                            <h3>
                                Pesanan
                            </h3>

                            <p>
                                Mengelola pesanan pelanggan.
                            </p>

                        </a>


                    </div>

                </div>


                <!-- INFORMASI SISTEM -->
                <div class="admin-card">

                    <div class="admin-card-header">

                        <h2>
                            Informasi Sistem
                        </h2>

                        <p>
                            Ringkasan sistem toko.
                        </p>

                    </div>


                    <div class="system-info">

                        <div class="info-row">

                            <strong>
                                Nama Sistem
                            </strong>

                            <span>
                                Toko ATK & Alat Jahit
                            </span>

                        </div>


                        <div class="info-row">

                            <strong>
                                Platform
                            </strong>

                            <span>
                                PHP Native + MySQL
                            </span>

                        </div>


                        <div class="info-row">

                            <strong>
                                Jenis Sistem
                            </strong>

                            <span>
                                Web App + ERP
                            </span>

                        </div>


                        <div class="info-row">

                            <strong>
                                Pengguna
                            </strong>

                            <span>
                                Administrator
                            </span>

                        </div>

                    </div>

                </div>


            </div>

        </section>

    </main>

</div>

</body>

</html>