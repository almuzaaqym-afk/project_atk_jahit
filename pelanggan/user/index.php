
<?php
session_start();

if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ANIMASI SETELAH LOGIN
|--------------------------------------------------------------------------
| Penanda hanya digunakan satu kali, tepat setelah login berhasil.
|--------------------------------------------------------------------------
*/

$showLoginAnimation =
    isset($_SESSION["show_login_animation"]) &&
    $_SESSION["show_login_animation"] === true;

// Hapus penanda supaya refresh tidak mengulang animasi.
unset($_SESSION["show_login_animation"]);

require_once __DIR__ . "/../../config/koneksi.php";


/* =========================
   AMBIL BANNER AKTIF
========================= */

$query_banner = mysqli_query($conn, "
    SELECT *
    FROM banner
    WHERE status = 'Aktif'
      AND link_tombol NOT IN (
          '/project_atk_jahit/pelanggan/login.php',
          '/project_atk_jahit/pelanggan/register.php'
      )
    ORDER BY urutan ASC, id_banner DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Beranda Pelanggan - Toko ATK & Alat Jahit
    </title>

    <!-- CSS UTAMA PELANGGAN -->
    <link
        rel="stylesheet"
        href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css"
    >

    <!-- CSS ANIMASI: HANYA JIKA LOGIN BARU BERHASIL -->
    <?php if ($showLoginAnimation): ?>
        <link
            rel="stylesheet"
            href="/project_atk_jahit/pelanggan/assets/css/animasi-login.css"
        >
    <?php endif; ?>

</head>

<body>

<!-- ANIMASI DITAMPILKAN SEBELUM NAVBAR -->
<?php if ($showLoginAnimation): ?>
    <?php
    require __DIR__ . "/includes/animasi_login.php";
    ?>
<?php endif; ?>


<!-- NAVBAR PELANGGAN -->
<?php require_once __DIR__ . "/includes/navbar.php"; ?>


<main>

    <!-- =========================
         SAPAAN PELANGGAN
    ========================== -->

    <section class="section user-welcome">

        <span class="eyebrow">
            PELANGGAN
        </span>

        <h1>
            Halo,
            <?= htmlspecialchars(
                $_SESSION["nama_pelanggan"] ?? "Pelanggan",
                ENT_QUOTES,
                "UTF-8"
            ) ?>
            👋
        </h1>

        <p>
            Selamat datang kembali di Toko ATK & Alat Jahit.
        </p>


        <!-- =========================
             BANNER ADMIN
        ========================== -->

        <?php if ($query_banner && mysqli_num_rows($query_banner) > 0): ?>

            <div class="user-banner-section">

                <div class="banner-slider">

                    <?php
                    $no = 0;

                    while ($banner = mysqli_fetch_assoc($query_banner)):
                        $no++;
                    ?>

                        <div
                            class="banner-slide <?= $no === 1 ? 'active' : '' ?>"
                        >

                            <img
                                src="../../admin/assets/images/banners/<?= htmlspecialchars(
                                    $banner["gambar"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $banner["judul"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <div class="banner-overlay">

                                <div class="banner-text">

                                    <h2>
                                        <?= htmlspecialchars(
                                            $banner["judul"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </h2>

                                    <?php if (!empty($banner["deskripsi"])): ?>

                                        <p>
                                            <?= htmlspecialchars(
                                                $banner["deskripsi"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </p>

                                    <?php endif; ?>

                                    <?php if (!empty($banner["teks_tombol"])): ?>

                                        <a
                                            href="<?= htmlspecialchars(
                                                $banner["link_tombol"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            class="banner-button"
                                        >
                                            <?= htmlspecialchars(
                                                $banner["teks_tombol"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endwhile; ?>


                    <!-- PANAH DAN TITIK BANNER -->
                    <?php if ($no > 1): ?>

                        <button
                            type="button"
                            class="banner-arrow banner-prev"
                            onclick="prevUserBanner()"
                            aria-label="Banner sebelumnya"
                        >
                            ‹
                        </button>

                        <button
                            type="button"
                            class="banner-arrow banner-next"
                            onclick="nextUserBanner()"
                            aria-label="Banner berikutnya"
                        >
                            ›
                        </button>

                        <div class="banner-dots">

                            <?php for ($i = 0; $i < $no; $i++): ?>

                                <button
                                    type="button"
                                    class="banner-dot <?= $i === 0 ? 'active' : '' ?>"
                                    onclick="showUserBanner(<?= $i ?>)"
                                    aria-label="Tampilkan banner <?= $i + 1 ?>"
                                ></button>

                            <?php endfor; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- =========================
             MENU PELANGGAN
        ========================== -->

        <div class="cards">

            <a
                class="card card-link"
                href="../pages/katalog.php"
            >

                <h3>
                    Katalog Produk
                </h3>

                <p>
                    Lihat produk yang tersedia.
                </p>

            </a>


            <a
                class="card card-link"
                href="favorit.php"
            >

                <h3>
                    Favorit
                </h3>

                <p>
                    Produk yang kamu simpan.
                </p>

            </a>


            <a
                class="card card-link"
                href="keranjang.php"
            >

                <h3>
                    Keranjang
                </h3>

                <p>
                    Lihat produk yang dipilih.
                </p>

            </a>


            <a
                class="card card-link"
                href="pesanan.php"
            >

                <h3>
                    Pesanan
                </h3>

                <p>
                    Lihat riwayat pesanan.
                </p>

            </a>

        </div>

    </section>

</main>


<!-- FOOTER PELANGGAN -->
<?php require_once __DIR__ . "/includes/footer.php"; ?>


<script>

/* =========================
   BANNER SESUDAH LOGIN
========================= */

let currentUserBanner = 0;

const userBanners =
    document.querySelectorAll(".banner-slide");

const userDots =
    document.querySelectorAll(".banner-dot");


function showUserBanner(index)
{
    if (userBanners.length === 0) {
        return;
    }

    if (index >= userBanners.length) {
        currentUserBanner = 0;
    } else if (index < 0) {
        currentUserBanner = userBanners.length - 1;
    } else {
        currentUserBanner = index;
    }

    userBanners.forEach(function(banner) {
        banner.classList.remove("active");
    });

    userDots.forEach(function(dot) {
        dot.classList.remove("active");
    });

    userBanners[currentUserBanner].classList.add("active");

    if (userDots[currentUserBanner]) {
        userDots[currentUserBanner].classList.add("active");
    }
}


function nextUserBanner()
{
    showUserBanner(currentUserBanner + 1);
}


function prevUserBanner()
{
    showUserBanner(currentUserBanner - 1);
}


/* Banner berganti setiap 5 detik */

if (userBanners.length > 1) {
    setInterval(function() {
        nextUserBanner();
    }, 5000);
}

</script>


<!-- JAVASCRIPT ANIMASI: HANYA JIKA LOGIN BARU BERHASIL -->
<?php if ($showLoginAnimation): ?>
    <script src="/project_atk_jahit/pelanggan/assets/js/animasi-login.js"></script>
<?php endif; ?>


</body>
</html>
