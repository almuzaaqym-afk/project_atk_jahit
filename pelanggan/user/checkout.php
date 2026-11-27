<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/koneksi.php";
if (!isset($_SESSION['id_pelanggan'])) { header("Location: ../login.php"); exit; }

/* =========================================================
   TENTUKAN SUMBER CHECKOUT
   - checkout.php?cart=1 = selalu dari keranjang
   - mode langsung = dari Beli Sekarang
========================================================= */
$force_cart_checkout = isset($_GET['cart']) && $_GET['cart'] === '1';
if ($force_cart_checkout) {
    $checkout_mode = 'keranjang';
    $_SESSION['checkout_mode'] = 'keranjang';
    unset($_SESSION['checkout_items']);
    unset($_SESSION['voucher_checkout']);
} else {
    $checkout_mode = $_SESSION['checkout_mode'] ?? 'keranjang';
}

$voucher_error = '';
$voucher_success = '';
$produk_checkout = [];
$total = 0;
$source_items = [];

/* =========================================================
   SIAPKAN ITEM CHECKOUT
========================================================= */
if ($checkout_mode === 'langsung' && !empty($_SESSION['checkout_items'])) {
    $source_items = $_SESSION['checkout_items'];
} else {
    $checkout_mode = 'keranjang';
    $_SESSION['checkout_mode'] = 'keranjang';
    $keranjang = $_SESSION['keranjang'] ?? [];
    $keranjang_varian = $_SESSION['keranjang_varian'] ?? [];

    foreach ($keranjang as $id_produk => $jumlah) {
        $id_produk = (int)$id_produk;
        $jumlah = (int)$jumlah;

        if ($id_produk > 0 && $jumlah > 0) {
            $source_items[] = [
                'id_produk' => $id_produk,
                'id_varian' => 0,
                'jumlah' => $jumlah
            ];
        }
    }

    foreach ($keranjang_varian as $id_varian => $jumlah) {
        $id_varian = (int)$id_varian;
        $jumlah = (int)$jumlah;

        if ($id_varian <= 0 || $jumlah <= 0) continue;

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_produk
             FROM produk_varian
             WHERE id_varian = ? AND status = 'Aktif'
             LIMIT 1"
        );

        if (!$stmt) continue;

        mysqli_stmt_bind_param($stmt, "i", $id_varian);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row) {
            $source_items[] = [
                'id_produk' => (int)$row['id_produk'],
                'id_varian' => $id_varian,
                'jumlah' => $jumlah
            ];
        }
    }

    $_SESSION['checkout_items'] = $source_items;
}

if (empty($source_items)) {
    header("Location: keranjang.php");
    exit;
}

/* =========================================================
   AMBIL DATA PRODUK TERBARU
========================================================= */
foreach ($source_items as $item) {
    $id_produk = (int)($item['id_produk'] ?? 0);
    $id_varian = (int)($item['id_varian'] ?? 0);
    $jumlah = (int)($item['jumlah'] ?? 0);

    if ($id_produk <= 0 || $jumlah <= 0) continue;

    if ($id_varian > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                p.id_produk,
                p.nama_produk,
                p.deskripsi,
                p.gambar,
                k.nama_kategori,
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
             FROM produk p
             INNER JOIN produk_varian v ON v.id_produk = p.id_produk
             LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
             WHERE p.id_produk = ?
               AND v.id_varian = ?
               AND v.status = 'Aktif'
             LIMIT 1"
        );

        if (!$stmt) continue;
        mysqli_stmt_bind_param($stmt, "ii", $id_produk, $id_varian);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                p.id_produk,
                p.nama_produk,
                p.deskripsi,
                p.harga,
                p.stok,
                p.gambar,
                k.nama_kategori
             FROM produk p
             LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
             WHERE p.id_produk = ?
             LIMIT 1"
        );

        if (!$stmt) continue;
        mysqli_stmt_bind_param($stmt, "i", $id_produk);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$produk) continue;

    $stok = (int)$produk['stok'];
    if ($stok <= 0) continue;
    if ($jumlah > $stok) $jumlah = $stok;
    if ($jumlah < 1) continue;

    $harga = (float)$produk['harga'];
    $subtotal = $harga * $jumlah;

    $produk['id_varian'] = $id_varian;
    $produk['nama_varian'] = $id_varian > 0
        ? ($produk['nama_varian'] ?? '')
        : '';
    $produk['gambar_checkout'] = (
        $id_varian > 0 && !empty($produk['gambar_varian'])
    )
        ? $produk['gambar_varian']
        : ($produk['gambar'] ?? '');
    $produk['jumlah'] = $jumlah;
    $produk['harga'] = $harga;
    $produk['stok'] = $stok;
    $produk['subtotal'] = $subtotal;

    $produk_checkout[] = $produk;
    $total += $subtotal;
}

