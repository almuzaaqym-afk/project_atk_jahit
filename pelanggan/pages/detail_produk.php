
<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";

/* =========================================================
   AKSES PENGUNJUNG DAN PELANGGAN
========================================================= */

$is_logged_in = isset($_SESSION["id_pelanggan"]) &&
    (int) $_SESSION["id_pelanggan"] > 0;

$id_pelanggan = $is_logged_in ? (int) $_SESSION["id_pelanggan"] : 0;
$id_produk = (int) ($_GET["id"] ?? 0);

if ($id_produk <= 0) {
    header("Location: katalog.php");
    exit;
}

/* =========================================================
   ENDPOINT DATA PRODUK LIVE
========================================================= */

if (isset($_GET["live"]) && $_GET["live"] === "1") {
    header("Content-Type: application/json; charset=UTF-8");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.id_produk, p.id_kategori, p.nama_produk, p.deskripsi,
                p.harga, p.stok, p.gambar, k.nama_kategori
         FROM produk p
         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
         WHERE p.id_produk = ?
         LIMIT 1"
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal menyiapkan data produk."
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $produk_live = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$produk_live) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Produk tidak ditemukan."
        ]);
        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            v.id_varian,
            v.nama_varian,
            v.harga,
            v.stok,
            v.lokasi_rak,
            (
                SELECT g.nama_gambar
                FROM produk_varian_gambar g
                WHERE g.id_varian = v.id_varian
                ORDER BY g.urutan ASC, g.id_gambar_varian ASC
                LIMIT 1
            ) AS gambar_varian
         FROM produk_varian v
         WHERE v.id_produk = ?
           AND v.status = 'Aktif'
         ORDER BY v.id_varian ASC"
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal menyiapkan data varian."
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_produk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $varian_live = [];
    $total_stok_live = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $varian_live[] = [
            "id_varian" => (int) $row["id_varian"],
            "nama_varian" => $row["nama_varian"],
            "harga" => (float) $row["harga"],
            "stok" => (int) $row["stok"],
            "lokasi_rak" => $row["lokasi_rak"] ?? "",
            "gambar_varian" => $row["gambar_varian"] ?? ""
        ];

        $total_stok_live += (int) $row["stok"];
    }

    mysqli_stmt_close($stmt);

    $produk_live["id_produk"] = (int) $produk_live["id_produk"];
    $produk_live["id_kategori"] = (int) ($produk_live["id_kategori"] ?? 0);
    $produk_live["harga"] = (float) $produk_live["harga"];
    $produk_live["stok"] = count($varian_live) > 0
        ? $total_stok_live
        : (int) $produk_live["stok"];

    $produk_live["memiliki_varian"] = count($varian_live) > 0;

    echo json_encode([
        "success" => true,
        "product" => $produk_live,
        "variants" => $varian_live
    ], JSON_UNESCAPED_UNICODE |
       JSON_UNESCAPED_SLASHES |
       JSON_INVALID_UTF8_SUBSTITUTE);

    exit;
}

/* =========================================================
   PARAMETER HALAMAN
========================================================= */

$pesan_ulasan = $_GET["ulasan"] ?? "";
$status_keranjang = $_GET["status"] ?? "";
$error_keranjang = $_GET["error"] ?? "";

/* =========================================================
   DATA PRODUK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id_produk,
        p.id_kategori,
        p.nama_produk,
        p.deskripsi,
        p.harga,
        p.stok,
        p.gambar,
        p.lokasi_rak,
        k.nama_kategori
     FROM produk p
     LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
     WHERE p.id_produk = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: katalog.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$item) {
    header("Location: katalog.php");
    exit;
}

/* =========================================================
   DATA VARIAN PRODUK
========================================================= */

$varian = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        v.id_varian,
        v.nama_varian,
        v.harga,
        v.stok,
        v.lokasi_rak,
        (
            SELECT g.nama_gambar
            FROM produk_varian_gambar g
            WHERE g.id_varian = v.id_varian
            ORDER BY g.urutan ASC, g.id_gambar_varian ASC
            LIMIT 1
        ) AS gambar_varian
     FROM produk_varian v
     WHERE v.id_produk = ?
       AND v.status = 'Aktif'
     ORDER BY v.id_varian ASC"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$result_varian = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result_varian)) {
    $varian[] = [
        "id_varian" => (int) $row["id_varian"],
        "nama_varian" => $row["nama_varian"],
        "harga" => (float) $row["harga"],
        "stok" => (int) $row["stok"],
        "lokasi_rak" => $row["lokasi_rak"] ?? "",
        "gambar_varian" => $row["gambar_varian"] ?? ""
    ];
}

mysqli_stmt_close($stmt);

$memiliki_varian = !empty($varian);

/* =========================================================
   TOTAL STOK
========================================================= */

if ($memiliki_varian) {
    $stok = 0;

    foreach ($varian as $v) {
        $stok += (int) $v["stok"];
    }
} else {
    $stok = (int) $item["stok"];
}

/* =========================================================
   FAVORIT: KHUSUS PELANGGAN LOGIN
========================================================= */

$is_favorit = false;

