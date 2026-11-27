<?php
session_start();

if (isset($_SESSION['id_pelanggan'])) {
    header("Location: user/index.php");
    exit;
}

require_once "../config/koneksi.php";

/* Ambil banner aktif dari Admin */
$query_banner = mysqli_query($conn, "
    SELECT *
    FROM banner
    WHERE status = 'Aktif'
    ORDER BY urutan ASC, id_banner DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <!-- PENTING UNTUK RESPONSIVE HP -->
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Toko ATK & Alat Jahit</title>

    <link
        rel="stylesheet"
        href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css"
    >

</head>

<body>

<?php require_once "guest/includes/navbar.php"; ?>


<main>

    <!-- =========================
         HERO
    ========================== -->

    <section class="hero">

        <div>

            <span class="eyebrow">
                TOKO ATK & ALAT JAHIT
            </span>

            <h1>
                Kebutuhan ATK dan alat jahit dalam satu tempat.
            </h1>

            <p>
                Temukan berbagai perlengkapan alat tulis kantor dan alat jahit
                dengan tampilan katalog yang mudah digunakan.
            </p>

            <div class="hero-actions">

                <a
                    class="btn-primary"
                    href="pages/katalog.php"
                >
                    Lihat Katalog
                </a>

                <a
                    class="btn-outline"
                    href="login.php"
                >
                    Login Pelanggan
                </a>

            </div>

        </div>

    </section>


    <!-- =========================
         BANNER DARI ADMIN
    ========================== -->

    <?php if (mysqli_num_rows($query_banner) > 0): ?>

        <section class="banner-section">

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
                            src="../admin/assets/images/banners/<?= htmlspecialchars($banner['gambar']) ?>"
                            alt="<?= htmlspecialchars($banner['judul']) ?>"
                        >

                        <div class="banner-overlay">

                            <div class="banner-text">

                                <h2>
                                    <?= htmlspecialchars($banner['judul']) ?>
                                </h2>

                                <?php if (!empty($banner['deskripsi'])): ?>

                                    <p>
                                        <?= htmlspecialchars($banner['deskripsi']) ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($banner['teks_tombol'])): ?>

                                    <a
                                        href="<?= !empty($banner['link_tombol'])
                                            ? htmlspecialchars($banner['link_tombol'])
                                            : 'pages/katalog.php' ?>"
                                        class="banner-button"
                                    >
                                        <?= htmlspecialchars($banner['teks_tombol']) ?>
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>


                <?php if ($no > 1): ?>

                    <button
                        type="button"
                        class="banner-arrow banner-prev"
                        onclick="prevBanner()"
                    >
                        ‹
                    </button>

                    <button
                        type="button"
                        class="banner-arrow banner-next"
                        onclick="nextBanner()"
                    >
                        ›
                    </button>


                    <div class="banner-dots">

                        <?php for ($i = 0; $i < $no; $i++): ?>

                            <span
                                class="banner-dot <?= $i === 0 ? 'active' : '' ?>"
                                onclick="showBanner(<?= $i ?>)"
                            ></span>

                        <?php endfor; ?>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>


    <!-- =========================
         KENAPA KAMI
    ========================== -->

    <section class="section">

        <div class="section-heading">

            <span class="eyebrow">
                KENAPA KAMI
            </span>

            <h2>
                Belanja lebih mudah
            </h2>

        </div>


        <div class="cards">

            <a
                href="pages/katalog.php"
                class="card"
                style="text-decoration:none;color:inherit;"
            >

                <h3>
                    Katalog Produk
                </h3>

                <p>
                    Lihat produk ATK dan alat jahit yang tersedia.
                </p>

            </a>


            <a
                href="https://maps.app.goo.gl/retvodNQKMfwRy7X7"
                target="_blank"
                rel="noopener noreferrer"
                class="card"
                style="text-decoration:none;color:inherit;"
            >

                <h3>
                    Lokasi Toko
                </h3>

                <p>
                    Informasi lokasi toko tersedia untuk pelanggan.
                </p>

            </a>

        </div>

    </section>

</main>


<?php require_once "guest/includes/footer.php"; ?>


<script>

let currentBanner = 0;

const banners = document.querySelectorAll(".banner-slide");
const dots = document.querySelectorAll(".banner-dot");


function showBanner(index) {

    if (banners.length === 0) {
        return;
    }

    if (index >= banners.length) {

        currentBanner = 0;

    } else if (index < 0) {

        currentBanner = banners.length - 1;

    } else {

        currentBanner = index;

    }


    banners.forEach(function (banner) {

        banner.classList.remove("active");

    });


    dots.forEach(function (dot) {

        dot.classList.remove("active");

    });


    banners[currentBanner].classList.add("active");


    if (dots[currentBanner]) {

        dots[currentBanner].classList.add("active");

    }

}


function nextBanner() {

    showBanner(currentBanner + 1);

}


function prevBanner() {

    showBanner(currentBanner - 1);

}


if (banners.length > 1) {

    setInterval(function () {

        nextBanner();

    }, 5000);

}

</script>

</body>
</html>