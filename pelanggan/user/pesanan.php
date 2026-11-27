<?php
session_start();

if (!isset($_SESSION["id_pelanggan"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int)$_SESSION["id_pelanggan"];

/* AMBIL PESANAN PELANGGAN */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        p.catatan,
        COUNT(dp.id_pesanan) AS jumlah_item
    FROM pesanan p
    LEFT JOIN detail_pesanan dp
        ON p.id_pesanan = dp.id_pesanan
    WHERE p.id_pelanggan = ?
    GROUP BY
        p.id_pesanan,
        p.nomor_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        p.status_pesanan,
        p.catatan
    ORDER BY
        p.tanggal_pesanan DESC,
        p.id_pesanan DESC"
);

$pesanan = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $pesanan[] = $row;
    }

    mysqli_stmt_close($stmt);
}

/* STATUS PESANAN */
function classStatus($status)
{
    switch ($status) {
        case 'Menunggu Pembayaran':
            return 'status-menunggu';

        case 'Menunggu Verifikasi':
            return 'status-verifikasi';

        case 'Diproses':
            return 'status-proses';

        case 'Siap Diambil':
            return 'status-siap';

        case 'Selesai':
            return 'status-selesai';

        case 'Dibatalkan':
            return 'status-batal';

        default:
            return 'status-menunggu';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#102a43">
    <title>Pesanan Saya | Toko ATK &amp; Alat Jahit</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            min-width: 0;
        }

        .section {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 38px 20px 60px;
        }

        .section-heading {
            text-align: center;
            margin: 0 auto 28px;
            max-width: 650px;
        }

        .section-heading .eyebrow {
            display: inline-block;
            color: #627d98;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }

        .section-heading h1 {
            color: #102a43;
            font-size: 32px;
            line-height: 1.25;
            margin: 0 0 10px;
        }

        .section-heading p {
            color: #627d98;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .order-list {
            width: 100%;
            max-width: 950px;
            margin: 30px auto;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .order-card {
            min-width: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            transition: box-shadow .2s;
        }

        .order-card:hover {
            box-shadow: 0 8px 25px rgba(16, 42, 67, .08);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #edf0f2;
        }

        .order-number {
            color: #102a43;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 5px;
            overflow-wrap: anywhere;
        }

        .order-date {
            color: #7b8794;
            font-size: 12px;
            line-height: 1.5;
        }

        .order-status {
            display: inline-block;
            max-width: 100%;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-menunggu {
            background: #fff4d6;
            color: #8a6500;
        }

        .status-verifikasi {
            background: #fff0d9;
            color: #9a5b00;
        }

        .status-proses {
            background: #e8f1ff;
            color: #245b9e;
        }

        .status-siap {
            background: #e8f8ef;
            color: #28734a;
        }

        .status-selesai {
            background: #dff6e8;
            color: #1f6b40;
        }

        .status-batal {
            background: #fde8e8;
            color: #b83232;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            padding: 18px 0;
        }

        .order-info-item {
            min-width: 0;
        }

        .order-info-item span {
            display: block;
            color: #7b8794;
            font-size: 11px;
            line-height: 1.5;
            margin-bottom: 5px;
        }

        .order-info-item strong {
            display: block;
            color: #102a43;
            font-size: 14px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid #edf0f2;
        }

        .order-items {
            color: #627d98;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .order-total {
            color: #102a43;
            font-size: 19px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .btn-detail-order {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 40px;
            padding: 10px 17px;
            background: #102a43;
            color: #fff;
            text-decoration: none;
            border: 0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            transition: background .2s;
        }

        .btn-detail-order:hover {
            background: #183b56;
        }

        .order-empty {
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            padding: 45px 25px;
            text-align: center;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        .order-empty h2 {
            color: #102a43;
            font-size: 23px;
            margin: 0 0 8px;
        }

        .order-empty p {
            color: #627d98;
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 20px;
        }

        .order-empty .btn-primary {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            padding: 12px 20px;
            background: #102a43;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
        }

        .order-empty .btn-primary:hover {
            background: #183b56;
        }

        .order-success {
            width: 100%;
            max-width: 950px;
            margin: 20px auto;
            padding: 14px 18px;
            background: #e9f8ef;
            color: #28734a;
            border: 1px solid #c8ead6;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            .section {
                padding: 28px 15px 45px;
            }

            .section-heading {
                margin-bottom: 22px;
            }

            .section-heading h1 {
                font-size: 27px;
            }

            .order-list {
                margin: 22px auto;
                gap: 14px;
            }

            .order-card {
                padding: 18px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .order-number {
                font-size: 16px;
            }

            .order-info {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                padding: 16px 0;
            }

            .order-footer {
                align-items: flex-start;
            }

            .order-total {
                font-size: 18px;
            }

            .btn-detail-order {
                padding: 10px 13px;
            }

            .order-success {
                margin: 15px auto;
            }
        }

        @media (max-width: 450px) {
            .section {
                padding: 24px 12px 35px;
            }

            .section-heading h1 {
                font-size: 24px;
            }

            .section-heading p {
                font-size: 13px;
            }

            .order-card {
                padding: 15px;
                border-radius: 12px;
            }

            .order-info {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px 10px;
            }

            .order-info-item strong {
                font-size: 13px;
            }

            .order-status {
                white-space: normal;
                line-height: 1.4;
            }

            .order-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }

            .order-footer > div {
                width: 100%;
            }

            .order-total {
                font-size: 20px;
            }

            .btn-detail-order {
                width: 100%;
                min-height: 43px;
                font-size: 13px;
            }

            .order-empty {
                margin: 25px auto;
                padding: 30px 18px;
            }

            .order-empty h2 {
                font-size: 21px;
            }

            .order-success {
                padding: 12px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . "/includes/navbar.php"; ?>

    <main>
        <section class="section">
            <div class="section-heading">
                <span class="eyebrow">PESANAN SAYA</span>
                <h1>Pesanan Saya</h1>
                <p>Lihat pesanan dan status pengambilan kamu.</p>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="order-success">
                    Pesanan berhasil dibuat. Silakan tunggu proses pesanan sesuai status yang ditampilkan.
                </div>
            <?php endif; ?>

            <div id="orderLiveContent">
                <?php if (empty($pesanan)): ?>
                    <div class="order-empty">
                        <h2>Belum Ada Pesanan</h2>
                        <p>Kamu belum memiliki pesanan. Yuk pilih produk yang ingin dibeli.</p>
                        <a href="../pages/katalog.php" class="btn-primary">Lihat Katalog</a>
                    </div>
                <?php else: ?>
                    <div class="order-list">
                        <?php foreach ($pesanan as $order): ?>
                            <article class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="order-number">
                                            <?= htmlspecialchars($order['nomor_pesanan'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="order-date">
                                            <?= !empty($order['tanggal_pesanan']) ? date('d M Y, H:i', strtotime($order['tanggal_pesanan'])) : '-'; ?>
                                        </div>
                                    </div>

                                    <span class="order-status <?= classStatus($order['status_pesanan']); ?>">
                                        <?= htmlspecialchars($order['status_pesanan'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>

                                <div class="order-info">
                                    <div class="order-info-item">
                                        <span>Jumlah Produk</span>
                                        <strong><?= (int)$order['jumlah_item']; ?> produk</strong>
                                    </div>

                                    <div class="order-info-item">
                                        <span>Metode Pembayaran</span>
                                        <strong>QRIS</strong>
                                    </div>

                                    <div class="order-info-item">
                                        <span>Pengambilan</span>
                                        <strong>Pickup di Toko</strong>
                                    </div>
                                </div>

                                <div class="order-footer">
                                    <div>
                                        <div class="order-items">Total Pesanan</div>
                                        <div class="order-total">
                                            Rp <?= number_format((float)$order['total_harga'], 0, ',', '.'); ?>
                                        </div>
                                    </div>

                                    <a
                                        href="detail_pesanan.php?id=<?= (int)$order['id_pesanan']; ?>"
                                        class="btn-detail-order"
                                    >
                                        Lihat Detail
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . "/includes/footer.php"; ?>

    <script>
    /* SINKRONISASI PESANAN OTOMATIS */
    (function () {
        const orderContainer = document.getElementById("orderLiveContent");

        if (!orderContainer) {
            return;
        }

        let sedangMemuatPesanan = false;

        async function sinkronPesanan() {
            if (
                sedangMemuatPesanan ||
                document.visibilityState !== "visible"
            ) {
                return;
            }

            sedangMemuatPesanan = true;

            try {
                const url = new URL(window.location.href);
                url.searchParams.set("_live_orders", Date.now().toString());

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
                const kontenBaru = halamanBaru.getElementById("orderLiveContent");

                if (!kontenBaru) {
                    return;
                }

                if (
                    orderContainer.innerHTML.trim() !==
                    kontenBaru.innerHTML.trim()
                ) {
                    orderContainer.innerHTML = kontenBaru.innerHTML;
                }
            } catch (error) {
                console.error("Gagal menyinkronkan pesanan:", error);
            } finally {
                sedangMemuatPesanan = false;
            }
        }

        setInterval(sinkronPesanan, 4000);
    })();
    </script>
</body>
</html>