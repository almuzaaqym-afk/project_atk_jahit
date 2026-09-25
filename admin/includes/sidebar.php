<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar">

    <div class="sidebar-header">

        <h2>
            Toko ATK & Alat Jahit
        </h2>

        <p>
            ERP Administrator
        </p>

    </div>


    <nav class="sidebar-menu">


        <div class="menu-title">
            UTAMA
        </div>

        <a
            href="dashboard.php"
            class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"
        >
            Dashboard
        </a>


        <div class="menu-title">
            MASTER DATA
        </div>

        <a
            href="kategori.php"
            class="<?= $currentPage == 'kategori.php' ? 'active' : '' ?>"
        >
            Kategori
        </a>

        <a
            href="produk.php"
            class="<?= $currentPage == 'produk.php' ? 'active' : '' ?>"
        >
            Produk
        </a>


        <div class="menu-title">
            PERSEDIAAN
        </div>

        <a
            href="stok.php"
            class="<?= $currentPage == 'stok.php' ? 'active' : '' ?>"
        >
            Stok
        </a>

        <a
            href="lokasi.php"
            class="<?= $currentPage == 'lokasi.php' ? 'active' : '' ?>"
        >
            Lokasi / Rak
        </a>


        <div class="menu-title">
            PENGADAAN
        </div>

        <a
            href="supplier.php"
            class="<?= $currentPage == 'supplier.php' ? 'active' : '' ?>"
        >
            Supplier
        </a>

        <a
            href="pembelian.php"
            class="<?= $currentPage == 'pembelian.php' ? 'active' : '' ?>"
        >
            Pembelian
        </a>


        <div class="menu-title">
            PENJUALAN
        </div>

        <a
            href="pesanan.php"
            class="<?= $currentPage == 'pesanan.php' ? 'active' : '' ?>"
        >
            Pesanan
        </a>


        <div class="menu-title">
            SISTEM
        </div>

        <a
            href="admin.php"
            class="<?= $currentPage == 'admin.php' ? 'active' : '' ?>"
        >
            Admin & Hak Akses
        </a>

        <a
            href="logout.php"
            class="logout-link"
        >
            Logout
        </a>

    </nav>

</aside>