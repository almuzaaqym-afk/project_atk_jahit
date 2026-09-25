<?php
$base_url = "/project_atk_jahit";
?>
<link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">
<nav class="navbar guest-navbar">
    <div class="nav-container">
        <a href="<?= $base_url ?>/pelanggan/index.php" class="brand">
            <img src="<?= $base_url ?>/pelanggan/assets/images/logo.png" alt="Logo">
            <span><strong>Toko ATK & Alat Jahit</strong><small>Perlengkapan ATK & Alat Jahit</small></span>
        </a>

        <div class="nav-menu">
            <a href="<?= $base_url ?>/pelanggan/index.php">Beranda</a>
            <a href="<?= $base_url ?>/pelanggan/pages/katalog.php">Katalog</a>
            <a href="<?= $base_url ?>/pelanggan/pages/tentang.php">Tentang Kami</a>
            <a href="<?= $base_url ?>/pelanggan/pages/lokasi.php">Lokasi</a>
            <a class="nav-login" href="<?= $base_url ?>/pelanggan/login.php">Login</a>
            <a class="nav-register" href="<?= $base_url ?>/pelanggan/register.php">Daftar</a>
        </div>
    </div>
</nav>
