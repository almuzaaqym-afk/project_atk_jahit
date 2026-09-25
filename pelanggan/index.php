<?php
session_start();

if (isset($_SESSION['id_pelanggan'])) {
    header("Location: user/index.php");
    exit;
}

require_once "guest/includes/navbar.php";
?>
<main>
    <section class="hero">
        <div>
            <span class="eyebrow">TOKO ATK & ALAT JAHIT</span>
            <h1>Kebutuhan ATK dan alat jahit dalam satu tempat.</h1>
            <p>
                Temukan berbagai perlengkapan alat tulis kantor dan alat jahit
                dengan tampilan katalog yang mudah digunakan.
            </p>
            <div class="hero-actions">
                <a class="btn-primary" href="pages/katalog.php">Lihat Katalog</a>
                <a class="btn-outline" href="login.php">Login Pelanggan</a>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section-heading">
            <span class="eyebrow">KENAPA KAMI</span>
            <h2>Belanja lebih mudah</h2>
        </div>
        <div class="cards">
            <div class="card"><h3>Katalog Produk</h3><p>Lihat produk ATK dan alat jahit yang tersedia.</p></div>
            <div class="card"><h3>Informasi Produk</h3><p>Lihat detail produk sebelum memilih.</p></div>
            <div class="card"><h3>Lokasi Toko</h3><p>Informasi lokasi toko tersedia untuk pelanggan.</p></div>
        </div>
    </section>
</main>
<?php require_once "guest/includes/footer.php"; ?>
