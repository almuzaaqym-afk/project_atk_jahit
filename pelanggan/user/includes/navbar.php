<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base_url = "/project_atk_jahit";

if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: {$base_url}/pelanggan/login.php");
    exit;
}
?>
<link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">
<nav class="navbar user-navbar">
    <div class="nav-container">
        <a href="<?= $base_url ?>/pelanggan/user/index.php" class="brand">
            <img src="<?= $base_url ?>/pelanggan/assets/images/logo.png" alt="Logo">
            <span><strong>Toko ATK & Alat Jahit</strong><small>Pelanggan</small></span>
        </a>

        <div class="nav-menu">
            <a href="<?= $base_url ?>/pelanggan/user/index.php">Beranda</a>
            <a href="<?= $base_url ?>/pelanggan/pages/katalog.php">Katalog</a>
            <a href="<?= $base_url ?>/pelanggan/user/favorit.php">Favorit</a>
            <a href="<?= $base_url ?>/pelanggan/user/keranjang.php">Keranjang</a>
            <a href="<?= $base_url ?>/pelanggan/user/pesanan.php">Pesanan</a>
            <span class="customer-name"><?= htmlspecialchars($_SESSION["nama_pelanggan"] ?? "Pelanggan") ?></span>
            <a class="nav-logout" href="<?= $base_url ?>/pelanggan/logout.php">Logout</a>
        </div>
    </div>
</nav>
