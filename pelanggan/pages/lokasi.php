<?php
session_start();

$is_login = isset($_SESSION["id_pelanggan"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokasi Toko - Toko ATK & Alat Jahit</title>
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
    <section class="lokasi-hero">
        <div class="lokasi-hero-content">
            <span>LOKASI TOKO</span>
            <h1>Temukan Kami</h1>
            <p>Kunjungi Toko ATK & Alat Jahit untuk mengambil pesanan dan memenuhi kebutuhan Anda.</p>
        </div>
    </section>

    <!-- INFORMASI LOKASI -->
    <section class="lokasi-section">
        <div class="lokasi-grid">

            <!-- INFORMASI TOKO -->
            <div class="lokasi-info">
                <span class="lokasi-label">INFORMASI TOKO</span>
                <h2>Toko ATK & Alat Jahit</h2>

                <div class="info-item">
                    <div class="info-icon">📍</div>
                    <div class="info-content">
                        <h3>Alamat</h3>
                        <p>
                            Gg. Aliyas, RT.1/RW.9 No.58,<br>
                            Kp. Tengah, Kec. Kramat Jati,<br>
                            Kota Jakarta Timur, DKI Jakarta 13540
                        </p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">🕒</div>
                    <div class="info-content">
                        <h3>Jam Operasional</h3>
                        <p>
                            Senin - Minggu<br>
                            08.00 - 21.00 WIB
                        </p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">📞</div>
                    <div class="info-content">
                        <h3>Kontak</h3>
                        <p>
                            0858-1352-2811<br>
                            Hubungi toko untuk informasi produk dan pesanan.
                        </p>
                    </div>
                </div>

                <a
                    href="https://maps.app.goo.gl/retvodNQKMfwRy7X7"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn-maps"
                >
                    📍 Buka Google Maps
                </a>
            </div>

            <!-- MAP -->
            <div class="lokasi-map">
                <iframe
                    src="https://www.google.com/maps?q=Toko+ATK+%26+Alat+Jahit&output=embed"
                    width="100%"
                    height="450"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

        </div>
    </section>

    <!-- PICKUP -->
    <section class="pickup-section">
        <div class="pickup-card">
            <div class="pickup-icon">📦</div>
            <div class="pickup-content">
                <h2>Pesanan Bisa Diambil di Toko</h2>
                <p>
                    Setelah melakukan pemesanan melalui website, pelanggan dapat mengambil pesanan langsung di lokasi toko.
                </p>
            </div>
        </div>
    </section>

</main>

<style>
.lokasi-hero{background:#123452;padding:70px 30px;text-align:center;color:#fff}
.lokasi-hero-content{width:100%;max-width:850px;margin:auto}
.lokasi-hero span{font-size:13px;font-weight:700;letter-spacing:2px;color:#9bb4c9}
.lokasi-hero h1{margin:15px 0;font-size:42px;line-height:1.2}
.lokasi-hero p{margin:0;font-size:18px;line-height:1.7;color:#e2ebf2}

.lokasi-section{padding:70px 7%;background:#f5f7fa}
.lokasi-grid{width:100%;max-width:1150px;margin:auto;display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:35px;align-items:stretch}

.lokasi-info{background:#fff;padding:40px;border-radius:18px;box-shadow:0 8px 25px rgba(0,0,0,.06);min-width:0}
.lokasi-label{font-size:12px;font-weight:700;letter-spacing:2px;color:#6f8ba3}
.lokasi-info h2{color:#123452;font-size:30px;line-height:1.3;margin:12px 0 30px}

.info-item{display:flex;align-items:flex-start;gap:16px;margin-bottom:24px}
.info-icon{width:45px;height:45px;min-width:45px;background:#edf3f7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:21px}
.info-content{min-width:0}
.info-item h3{margin:0 0 6px;color:#123452;font-size:16px}
.info-item p{margin:0;color:#60758a;line-height:1.6;font-size:13px;overflow-wrap:anywhere}

.btn-maps{display:inline-flex;align-items:center;justify-content:center;min-height:44px;margin-top:8px;padding:12px 20px;background:#123452;color:#fff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:700}
.btn-maps:hover{background:#1d527d}

.lokasi-map{width:100%;min-height:450px;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,.06)}
.lokasi-map iframe{width:100%;height:100%;min-height:450px;display:block}

.pickup-section{padding:50px 7%;background:#fff}
.pickup-card{width:100%;max-width:1100px;margin:auto;padding:30px 35px;border-radius:16px;background:#edf3f7;display:flex;align-items:center;gap:25px}
.pickup-icon{flex-shrink:0;font-size:45px;line-height:1}
.pickup-content{min-width:0}
.pickup-card h2{margin:0 0 8px;color:#123452;font-size:24px;line-height:1.3}
.pickup-card p{margin:0;color:#60758a;line-height:1.7;font-size:14px}

@media(max-width:900px){
    .lokasi-hero{padding:60px 25px}
    .lokasi-hero h1{font-size:36px}
    .lokasi-hero p{font-size:16px}
    .lokasi-section{padding:55px 25px}
    .lokasi-grid{grid-template-columns:1fr;gap:25px}
    .lokasi-info{padding:30px}
    .lokasi-map,.lokasi-map iframe{min-height:400px}
    .pickup-section{padding:45px 25px}
}

@media(max-width:768px){
    .lokasi-hero{padding:50px 20px}
    .lokasi-hero span{font-size:11px}
    .lokasi-hero h1{font-size:30px}
    .lokasi-hero p{font-size:13px;line-height:1.7}
    .lokasi-section{padding:45px 15px}
    .lokasi-info{padding:25px 20px;border-radius:14px}
    .lokasi-info h2{font-size:24px;margin-bottom:25px}
    .lokasi-label{font-size:11px}
    .info-item{gap:12px;margin-bottom:20px}
    .info-icon{width:40px;height:40px;min-width:40px;font-size:19px}
    .info-item h3{font-size:15px}
    .info-item p{font-size:12px;line-height:1.7}
    .btn-maps{width:100%;font-size:12px}
    .lokasi-map,.lokasi-map iframe{min-height:320px}
    .pickup-section{padding:35px 15px}
    .pickup-card{padding:25px 20px;flex-direction:column;text-align:center;gap:15px;border-radius:14px}
    .pickup-icon{font-size:40px}
    .pickup-card h2{font-size:21px}
    .pickup-card p{font-size:12px}
}

@media(max-width:480px){
    .lokasi-hero{padding:42px 15px}
    .lokasi-hero h1{font-size:25px}
    .lokasi-hero p{font-size:12px}
    .lokasi-section{padding:35px 10px}
    .lokasi-info{padding:22px 16px}
    .lokasi-info h2{font-size:21px}
    .info-item{gap:10px}
    .info-icon{width:38px;height:38px;min-width:38px;font-size:17px}
    .info-item h3{font-size:14px}
    .info-item p{font-size:11px}
    .lokasi-map,.lokasi-map iframe{min-height:280px}
    .pickup-section{padding:30px 10px}
    .pickup-card{padding:22px 16px}
    .pickup-card h2{font-size:19px}
    .pickup-card p{font-size:11px}
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