<?php
session_start();

if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int)$_SESSION["id_pelanggan"];
$id_pesanan = (int)($_GET["id"] ?? 0);

if ($id_pesanan <= 0) {
    header("Location: pesanan.php");
    exit;
}

/* AMBIL DATA PESANAN */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        p.catatan,
        p.id_voucher,
        p.kode_voucher,
        p.diskon_voucher
    FROM pesanan p
    WHERE p.id_pesanan = ?
      AND p.id_pelanggan = ?
    LIMIT 1"
);

if (!$stmt) {
    header("Location: pesanan.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $id_pesanan, $id_pelanggan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    header("Location: pesanan.php");
    exit;
}

/* DETAIL PRODUK */
$detail_produk = [];
$subtotal_pesanan = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        dp.id_detail,
        dp.id_produk,
        dp.id_varian,
        dp.nama_varian,
        dp.jumlah,
        dp.harga,
        dp.subtotal,
        pr.nama_produk,
        pr.gambar,
        k.nama_kategori,
        (
            SELECT g.nama_gambar
            FROM produk_varian_gambar g
            WHERE g.id_varian = dp.id_varian
            ORDER BY g.urutan ASC, g.id_gambar_varian ASC
            LIMIT 1
        ) AS gambar_varian
    FROM detail_pesanan dp
    LEFT JOIN produk pr
        ON dp.id_produk = pr.id_produk
    LEFT JOIN kategori k
        ON pr.id_kategori = k.id_kategori
    WHERE dp.id_pesanan = ?
    ORDER BY dp.id_detail ASC"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
    mysqli_stmt_execute($stmt);

    $result_detail = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result_detail)) {
        $detail_produk[] = $row;
        $subtotal_pesanan += (float)($row["subtotal"] ?? 0);
    }

    mysqli_stmt_close($stmt);
}

/* DATA VOUCHER */
$id_voucher = (int)($pesanan["id_voucher"] ?? 0);
$kode_voucher = trim($pesanan["kode_voucher"] ?? "");
$diskon_voucher = (float)($pesanan["diskon_voucher"] ?? 0);