if ($is_logged_in) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_favorit
         FROM favorit
         WHERE id_pelanggan = ?
           AND id_produk = ?
         LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id_pelanggan, $id_produk);
        mysqli_stmt_execute($stmt);
        $result_favorit = mysqli_stmt_get_result($stmt);

        $is_favorit = $result_favorit &&
            mysqli_num_rows($result_favorit) > 0;

        mysqli_stmt_close($stmt);
    }
}

/* =========================================================
   CEK RIWAYAT PEMBELIAN DAN ULASAN
========================================================= */

$sudah_membeli = false;
$id_pesanan_untuk_ulasan = 0;
$sudah_mengulas = false;

if ($is_logged_in) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT dp.id_pesanan
         FROM detail_pesanan dp
         INNER JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
         WHERE p.id_pelanggan = ?
           AND dp.id_produk = ?
         ORDER BY dp.id_pesanan DESC
         LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id_pelanggan, $id_produk);
        mysqli_stmt_execute($stmt);
        $result_beli = mysqli_stmt_get_result($stmt);
        $data_beli = $result_beli
            ? mysqli_fetch_assoc($result_beli)
            : null;

        if ($data_beli) {
            $sudah_membeli = true;
            $id_pesanan_untuk_ulasan = (int) $data_beli["id_pesanan"];
        }

        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_ulasan
         FROM ulasan
         WHERE id_produk = ?
           AND id_pelanggan = ?
         LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id_produk, $id_pelanggan);
        mysqli_stmt_execute($stmt);
        $result_cek_ulasan = mysqli_stmt_get_result($stmt);

        $sudah_mengulas = $result_cek_ulasan &&
            mysqli_num_rows($result_cek_ulasan) > 0;

        mysqli_stmt_close($stmt);
    }
}

/* =========================================================
   ULASAN PRODUK
========================================================= */

$ulasan = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        u.id_ulasan,
        u.rating,
        u.komentar,
        u.created_at,
        p.nama
     FROM ulasan u
     INNER JOIN pelanggan p ON u.id_pelanggan = p.id_pelanggan
     WHERE u.id_produk = ?
     ORDER BY u.created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$result_ulasan = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result_ulasan)) {
    $ulasan[] = $row;
}

mysqli_stmt_close($stmt);

$total_ulasan = count($ulasan);
$total_rating = 0;

foreach ($ulasan as $row) {
    $total_rating += (int) $row["rating"];
}

$rata_rating = $total_ulasan > 0
    ? $total_rating / $total_ulasan
    : 0;

