<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../config/koneksi.php";

/* KATEGORI TERPILIH */
$id_kategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

/* PENCARIAN */
$search = trim($_GET['search'] ?? '');
$search = mb_substr($search, 0, 100);

/* AMBIL SEMUA KATEGORI */
$daftar_kategori = [];
$query_kategori = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");
if ($query_kategori) {
    while ($row = mysqli_fetch_assoc($query_kategori)) {
        $daftar_kategori[] = $row;
    }
}

/* AMBIL KATEGORI TERPILIH */
$kategori_terpilih = null;
if ($id_kategori > 0) {
    $stmt_kategori = mysqli_prepare($conn, "SELECT id_kategori, nama_kategori FROM kategori WHERE id_kategori = ? LIMIT 1");
    if ($stmt_kategori) {
        mysqli_stmt_bind_param($stmt_kategori, "i", $id_kategori);
        mysqli_stmt_execute($stmt_kategori);
        $result_kategori = mysqli_stmt_get_result($stmt_kategori);
        $kategori_terpilih = mysqli_fetch_assoc($result_kategori);
        mysqli_stmt_close($stmt_kategori);
    }
}

/* JOIN HARGA VARIAN AKTIF */
$join_varian_harga = "
    LEFT JOIN (
        SELECT id_produk, MIN(harga) AS harga_min_varian, MAX(harga) AS harga_max_varian
        FROM produk_varian
        WHERE status = 'Aktif'
        GROUP BY id_produk
    ) pv_harga ON pv_harga.id_produk = p.id_produk
";

/* AMBIL PRODUK */
$produk = [];

if ($search !== '') {
    $keyword = "%" . $search . "%";

    if ($id_kategori > 0 && $kategori_terpilih) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.lokasi_rak,
                    k.nama_kategori, pv_harga.harga_min_varian, pv_harga.harga_max_varian
             FROM produk p
             LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
             $join_varian_harga
             WHERE p.id_kategori = ?
               AND (
                    p.nama_produk LIKE ?
                    OR p.deskripsi LIKE ?
                    OR k.nama_kategori LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM produk_varian pv
                        WHERE pv.id_produk = p.id_produk
                          AND pv.status = 'Aktif'
                          AND pv.nama_varian LIKE ?
                    )
               )
             ORDER BY p.id_produk DESC"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issss", $id_kategori, $keyword, $keyword, $keyword, $keyword);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $produk[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.lokasi_rak,
                    k.nama_kategori, pv_harga.harga_min_varian, pv_harga.harga_max_varian
             FROM produk p
             LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
             $join_varian_harga
             WHERE (
                    p.nama_produk LIKE ?
                    OR p.deskripsi LIKE ?
                    OR k.nama_kategori LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM produk_varian pv
                        WHERE pv.id_produk = p.id_produk
                          AND pv.status = 'Aktif'
                          AND pv.nama_varian LIKE ?
                    )
             )
             ORDER BY p.id_produk DESC"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $keyword, $keyword, $keyword, $keyword);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $produk[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
} elseif ($id_kategori > 0 && $kategori_terpilih) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.lokasi_rak,
                k.nama_kategori, pv_harga.harga_min_varian, pv_harga.harga_max_varian
         FROM produk p
         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
         $join_varian_harga
         WHERE p.id_kategori = ?
         ORDER BY p.id_produk DESC"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_kategori);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $produk[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $result = mysqli_query(
        $conn,
        "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.lokasi_rak,
                k.nama_kategori, pv_harga.harga_min_varian, pv_harga.harga_max_varian
         FROM produk p
         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
         $join_varian_harga
         ORDER BY p.id_produk DESC"
    );

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $produk[] = $row;
        }
    }
}

/* JUDUL */
if ($search !== '') {
    $judul = "Hasil Pencarian";
    $deskripsi_kategori = 'Menampilkan hasil untuk kata kunci "' . $search . '".';
} elseif ($kategori_terpilih) {
    $judul = "Produk " . $kategori_terpilih['nama_kategori'];
    $deskripsi_kategori = "Produk dalam kategori " . $kategori_terpilih['nama_kategori'] . ".";
} else {
    $judul = "Semua Produk";
    $deskripsi_kategori = "Berbagai produk ATK dan alat jahit.";
}

/* ICON KATEGORI */
function iconKategori($nama)
{
    $nama = strtolower(trim($nama));
    if (strpos($nama, 'atk') !== false) return '✏️';
    if (strpos($nama, 'jahit') !== false || strpos($nama, 'alat jahit') !== false) return '🧵';
    if (strpos($nama, 'buku') !== false) return '📚';
    if (strpos($nama, 'aksesoris') !== false) return '🧰';
    return '📦';
}