/* STATUS PESANAN */
function classStatusDetail($status)
{
    switch ($status) {
        case "Menunggu Pembayaran":
            return "status-menunggu";
        case "Menunggu Verifikasi":
            return "status-verifikasi";
        case "Diproses":
            return "status-proses";
        case "Siap Diambil":
            return "status-siap";
        case "Selesai":
            return "status-selesai";
        case "Dibatalkan":
            return "status-batal";
        default:
            return "status-default";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#102a43">
    <title>Detail Pesanan | Toko ATK &amp; Alat Jahit</title>

    <style>
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;width:100%;max-width:100%;overflow-x:hidden}
        body{min-width:0}
        .section{width:100%;max-width:1200px;margin:0 auto;padding:38px 20px 60px}
        .detail-order-page{width:100%;max-width:1000px;margin:auto}
        .detail-order-header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:25px}
        .detail-order-header h1{color:#102a43;margin:8px 0;font-size:30px}
        .detail-order-header p{color:#627d98;font-size:13px;line-height:1.6;margin:0}
        .eyebrow{color:#627d98;font-size:12px;font-weight:700;letter-spacing:1px}
        .btn-back-order{display:inline-flex;justify-content:center;align-items:center;min-height:40px;padding:10px 16px;background:#eef1f5;color:#102a43;text-decoration:none;border-radius:8px;font-size:12px;font-weight:700;text-align:center}
        .btn-back-order:hover{background:#e3e8ed}
        .detail-order-card{min-width:0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;margin-bottom:20px;overflow:hidden}
        .detail-order-card-header{padding:20px 22px;border-bottom:1px solid #edf0f2}
        .detail-order-card-header h2{color:#102a43;margin:0;font-size:18px}
        .detail-order-card-body{padding:22px}
        .order-detail-info{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .order-detail-item{min-width:0;padding:14px;background:#f7f9fb;border-radius:9px;overflow-wrap:anywhere}
        .order-detail-item>span{display:block;color:#7b8794;font-size:11px;margin-bottom:5px}
        .order-detail-item>strong{color:#102a43;font-size:14px}
        .detail-status{display:inline-block;padding:7px 12px;border-radius:20px;font-size:11px;font-weight:700;line-height:1.5}
        .status-menunggu{background:#fff4d6;color:#8a6500}
        .status-verifikasi{background:#fff0d9;color:#9a5b00}
        .status-proses{background:#e8f1ff;color:#245b9e}
        .status-siap{background:#e8f8ef;color:#28734a}
        .status-selesai{background:#dff6e8;color:#1f6b40}
        .status-batal{background:#fde8e8;color:#b83232}
        .status-default{background:#edf0f2;color:#687681}
        .pickup-box{margin-top:20px;padding:18px;background:#f5f8fb;border:1px solid #dfe7ed;border-radius:10px}
        .pickup-box h3{color:#102a43;font-size:15px;margin:0 0 6px}
        .pickup-box p{color:#627d98;font-size:13px;margin:0;line-height:1.7}
        .btn-location,.btn-chat-order{display:inline-flex;justify-content:center;align-items:center;max-width:100%;margin-top:14px;padding:11px 15px;background:#102a43;color:#fff;text-decoration:none;border-radius:8px;font-size:12px;font-weight:700;line-height:1.5;text-align:center;transition:background .2s}
        .btn-chat-order{margin-top:12px}
        .btn-location:hover,.btn-chat-order:hover{background:#183b56}
        .order-note{margin-top:15px;padding:14px;background:#fffaf0;border:1px solid #f0dfb0;border-radius:9px}
        .order-note strong{display:block;color:#8a6500;font-size:12px;margin-bottom:5px}
        .order-note p{color:#6b5a32;font-size:13px;line-height:1.6;margin:0;overflow-wrap:anywhere}
        .product-order-item{display:flex;gap:18px;align-items:center;padding:18px 0;border-bottom:1px solid #edf0f2}
        .product-order-item:last-child{border-bottom:none}
        .product-order-image{width:90px;height:90px;flex-shrink:0;border-radius:10px;background:#f5f7fa;border:1px solid #e1e7ec;overflow:hidden}
        .product-order-image img{width:100%;height:100%;object-fit:contain}
        .product-order-info{flex:1;min-width:0}
        .product-order-category{color:#7b8794;font-size:11px;margin-bottom:4px}
        .product-order-name{color:#102a43;font-size:16px;font-weight:700;margin-bottom:7px;overflow-wrap:anywhere}
        .product-order-variant{display:inline-block;margin:0 0 8px;padding:5px 9px;background:#eef4f8;color:#486581;border-radius:6px;font-size:11px;font-weight:600;overflow-wrap:anywhere}
        .product-order-price{color:#627d98;font-size:13px;line-height:1.8}
        .product-order-price strong{color:#102a43}
        .product-order-subtotal{color:#102a43;font-size:15px;font-weight:700;white-space:nowrap;text-align:right;overflow-wrap:anywhere}
        .product-order-subtotal small{display:block;color:#829ab1;font-size:10px;font-weight:400;margin-bottom:4px}
        .order-summary-box{margin-top:20px;padding:20px;background:#f7f9fb;border-radius:12px;border:1px solid #e1e7ec}
        .order-summary-title{color:#102a43;font-size:15px;font-weight:700;margin-bottom:15px}
        .order-summary-row{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:9px 0}
        .order-summary-row span{color:#627d98;font-size:13px}
        .order-summary-row strong{color:#102a43;font-size:14px;text-align:right;overflow-wrap:anywhere}
        .voucher-summary{margin:12px 0;padding:12px 14px;background:#eef8f2;border:1px solid #cce8d7;border-radius:9px}
        .voucher-summary-label{color:#28734a;font-size:11px;font-weight:700;margin-bottom:4px}
        .voucher-summary-code{color:#102a43;font-size:14px;font-weight:700;overflow-wrap:anywhere}
        .voucher-summary-discount{color:#28734a;font-size:13px;font-weight:700;margin-top:5px}
        .no-voucher{margin:12px 0;padding:12px 14px;background:#f5f7f9;border:1px solid #e1e7ec;border-radius:9px;color:#829ab1;font-size:12px;line-height:1.6}
        .order-total-box{border-top:2px solid #102a43;margin-top:20px;padding-top:20px}
        .order-total-row{display:flex;justify-content:space-between;align-items:center;gap:20px}
        .order-total-row span{color:#102a43;font-size:15px;font-weight:700}
        .order-total-row strong{color:#102a43;font-size:25px;text-align:right;overflow-wrap:anywhere}
        .discount-text{color:#28734a!important}
        .empty{padding:25px;text-align:center;color:#627d98;background:#f7f9fb;border-radius:10px;line-height:1.6}

        @media(max-width:700px){
            .section{padding:28px 15px 45px}
            .detail-order-header{flex-direction:column;gap:15px}
            .detail-order-header h1{font-size:26px}
            .detail-order-card-header{padding:17px}
            .detail-order-card-body{padding:17px}
            .order-detail-info{gap:12px}
            .product-order-item{align-items:flex-start;gap:13px}
            .product-order-image{width:75px;height:75px}
            .product-order-subtotal{font-size:13px}
            .pickup-box{padding:15px}
            .order-summary-box{padding:16px}
        }

        @media(max-width:500px){
            .section{padding:24px 12px 35px}
            .detail-order-header h1{font-size:24px}
            .detail-order-card{border-radius:12px}
            .detail-order-card-header{padding:15px}
            .detail-order-card-header h2{font-size:16px}
            .detail-order-card-body{padding:15px}
            .order-detail-info{grid-template-columns:minmax(0,1fr);gap:10px}
            .order-detail-item{padding:12px}
            .product-order-item{display:grid;grid-template-columns:65px minmax(0,1fr);gap:12px}
            .product-order-image{width:65px;height:65px}
            .product-order-name{font-size:14px}
            .product-order-price{font-size:12px}
            .product-order-subtotal{grid-column:2;text-align:left;white-space:normal;border-top:1px solid #edf0f2;padding-top:10px}
            .product-order-subtotal small{display:inline;margin-right:5px}
            .order-summary-box{padding:14px}
            .order-summary-row{gap:12px;align-items:flex-start}
            .order-summary-row span,.order-summary-row strong{font-size:12px}
            .order-total-row{align-items:flex-start;gap:12px}
            .order-total-row span{font-size:13px}
            .order-total-row strong{font-size:19px}
            .btn-back-order,.btn-location,.btn-chat-order{width:100%}
        }
    </style>
</head>

<body>
<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<main>
<section class="section">
<div class="detail-order-page">

    <div class="detail-order-header">
        <div>
            <span class="eyebrow">DETAIL PESANAN</span>
            <h1>Detail Pesanan</h1>
            <p>Informasi lengkap pesanan kamu.</p>
        </div>
        <a href="pesanan.php" class="btn-back-order">← Kembali ke Pesanan</a>
    </div>

    <div class="detail-order-card">
        <div class="detail-order-card-header">
            <h2>Informasi Pesanan</h2>
        </div>

        <div class="detail-order-card-body">
            <div class="order-detail-info">
                <div class="order-detail-item">
                    <span>Nomor Pesanan</span>
                    <strong><?= htmlspecialchars($pesanan["nomor_pesanan"], ENT_QUOTES, "UTF-8"); ?></strong>
                </div>

                <div class="order-detail-item">
                    <span>Tanggal Pesanan</span>
                    <strong><?= !empty($pesanan["tanggal_pesanan"]) ? date("d M Y, H:i", strtotime($pesanan["tanggal_pesanan"])) : "-"; ?></strong>
                </div>

                <div class="order-detail-item">
                    <span>Status Pesanan</span>
                    <strong>
                        <span class="detail-status <?= classStatusDetail($pesanan["status_pesanan"]); ?>">
                            <?= htmlspecialchars($pesanan["status_pesanan"], ENT_QUOTES, "UTF-8"); ?>
                        </span>
                    </strong>
                </div>

                <div class="order-detail-item">
                    <span>Metode Pembayaran</span>
                    <strong>QRIS</strong>
                </div>
            </div>

            <div class="pickup-box">
                <h3>Pengambilan Pesanan</h3>
                <p>
                    Pesanan diambil langsung di toko setelah status berubah menjadi
                    <strong>Siap Diambil</strong>. Pastikan status pesanan sudah sesuai sebelum datang ke toko.
                </p>

                <a href="https://maps.app.goo.gl/retvodNQKMfwRy7X7" target="_blank" rel="noopener noreferrer" class="btn-location">
                    📍 Buka Lokasi Toko di Google Maps
                </a>

                <?php if (in_array($pesanan["status_pesanan"], ["Diproses", "Siap Diambil"], true)): ?>
                    <br>
                    <a href="chat_pesanan.php?id=<?= $id_pesanan; ?>" class="btn-chat-order">
                        💬 Live Chat Pesanan
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($pesanan["catatan"])): ?>
                <div class="order-note">
                    <strong>Catatan</strong>
                    <p><?= htmlspecialchars($pesanan["catatan"], ENT_QUOTES, "UTF-8"); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="detail-order-card">
        <div class="detail-order-card-header">
            <h2>Produk Pesanan</h2>
        </div>

        <div class="detail-order-card-body">
            <?php if (!empty($detail_produk)): ?>
                <?php foreach ($detail_produk as $item): ?>
                    <?php
                    $harga = (float)($item["harga"] ?? 0);
                    $jumlah = (int)($item["jumlah"] ?? 0);
                    $subtotal = (float)($item["subtotal"] ?? ($harga * $jumlah));
                    $id_varian_item = (int)($item["id_varian"] ?? 0);
                    $nama_varian_item = trim($item["nama_varian"] ?? "");
                    $gambar_item = "";
                    $gambar_path = "";

                    if ($id_varian_item > 0 && !empty($item["gambar_varian"])) {
                        $gambar_item = $item["gambar_varian"];
                        $gambar_path = "../../admin/assets/images/products/variants/" . $gambar_item;
                    } elseif (!empty($item["gambar"])) {
                        $gambar_item = $item["gambar"];
                        $gambar_path = "../../admin/assets/images/products/" . $gambar_item;
                    }
                    ?>

                    <div class="product-order-item">
                        <div class="product-order-image">
                            <?php if ($gambar_item !== ""): ?>
                                <img
                                    src="<?= htmlspecialchars($gambar_path, ENT_QUOTES, "UTF-8"); ?>"
                                    alt="<?= htmlspecialchars($item["nama_produk"] ?? "Produk", ENT_QUOTES, "UTF-8"); ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9fb3c8;font-size:10px;text-align:center;padding:6px;">
                                    Tidak Ada Gambar
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="product-order-info">
                            <div class="product-order-category">
                                <?= htmlspecialchars($item["nama_kategori"] ?? "Produk", ENT_QUOTES, "UTF-8"); ?>
                            </div>

                            <div class="product-order-name">
                                <?= htmlspecialchars($item["nama_produk"] ?? "Produk", ENT_QUOTES, "UTF-8"); ?>
                            </div>

                            <?php if ($id_varian_item > 0 && $nama_varian_item !== ""): ?>
                                <div class="product-order-variant">
                                    Varian: <?= htmlspecialchars($nama_varian_item, ENT_QUOTES, "UTF-8"); ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-order-price">
                                Harga satuan: <strong>Rp <?= number_format($harga, 0, ",", "."); ?></strong><br>
                                Jumlah: <strong><?= $jumlah; ?> item</strong>
                            </div>
                        </div>

                        <div class="product-order-subtotal">
                            <small>Subtotal</small>
                            Rp <?= number_format($subtotal, 0, ",", "."); ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="order-summary-box">
                    <div class="order-summary-title">Ringkasan Harga</div>

                    <div class="order-summary-row">
                        <span>Subtotal Produk</span>
                        <strong>Rp <?= number_format($subtotal_pesanan, 0, ",", "."); ?></strong>
                    </div>

                    <?php if ($id_voucher > 0 || $kode_voucher !== "" || $diskon_voucher > 0): ?>
                        <div class="voucher-summary">
                            <div class="voucher-summary-label">VOUCHER / PROMO</div>
                            <div class="voucher-summary-code">
                                <?= htmlspecialchars($kode_voucher !== "" ? $kode_voucher : "Voucher", ENT_QUOTES, "UTF-8"); ?>
                            </div>
                            <div class="voucher-summary-discount">
                                Hemat Rp <?= number_format($diskon_voucher, 0, ",", "."); ?>
                            </div>
                        </div>

                        <div class="order-summary-row">
                            <span>Diskon Voucher</span>
                            <strong class="discount-text">
                                - Rp <?= number_format($diskon_voucher, 0, ",", "."); ?>
                            </strong>
                        </div>
                    <?php else: ?>
                        <div class="no-voucher">
                            Tidak ada voucher atau promo yang digunakan.
                        </div>
                    <?php endif; ?>

                    <div class="order-total-box">
                        <div class="order-total-row">
                            <span>Total Pembayaran</span>
                            <strong>Rp <?= number_format((float)$pesanan["total_harga"], 0, ",", "."); ?></strong>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty">Belum ada detail produk pada pesanan ini.</div>
            <?php endif; ?>
        </div>
    </div>

</div>
</section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<script>
/* SINKRONISASI DETAIL PESANAN OTOMATIS */
(function () {
    const detailContainer = document.querySelector(".detail-order-page");

    if (!detailContainer) {
        return;
    }

    let sedangMemuat = false;

    async function sinkronDetailPesanan() {
        if (
            sedangMemuat ||
            document.visibilityState !== "visible"
        ) {
            return;
        }

        sedangMemuat = true;

        try {
            const url = new URL(window.location.href);
            url.searchParams.set("_live_detail", Date.now().toString());

            const response = await fetch(url.toString(), {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!response.ok) {
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const halamanBaru = parser.parseFromString(html, "text/html");
            const detailBaru = halamanBaru.querySelector(".detail-order-page");

            if (!detailBaru) {
                return;
            }

            if (
                detailContainer.innerHTML.trim() !==
                detailBaru.innerHTML.trim()
            ) {
                detailContainer.innerHTML = detailBaru.innerHTML;
            }
        } catch (error) {
            console.error("Gagal menyinkronkan detail pesanan:", error);
        } finally {
            sedangMemuat = false;
        }
    }

    setInterval(sinkronDetailPesanan, 4000);
})();
</script>
</body>
</html>