$gambar_default = !empty($item["gambar"])
    ? "../../admin/assets/images/products/" . $item["gambar"]
    : "";

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($item["nama_produk"], ENT_QUOTES, "UTF-8"); ?>
        - Toko ATK &amp; Alat Jahit
    </title>

    <link rel="stylesheet" href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css">

    <style>
        .product-detail-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 40px;
            align-items: start;
        }

        .product-detail-image {
            position: relative;
            width: 100%;
            min-width: 0;
        }

        .product-detail-image img,
        .product-detail-image .no-image {
            width: 100%;
            max-width: 520px;
            height: 420px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
            margin: auto;
            background: #f5f7fa;
        }

        .product-detail-image .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9fb3c8;
        }

        .product-stock-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #c53030;
            color: #fff;
            padding: 8px 13px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
        }

        .product-detail-image.is-habis img {
            filter: grayscale(35%);
        }

        .product-detail-info {
            min-width: 0;
        }

        .product-detail-info h1 {
            color: #102a43;
            margin: 8px 0 12px;
            font-size: 32px;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .product-detail-info h2 {
            color: #102a43;
            margin: 0 0 18px;
            font-size: 24px;
            line-height: 1.3;
        }

        .product-detail-info p {
            color: #627d98;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .variant-wrapper {
            margin-top: 20px;
        }

        .variant-wrapper label {
            display: block;
            margin-bottom: 8px;
            color: #102a43;
            font-size: 13px;
            font-weight: bold;
        }

        .variant-select {
            width: 100%;
            min-height: 44px;
            padding: 11px 13px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            background: #fff;
            color: #102a43;
            font-size: 13px;
            outline: none;
        }

        .variant-select:focus {
            border-color: #102a43;
        }

        .variant-help {
            display: block;
            margin-top: 7px;
            color: #829ab1;
            font-size: 11px;
        }

        .variant-stock-info {
            margin-top: 8px;
            color: #627d98;
            font-size: 12px;
            line-height: 1.5;
        }

        .product-detail-stock {
            margin-top: 20px;
            padding: 12px 15px;
            background: #f5f7fa;
            border-radius: 8px;
            color: #627d98;
            font-size: 13px;
        }

        .product-detail-stock strong {
            color: #102a43;
        }

        .quantity-wrapper {
            margin-top: 20px;
        }

        .quantity-label {
            display: block;
            margin-bottom: 8px;
            color: #102a43;
            font-size: 13px;
            font-weight: bold;
        }

        .quantity-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            overflow: hidden;
        }

        .quantity-control button {
            width: 42px;
            height: 42px;
            border: 0;
            background: #f5f7fa;
            color: #102a43;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }

        .quantity-control button:hover {
            background: #e5ebf0;
        }

        .quantity-control button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .quantity-control input {
            width: 55px;
            height: 42px;
            border: 0;
            border-left: 1px solid #d9e2ec;
            border-right: 1px solid #d9e2ec;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: #102a43;
            outline: none;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .product-actions a {
            text-decoration: none;
        }

        .btn-beli-detail,
        .btn-primary,
        .btn-favorit,
        .btn-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            box-sizing: border-box;
        }

        .btn-beli-detail {
            background: #102a43;
            color: #fff;
        }

        .btn-beli-detail:hover {
            background: #183b56;
        }

        .btn-beli-detail.disabled,
        .btn-primary.disabled {
            background: #d9e2ec;
            color: #7b8794;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-favorit {
            gap: 7px;
            border: 1px solid #163653;
            background: #fff;
            color: #163653;
            font-weight: 600;
            font-size: 13px;
        }

        .btn-favorit.active {
            color: #e53935;
            border-color: #e53935;
            background: #fff5f5;
        }

        .btn-favorit .heart {
            font-size: 22px;
            line-height: 1;
        }

        .cart-alert {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 14px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            box-sizing: border-box;
        }

        .cart-alert-success {
            background: #eefbf3;
            border: 1px solid #c9ecd8;
            color: #26734d;
        }

        .cart-alert-error {
            background: #fff0f0;
            border: 1px solid #f3caca;
            color: #c53030;
        }

        .cart-alert-icon {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 27px;
            font-weight: bold;
            background: rgba(255,255,255,.75);
        }

        .cart-alert-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 180px;
        }

        .cart-alert-content strong {
            font-size: 13px;
        }

        .cart-alert-content span {
            font-size: 11px;
        }

        .cart-alert-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 7px;
            background: #102a43;
            color: #fff;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .review-section {
            width: 100%;
            max-width: 1200px;
            margin: 35px auto 0;
            padding: 0 20px 55px;
            box-sizing: border-box;
        }

        .review-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
        }

        .review-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .review-heading h2 {
            color: #102a43;
            font-size: 24px;
            margin: 0;
        }

        .review-summary {
            color: #627d98;
            font-size: 13px;
        }

        .review-rating-average {
            color: #f5a623;
            font-size: 18px;
            font-weight: bold;
        }

        .review-form {
            background: #f5f7fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .review-form h3 {
            color: #102a43;
            margin-bottom: 15px;
            font-size: 17px;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 3px;
            margin-bottom: 15px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            color: #d9e2ec;
            font-size: 30px;
            cursor: pointer;
        }

        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #f5a623;
        }

        .review-form textarea {
            width: 100%;
            min-height: 100px;
            resize: vertical;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            padding: 12px;
            font-family: Arial, sans-serif;
            outline: none;
            box-sizing: border-box;
        }

        .review-submit {
            margin-top: 12px;
            border: 0;
            cursor: pointer;
        }

        .review-item {
            padding: 18px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-user {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .review-user-name {
            color: #102a43;
            font-weight: bold;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .review-date {
            color: #9fb3c8;
            font-size: 11px;
            margin-top: 3px;
        }

        .review-stars {
            color: #f5a623;
            font-size: 16px;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .review-comment {
            color: #486581;
            font-size: 13px;
            line-height: 1.7;
            margin-top: 10px;
            overflow-wrap: anywhere;
        }

        .review-empty,
        .review-note,
        .review-success,
        .review-error {
            padding: 14px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
        }

        .review-empty {
            text-align: center;
            color: #7b8794;
        }

        .review-note {
            background: #eef4f8;
            color: #627d98;
            margin-bottom: 20px;
        }

        .review-success {
            background: #eefbf3;
            color: #26734d;
            margin-bottom: 20px;
        }

        .review-error {
            background: #fff0f0;
            color: #c53030;
            margin-bottom: 20px;
        }

        @media (max-width: 950px) {
            .product-detail-card {
                grid-template-columns: 1fr 1fr;
                gap: 25px;
            }

            .product-detail-image img,
            .product-detail-image .no-image {
                height: 330px;
            }

            .product-detail-info h1 {
                font-size: 27px;
            }

            .product-detail-info h2 {
                font-size: 21px;
            }

            .product-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .product-actions a {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .product-detail {
                margin-top: 20px;
            }

            .product-detail-card {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .product-detail-image img,
            .product-detail-image .no-image {
                max-width: none;
                height: 320px;
            }

            .product-detail-info h1 {
                font-size: 25px;
            }

            .product-detail-info h2 {
                font-size: 20px;
            }

            .product-detail-info p {
                font-size: 13px;
            }

            .product-actions {
                width: 100%;
                gap: 8px;
            }

            .cart-alert {
                margin: 15px;
                width: calc(100% - 30px);
            }

            .cart-alert-link {
                width: 100%;
            }

            .review-section {
                margin-top: 25px;
                padding: 0 15px 40px;
            }

            .review-box {
                padding: 18px;
            }

            .review-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .review-heading h2 {
                font-size: 21px;
            }

            .review-user {
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .product-detail {
                margin-top: 15px;
            }

            .product-detail-card {
                gap: 18px;
            }

            .product-detail-image img,
            .product-detail-image .no-image {
                height: 250px;
                border-radius: 10px;
            }

            .product-stock-badge {
                top: 10px;
                left: 10px;
                padding: 7px 10px;
                font-size: 10px;
            }

            .product-detail-info h1 {
                font-size: 22px;
            }

            .product-detail-info h2 {
                font-size: 19px;
            }

            .product-detail-info p {
                font-size: 12px;
            }

            .variant-select {
                font-size: 12px;
            }

            .product-detail-stock {
                font-size: 12px;
            }

            .quantity-control button {
                width: 41px;
                height: 41px;
            }

            .quantity-control input {
                width: 54px;
                height: 41px;
            }

            .review-section {
                padding-left: 10px;
                padding-right: 10px;
            }

            .review-box,
            .review-form {
                padding: 15px;
            }

            .rating-input label {
                font-size: 24px;
            }
        }

        @media (max-width: 360px) {
            .product-detail-image img,
            .product-detail-image .no-image {
                height: 220px;
            }

            .product-detail-info h1 {
                font-size: 20px;
            }

            .product-detail-info h2 {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

<?php
if ($is_logged_in) {
    require_once __DIR__ . "/../user/includes/navbar.php";
} else {
    require_once __DIR__ . "/../guest/includes/navbar.php";
}
?>

<main>

    <?php if ($is_logged_in && $status_keranjang === "keranjang"): ?>

        <div class="cart-alert cart-alert-success">
            <span class="cart-alert-icon">✓</span>
            <div class="cart-alert-content">
                <strong>Produk berhasil ditambahkan ke keranjang.</strong>
                <span>Kamu tetap berada di halaman detail produk.</span>
            </div>
            <a href="../user/keranjang.php" class="cart-alert-link">
                Lihat Keranjang
            </a>
        </div>

    <?php elseif ($is_logged_in && $error_keranjang !== ""): ?>

        <div class="cart-alert cart-alert-error">
            <span class="cart-alert-icon">!</span>
            <div class="cart-alert-content">

                <?php if ($error_keranjang === "pilih_varian"): ?>
                    <strong>Silakan pilih varian terlebih dahulu.</strong>
                    <span>Pilih varian produk sebelum memasukkannya ke keranjang.</span>

                <?php elseif ($error_keranjang === "varian_tidak_ditemukan"): ?>
                    <strong>Varian produk tidak ditemukan.</strong>
                    <span>Silakan pilih varian yang tersedia.</span>

                <?php elseif ($error_keranjang === "stok_habis"): ?>
                    <strong>Stok produk sedang habis.</strong>
                    <span>Produk tidak dapat dimasukkan ke keranjang.</span>

                <?php elseif ($error_keranjang === "stok_maksimal"): ?>
                    <strong>Jumlah stok maksimal sudah ada di keranjang.</strong>
                    <span>Kamu sudah memasukkan jumlah maksimal yang tersedia.</span>

                <?php else: ?>
                    <strong>Produk tidak dapat dimasukkan ke keranjang.</strong>
                    <span>Silakan coba lagi.</span>
                <?php endif; ?>

            </div>
        </div>

    <?php endif; ?>


    <!-- DETAIL PRODUK -->

    <section class="section product-detail">

        <div class="product-detail-card">

            <div
                class="product-detail-image <?= $stok <= 0 ? 'is-habis' : ''; ?>"
                id="productImageWrapper"
            >

                <?php if ($gambar_default !== ''): ?>

                    <img
                        id="productImage"
                        src="<?= htmlspecialchars($gambar_default, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?= htmlspecialchars($item["nama_produk"], ENT_QUOTES, 'UTF-8'); ?>"
                    >

                <?php else: ?>

                    <div class="no-image" id="productNoImage">
                        Tidak ada gambar
                    </div>

                <?php endif; ?>

                <div
                    class="product-stock-badge"
                    id="stockBadge"
                    style="<?= $stok <= 0 ? '' : 'display:none;'; ?>"
                >
                    HABIS
                </div>

            </div>


            <div class="product-detail-info">

                <span class="eyebrow" id="kategoriProduk">
                    <?= htmlspecialchars($item["nama_kategori"] ?? "Produk", ENT_QUOTES, "UTF-8"); ?>
                </span>

                <h1 id="namaProduk">
                    <?= htmlspecialchars($item["nama_produk"], ENT_QUOTES, "UTF-8"); ?>
                </h1>

                <h2 id="hargaProduk">
                    <?= $memiliki_varian
                        ? "Pilih varian"
                        : "Rp " . number_format((float) $item["harga"], 0, ",", "."); ?>
                </h2>

                <p id="deskripsiProduk" style="white-space:pre-line;">
                    <?= htmlspecialchars($item["deskripsi"] ?? "Tidak ada deskripsi produk.", ENT_QUOTES, "UTF-8"); ?>
                </p>


                <!-- VARIAN PRODUK -->

                <div
                    class="variant-wrapper"
                    id="variantWrapper"
                    style="<?= $memiliki_varian ? '' : 'display:none;'; ?>"
                >

                    <label for="varianProduk">Pilih Varian</label>

                    <select
                        id="varianProduk"
                        class="variant-select"
                        onchange="pilihVarian(this.value)"
                    >
                        <option value="">-- Pilih Varian --</option>

                        <?php foreach ($varian as $v): ?>
                            <option value="<?= (int) $v["id_varian"]; ?>">
                                <?= htmlspecialchars($v["nama_varian"], ENT_QUOTES, "UTF-8"); ?>
                                - Stok: <?= (int) $v["stok"]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <span class="variant-help">
                        Pilih varian untuk melihat harga dan stoknya.
                    </span>

                    <div class="variant-stock-info" id="variantStockInfo"></div>

                </div>


                <div class="product-detail-stock">
                    Stok:
                    <strong id="stokProduk">
                        <?= $memiliki_varian ? "Pilih varian" : $stok; ?>
                    </strong>
                </div>


                <!-- JUMLAH PRODUK -->

                <div
                    class="quantity-wrapper"
                    id="quantityWrapper"
                    style="<?= $memiliki_varian || $stok <= 0 ? 'opacity:.55;' : ''; ?>"
                >

                    <label for="jumlahProduk" class="quantity-label">
                        Jumlah
                    </label>

                    <div class="quantity-control">

                        <button
                            type="button"
                            onclick="kurangiJumlah()"
                            id="btnKurang"
                            <?= ($memiliki_varian || $stok <= 0) ? 'disabled' : ''; ?>
                        >
                            −
                        </button>

                        <input
                            type="number"
                            id="jumlahProduk"
                            value="1"
                            min="1"
                            max="<?= $memiliki_varian ? 1 : max(1, $stok); ?>"
                            readonly
                            <?= ($memiliki_varian || $stok <= 0) ? 'disabled' : ''; ?>
                        >

                        <button
                            type="button"
                            onclick="tambahJumlah()"
                            id="btnTambah"
                            <?= ($memiliki_varian || $stok <= 0) ? 'disabled' : ''; ?>
                        >
                            +
                        </button>

                    </div>

                </div>


                <!-- AKSI PELANGGAN -->

                <div class="product-actions">

                    <?php if ($is_logged_in): ?>

                        <a
                            href="javascript:void(0)"
                            id="btnBeli"
                            class="btn-beli-detail <?= ($memiliki_varian || $stok <= 0) ? 'disabled' : ''; ?>"
                            aria-disabled="<?= ($memiliki_varian || $stok <= 0) ? 'true' : 'false'; ?>"
                        >
                            Beli Sekarang
                        </a>

                        <a
                            href="javascript:void(0)"
                            id="btnKeranjang"
                            class="btn-primary <?= ($memiliki_varian || $stok <= 0) ? 'disabled' : ''; ?>"
                            aria-disabled="<?= ($memiliki_varian || $stok <= 0) ? 'true' : 'false'; ?>"
                        >
                            Masukkan Keranjang
                        </a>

                        <?php if ($is_favorit): ?>

                            <a
                                href="../actions/hapus_favorit.php?id=<?= $id_produk; ?>"
                                class="btn-favorit active"
                            >
                                <span class="heart">♥</span> Favorit
                            </a>

                        <?php else: ?>

                            <a
                                href="../actions/tambah_favorit.php?id=<?= $id_produk; ?>"
                                class="btn-favorit"
                            >
                                <span class="heart">♡</span> Favorit
                            </a>

                        <?php endif; ?>

                    <?php else: ?>

                        <a href="../login.php" class="btn-beli-detail">
                            Login untuk Membeli
                        </a>

                        <a href="../login.php" class="btn-primary">
                            Login untuk Keranjang
                        </a>

                        <a href="../login.php" class="btn-favorit">
                            <span class="heart">♡</span>
                            Login untuk Favorit
                        </a>

                    <?php endif; ?>

                </div>

                <?php if (!$is_logged_in): ?>
                    <p class="variant-help">
                        Kamu dapat melihat detail produk tanpa login.
                        Login diperlukan untuk membeli, menyimpan favorit,
                        dan menggunakan keranjang.
                    </p>
                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- ULASAN PRODUK -->

    <section class="review-section">

        <div class="review-box">

            <div class="review-heading">
                <div>
                    <h2>Ulasan Pelanggan</h2>

                    <div class="review-summary">

                        <?php if ($total_ulasan > 0): ?>

                            <span class="review-rating-average">
                                ★ <?= number_format($rata_rating, 1); ?>
                            </span>
                            &nbsp;
                            <?= $total_ulasan; ?> ulasan

                        <?php else: ?>

                            Belum ada ulasan

                        <?php endif; ?>

                    </div>
                </div>
            </div>


            <?php if ($is_logged_in && $pesan_ulasan === "berhasil"): ?>

                <div class="review-success">
                    Ulasan berhasil ditambahkan. Terima kasih atas ulasan kamu.
                </div>

            <?php elseif ($is_logged_in && $pesan_ulasan === "sudah"): ?>

                <div class="review-error">
                    Kamu sudah memberikan ulasan untuk produk ini.
                </div>

            <?php elseif ($is_logged_in && $pesan_ulasan === "gagal"): ?>

                <div class="review-error">
                    Ulasan gagal disimpan. Silakan coba lagi.
                </div>

            <?php endif; ?>


            <?php if (!$is_logged_in): ?>

                <div class="review-note">
                    Login terlebih dahulu dan beli produk ini untuk memberikan ulasan.
                </div>

            <?php elseif ($sudah_membeli && !$sudah_mengulas): ?>

                <div class="review-form">

                    <h3>Berikan Ulasan Kamu</h3>

                    <form action="../actions/tambah_ulasan.php" method="POST">

                        <input
                            type="hidden"
                            name="id_produk"
                            value="<?= $id_produk; ?>"
                        >

                        <input
                            type="hidden"
                            name="id_pesanan"
                            value="<?= $id_pesanan_untuk_ulasan; ?>"
                        >

                        <div class="rating-input">

                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5">★</label>

                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4">★</label>

                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3">★</label>

                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2">★</label>

                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1">★</label>

                        </div>

                        <textarea
                            name="komentar"
                            placeholder="Tulis pengalaman kamu menggunakan produk ini..."
                            maxlength="1000"
                        ></textarea>

                        <button
                            type="submit"
                            class="btn-primary review-submit"
                        >
                            Kirim Ulasan
                        </button>

                    </form>

                </div>

            <?php elseif (!$sudah_membeli): ?>

                <div class="review-note">
                    Kamu harus membeli produk ini terlebih dahulu untuk memberikan ulasan.
                </div>

            <?php elseif ($sudah_mengulas): ?>

                <div class="review-note">
                    Kamu sudah memberikan ulasan untuk produk ini.
                </div>

            <?php endif; ?>


            <div class="review-list">

                <?php if ($total_ulasan > 0): ?>

                    <?php foreach ($ulasan as $review): ?>

                        <div class="review-item">

                            <div class="review-user">

                                <div>
                                    <div class="review-user-name">
                                        <?= htmlspecialchars($review["nama"], ENT_QUOTES, "UTF-8"); ?>
                                    </div>

                                    <div class="review-date">
                                        <?= date("d M Y", strtotime($review["created_at"])); ?>
                                    </div>
                                </div>

                                <div class="review-stars">
                                    <?= str_repeat("★", (int) $review["rating"]); ?>
                                    <span style="color:#d9e2ec;">
                                        <?= str_repeat("★", 5 - (int) $review["rating"]); ?>
                                    </span>
                                </div>

                            </div>

                            <?php if (!empty($review["komentar"])): ?>

                                <div class="review-comment">
                                    <?= nl2br(htmlspecialchars($review["komentar"], ENT_QUOTES, "UTF-8")); ?>
                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="review-empty">
                        Belum ada ulasan untuk produk ini.<br>
                        Jadilah pelanggan pertama yang memberikan ulasan.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>


<script>
/* =========================================================
   DETAIL PRODUK DAN PILIHAN VARIAN
========================================================= */

const idProduk = <?= $id_produk; ?>;
const baseImagePath = "../../admin/assets/images/products/";

let memilikiVarian = <?= $memiliki_varian ? "true" : "false"; ?>;

let gambarDefault = <?= json_encode(
    $gambar_default,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
); ?>;

let namaProduk = <?= json_encode(
    $item["nama_produk"],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
); ?>;

let varianProduk = <?= json_encode(
    $varian,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
); ?>;

let stokProdukAktif = <?= max(0, $stok); ?>;
let stokAktif = memilikiVarian ? 0 : stokProdukAktif;
let idVarianAktif = 0;
let sinkronSedangBerjalan = false;

const jumlahInput = document.getElementById("jumlahProduk");
const btnBeli = document.getElementById("btnBeli");
const btnKeranjang = document.getElementById("btnKeranjang");
const btnKurang = document.getElementById("btnKurang");
const btnTambah = document.getElementById("btnTambah");
const hargaProduk = document.getElementById("hargaProduk");
const stokProduk = document.getElementById("stokProduk");
const variantStockInfo = document.getElementById("variantStockInfo");
const variantSelect = document.getElementById("varianProduk");
const variantWrapper = document.getElementById("variantWrapper");
const quantityWrapper = document.getElementById("quantityWrapper");
const productImageWrapper = document.getElementById("productImageWrapper");
const stockBadge = document.getElementById("stockBadge");
const namaProdukEl = document.getElementById("namaProduk");
const kategoriProdukEl = document.getElementById("kategoriProduk");
const deskripsiProdukEl = document.getElementById("deskripsiProduk");


function formatRupiah(number) {
    return "Rp " + Number(number).toLocaleString("id-ID");
}


function ubahGambar(src) {
    let image = document.getElementById("productImage");
    const noImage = document.getElementById("productNoImage");

    if (src) {
        if (!image) {
            image = document.createElement("img");
            image.id = "productImage";
            productImageWrapper.prepend(image);
        }

        image.src = src;
        image.alt = namaProduk;
        image.style.display = "block";

        if (noImage) {
            noImage.style.display = "none";
        }

    } else {
        if (image) {
            image.style.display = "none";
        }

        if (noImage) {
            noImage.style.display = "flex";
        } else {
            const placeholder = document.createElement("div");
            placeholder.id = "productNoImage";
            placeholder.className = "no-image";
            placeholder.textContent = "Tidak ada gambar";
            productImageWrapper.prepend(placeholder);
        }
    }
}


function updateLink() {
    if (!jumlahInput) return;

    let jumlah = parseInt(jumlahInput.value, 10) || 1;

    if (jumlah < 1) jumlah = 1;
    if (stokAktif > 0 && jumlah > stokAktif) jumlah = stokAktif;

    jumlahInput.value = jumlah;

    let params = "id=" + encodeURIComponent(idProduk) +
        "&jumlah=" + encodeURIComponent(jumlah);

    if (memilikiVarian && idVarianAktif > 0) {
        params += "&id_varian=" + encodeURIComponent(idVarianAktif);
    }

    if (btnBeli) {
        btnBeli.href = "../actions/beli_sekarang.php?" + params;
    }

    if (btnKeranjang) {
        btnKeranjang.href = "../actions/tambah_keranjang.php?" + params;
    }
}


function updateTombol() {
    const tersedia =
        stokAktif > 0 &&
        (!memilikiVarian || idVarianAktif > 0);

    if (jumlahInput) {
        jumlahInput.disabled = !tersedia;
        jumlahInput.max = tersedia ? stokAktif : 1;

        if (!tersedia) {
            jumlahInput.value = 1;
        }
    }

    if (btnKurang) btnKurang.disabled = !tersedia;
    if (btnTambah) btnTambah.disabled = !tersedia;

    [btnBeli, btnKeranjang].forEach(function (button) {
        if (!button) return;

        button.classList.toggle("disabled", !tersedia);
        button.setAttribute("aria-disabled", tersedia ? "false" : "true");

        if (!tersedia) {
            button.href = "javascript:void(0)";
        }
    });

    if (quantityWrapper) {
        quantityWrapper.style.opacity = tersedia ? "1" : ".55";
    }

    if (stockBadge) {
        stockBadge.style.display =
            (!memilikiVarian || idVarianAktif > 0) && stokAktif <= 0
                ? "block"
                : "none";
    }

    if (productImageWrapper) {
        productImageWrapper.classList.toggle(
            "is-habis",
            (!memilikiVarian || idVarianAktif > 0) && stokAktif <= 0
        );
    }

    if (tersedia) {
        updateLink();
    }
}


function pilihVarian(idVarian, pertahankanJumlah = false) {
    idVarianAktif = parseInt(idVarian, 10) || 0;

    if (!idVarianAktif) {
        stokAktif = 0;

        if (hargaProduk) {
            hargaProduk.textContent = memilikiVarian
                ? "Pilih varian"
                : formatRupiah(window.hargaProdukDasar || 0);
        }

        if (stokProduk) {
            stokProduk.textContent = memilikiVarian
                ? "Pilih varian"
                : stokProdukAktif;
        }

        if (variantStockInfo) {
            variantStockInfo.textContent = memilikiVarian
                ? "Pilih varian untuk melihat harga dan stok."
                : "";
        }

        ubahGambar(gambarDefault);
        updateTombol();
        return;
    }

    const variant = varianProduk.find(function (item) {
        return Number(item.id_varian) === idVarianAktif;
    });

    if (!variant) return;

    stokAktif = Math.max(0, parseInt(variant.stok, 10) || 0);

    if (hargaProduk) {
        hargaProduk.textContent = formatRupiah(variant.harga);
    }

    if (stokProduk) {
        stokProduk.textContent = stokAktif;
    }

    if (variantStockInfo) {
        variantStockInfo.textContent = "Stok varian: " + stokAktif;
    }

    if (variant.gambar_varian) {
        ubahGambar(
            baseImagePath + "variants/" +
            encodeURIComponent(variant.gambar_varian)
        );
    } else {
        ubahGambar(gambarDefault);
    }

    if (jumlahInput && !pertahankanJumlah) {
        jumlahInput.value = 1;
    }

    updateTombol();
}


function tambahJumlah() {
    if (!jumlahInput || stokAktif <= 0) return;

    let jumlah = parseInt(jumlahInput.value, 10) || 1;

    if (jumlah < stokAktif) {
        jumlah++;
    }

    jumlahInput.value = jumlah;
    updateLink();
}


function kurangiJumlah() {
    if (!jumlahInput || stokAktif <= 0) return;

    let jumlah = parseInt(jumlahInput.value, 10) || 1;

    if (jumlah > 1) {
        jumlah--;
    }

    jumlahInput.value = jumlah;
    updateLink();
}


/* =========================================================
   PERBARUI PILIHAN VARIAN DARI DATA LIVE
========================================================= */

function perbaruiPilihanVarian(varianBaru) {
    const pilihanSaatIni = idVarianAktif ||
        (variantSelect ? parseInt(variantSelect.value, 10) : 0) ||
        0;

    varianProduk = Array.isArray(varianBaru) ? varianBaru : [];
    memilikiVarian = varianProduk.length > 0;

    if (variantWrapper) {
        variantWrapper.style.display = memilikiVarian ? "" : "none";
    }

    if (variantSelect) {
        const optionDefault = document.createElement("option");
        optionDefault.value = "";
        optionDefault.textContent = "-- Pilih Varian --";

        variantSelect.replaceChildren(optionDefault);

        varianProduk.forEach(function (variant) {
            const option = document.createElement("option");
            option.value = variant.id_varian;
            option.textContent =
                variant.nama_varian + " - Stok: " + variant.stok;

            variantSelect.appendChild(option);
        });
    }

    const varianMasihAda = varianProduk.some(function (variant) {
        return Number(variant.id_varian) === pilihanSaatIni;
    });

    if (memilikiVarian) {
        if (varianMasihAda) {
            if (variantSelect) {
                variantSelect.value = String(pilihanSaatIni);
            }

            pilihVarian(pilihanSaatIni, true);

        } else {
            idVarianAktif = 0;

            if (variantSelect) {
                variantSelect.value = "";
            }

            stokAktif = 0;

            if (hargaProduk) hargaProduk.textContent = "Pilih varian";
            if (stokProduk) stokProduk.textContent = "Pilih varian";

            if (variantStockInfo) {
                variantStockInfo.textContent =
                    "Pilih varian untuk melihat harga dan stok.";
            }

            ubahGambar(gambarDefault);
            updateTombol();
        }

    } else {
        idVarianAktif = 0;
        stokAktif = stokProdukAktif;

        if (hargaProduk) {
            hargaProduk.textContent =
                formatRupiah(window.hargaProdukDasar || 0);
        }

        if (stokProduk) {
            stokProduk.textContent = stokProdukAktif;
        }

        if (variantStockInfo) {
            variantStockInfo.textContent = "";
        }

        ubahGambar(gambarDefault);
        updateTombol();
    }
}


/* =========================================================
   SINKRONISASI DATA PRODUK TANPA RELOAD
========================================================= */

async function sinkronkanDetailProduk() {
    if (sinkronSedangBerjalan || document.hidden) {
        return;
    }

    sinkronSedangBerjalan = true;

    try {
        const liveUrl = new URL(
            window.location.pathname,
            window.location.origin
        );

        liveUrl.searchParams.set("id", String(idProduk));
        liveUrl.searchParams.set("live", "1");
        liveUrl.searchParams.set("_", String(Date.now()));

        const response = await fetch(liveUrl.toString(), {
            method: "GET",
            cache: "no-store",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Cache-Control": "no-cache",
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!response.ok) {
            console.error(
                "Sinkronisasi detail produk gagal. HTTP:",
                response.status
            );
            return;
        }

        const contentType =
            response.headers.get("content-type") || "";

        if (!contentType.includes("application/json")) {
            console.error("Respons data live bukan JSON.");
            return;
        }

        const data = await response.json();

        if (!data.success || !data.product) {
            console.error(
                "Data live produk tidak valid:",
                data.message || data
            );
            return;
        }

        const produkTerbaru = data.product;

        window.hargaProdukDasar =
            Number(produkTerbaru.harga) || 0;

        namaProduk = produkTerbaru.nama_produk || namaProduk;

        if (namaProdukEl) {
            namaProdukEl.textContent = namaProduk;
        }

        document.title =
            namaProduk + " - Toko ATK & Alat Jahit";

        if (kategoriProdukEl) {
            kategoriProdukEl.textContent =
                produkTerbaru.nama_kategori || "Produk";
        }

        if (deskripsiProdukEl) {
            deskripsiProdukEl.textContent =
                produkTerbaru.deskripsi ||
                "Tidak ada deskripsi produk.";
        }

        gambarDefault = produkTerbaru.gambar
            ? baseImagePath +
              encodeURIComponent(produkTerbaru.gambar)
            : "";

        stokProdukAktif = Math.max(
            0,
            Number(produkTerbaru.stok) || 0
        );

        perbaruiPilihanVarian(data.variants || []);

        if (!memilikiVarian) {
            stokAktif = stokProdukAktif;

            if (hargaProduk) {
                hargaProduk.textContent =
                    formatRupiah(window.hargaProdukDasar);
            }

            if (stokProduk) {
                stokProduk.textContent = stokProdukAktif;
            }

            updateTombol();
        }

    } catch (error) {
        console.error(
            "Sinkronisasi detail produk gagal:",
            error
        );

    } finally {
        sinkronSedangBerjalan = false;
    }
}


/* =========================================================
   JALANKAN
========================================================= */

window.hargaProdukDasar = <?= json_encode((float) $item["harga"]); ?>;

updateTombol();
sinkronkanDetailProduk();

window.setInterval(sinkronkanDetailProduk, 5000);

document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
        sinkronkanDetailProduk();
    }
});
</script>

<?php
if ($is_logged_in) {
    require_once __DIR__ . "/../user/includes/footer.php";
} else {
    require_once __DIR__ . "/../guest/includes/footer.php";
}
?>

</body>
</html>
