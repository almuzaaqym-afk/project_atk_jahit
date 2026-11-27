<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}
require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
cekAkses('produk');

$search = trim($_GET['search'] ?? '');
$search_escaped = mysqli_real_escape_string($conn, $search);

$where_search = '';
if ($search !== '') {
    $where_search = "WHERE (
        produk.nama_produk LIKE '%$search_escaped%'
        OR produk.deskripsi LIKE '%$search_escaped%'
        OR kategori.nama_kategori LIKE '%$search_escaped%'
        OR EXISTS (
            SELECT 1
            FROM produk_varian pv_search
            WHERE pv_search.id_produk = produk.id_produk
            AND pv_search.nama_varian LIKE '%$search_escaped%'
            AND pv_search.status = 'Aktif'
        )
    )";
}

$query_produk = mysqli_query(
    $conn,
    "SELECT
        produk.id_produk,
        produk.nama_produk,
        produk.deskripsi,
        produk.harga,
        produk.stok,
        produk.lokasi_rak,
        produk.gambar,
        kategori.nama_kategori,
        lokasi_rak.kode_rak,
        lokasi_rak.nama_lokasi,
        varian_data.harga_min,
        varian_data.harga_max,
        varian_data.jumlah_varian,
        varian_data.data_lokasi_varian
    FROM produk
    LEFT JOIN kategori
        ON produk.id_kategori = kategori.id_kategori
    LEFT JOIN lokasi_rak
        ON produk.lokasi_rak = lokasi_rak.kode_rak
    LEFT JOIN (
        SELECT
            pv.id_produk,
            MIN(pv.harga) AS harga_min,
            MAX(pv.harga) AS harga_max,
            COUNT(pv.id_varian) AS jumlah_varian,
            GROUP_CONCAT(
                CONCAT(
                    pv.nama_varian,
                    '|||',
                    COALESCE(pv.lokasi_rak, ''),
                    '|||',
                    COALESCE(lv.nama_lokasi, '')
                )
                SEPARATOR ';;;'
            ) AS data_lokasi_varian
        FROM produk_varian pv
        LEFT JOIN lokasi_rak lv
            ON pv.lokasi_rak = lv.kode_rak
        WHERE pv.status = 'Aktif'
        GROUP BY pv.id_produk
    ) AS varian_data
        ON produk.id_produk = varian_data.id_produk
    $where_search
    ORDER BY produk.id_produk DESC"
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .product-page {
            max-width: 1500px
        }

        .page-heading {
            margin-bottom: 25px
        }

        .page-heading h1 {
            margin: 0 0 7px;
            color: #102f4b;
            font-size: 27px
        }

        .page-heading p {
            margin: 0;
            color: #7890a4;
            font-size: 14px
        }

        .product-card {
            background: #fff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 47, 75, .04)
        }

        .product-card-header {
            padding: 22px;
            border-bottom: 1px solid #e6edf2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px
        }

        .product-card-title h2 {
            margin: 0 0 6px;
            color: #102f4b;
            font-size: 18px
        }

        .product-card-title p {
            margin: 0;
            color: #7890a4;
            font-size: 13px
        }

        .product-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap
        }

        .product-search-form {
            display: flex;
            align-items: center;
            gap: 7px
        }

        .product-search-input {
            width: 260px;
            height: 40px;
            padding: 0 13px;
            border: 1px solid #dce5ec;
            border-radius: 8px;
            outline: none;
            color: #334f66;
            font-size: 13px;
            background: #fff
        }

        .product-search-input:focus {
            border-color: #315b78;
            box-shadow: 0 0 0 3px rgba(49, 91, 120, .08)
        }

        .btn-search-product,
        .btn-clear-search {
            height: 40px;
            padding: 0 13px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer
        }

        .btn-search-product {
            background: #102f4b;
            color: #fff
        }

        .btn-search-product:hover {
            background: #245b83
        }

        .btn-clear-search {
            background: #eef3f7;
            color: #506d84
        }

        .btn-clear-search:hover {
            background: #e3ebf0
        }

        .btn-add-product {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 11px 17px;
            background: #102f4b;
            color: #fff;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s ease
        }

        .btn-add-product:hover {
            background: #245b83
        }

        .search-result-info {
            padding: 14px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #e6edf2;
            color: #7890a4;
            font-size: 12px
        }

        .search-result-info strong {
            color: #102f4b
        }

        .product-table-wrapper {
            width: 100%;
            overflow-x: auto
        }

        .product-table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse
        }

        .product-table th {
            padding: 14px 17px;
            background: #f7f9fb;
            border-bottom: 1px solid #e3e9ee;
            color: #7890a4;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap
        }

        .product-table td {
            padding: 15px 17px;
            border-bottom: 1px solid #edf1f4;
            color: #405b70;
            font-size: 13px;
            vertical-align: middle
        }

        .product-table tbody tr:hover {
            background: #fafcfd
        }

        .product-id {
            color: #7890a4;
            font-weight: 600
        }

        .product-image,
        .no-product-image {
            width: 72px;
            height: 60px;
            border-radius: 8px
        }

        .product-image {
            object-fit: cover;
            border: 1px solid #e0e7ed;
            background: #f4f7fa
        }

        .no-product-image {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #f4f7fa;
            border: 1px solid #e0e7ed;
            color: #8a9dac;
            font-size: 10px;
            line-height: 1.3
        }

        .product-name {
            color: #102f4b;
            font-weight: 600;
            line-height: 1.4
        }

        .product-category {
            display: inline-block;
            padding: 5px 9px;
            background: #edf3f7;
            color: #315b78;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600
        }

        .product-price-range {
            display: block;
            color: #102f4b;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1.5
        }

        .product-price {
            color: #102f4b;
            font-weight: 600;
            white-space: nowrap
        }

        .product-price-variant {
            display: block;
            color: #8a9dac;
            font-size: 10px;
            margin-top: 4px
        }

        .product-stock {
            color: #102f4b;
            font-weight: 600;
            white-space: nowrap
        }

        .product-location {
            min-width: 190px
        }

        .product-location-main {
            display: flex;
            flex-direction: column;
            gap: 7px
        }

        .product-location-count {
            display: inline-flex;
            align-items: center;
            width: max-content;
            padding: 4px 8px;
            background: #eef3f7;
            color: #315b78;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold
        }

        .product-variant-location-list {
            display: flex;
            flex-direction: column;
            gap: 9px
        }

        .product-variant-location-item {
            display: flex;
            flex-direction: column;
            gap: 2px
        }

        .product-variant-name {
            color: #405b70;
            font-size: 11px;
            line-height: 1.3;
            word-break: break-word
        }

        .product-variant-rack {
            color: #102f4b;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3
        }

        .product-variant-rack-name {
            display: block;
            color: #8a9dac;
            font-size: 10px;
            font-weight: 400;
            line-height: 1.3
        }

        .product-variant-rack.empty {
            color: #a0acb5;
            font-weight: 400
        }

        .product-location-normal strong {
            color: #102f4b;
            display: block;
            margin-bottom: 3px
        }

        .product-location-normal small {
            color: #8a9dac;
            font-size: 11px
        }

        .location-empty {
            color: #a0acb5;
            font-size: 12px
        }

        .product-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap
        }

        .btn-edit-product,
        .btn-delete-product {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: .2s ease
        }

        .btn-edit-product {
            background: #102f4b;
            color: #fff
        }

        .btn-edit-product:hover {
            background: #245b83
        }

        .btn-delete-product {
            background: #fdeaea;
            color: #ad3d3d
        }

        .btn-delete-product:hover {
            background: #f8dada
        }

        .empty-product {
            padding: 55px 20px;
            text-align: center;
            color: #7890a4
        }

        .empty-product-icon {
            font-size: 35px;
            margin-bottom: 10px
        }

        @media(max-width:950px) {
            .product-card-header {
                flex-direction: column;
                align-items: flex-start
            }

            .product-header-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch
            }

            .product-search-form {
                width: 100%
            }

            .product-search-input {
                flex: 1;
                width: auto
            }

            .btn-add-product {
                width: 100%
            }
        }

        @media(max-width:700px) {
            .page-heading h1 {
                font-size: 23px
            }

            .product-search-form {
                flex-wrap: wrap
            }

            .product-search-input {
                width: 100%;
                flex: none
            }

            .btn-search-product,
            .btn-clear-search {
                flex: 1
            }
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php include "../includes/sidebar.php"; ?>
        <main class="admin-main">
            <?php include "../includes/navbar.php"; ?>
            <section class="admin-content product-page">
                <div class="page-heading">
                    <h1>Kelola Produk</h1>
                    <p>Kelola data produk ATK dan alat jahit.</p>
                </div>
                <div class="product-card">
                    <div class="product-card-header">
                        <div class="product-card-title">
                            <h2>Daftar Produk</h2>
                            <p>Produk yang tersedia di toko.</p>
                        </div>
                        <div class="product-header-actions">
                            <form method="GET" action="index.php" class="product-search-form">
                                <input type="text" name="search" class="product-search-input" placeholder="Cari produk, kategori, varian..." value="<?= htmlspecialchars($search); ?>">
                                <button type="submit" class="btn-search-product">Cari</button>
                                <?php if ($search !== ''): ?>
                                    <a href="index.php" class="btn-clear-search">Reset</a>
                                <?php endif; ?>
                            </form>
                            <a href="tambah.php" class="btn-add-product">+ Tambah Produk</a>
                        </div>
                    </div>
                    <?php if ($search !== ''): ?>
                        <div class="search-result-info">Hasil pencarian untuk <strong>"<?= htmlspecialchars($search); ?>"</strong></div>
                    <?php endif; ?>
                    <?php if ($query_produk && mysqli_num_rows($query_produk) > 0): ?>
                        <div class="product-table-wrapper">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Foto</th>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Lokasi / Rak</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($produk = mysqli_fetch_assoc($query_produk)): ?>
                                        <?php
                                        $harga_produk = (float)$produk['harga'];
                                        $harga_min = $produk['harga_min'] !== null ? (float)$produk['harga_min'] : null;
                                        $harga_max = $produk['harga_max'] !== null ? (float)$produk['harga_max'] : null;
                                        $jumlah_varian = (int)($produk['jumlah_varian'] ?? 0);
                                        $punya_varian = $jumlah_varian > 0;
                                        $data_lokasi_varian = [];
                                        if ($punya_varian && !empty($produk['data_lokasi_varian'])) {
                                            $data_varian = explode(';;;', $produk['data_lokasi_varian']);
                                            foreach ($data_varian as $data_item) {
                                                $parts = explode('|||', $data_item, 3);
                                                $nama_varian = trim($parts[0] ?? '');
                                                $lokasi_rak = trim($parts[1] ?? '');
                                                $nama_lokasi = trim($parts[2] ?? '');
                                                if ($nama_varian !== '') {
                                                    $data_lokasi_varian[] = [
                                                        'nama_varian' => $nama_varian,
                                                        'lokasi_rak' => $lokasi_rak,
                                                        'nama_lokasi' => $nama_lokasi
                                                    ];
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><span class="product-id">#<?= (int)$produk['id_produk']; ?></span></td>
                                            <td>
                                                <?php if (!empty($produk['gambar'])): ?>
                                                    <img src="../assets/images/products/<?= htmlspecialchars($produk['gambar']); ?>" alt="<?= htmlspecialchars($produk['nama_produk']); ?>" class="product-image">
                                                <?php else: ?>
                                                    <div class="no-product-image">Belum ada foto</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="product-name"><?= htmlspecialchars($produk['nama_produk']); ?></div>
                                            </td>
                                            <td><span class="product-category"><?= htmlspecialchars($produk['nama_kategori'] ?? '-'); ?></span></td>
                                            <td>
                                                <?php if ($punya_varian): ?>
                                                    <?php if ($harga_min == $harga_max): ?>
                                                        <span class="product-price-range">Rp <?= number_format($harga_min, 0, ',', '.'); ?></span>
                                                    <?php else: ?>
                                                        <span class="product-price-range">Rp <?= number_format($harga_min, 0, ',', '.'); ?> - Rp <?= number_format($harga_max, 0, ',', '.'); ?></span>
                                                    <?php endif; ?>
                                                    <small class="product-price-variant">Harga berdasarkan varian</small>
                                                <?php else: ?>
                                                    <span class="product-price">Rp <?= number_format($harga_produk, 0, ',', '.'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="product-stock"><?= number_format((int)$produk['stok']); ?></span></td>
                                            <td>
                                                <?php if ($punya_varian): ?>
                                                    <div class="product-location">
                                                        <div class="product-location-main">
                                                            <span class="product-location-count"><?= $jumlah_varian; ?> Varian</span>
                                                            <div class="product-variant-location-list">
                                                                <?php foreach ($data_lokasi_varian as $varian_lokasi): ?>
                                                                    <div class="product-variant-location-item">
                                                                        <span class="product-variant-name"><?= htmlspecialchars($varian_lokasi['nama_varian']); ?></span>
                                                                        <?php if ($varian_lokasi['lokasi_rak'] !== ''): ?>
                                                                            <span class="product-variant-rack">
                                                                                <?= htmlspecialchars($varian_lokasi['lokasi_rak']); ?>
                                                                                <?php if ($varian_lokasi['nama_lokasi'] !== ''): ?>
                                                                                    <span class="product-variant-rack-name"><?= htmlspecialchars($varian_lokasi['nama_lokasi']); ?></span>
                                                                                <?php endif; ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="product-variant-rack empty">Belum ditentukan</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php elseif (!empty($produk['kode_rak'])): ?>
                                                    <div class="product-location-normal">
                                                        <strong><?= htmlspecialchars($produk['kode_rak']); ?></strong>
                                                        <small><?= htmlspecialchars($produk['nama_lokasi']); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="location-empty">Belum ditentukan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="product-actions">
                                                    <a href="edit.php?id=<?= (int)$produk['id_produk']; ?>" class="btn-edit-product">Edit</a>
                                                    <a href="hapus.php?id=<?= (int)$produk['id_produk']; ?>" class="btn-delete-product" onclick="return confirm('Yakin ingin menghapus produk ini?');">Hapus</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-product">
                            <div class="empty-product-icon">📦</div>
                            <?php if ($search !== ''): ?>
                                Produk dengan kata kunci "<strong><?= htmlspecialchars($search); ?></strong>" tidak ditemukan.
                            <?php else: ?>
                                Belum ada data produk.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php include "../includes/footer.php"; ?>
        </main>
    </div>
    <script src="../assets/js/admin.js"></script>
</body>

</html>