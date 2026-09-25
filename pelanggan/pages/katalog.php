<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/koneksi.php";

/* =========================
   KATEGORI
========================= */

$kategori = strtolower(trim($_GET['kategori'] ?? ''));

$kategori_db = '';

if ($kategori === 'atk') {
    $kategori_db = 'ATK';
} elseif ($kategori === 'jahit') {
    $kategori_db = 'Alat Jahit';
}

/* =========================
   AMBIL PRODUK
========================= */

$produk = [];

if ($kategori_db !== '') {

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
        WHERE LOWER(kategori.nama_kategori) = LOWER(?)
        ORDER BY produk.id_produk DESC"
    );

    mysqli_stmt_bind_param($stmt, "s", $kategori_db);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $produk[] = $row;
    }

    mysqli_stmt_close($stmt);

} else {

    $result = mysqli_query(
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
        ORDER BY produk.id_produk DESC"
    );

    while ($row = mysqli_fetch_assoc($result)) {
        $produk[] = $row;
    }
}

/* =========================
   JUDUL
========================= */

if (strtolower($kategori) === 'atk') {

    $judul = "Produk ATK";
    $deskripsi_kategori = "Alat tulis kantor dan perlengkapan sekolah.";

} elseif (
    strtolower($kategori) === 'jahit' ||
    strtolower($kategori) === 'alat jahit'
) {

    $judul = "Produk Alat Jahit";
    $deskripsi_kategori = "Benang, jarum, gunting dan perlengkapan jahit.";

} else {

    $judul = "Semua Produk";
    $deskripsi_kategori = "Berbagai produk ATK dan alat jahit.";
}


/* =========================
   NAVBAR
========================= */

if (isset($_SESSION['id_pelanggan'])) {

    require_once __DIR__ . "/../user/includes/navbar.php";

} else {

    require_once __DIR__ . "/../guest/includes/navbar.php";

}

?>

<main>

    <section class="section katalog-section">

        <div class="section-heading">

            <span class="eyebrow">KATALOG PRODUK</span>

            <h1><?= htmlspecialchars($judul); ?></h1>

            <p class="muted">
                <?= htmlspecialchars($deskripsi_kategori); ?>
            </p>

        </div>


        <!-- =========================
             KATEGORI
        ========================== -->

        <?php if ($kategori === ''): ?>

            <div class="kategori-grid">

                <a href="katalog.php?kategori=atk" class="kategori-card">

                    <div class="kategori-icon">
                        ✏️
                    </div>

                    <span class="kategori-label">
                        KATEGORI
                    </span>

                    <h2>ATK</h2>

                    <p>
                        Alat tulis kantor, perlengkapan sekolah,
                        buku, pulpen dan berbagai kebutuhan ATK.
                    </p>

                    <span class="kategori-link">
                        Lihat Produk →
                    </span>

                </a>


                <a href="katalog.php?kategori=jahit" class="kategori-card">

                    <div class="kategori-icon">
                        🧵
                    </div>

                    <span class="kategori-label">
                        KATEGORI
                    </span>

                    <h2>Alat Jahit</h2>

                    <p>
                        Benang, jarum, gunting dan berbagai
                        perlengkapan untuk kebutuhan menjahit.
                    </p>

                    <span class="kategori-link">
                        Lihat Produk →
                    </span>

                </a>

            </div>

        <?php endif; ?>


        <!-- =========================
             PRODUK
        ========================== -->

        <div class="produk-heading">

            <span class="eyebrow">PRODUK</span>

            <h2><?= htmlspecialchars($judul); ?></h2>

        </div>


        <?php if (count($produk) > 0): ?>

            <div class="produk-grid">

                <?php foreach ($produk as $item): ?>

                    <div class="produk-card">

                        <div class="produk-image">

                            <?php if (!empty($item['gambar'])): ?>

                                <img
                                    src="../../admin/assets/images/products/<?= htmlspecialchars($item['gambar']); ?>"
                                    alt="<?= htmlspecialchars($item['nama_produk']); ?>"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    Tidak ada gambar
                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="produk-info">

                            <div class="produk-kategori">

                                <?= htmlspecialchars(
                                    $item['nama_kategori'] ?? '-'
                                ); ?>

                            </div>


                            <div class="produk-nama">

                                <?= htmlspecialchars(
                                    $item['nama_produk']
                                ); ?>

                            </div>


                            <div class="produk-harga">

                                Rp <?= number_format(
                                    $item['harga'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                            </div>


                            <div class="produk-stok">

                                Stok: <?= (int)$item['stok']; ?>

                            </div>


                            <a
                                href="detail_produk.php?id=<?= (int)$item['id_produk']; ?>"
                                class="btn-detail"
                            >
                                Lihat Detail
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty">

                Belum ada produk pada kategori ini.

            </div>

        <?php endif; ?>

    </section>

</main>


<?php

require_once __DIR__ . "/../guest/includes/footer.php";

?>