if (empty($produk_checkout)) {
    unset($_SESSION['checkout_items']);
    header("Location: keranjang.php");
    exit;
}

/* =========================================================
   SIMPAN CHECKOUT ITEMS TERBARU
========================================================= */
$_SESSION['checkout_items'] = array_map(function ($p) {
    return [
        'id_produk' => (int)$p['id_produk'],
        'id_varian' => (int)$p['id_varian'],
        'nama_varian' => $p['nama_varian'] ?? '',
        'harga' => (float)$p['harga'],
        'jumlah' => (int)$p['jumlah']
    ];
}, $produk_checkout);

/* =========================================================
   HAPUS VOUCHER
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_voucher'])) {
    unset($_SESSION['voucher_checkout']);
    $redirect = $checkout_mode === 'keranjang'
        ? 'checkout.php?cart=1'
        : 'checkout.php';
    header("Location: $redirect");
    exit;
}

/* =========================================================
   PAKAI VOUCHER
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pakai_voucher'])) {
    $kode_voucher = strtoupper(trim($_POST['kode_voucher'] ?? ''));

    if ($kode_voucher === '') {
        $voucher_error = "Silakan masukkan kode voucher.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT
                id_voucher,
                kode_voucher,
                nama_voucher,
                jenis_diskon,
                nilai_diskon,
                minimal_belanja,
                maksimal_diskon,
                tanggal_mulai,
                tanggal_berakhir,
                kuota,
                jumlah_digunakan,
                status
             FROM voucher
             WHERE kode_voucher = ?
             LIMIT 1"
        );

        $voucher = null;

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $kode_voucher);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $voucher = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
        }

        if (!$voucher) {
            $voucher_error = "Kode voucher tidak ditemukan.";
        } elseif ($voucher['status'] !== 'Aktif') {
            $voucher_error = "Voucher sedang tidak aktif.";
        } elseif (strtotime($voucher['tanggal_mulai']) > time()) {
            $voucher_error = "Voucher belum dapat digunakan.";
        } elseif (strtotime($voucher['tanggal_berakhir']) < time()) {
            $voucher_error = "Voucher sudah kedaluwarsa.";
        } elseif (
            (int)$voucher['kuota'] > 0 &&
            (int)$voucher['jumlah_digunakan'] >= (int)$voucher['kuota']
        ) {
            $voucher_error = "Kuota voucher sudah habis.";
        } elseif ($total < (float)$voucher['minimal_belanja']) {
            $voucher_error = "Minimal belanja untuk voucher ini adalah Rp " .
                number_format($voucher['minimal_belanja'], 0, ',', '.') . ".";
        } else {
            if ($voucher['jenis_diskon'] === 'Persen') {
                $diskon_voucher = $total * ((float)$voucher['nilai_diskon'] / 100);

                if (
                    $voucher['maksimal_diskon'] !== null &&
                    $voucher['maksimal_diskon'] !== '' &&
                    (float)$voucher['maksimal_diskon'] > 0
                ) {
                    $diskon_voucher = min(
                        $diskon_voucher,
                        (float)$voucher['maksimal_diskon']
                    );
                }
            } else {
                $diskon_voucher = (float)$voucher['nilai_diskon'];
            }

            $diskon_voucher = max(0, min($diskon_voucher, $total));

            $_SESSION['voucher_checkout'] = [
                'id_voucher' => (int)$voucher['id_voucher'],
                'kode_voucher' => $voucher['kode_voucher'],
                'nama_voucher' => $voucher['nama_voucher'],
                'jenis_diskon' => $voucher['jenis_diskon'],
                'nilai_diskon' => (float)$voucher['nilai_diskon'],
                'minimal_belanja' => (float)$voucher['minimal_belanja'],
                'maksimal_diskon' => $voucher['maksimal_diskon'] !== null
                    ? (float)$voucher['maksimal_diskon']
                    : null,
                'diskon' => $diskon_voucher
            ];

            $voucher_success = "Voucher berhasil digunakan.";
        }
    }
}

/* =========================================================
   VALIDASI VOUCHER DARI SESSION
========================================================= */
$voucher_data = $_SESSION['voucher_checkout'] ?? null;
$diskon_voucher = 0;

