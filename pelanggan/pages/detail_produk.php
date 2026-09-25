<?php
session_start();

require_once "../../config/koneksi.php";

$is_login = isset($_SESSION["id_pelanggan"]);

/* =========================
   AMBIL ID PRODUK
========================= */

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    header("Location: katalog.php");
    exit;
}

/* =========================
   AMBIL DATA PRODUK
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        produk.id_produk,
        produk.nama_produk,
        produk.deskripsi,
        produk.harga,
        produk.stok,
        produk.gambar,
        produk.lokasi_rak,
        kategori.nama_kategori
    FROM produk
    LEFT JOIN kategori
        ON produk.id_kategori = kategori.id_kategori
    WHERE produk.id_produk = ?
    LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================
   PRODUK TIDAK DITEMUKAN
========================= */

if (!$item) {
    header("Location: katalog.php");
    exit;
}

/* =========================
   NAVBAR
========================= */

if ($is_login) {
    require_once "../user/includes/navbar.php";
} else {
    require_once "../guest/includes/navbar.php";
}
?>

<main>

    <section class="section product-detail">

        <div class="product-detail-card">

            <!-- GAMBAR PRODUK -->
            <div class="product-detail-image">

                <?php if (!empty($item["gambar"])): ?>

    <img
        src="../../admin/assets/images/products/<?= htmlspecialchars($item["gambar"]); ?>"
        alt="<?= htmlspecialchars($item["nama_produk"]); ?>"
        style="
            width:100%;
            max-width:500px;
            height:350px;
            object-fit:cover;
            border-radius:12px;
            margin-bottom:25px;
        "
    >

<?php else: ?>

    <div class="no-image">
        Tidak ada gambar
    </div>

<?php endif; ?>

            </div>


            <!-- INFORMASI PRODUK -->
            <div class="product-detail-info">

                <span class="eyebrow">
                    <?= htmlspecialchars(
                        $item["nama_kategori"] ?? "Produk"
                    ); ?>
                </span>


                <h1>
                    <?= htmlspecialchars($item["nama_produk"]); ?>
                </h1>


                <h2>
                    Rp <?= number_format(
                        $item["harga"],
                        0,
                        ",",
                        "."
                    ); ?>
                </h2>


                <p>
                    <?= htmlspecialchars(
                        $item["deskripsi"] ?? "Tidak ada deskripsi produk."
                    ); ?>
                </p>


                <div class="product-detail-stock">

                    Stok:
                    <strong>
                        <?= (int)$item["stok"]; ?>
                    </strong>

                </div>


                <?php if (!empty($item["lokasi_rak"])): ?>

                    <div class="product-detail-location">

                        Lokasi Rak:
                        <strong>
                            <?= htmlspecialchars(
                                $item["lokasi_rak"]
                            ); ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <!-- AKSI -->
                <?php if ($is_login): ?>

                    <div class="product-actions">

                        <button
                            class="btn-primary"
                            type="button"
                        >
                            Masukkan Keranjang
                        </button>

                        <button
                            class="btn-outline"
                            type="button"
                        >
                            Favorit
                        </button>

                    </div>

                <?php else: ?>

                    <div class="product-actions">

                        <a
                            class="btn-primary"
                            href="../login.php"
                        >
                            Login untuk Melanjutkan
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<?php

if ($is_login) {
    require_once "../user/includes/footer.php";
} else {
    require_once "../guest/includes/footer.php";
}

?>