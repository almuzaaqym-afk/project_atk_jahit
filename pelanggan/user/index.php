<?php
session_start();

if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: ../login.php");
    exit;
}

require_once "includes/navbar.php";
?>
<main>
    <section class="section user-welcome">
        <span class="eyebrow">PELANGGAN</span>
        <h1>Halo, <?= htmlspecialchars($_SESSION["nama_pelanggan"]) ?> 👋</h1>
        <p>Selamat datang kembali di Toko ATK & Alat Jahit.</p>

        <div class="cards">
            <a class="card card-link" href="../pages/katalog.php">
                <h3>Katalog Produk</h3>
                <p>Lihat produk yang tersedia.</p>
            </a>
            <a class="card card-link" href="favorit.php">
                <h3>Favorit</h3>
                <p>Produk yang kamu simpan.</p>
            </a>
            <a class="card card-link" href="keranjang.php">
                <h3>Keranjang</h3>
                <p>Lihat produk yang dipilih.</p>
            </a>
            <a class="card card-link" href="pesanan.php">
                <h3>Pesanan</h3>
                <p>Lihat riwayat pesanan.</p>
            </a>
        </div>
    </section>
</main>
<?php require_once "includes/footer.php"; ?>