if (is_array($voucher_data) && !empty($voucher_data['id_voucher'])) {
    $id_voucher = (int)$voucher_data['id_voucher'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            id_voucher,
            kode_voucher,
            nama_voucher,
            jenis_diskon,
            nilai_diskon,
            minimal_belanja,
            maksimal_diskon,
            tanggal_mulai,
            tanggal_berakhir,
            kuota,
            jumlah_digunakan,
            status
         FROM voucher
         WHERE id_voucher = ?
         LIMIT 1"
    );

    $voucher_check = null;

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_voucher);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $voucher_check = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    $voucher_valid = $voucher_check
        && $voucher_check['status'] === 'Aktif'
        && strtotime($voucher_check['tanggal_mulai']) <= time()
        && strtotime($voucher_check['tanggal_berakhir']) >= time()
        && (
            (int)$voucher_check['kuota'] <= 0 ||
            (int)$voucher_check['jumlah_digunakan'] < (int)$voucher_check['kuota']
        )
        && $total >= (float)$voucher_check['minimal_belanja'];

    if (!$voucher_valid) {
        unset($_SESSION['voucher_checkout']);
        $voucher_data = null;
    } else {
        if ($voucher_check['jenis_diskon'] === 'Persen') {
            $diskon_voucher = $total * ((float)$voucher_check['nilai_diskon'] / 100);
        } else {
            $diskon_voucher = (float)$voucher_check['nilai_diskon'];
        }

        if (
            $voucher_check['maksimal_diskon'] !== null &&
            $voucher_check['maksimal_diskon'] !== '' &&
            (float)$voucher_check['maksimal_diskon'] > 0
        ) {
            $diskon_voucher = min(
                $diskon_voucher,
                (float)$voucher_check['maksimal_diskon']
            );
        }

        $diskon_voucher = max(0, min($diskon_voucher, $total));
        $_SESSION['voucher_checkout']['diskon'] = $diskon_voucher;
        $voucher_data = $_SESSION['voucher_checkout'];
    }
}

$total_setelah_diskon = max(0, $total - $diskon_voucher);
$jumlah_total_produk = 0;

foreach ($produk_checkout as $produk) {
    $jumlah_total_produk += (int)$produk['jumlah'];
}

