<?php
session_start();

$is_login = isset($_SESSION["id_pelanggan"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css">
</head>
<body>

<?php
if ($is_login) {
    require_once "../user/includes/navbar.php";
} else {
    require_once "../guest/includes/navbar.php";
}
?>

<main>

    <!-- HERO -->
    <section class="tentang-hero">
        <div class="tentang-hero-content">
            <span>TENTANG KAMI</span>
            <h1>Toko ATK & Alat Jahit</h1>
            <p>Tempat untuk memenuhi kebutuhan alat tulis dan perlengkapan menjahit dengan mudah.</p>
        </div>
    </section>

    <!-- TENTANG TOKO -->
    <section class="tentang-section">
        <div class="tentang-card">
            <div class="tentang-icon">🏪</div>
            <div class="tentang-content">
                <span class="tentang-label">TENTANG TOKO</span>
                <h2>Toko ATK & Alat Jahit</h2>
                <p>Toko ATK & Alat Jahit merupakan toko yang menyediakan berbagai kebutuhan alat tulis kantor dan perlengkapan menjahit dalam satu tempat.</p>
                <p>Melalui website ini, pelanggan dapat melihat berbagai produk yang tersedia, mengetahui harga dan stok, serta melakukan pemesanan untuk diambil langsung di toko.</p>
            </div>
        </div>
    </section>

    <!-- KEUNGGULAN -->
    <section class="keunggulan-section">
        <div class="keunggulan-heading">
            <span>LAYANAN KAMI</span>
            <h2>Kemudahan Untuk Pelanggan</h2>
            <p>Kami menyediakan beberapa kemudahan melalui website Toko ATK & Alat Jahit.</p>
        </div>

        <div class="keunggulan-grid">

            <div class="keunggulan-card">
                <div class="keunggulan-icon">🛍️</div>
                <h3>Pilihan Produk</h3>
                <p>Pelanggan dapat melihat berbagai produk ATK dan alat jahit yang tersedia di toko.</p>
            </div>

            <div class="keunggulan-card">
                <div class="keunggulan-icon">📦</div>
                <h3>Informasi Stok</h3>
                <p>Informasi stok produk dapat dilihat sebelum pelanggan melakukan pemesanan.</p>
            </div>

            <div class="keunggulan-card">
                <div class="keunggulan-icon">🏪</div>
                <h3>Pickup di Toko</h3>
                <p>Pesanan dapat diambil langsung oleh pelanggan di lokasi toko setelah pesanan diproses.</p>
            </div>

        </div>
    </section>

    <!-- CTA -->
    <section class="tentang-cta">
        <h2>Temukan Kebutuhan Anda</h2>
        <p>Jelajahi katalog produk kami dan temukan berbagai kebutuhan ATK dan alat jahit.</p>
        <a href="katalog.php" class="btn-katalog">Lihat Katalog</a>
    </section>

</main>

<style>
.tentang-hero{background:#123452;padding:75px 30px;text-align:center;color:#fff}
.tentang-hero-content{width:100%;max-width:850px;margin:auto}
.tentang-hero span,.keunggulan-heading span,.tentang-label{font-size:13px;font-weight:700;letter-spacing:2px;color:#8faec7}
.tentang-hero h1{margin:15px 0;font-size:42px;line-height:1.2}
.tentang-hero p{margin:0;font-size:18px;line-height:1.8;color:#e5edf4}

.tentang-section{padding:70px 7%;background:#f5f7fa}
.tentang-card{width:100%;max-width:1100px;margin:auto;background:#fff;border-radius:18px;padding:50px;display:flex;gap:45px;align-items:center;box-shadow:0 8px 25px rgba(0,0,0,.06)}
.tentang-icon{width:170px;height:170px;min-width:170px;border-radius:18px;background:#123452;color:#fff;display:flex;align-items:center;justify-content:center;font-size:75px}
.tentang-content{flex:1;min-width:0}
.tentang-content h2{color:#123452;font-size:30px;line-height:1.3;margin:12px 0 20px}
.tentang-content p{color:#60758a;line-height:1.8;margin-bottom:15px;font-size:14px}

.keunggulan-section{padding:70px 7%;background:#fff}
.keunggulan-heading{width:100%;max-width:850px;margin:0 auto 45px;text-align:center}
.keunggulan-heading h2{color:#123452;font-size:32px;line-height:1.3;margin:12px 0}
.keunggulan-heading p{color:#60758a;line-height:1.7;font-size:14px}
.keunggulan-grid{width:100%;max-width:1100px;margin:auto;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:25px}
.keunggulan-card{padding:35px 25px;text-align:center;border:1px solid #e2e8ee;border-radius:16px;background:#f9fafb}
.keunggulan-icon{font-size:45px;line-height:1;margin-bottom:18px}
.keunggulan-card h3{color:#123452;margin-bottom:12px;font-size:18px}
.keunggulan-card p{color:#60758a;line-height:1.7;font-size:13px}

.tentang-cta{background:#123452;color:#fff;text-align:center;padding:65px 25px}
.tentang-cta h2{font-size:30px;line-height:1.3;margin-bottom:12px}
.tentang-cta p{color:#dce7ef;margin-bottom:25px;line-height:1.7;font-size:14px}
.btn-katalog{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:13px 28px;background:#fff;color:#123452;text-decoration:none;border-radius:8px;font-weight:700;font-size:13px}
.btn-katalog:hover{background:#e9eef3}

@media(max-width:900px){
    .tentang-hero{padding:60px 25px}
    .tentang-hero h1{font-size:36px}
    .tentang-hero p{font-size:16px}
    .tentang-section,.keunggulan-section{padding:55px 25px}
    .tentang-card{padding:35px;gap:30px}
    .tentang-icon{width:140px;height:140px;min-width:140px;font-size:60px}
    .tentang-content h2{font-size:26px}
    .keunggulan-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}

@media(max-width:768px){
    .tentang-hero{padding:50px 20px}
    .tentang-hero span,.keunggulan-heading span,.tentang-label{font-size:11px}
    .tentang-hero h1{font-size:30px}
    .tentang-hero p{font-size:13px;line-height:1.7}
    .tentang-section,.keunggulan-section{padding:45px 15px}
    .tentang-card{flex-direction:column;text-align:center;padding:30px 20px;gap:22px;border-radius:14px}
    .tentang-icon{width:120px;height:120px;min-width:120px;font-size:52px;border-radius:14px}
    .tentang-content h2{font-size:24px;margin:10px 0 15px}
    .tentang-content p{font-size:12px;line-height:1.7}
    .keunggulan-heading{margin-bottom:30px}
    .keunggulan-heading h2{font-size:25px}
    .keunggulan-heading p{font-size:12px}
    .keunggulan-grid{grid-template-columns:1fr;gap:15px}
    .keunggulan-card{padding:25px 20px}
    .keunggulan-icon{font-size:38px}
    .keunggulan-card h3{font-size:17px}
    .keunggulan-card p{font-size:12px}
    .tentang-cta{padding:50px 20px}
    .tentang-cta h2{font-size:25px}
    .tentang-cta p{font-size:12px}
    .btn-katalog{width:100%;max-width:260px}
}

@media(max-width:480px){
    .tentang-hero{padding:42px 15px}
    .tentang-hero h1{font-size:24px}
    .tentang-hero p{font-size:12px}
    .tentang-section,.keunggulan-section{padding:35px 10px}
    .tentang-card{padding:25px 16px}
    .tentang-icon{width:100px;height:100px;min-width:100px;font-size:45px}
    .tentang-content h2{font-size:21px}
    .tentang-content p{font-size:11px}
    .keunggulan-heading h2{font-size:22px}
    .keunggulan-card{padding:22px 16px}
    .tentang-cta{padding:42px 15px}
    .tentang-cta h2{font-size:22px}
    .btn-katalog{font-size:12px}
}
</style>

<?php
if ($is_login) {
    require_once "../user/includes/footer.php";
} else {
    require_once "../guest/includes/footer.php";
}
?>

</body>
</html>