/* DESKRIPSI KATEGORI */
function deskripsiKategori($nama)
{
    $nama_lower = strtolower(trim($nama));
    if (strpos($nama_lower, 'atk') !== false) {
        return "Alat tulis kantor, perlengkapan sekolah, buku, pulpen dan berbagai kebutuhan ATK.";
    }
    if (strpos($nama_lower, 'jahit') !== false || strpos($nama_lower, 'alat jahit') !== false) {
        return "Benang, jarum, gunting dan berbagai perlengkapan untuk kebutuhan menjahit.";
    }
    return "Berbagai produk dalam kategori " . $nama . ".";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul); ?> - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css">
</head>
<body>

<!-- NAVBAR -->
<?php
if (isset($_SESSION['id_pelanggan'])) {
    require_once __DIR__ . "/../user/includes/navbar.php";
} else {
    require_once __DIR__ . "/../guest/includes/navbar.php";
}
?>

<style>
.produk-image{position:relative}
.produk-image-habis{filter:grayscale(35%)}
.produk-stock-badge{position:absolute;top:12px;left:12px;background:#c53030;color:#fff;padding:7px 12px;border-radius:6px;font-size:11px;font-weight:bold;letter-spacing:.5px;z-index:2}
.produk-actions{margin-top:15px}
.btn-detail{display:inline-flex;align-items:center;justify-content:center;width:100%;min-height:40px;padding:10px 15px;background:#102a43;color:#fff;border:1px solid #102a43;border-radius:7px;text-decoration:none;font-size:13px;font-weight:bold;transition:.2s}
.btn-detail:hover{background:#183b56;border-color:#183b56}
.produk-harga{min-height:24px;display:flex;align-items:center;flex-wrap:wrap;gap:3px;color:#102a43;font-size:17px;font-weight:bold;line-height:1.4}
.harga-range{display:inline}
.harga-separator{margin:0 2px;color:#627d98;font-weight:normal}
.search-result-info{width:100%;max-width:1200px;margin:0 auto 20px;padding:13px 16px;background:#f5f8fb;border:1px solid #dfe7ed;border-radius:9px;color:#486581;font-size:13px}
.search-result-info strong{color:#102a43}
.search-empty{width:100%;max-width:650px;margin:30px auto;padding:45px 25px;text-align:center;background:#fff;border:1px solid #e5e7eb;border-radius:14px}
.search-empty h2{color:#102a43;margin-bottom:8px}
.search-empty p{color:#627d98;font-size:13px;margin-bottom:20px}
.search-empty a{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:10px 16px;background:#102a43;color:#fff;border-radius:8px;text-decoration:none;font-size:12px;font-weight:bold}
@media(max-width:768px){
    .search-result-info{margin-bottom:16px;padding:11px 13px;font-size:12px;line-height:1.5}
    .produk-actions{margin-top:12px}
    .produk-harga{font-size:15px}
}
@media(max-width:550px){
    .search-empty{margin:20px auto;padding:35px 18px}
    .search-empty h2{font-size:20px}
    .search-empty p{font-size:12px}
    .produk-harga{font-size:15px}
}
</style>

<main>
    <section class="section katalog-section">
        <!-- JUDUL -->
        <div class="section-heading">
            <span class="eyebrow">KATALOG PRODUK</span>
            <h1><?= htmlspecialchars($judul); ?></h1>
            <p class="muted"><?= htmlspecialchars($deskripsi_kategori); ?></p>
        </div>

        <!-- INFO PENCARIAN -->
        <?php if ($search !== ''): ?>
            <div class="search-result-info">
                Hasil pencarian untuk:
                <strong>"<?= htmlspecialchars($search); ?>"</strong>
                — <?= count($produk); ?> produk ditemukan.
            </div>
        <?php endif; ?>

        <!-- KATEGORI -->
        <?php if ($id_kategori === 0 && $search === ''): ?>
            <?php if (count($daftar_kategori) > 0): ?>
                <div class="kategori-grid">
                    <?php foreach ($daftar_kategori as $kategori_item): ?>
                        <?php
                        $nama_kategori = $kategori_item['nama_kategori'];
                        $icon = iconKategori($nama_kategori);
                        $deskripsi = deskripsiKategori($nama_kategori);
                        ?>
                        <a href="katalog.php?kategori=<?= (int) $kategori_item['id_kategori']; ?>" class="kategori-card">
                            <div class="kategori-icon"><?= $icon; ?></div>
                            <span class="kategori-label">KATEGORI</span>
                            <h2><?= htmlspecialchars($nama_kategori); ?></h2>
                            <p><?= htmlspecialchars($deskripsi); ?></p>
                            <span class="kategori-link">Lihat Produk →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty">Belum ada kategori produk.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- HEADING PRODUK -->
        <div class="produk-heading">
            <span class="eyebrow">PRODUK</span>
            <h2><?= htmlspecialchars($judul); ?></h2>
        </div>

        <!-- DAFTAR PRODUK -->
        <?php if (count($produk) > 0): ?>
            <div class="produk-grid">
                <?php foreach ($produk as $item): ?>
                    <?php
                    $stok_produk = (int) $item['stok'];
                    $produk_habis = $stok_produk <= 0;
                    $punya_varian = $item['harga_min_varian'] !== null && $item['harga_max_varian'] !== null;

                    if ($punya_varian) {
                        $harga_min = (float) $item['harga_min_varian'];
                        $harga_max = (float) $item['harga_max_varian'];
                    } else {
                        $harga_min = (float) $item['harga'];
                        $harga_max = $harga_min;
                    }
                    ?>
                    <div class="produk-card">
                        <!-- GAMBAR -->
                        <div class="produk-image <?= $produk_habis ? 'produk-image-habis' : ''; ?>">
                            <?php if (!empty($item['gambar'])): ?>
                                <img src="../../admin/assets/images/products/<?= htmlspecialchars($item['gambar']); ?>" alt="<?= htmlspecialchars($item['nama_produk']); ?>">
                            <?php else: ?>
                                <div class="no-image">Tidak ada gambar</div>
                            <?php endif; ?>
                            <div class="produk-stock-badge" style="<?= $produk_habis ? '' : 'display:none;'; ?>">HABIS</div>
                        </div>

                        <!-- INFORMASI PRODUK -->
                        <div class="produk-info">
                            <div class="produk-kategori"><?= htmlspecialchars($item['nama_kategori'] ?? '-'); ?></div>
                            <div class="produk-nama"><?= htmlspecialchars($item['nama_produk']); ?></div>

                            <!-- HARGA -->
                            <div class="produk-harga">
                                <?php if ($punya_varian): ?>
                                    <?php if ($harga_min == $harga_max): ?>
                                        Rp <?= number_format($harga_min, 0, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="harga-range">Rp <?= number_format($harga_min, 0, ',', '.'); ?></span>
                                        <span class="harga-separator">-</span>
                                        <span class="harga-range">Rp <?= number_format($harga_max, 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Rp <?= number_format($harga_min, 0, ',', '.'); ?>
                                <?php endif; ?>
                            </div>

                            <!-- STOK -->
                            <div class="produk-stok">
                                <?php if ($produk_habis): ?>
                                    <span style="color:#c53030;font-weight:bold;">Stok Habis</span>
                                <?php else: ?>
                                    Stok: <?= $stok_produk; ?>
                                <?php endif; ?>
                            </div>

                            <!-- DETAIL -->
                            <div class="produk-actions">
                                <a href="detail_produk.php?id=<?= (int) $item['id_produk']; ?>" class="btn-detail">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- PRODUK KOSONG -->
            <?php if ($search !== ''): ?>
                <div class="search-empty">
                    <h2>Produk Tidak Ditemukan</h2>
                    <p>Tidak ada produk yang cocok dengan pencarian "<strong><?= htmlspecialchars($search); ?></strong>".</p>
                    <a href="katalog.php">Lihat Semua Produk</a>
                </div>
            <?php else: ?>
                <div class="empty">Belum ada produk pada kategori ini.</div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<!-- FOOTER -->
<?php
if (isset($_SESSION['id_pelanggan'])) {
    require_once __DIR__ . "/../user/includes/footer.php";
} else {
    require_once __DIR__ . "/../guest/includes/footer.php";
}
?>

<!-- SINKRONISASI KATALOG TANPA RELOAD HALAMAN -->
<script>
(function () {
    let sedangMemuat = false;

    async function sinkronkanKatalog() {
        if (sedangMemuat || document.hidden) return;

        sedangMemuat = true;

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('_sinkron', Date.now().toString());

            const response = await fetch(url.toString(), {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) return;

            const html = await response.text();
            const dokumenBaru = new DOMParser().parseFromString(html, 'text/html');
            const katalogLama = document.querySelector('.katalog-section');
            const katalogBaru = dokumenBaru.querySelector('.katalog-section');

            if (!katalogLama || !katalogBaru) return;

            if (katalogLama.outerHTML !== katalogBaru.outerHTML) {
                katalogLama.replaceWith(katalogBaru);
            }
        } catch (error) {
            console.error('Sinkronisasi katalog gagal:', error);
        } finally {
            sedangMemuat = false;
        }
    }

    sinkronkanKatalog();
    window.setInterval(sinkronkanKatalog, 5000);

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            sinkronkanKatalog();
        }
    });
})();
</script>

</body>
</html>