$back_url = ($checkout_mode === 'langsung' && count($produk_checkout) === 1)
    ? "../pages/detail_produk.php?id=" . (int)$produk_checkout[0]['id_produk']
    : "keranjang.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#102a43">
    <title>Checkout Pesanan | Toko ATK &amp; Alat Jahit</title>
    <style>
    .checkout-page{max-width:1200px;margin:auto;padding:55px 20px 70px}
    .checkout-header{text-align:center;margin-bottom:35px}
    .checkout-header h1{color:#102a43;font-size:34px;margin:8px 0 10px}
    .checkout-header p{color:#627d98;font-size:14px}
    .checkout-steps{max-width:850px;margin:0 auto 40px;display:flex;align-items:center;justify-content:center}
    .checkout-step{display:flex;align-items:center;gap:10px;color:#9fb3c8;min-width:145px}
    .checkout-step.active,.checkout-step.completed{color:#102a43}
    .step-number{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#e8edf2;color:#627d98;font-weight:bold;font-size:13px}
    .checkout-step.active .step-number,.checkout-step.completed .step-number{background:#102a43;color:#fff}
    .checkout-step strong{display:block;font-size:13px}
    .checkout-step small{display:block;margin-top:2px;font-size:10px;color:#9fb3c8}
    .checkout-step.active small,.checkout-step.completed small{color:#627d98}
    .step-line{width:70px;height:2px;background:#e5e9ed;margin:0 12px}
    .step-line.active{background:#102a43}
    .checkout-layout{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(300px,.9fr);gap:25px;align-items:start}
    .checkout-products{background:#fff;border:1px solid #e5e9ed;border-radius:16px;overflow:hidden;box-shadow:0 5px 20px rgba(16,42,67,.05)}
    .checkout-card-header{padding:24px 25px;border-bottom:1px solid #edf0f3;display:flex;justify-content:space-between;align-items:center}
    .checkout-card-header h2{color:#102a43;font-size:20px;margin-bottom:4px}
    .checkout-card-header p{color:#829ab1;font-size:12px}
    .checkout-product{display:grid;grid-template-columns:115px 1fr auto;gap:20px;align-items:center;padding:23px 25px;border-bottom:1px solid #edf0f3}
    .checkout-product-image{width:115px;height:115px;border-radius:12px;background:#f5f7fa;overflow:hidden;display:flex;align-items:center;justify-content:center}
    .checkout-product-image img{width:100%;height:100%;object-fit:contain}
    .no-product-image{text-align:center;color:#9fb3c8;font-size:10px;padding:10px}
    .checkout-category{display:inline-block;padding:5px 9px;background:#eef3f7;color:#486581;border-radius:5px;font-size:10px;font-weight:bold;margin-bottom:7px}
    .checkout-product-info h3{color:#102a43;font-size:17px;margin-bottom:6px}
    .checkout-variant{display:inline-block;margin-bottom:8px;padding:5px 8px;border-radius:6px;background:#eefbf3;color:#26734d;font-size:11px}
    .checkout-description{color:#829ab1;font-size:12px;line-height:1.5;margin-bottom:13px}
    .checkout-product-bottom{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
    .checkout-price{color:#102a43;font-weight:bold;font-size:14px}
    .checkout-price span{color:#829ab1;font-size:10px;font-weight:normal}
    .checkout-quantity{color:#627d98;font-size:12px;padding-left:15px;border-left:1px solid #d9e2ec}
    .checkout-quantity strong{color:#102a43;margin-left:3px}
    .checkout-subtotal{text-align:right;min-width:125px}
    .checkout-subtotal small{display:block;color:#829ab1;font-size:10px;margin-bottom:5px}
    .checkout-subtotal strong{display:block;color:#102a43;font-size:15px}
    .back-cart{display:inline-block;margin:20px 25px;color:#486581;font-size:12px;text-decoration:none;font-weight:bold}
    .checkout-summary{background:#fff;border:1px solid #e5e9ed;border-radius:16px;padding:25px;box-shadow:0 5px 20px rgba(16,42,67,.07);position:sticky;top:90px}
    .summary-header{display:flex;align-items:center;justify-content:space-between;gap:15px}
    .summary-header h2{color:#102a43;font-size:19px}
    .summary-header span{background:#eef3f7;color:#486581;padding:5px 9px;border-radius:5px;font-size:10px}
    .summary-divider{height:1px;background:#e5e9ed;margin:20px 0}
    .summary-row{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:15px;font-size:13px}
    .summary-row span{color:#627d98}
    .summary-row strong{color:#102a43;text-align:right}
    .discount-row strong{color:#26734d}
    .pickup-text{color:#26734d!important;font-size:12px}
    .voucher-section{margin-top:5px;padding:15px;background:#f8fafb;border:1px solid #e5e9ed;border-radius:10px}
    .voucher-title{color:#102a43;font-size:12px;margin-bottom:10px}
    .voucher-form{display:flex;gap:7px}
    .voucher-form input{min-width:0;flex:1;padding:10px 11px;border:1px solid #d9e2ec;border-radius:7px;outline:none;color:#102a43;font-size:11px;background:#fff}
    .voucher-form button{padding:10px 13px;border:none;border-radius:7px;background:#102a43;color:#fff;font-size:11px;font-weight:bold;cursor:pointer;white-space:nowrap}
    .voucher-active{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px;background:#fff;border:1px solid #d9e2ec;border-radius:8px}
    .voucher-code{color:#102a43;font-weight:bold;font-size:12px;word-break:break-word}
    .voucher-name{color:#829ab1;font-size:9px;margin-top:3px}
    .voucher-remove{border:none;background:#fce8e8;color:#b23b3b;border-radius:6px;padding:7px 9px;font-size:9px;font-weight:bold;cursor:pointer}
    .voucher-discount{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px;font-size:11px}
    .voucher-discount span{color:#627d98}
    .voucher-discount strong{color:#26734d}
    .voucher-message{margin-top:8px;padding:8px 10px;border-radius:6px;font-size:9px;line-height:1.4}
    .voucher-message.error{background:#fde7e7;color:#b23b3b}
    .voucher-message.success{background:#e8f5ed;color:#26734d}
    .summary-total{display:flex;align-items:center;justify-content:space-between;gap:15px}
    .summary-total span{display:block;color:#102a43;font-weight:bold;font-size:14px}
    .summary-total small{display:block;color:#9fb3c8;font-size:9px;margin-top:4px}
    .summary-total>strong{color:#102a43;font-size:21px;white-space:nowrap}
    .checkout-info{display:flex;gap:12px;padding:13px;background:#f5f8fa;border-radius:10px;margin:22px 0}
    .info-icon{width:27px;height:27px;border-radius:50%;background:#102a43;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:bold}
    .checkout-info strong{display:block;color:#102a43;font-size:11px;margin-bottom:3px}
    .checkout-info p{color:#829ab1;font-size:10px;line-height:1.5}
    .checkout-payment-button{width:100%;display:flex;align-items:center;justify-content:center;gap:10px;padding:14px 18px;border-radius:9px;background:#102a43;color:#fff;text-decoration:none;font-size:13px;font-weight:bold}
    .checkout-payment-button:hover{background:#183b56}
    .checkout-payment-button span{font-size:17px}
    .checkout-cancel{display:block;text-align:center;margin-top:13px;color:#829ab1;text-decoration:none;font-size:11px}
    @media(max-width:900px){
        .checkout-layout{grid-template-columns:1fr}
        .checkout-summary{position:static}
    }
    @media(max-width:700px){
        .checkout-page{padding:35px 15px 50px}
        .checkout-header h1{font-size:28px}
        .checkout-steps{justify-content:flex-start;overflow-x:auto;padding-bottom:5px}
        .checkout-step{min-width:125px}
        .step-line{width:35px;margin:0 7px}
        .checkout-product{grid-template-columns:85px 1fr;gap:15px;padding:18px}
        .checkout-product-image{width:85px;height:85px}
        .checkout-subtotal{grid-column:2;text-align:left;border-top:1px solid #edf0f3;padding-top:12px;width:100%}
    }
    @media(max-width:480px){
        .checkout-card-header{padding:20px}
        .checkout-product{grid-template-columns:75px 1fr}
        .checkout-product-image{width:75px;height:75px}
        .checkout-product-info h3{font-size:14px}
        .checkout-description{display:none}
        .checkout-product-bottom{gap:8px;flex-direction:column;align-items:flex-start}
        .checkout-quantity{padding-left:0;border-left:none}
        .checkout-subtotal{grid-column:1/-1}
        .summary-total{align-items:flex-start;flex-direction:column}
        .summary-total>strong{font-size:23px}
        .voucher-form{flex-direction:column}
        .voucher-form button{width:100%}
    }
    </style>
</head>
<body>
<?php require_once __DIR__ . "/includes/navbar.php"; ?>
<main>
    <section class="checkout-page">
        <div class="checkout-header">
            <span class="eyebrow">CHECKOUT</span>
            <h1>Checkout Pesanan</h1>
            <p>Periksa kembali produk sebelum melanjutkan ke pembayaran.</p>
        </div>

        <div class="checkout-steps">
            <div class="checkout-step completed">
                <div class="step-number">✓</div>
                <div>
                    <strong><?= $checkout_mode === 'langsung' ? 'Produk' : 'Keranjang'; ?></strong>
                    <small>Produk dipilih</small>
                </div>
            </div>
            <div class="step-line active"></div>
            <div class="checkout-step active">
                <div class="step-number">2</div>
                <div>
                    <strong>Checkout</strong>
                    <small>Periksa pesanan</small>
                </div>
            </div>
            <div class="step-line"></div>
            <div class="checkout-step">
                <div class="step-number">3</div>
                <div>
                    <strong>Pembayaran</strong>
                    <small>Bayar dengan QRIS</small>
                </div>
            </div>
        </div>

        <div class="checkout-layout">
            <div class="checkout-products">
                <div class="checkout-card-header">
                    <div>
                        <h2>Produk yang Dibeli</h2>
                        <p><?= count($produk_checkout); ?> produk dalam pesanan</p>
                    </div>
                </div>

                <?php foreach ($produk_checkout as $produk): ?>
                    <div class="checkout-product">
                        <div class="checkout-product-image">
                            <?php if (!empty($produk['gambar_checkout'])): ?>
                                <?php
                                $gambar_checkout = $produk['id_varian'] > 0
                                    ? "../../admin/assets/images/products/variants/" . $produk['gambar_checkout']
                                    : "../../admin/assets/images/products/" . $produk['gambar_checkout'];
                                ?>
                                <img
                                    src="<?= htmlspecialchars($gambar_checkout); ?>"
                                    alt="<?= htmlspecialchars($produk['nama_produk']); ?>"
                                >
                            <?php else: ?>
                                <div class="no-product-image">Tidak Ada Gambar</div>
                            <?php endif; ?>
                        </div>

                        <div class="checkout-product-info">
                            <span class="checkout-category">
                                <?= htmlspecialchars($produk['nama_kategori'] ?? 'Produk'); ?>
                            </span>
                            <h3><?= htmlspecialchars($produk['nama_produk']); ?></h3>

                            <?php if (!empty($produk['nama_varian'])): ?>
                                <div class="checkout-variant">
                                    Varian:
                                    <strong><?= htmlspecialchars($produk['nama_varian']); ?></strong>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($produk['deskripsi'])): ?>
                                <p class="checkout-description">
                                    <?= htmlspecialchars(mb_strimwidth($produk['deskripsi'], 0, 100, '...')); ?>
                                </p>
                            <?php endif; ?>

                            <div class="checkout-product-bottom">
                                <div class="checkout-price">
                                    Rp <?= number_format($produk['harga'], 0, ',', '.'); ?>
                                    <span>/ produk</span>
                                </div>
                                <div class="checkout-quantity">
                                    Jumlah: <strong><?= (int)$produk['jumlah']; ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-subtotal">
                            <small>Subtotal</small>
                            <strong>Rp <?= number_format($produk['subtotal'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>

                <a href="<?= htmlspecialchars($back_url); ?>" class="back-cart">← Kembali</a>
            </div>

            <aside class="checkout-summary">
                <div class="summary-header">
                    <h2>Ringkasan Pesanan</h2>
                    <span><?= count($produk_checkout); ?> produk</span>
                </div>
                <div class="summary-divider"></div>

                <div class="summary-row">
                    <span>Total Produk</span>
                    <strong><?= $jumlah_total_produk; ?> item</strong>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong>
                </div>

                <div class="voucher-section">
                    <div class="voucher-title"><strong>Voucher / Promo</strong></div>

                    <?php if ($voucher_data !== null): ?>
                        <div class="voucher-active">
                            <div class="voucher-active-info">
                                <div class="voucher-code"><?= htmlspecialchars($voucher_data['kode_voucher']); ?></div>
                                <div class="voucher-name"><?= htmlspecialchars($voucher_data['nama_voucher']); ?></div>
                            </div>
                            <form method="POST">
                                <button type="submit" name="hapus_voucher" class="voucher-remove">Hapus</button>
                            </form>
                        </div>
                        <div class="voucher-discount">
                            <span>Diskon Voucher</span>
                            <strong>- Rp <?= number_format($diskon_voucher, 0, ',', '.'); ?></strong>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="voucher-form">
                            <input
                                type="text"
                                name="kode_voucher"
                                placeholder="Masukkan kode voucher"
                                autocomplete="off"
                                maxlength="50"
                            >
                            <button type="submit" name="pakai_voucher">Pakai</button>
                        </form>

                        <?php if ($voucher_error !== ''): ?>
                            <div class="voucher-message error"><?= htmlspecialchars($voucher_error); ?></div>
                        <?php endif; ?>

                        <?php if ($voucher_success !== ''): ?>
                            <div class="voucher-message success"><?= htmlspecialchars($voucher_success); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="summary-divider"></div>

                <?php if ($diskon_voucher > 0): ?>
                    <div class="summary-row discount-row">
                        <span>Diskon Voucher</span>
                        <strong>- Rp <?= number_format($diskon_voucher, 0, ',', '.'); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Pengambilan</span>
                    <strong class="pickup-text">Pickup di Toko</strong>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-total">
                    <div>
                        <span>Total Pembayaran</span>
                        <small>
                            <?= $diskon_voucher > 0
                                ? 'Voucher sudah diterapkan'
                                : 'Belum termasuk biaya tambahan'; ?>
                        </small>
                    </div>
                    <strong>Rp <?= number_format($total_setelah_diskon, 0, ',', '.'); ?></strong>
                </div>

                <div class="checkout-info">
                    <div class="info-icon">✓</div>
                    <div>
                        <strong>Pembayaran Aman</strong>
                        <p>Pembayaran dilakukan melalui QRIS pada tahap berikutnya.</p>
                    </div>
                </div>

                <a href="pembayaran.php" class="checkout-payment-button">
                    Lanjut Pembayaran <span>→</span>
                </a>
                <a href="<?= htmlspecialchars($back_url); ?>" class="checkout-cancel">Kembali</a>
            </aside>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>