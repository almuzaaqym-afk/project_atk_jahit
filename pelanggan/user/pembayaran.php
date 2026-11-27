<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once __DIR__ . "/../../config/koneksi.php";

/* CEK LOGIN */
if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../login.php");
    exit;
}
$error = '';

/* PROSES PEMBAYARAN */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_pembayaran'])) {
    $checkout_items = $_SESSION['checkout_items'] ?? [];
    if (empty($checkout_items)) {
        header("Location: ../pages/katalog.php");
        exit;
    }

    $id_pelanggan = (int)$_SESSION['id_pelanggan'];
    mysqli_begin_transaction($conn);

    try {
        $items_valid = [];
        $subtotal_pesanan = 0;
        $produk_varian_sync = [];

        /* VALIDASI PRODUK DAN STOK */
        foreach ($checkout_items as $item) {
            $id_produk = (int)($item['id_produk'] ?? 0);
            $id_varian = (int)($item['id_varian'] ?? 0);
            $jumlah = (int)($item['jumlah'] ?? 0);

            if ($id_produk <= 0 || $jumlah <= 0) {
                continue;
            }

            /* PRODUK DENGAN VARIAN */
            if ($id_varian > 0) {
                $stmt = mysqli_prepare($conn, "SELECT p.id_produk, p.nama_produk, p.gambar, k.nama_kategori, v.id_varian, v.nama_varian, v.harga, v.stok FROM produk p INNER JOIN produk_varian v ON v.id_produk = p.id_produk LEFT JOIN kategori k ON p.id_kategori = k.id_kategori WHERE p.id_produk = ? AND v.id_varian = ? AND v.status = 'Aktif' LIMIT 1 FOR UPDATE");

                if (!$stmt) {
                    throw new Exception("Gagal menyiapkan pemeriksaan varian.");
                }

                mysqli_stmt_bind_param($stmt, "ii", $id_produk, $id_varian);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $varian = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$varian) {
                    throw new Exception("Varian produk tidak ditemukan atau sudah tidak aktif.");
                }

                $stok = (int)$varian['stok'];
                if ($stok < $jumlah) {
                    throw new Exception("Stok varian " . $varian['nama_produk'] . " - " . $varian['nama_varian'] . " tidak mencukupi.");
                }

                $harga = (float)$varian['harga'];
                $subtotal = $harga * $jumlah;
                $items_valid[] = [
                    'id_produk' => $id_produk,
                    'id_varian' => $id_varian,
                    'nama_varian' => $varian['nama_varian'],
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ];
                $subtotal_pesanan += $subtotal;
                $produk_varian_sync[$id_produk] = true;
            } else {
                /* PRODUK TANPA VARIAN */
                $stmt = mysqli_prepare($conn, "SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = ? LIMIT 1 FOR UPDATE");

                if (!$stmt) {
                    throw new Exception("Gagal menyiapkan pemeriksaan produk.");
                }

                mysqli_stmt_bind_param($stmt, "i", $id_produk);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $produk = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$produk) {
                    throw new Exception("Produk tidak ditemukan.");
                }

                $stok = (int)$produk['stok'];
                if ($stok < $jumlah) {
                    throw new Exception("Stok produk " . $produk['nama_produk'] . " tidak mencukupi.");
                }

                $harga = (float)$produk['harga'];
                $subtotal = $harga * $jumlah;
                $items_valid[] = [
                    'id_produk' => $id_produk,
                    'id_varian' => 0,
                    'nama_varian' => '',
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ];
                $subtotal_pesanan += $subtotal;
            }
        }

        if (empty($items_valid)) {
            throw new Exception("Tidak ada produk yang dapat diproses.");
        }

        /* VALIDASI VOUCHER */
        $id_voucher = null;
        $kode_voucher = null;
        $diskon_voucher = 0;
        $voucher_session = $_SESSION['voucher_checkout'] ?? null;

        if (is_array($voucher_session) && !empty($voucher_session['id_voucher'])) {
            $id_voucher_session = (int)$voucher_session['id_voucher'];

            if ($id_voucher_session <= 0) {
                throw new Exception("Data voucher tidak valid.");
            }

            $stmt_voucher = mysqli_prepare($conn, "SELECT id_voucher, kode_voucher, nama_voucher, jenis_diskon, nilai_diskon, minimal_belanja, maksimal_diskon, tanggal_mulai, tanggal_berakhir, kuota, jumlah_digunakan, status FROM voucher WHERE id_voucher = ? LIMIT 1 FOR UPDATE");

            if (!$stmt_voucher) {
                throw new Exception("Gagal menyiapkan pemeriksaan voucher.");
            }

            mysqli_stmt_bind_param($stmt_voucher, "i", $id_voucher_session);
            mysqli_stmt_execute($stmt_voucher);
            $result_voucher = mysqli_stmt_get_result($stmt_voucher);
            $voucher = mysqli_fetch_assoc($result_voucher);
            mysqli_stmt_close($stmt_voucher);

            if (!$voucher) {
                throw new Exception("Voucher tidak ditemukan.");
            }
            if ($voucher['status'] !== 'Aktif') {
                throw new Exception("Voucher sudah tidak aktif.");
            }

            $sekarang = time();
            $tanggal_mulai = strtotime($voucher['tanggal_mulai']);
            $tanggal_berakhir = strtotime($voucher['tanggal_berakhir']);

            if ($sekarang < $tanggal_mulai) {
                throw new Exception("Voucher belum dapat digunakan.");
            }
            if ($sekarang > $tanggal_berakhir) {
                throw new Exception("Voucher sudah kedaluwarsa.");
            }
            if ((int)$voucher['kuota'] > 0 && (int)$voucher['jumlah_digunakan'] >= (int)$voucher['kuota']) {
                throw new Exception("Kuota voucher sudah habis.");
            }
            if ($subtotal_pesanan < (float)$voucher['minimal_belanja']) {
                throw new Exception("Minimal belanja untuk voucher ini adalah Rp " . number_format($voucher['minimal_belanja'], 0, ',', '.') . ".");
            }

            if ($voucher['jenis_diskon'] === 'Persen') {
                $diskon_voucher = $subtotal_pesanan * ((float)$voucher['nilai_diskon'] / 100);
                if ($voucher['maksimal_diskon'] !== null && $voucher['maksimal_diskon'] !== '' && (float)$voucher['maksimal_diskon'] > 0) {
                    $diskon_voucher = min($diskon_voucher, (float)$voucher['maksimal_diskon']);
                }
            } else {
                $diskon_voucher = (float)$voucher['nilai_diskon'];
            }

            $diskon_voucher = max(0, min($diskon_voucher, $subtotal_pesanan));
            $id_voucher = (int)$voucher['id_voucher'];
            $kode_voucher = $voucher['kode_voucher'];
        }

        /* TOTAL AKHIR */
        $total_harga = max(0, $subtotal_pesanan - $diskon_voucher);

        /* NOMOR PESANAN */
        $nomor_pesanan = 'PS-' . date('YmdHis') . '-' . rand(10, 99);
        $status_pesanan = 'Diproses';

        /* CATATAN */
        if ($id_voucher !== null) {
            $catatan = 'Pembayaran QRIS berhasil dikonfirmasi secara otomatis. Voucher ' . $kode_voucher . ' digunakan dengan diskon Rp ' . number_format($diskon_voucher, 0, ',', '.') . '.';
        } else {
            $catatan = 'Pembayaran QRIS berhasil dikonfirmasi secara otomatis.';
        }

        /* INSERT PESANAN */
        if ($id_voucher !== null) {
            $stmt_pesanan = mysqli_prepare($conn, "INSERT INTO pesanan (nomor_pesanan, id_pelanggan, id_voucher, kode_voucher, diskon_voucher, tanggal_pesanan, total_harga, status_pesanan, catatan) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)");

            if (!$stmt_pesanan) {
                throw new Exception("Gagal menyiapkan pesanan: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt_pesanan, "siisddss", $nomor_pesanan, $id_pelanggan, $id_voucher, $kode_voucher, $diskon_voucher, $total_harga, $status_pesanan, $catatan);
        } else {
            $stmt_pesanan = mysqli_prepare($conn, "INSERT INTO pesanan (nomor_pesanan, id_pelanggan, tanggal_pesanan, total_harga, status_pesanan, catatan) VALUES (?, ?, NOW(), ?, ?, ?)");

            if (!$stmt_pesanan) {
                throw new Exception("Gagal menyiapkan pesanan: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt_pesanan, "sidss", $nomor_pesanan, $id_pelanggan, $total_harga, $status_pesanan, $catatan);
        }

        if (!mysqli_stmt_execute($stmt_pesanan)) {
            throw new Exception("Gagal membuat pesanan: " . mysqli_stmt_error($stmt_pesanan));
        }

        $id_pesanan = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_pesanan);

        /* PREPARE DETAIL NON VARIAN */
        $stmt_detail_produk = mysqli_prepare($conn, "INSERT INTO detail_pesanan (id_pesanan, id_produk, id_varian, nama_varian, jumlah, harga, subtotal) VALUES (?, ?, NULL, NULL, ?, ?, ?)");

        if (!$stmt_detail_produk) {
            throw new Exception("Gagal menyiapkan detail pesanan produk.");
        }

        /* PREPARE DETAIL VARIAN */
        $stmt_detail_varian = mysqli_prepare($conn, "INSERT INTO detail_pesanan (id_pesanan, id_produk, id_varian, nama_varian, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt_detail_varian) {
            throw new Exception("Gagal menyiapkan detail pesanan varian.");
        }

        /* PREPARE STOK PRODUK */
        $stmt_stok_produk = mysqli_prepare($conn, "UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");

        if (!$stmt_stok_produk) {
            throw new Exception("Gagal menyiapkan update stok produk.");
        }

        /* PREPARE STOK VARIAN */
        $stmt_stok_varian = mysqli_prepare($conn, "UPDATE produk_varian SET stok = stok - ? WHERE id_varian = ? AND stok >= ?");

        if (!$stmt_stok_varian) {
            throw new Exception("Gagal menyiapkan update stok varian.");
        }

        /* DETAIL PESANAN DAN KURANGI STOK */
        foreach ($items_valid as $item) {
            $id_produk_item = (int)$item['id_produk'];
            $id_varian_item = (int)$item['id_varian'];
            $nama_varian_item = $item['nama_varian'];
            $jumlah_item = (int)$item['jumlah'];
            $harga_item = (float)$item['harga'];
            $subtotal_item = (float)$item['subtotal'];

            if ($id_varian_item > 0) {
                mysqli_stmt_bind_param($stmt_detail_varian, "iiisidd", $id_pesanan, $id_produk_item, $id_varian_item, $nama_varian_item, $jumlah_item, $harga_item, $subtotal_item);

                if (!mysqli_stmt_execute($stmt_detail_varian)) {
                    throw new Exception("Gagal menyimpan detail pesanan varian.");
                }

                mysqli_stmt_bind_param($stmt_stok_varian, "iii", $jumlah_item, $id_varian_item, $jumlah_item);

                if (!mysqli_stmt_execute($stmt_stok_varian)) {
                    throw new Exception("Gagal memperbarui stok varian.");
                }
                if (mysqli_stmt_affected_rows($stmt_stok_varian) <= 0) {
                    throw new Exception("Stok varian tidak mencukupi.");
                }

                $produk_varian_sync[$id_produk_item] = true;
            } else {
                mysqli_stmt_bind_param($stmt_detail_produk, "iiidd", $id_pesanan, $id_produk_item, $jumlah_item, $harga_item, $subtotal_item);

                if (!mysqli_stmt_execute($stmt_detail_produk)) {
                    throw new Exception("Gagal menyimpan detail pesanan.");
                }

                mysqli_stmt_bind_param($stmt_stok_produk, "iii", $jumlah_item, $id_produk_item, $jumlah_item);

                if (!mysqli_stmt_execute($stmt_stok_produk)) {
                    throw new Exception("Gagal memperbarui stok produk.");
                }
                if (mysqli_stmt_affected_rows($stmt_stok_produk) <= 0) {
                    throw new Exception("Stok produk tidak mencukupi.");
                }
            }
        }

        mysqli_stmt_close($stmt_detail_produk);
        mysqli_stmt_close($stmt_detail_varian);
        mysqli_stmt_close($stmt_stok_produk);
        mysqli_stmt_close($stmt_stok_varian);

        /* SINKRON STOK PRODUK BERVARIAN */
        if (!empty($produk_varian_sync)) {
            foreach (array_keys($produk_varian_sync) as $id_produk_sync) {
                $id_produk_sync = (int)$id_produk_sync;
                $stmt_sync = mysqli_prepare($conn, "UPDATE produk SET stok = (SELECT COALESCE(SUM(stok), 0) FROM produk_varian WHERE id_produk = ? AND status = 'Aktif'), stok_minimum = (SELECT COALESCE(SUM(stok_minimum), 0) FROM produk_varian WHERE id_produk = ? AND status = 'Aktif'), lokasi_rak = NULL WHERE id_produk = ?");

                if (!$stmt_sync) {
                    throw new Exception("Gagal menyiapkan sinkronisasi stok produk.");
                }

                mysqli_stmt_bind_param($stmt_sync, "iii", $id_produk_sync, $id_produk_sync, $id_produk_sync);

                if (!mysqli_stmt_execute($stmt_sync)) {
                    mysqli_stmt_close($stmt_sync);
                    throw new Exception("Gagal menyinkronkan stok produk.");
                }

                mysqli_stmt_close($stmt_sync);
            }
        }

        /* UPDATE JUMLAH PENGGUNAAN VOUCHER */
        if ($id_voucher !== null) {
            $stmt_update_voucher = mysqli_prepare($conn, "UPDATE voucher SET jumlah_digunakan = jumlah_digunakan + 1 WHERE id_voucher = ?");

            if (!$stmt_update_voucher) {
                throw new Exception("Gagal menyiapkan update voucher.");
            }

            mysqli_stmt_bind_param($stmt_update_voucher, "i", $id_voucher);

            if (!mysqli_stmt_execute($stmt_update_voucher)) {
                throw new Exception("Gagal memperbarui penggunaan voucher.");
            }

            mysqli_stmt_close($stmt_update_voucher);
        }

        /* COMMIT */
        mysqli_commit($conn);

        /* KOSONGKAN SESSION */
        $mode = $_SESSION['checkout_mode'] ?? 'langsung';
        if ($mode === 'keranjang') {
            $_SESSION['keranjang'] = [];
            $_SESSION['keranjang_varian'] = [];
        }

        unset($_SESSION['checkout_items'], $_SESSION['checkout_mode'], $_SESSION['voucher_checkout']);
        header("Location: pesanan.php?success=1");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

/* AMBIL CHECKOUT */
$checkout_items = $_SESSION['checkout_items'] ?? [];
if (empty($checkout_items)) {
    header("Location: ../pages/katalog.php");
    exit;
}

/* DATA PRODUK CHECKOUT */
$produk_checkout = [];
$total = 0;

foreach ($checkout_items as $item) {
    $id_produk = (int)($item['id_produk'] ?? 0);
    $id_varian = (int)($item['id_varian'] ?? 0);
    $jumlah = (int)($item['jumlah'] ?? 0);

    if ($id_produk <= 0 || $jumlah <= 0) {
        continue;
    }

    /* PRODUK VARIAN */
    if ($id_varian > 0) {
        $stmt = mysqli_prepare($conn, "SELECT p.id_produk, p.nama_produk, p.gambar, k.nama_kategori, v.id_varian, v.nama_varian, v.harga, v.stok, (SELECT g.nama_gambar FROM produk_varian_gambar g WHERE g.id_varian = v.id_varian ORDER BY g.urutan ASC, g.id_gambar_varian ASC LIMIT 1) AS gambar_varian FROM produk p INNER JOIN produk_varian v ON v.id_produk = p.id_produk LEFT JOIN kategori k ON p.id_kategori = k.id_kategori WHERE p.id_produk = ? AND v.id_varian = ? AND v.status = 'Aktif' LIMIT 1");

        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, "ii", $id_produk, $id_varian);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$produk) {
            continue;
        }

        $stok = (int)$produk['stok'];
        if ($stok <= 0) {
            continue;
        }
        if ($jumlah > $stok) {
            $jumlah = $stok;
        }
        if ($jumlah < 1) {
            continue;
        }

        $harga = (float)$produk['harga'];
        $subtotal = $harga * $jumlah;
        $produk['id_varian'] = (int)$produk['id_varian'];
        $produk['nama_varian'] = $produk['nama_varian'];
        $produk['gambar_checkout'] = !empty($produk['gambar_varian']) ? $produk['gambar_varian'] : $produk['gambar'];
        $produk['jumlah'] = $jumlah;
        $produk['harga'] = $harga;
        $produk['stok'] = $stok;
        $produk['subtotal'] = $subtotal;
        $produk_checkout[] = $produk;
        $total += $subtotal;
    } else {
        /* PRODUK TANPA VARIAN */
        $stmt = mysqli_prepare($conn, "SELECT produk.id_produk, produk.nama_produk, produk.harga, produk.stok, produk.gambar, kategori.nama_kategori FROM produk LEFT JOIN kategori ON produk.id_kategori = kategori.id_kategori WHERE produk.id_produk = ? LIMIT 1");

        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$produk) {
            continue;
        }

        $stok = (int)$produk['stok'];
        if ($stok <= 0) {
            continue;
        }
        if ($jumlah > $stok) {
            $jumlah = $stok;
        }
        if ($jumlah < 1) {
            continue;
        }

        $harga = (float)$produk['harga'];
        $subtotal = $harga * $jumlah;
        $produk['id_varian'] = 0;
        $produk['nama_varian'] = '';
        $produk['gambar_checkout'] = $produk['gambar'];
        $produk['jumlah'] = $jumlah;
        $produk['harga'] = $harga;
        $produk['stok'] = $stok;
        $produk['subtotal'] = $subtotal;
        $produk_checkout[] = $produk;
        $total += $subtotal;
    }
}

if (empty($produk_checkout)) {
    header("Location: ../pages/katalog.php");
    exit;
}

/* VOUCHER UNTUK TAMPILAN */
$diskon_voucher = 0;
$voucher_data = $_SESSION['voucher_checkout'] ?? null;

if (is_array($voucher_data) && !empty($voucher_data['id_voucher'])) {
    $id_voucher_tampilan = (int)$voucher_data['id_voucher'];
    $stmt_voucher_tampilan = mysqli_prepare($conn, "SELECT id_voucher, kode_voucher, nama_voucher, jenis_diskon, nilai_diskon, minimal_belanja, maksimal_diskon, tanggal_mulai, tanggal_berakhir, kuota, jumlah_digunakan, status FROM voucher WHERE id_voucher = ? LIMIT 1");

    $voucher_valid_tampilan = false;
    $voucher_tampilan = null;

    if ($stmt_voucher_tampilan) {
        mysqli_stmt_bind_param($stmt_voucher_tampilan, "i", $id_voucher_tampilan);
        mysqli_stmt_execute($stmt_voucher_tampilan);
        $result_voucher_tampilan = mysqli_stmt_get_result($stmt_voucher_tampilan);
        $voucher_tampilan = mysqli_fetch_assoc($result_voucher_tampilan);
        mysqli_stmt_close($stmt_voucher_tampilan);

        if ($voucher_tampilan) {
            $voucher_valid_tampilan = true;

            if ($voucher_tampilan['status'] !== 'Aktif') {
                $voucher_valid_tampilan = false;
            }
            if (time() < strtotime($voucher_tampilan['tanggal_mulai'])) {
                $voucher_valid_tampilan = false;
            }
            if (time() > strtotime($voucher_tampilan['tanggal_berakhir'])) {
                $voucher_valid_tampilan = false;
            }
            if ((int)$voucher_tampilan['kuota'] > 0 && (int)$voucher_tampilan['jumlah_digunakan'] >= (int)$voucher_tampilan['kuota']) {
                $voucher_valid_tampilan = false;
            }
            if ($total < (float)$voucher_tampilan['minimal_belanja']) {
                $voucher_valid_tampilan = false;
            }

            if ($voucher_valid_tampilan) {
                if ($voucher_tampilan['jenis_diskon'] === 'Persen') {
                    $diskon_voucher = $total * ((float)$voucher_tampilan['nilai_diskon'] / 100);

                    if ($voucher_tampilan['maksimal_diskon'] !== null && $voucher_tampilan['maksimal_diskon'] !== '' && (float)$voucher_tampilan['maksimal_diskon'] > 0) {
                        $diskon_voucher = min($diskon_voucher, (float)$voucher_tampilan['maksimal_diskon']);
                    }
                } else {
                    $diskon_voucher = (float)$voucher_tampilan['nilai_diskon'];
                }

                $diskon_voucher = min($diskon_voucher, $total);
            } else {
                $voucher_data = null;
                $diskon_voucher = 0;
                unset($_SESSION['voucher_checkout']);
            }
        }
    } else {
        $voucher_data = null;
        $diskon_voucher = 0;
        unset($_SESSION['voucher_checkout']);
    }
}

/* TOTAL AKHIR */
$total_setelah_diskon = max(0, $total - $diskon_voucher);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#123451">
<title>Pembayaran | Toko ATK &amp; Alat Jahit</title>
<style>
*{box-sizing:border-box}
html{width:100%;max-width:100%;overflow-x:hidden}
body{margin:0;min-width:0;width:100%;overflow-x:hidden}
.section{width:100%;max-width:1200px;margin:0 auto;padding:45px 20px 65px}
.section-heading{text-align:center;margin-bottom:25px}
.section-heading h1{color:#123451;margin:8px 0;font-size:32px}
.section-heading p{color:#66788a}
.muted{color:#66788a}
</style>
</head>
<body>
<?php require_once __DIR__ . "/includes/navbar.php"; ?>
<main>
<section class="section">
    <div class="section-heading">
        <span class="eyebrow">PEMBAYARAN</span>
        <h1>Pembayaran</h1>
        <p class="muted">Periksa pesanan dan lakukan pembayaran melalui QRIS.</p>
    </div>

    <?php if ($error !== ''): ?>
    <div class="payment-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="payment-card">
        <?php foreach ($produk_checkout as $produk): ?>
        <div class="payment-product">
            <?php if (!empty($produk['gambar_checkout'])): ?>
                <?php
                $gambar_produk_checkout = !empty($produk['id_varian'])
                    ? "../../admin/assets/images/products/variants/" . $produk['gambar_checkout']
                    : "../../admin/assets/images/products/" . $produk['gambar_checkout'];
                ?>
                <img src="<?= htmlspecialchars($gambar_produk_checkout); ?>" alt="<?= htmlspecialchars($produk['nama_produk']); ?>">
            <?php endif; ?>

            <div>
                <span class="muted"><?= htmlspecialchars($produk['nama_kategori'] ?? 'Produk'); ?></span>
                <h2><?= htmlspecialchars($produk['nama_produk']); ?></h2>

                <?php if (!empty($produk['nama_varian'])): ?>
                <p class="variant-checkout">Varian: <strong><?= htmlspecialchars($produk['nama_varian']); ?></strong></p>
                <?php endif; ?>

                <p>Harga: <strong>Rp <?= number_format($produk['harga'], 0, ',', '.'); ?></strong></p>
                <p>Jumlah: <strong><?= (int)$produk['jumlah']; ?></strong></p>
                <p>Subtotal: <strong>Rp <?= number_format($produk['subtotal'], 0, ',', '.'); ?></strong></p>
            </div>
        </div>
        <?php endforeach; ?>

        <hr>

        <div class="payment-summary">
            <div class="payment-summary-row">
                <span>Subtotal</span>
                <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong>
            </div>

            <?php if ($diskon_voucher > 0): ?>
            <div class="payment-summary-row discount">
                <span>
                    Diskon Voucher
                    <?php if (is_array($voucher_data) && !empty($voucher_data['kode_voucher'])): ?>
                    <small>(<?= htmlspecialchars($voucher_data['kode_voucher']); ?>)</small>
                    <?php endif; ?>
                </span>
                <strong>- Rp <?= number_format($diskon_voucher, 0, ',', '.'); ?></strong>
            </div>
            <?php endif; ?>

            <div class="payment-summary-divider"></div>
            <div class="payment-total">
                <span>Total Pembayaran</span>
                <strong>Rp <?= number_format($total_setelah_diskon, 0, ',', '.'); ?></strong>
            </div>
        </div>

        <div class="payment-method">
            <h2>Metode Pembayaran</h2>
            <div class="payment-box">
                <div class="qris-icon">QRIS</div>
                <div>
                    <strong>Bayar dengan QRIS</strong>
                    <p>Scan QRIS menggunakan DANA, OVO, GoPay, ShopeePay, mobile banking, atau aplikasi pembayaran lainnya.</p>
                </div>
            </div>
        </div>

        <div class="payment-actions">
            <a href="<?= ($_SESSION['checkout_mode'] ?? '') === 'keranjang' ? 'keranjang.php' : '../pages/detail_produk.php?id=' . (int)$produk_checkout[0]['id_produk']; ?>" class="btn-secondary">Kembali</a>
            <button type="button" class="btn-primary" onclick="bukaQRIS()">Bayar Sekarang</button>
        </div>
    </div>
</section>
</main>

<div id="qrisModal" class="qris-modal">
    <div class="qris-modal-content">
        <button type="button" class="qris-close" onclick="tutupQRIS()">&times;</button>
        <h2>Scan QRIS</h2>
        <p class="qris-total">Total Pembayaran</p>
        <strong class="qris-price">Rp <?= number_format($total_setelah_diskon, 0, ',', '.'); ?></strong>

        <div class="qris-image">
            <img src="../../admin/assets/images/qris.png" alt="QRIS Toko ATK & Alat Jahit">
        </div>

        <p class="qris-info">Silakan scan QRIS di atas dan selesaikan pembayaran.</p>

        <div id="paymentLoading" class="payment-loading">
            <div class="loading-spinner"></div>
            <strong>Menunggu pembayaran...</strong>
            <p>Sistem sedang memproses pembayaran.</p>
        </div>

        <div id="paymentSuccess" class="payment-success">
            <div class="success-icon">✓</div>
            <h3>Pembayaran Berhasil</h3>
            <p>Pembayaran telah berhasil dikonfirmasi.</p>
            <p class="success-redirect">Mengarahkan ke pesanan...</p>
        </div>

        <form method="POST" id="paymentForm">
            <input type="hidden" name="konfirmasi_pembayaran" value="1">
        </form>
    </div>
</div>

<style>
.payment-card{max-width:900px;margin:30px auto;background:#fff;padding:35px;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.payment-error{max-width:900px;margin:20px auto;padding:15px 20px;border-radius:10px;background:#fde7e7;color:#b23b3b}
.payment-product{display:flex;gap:25px;align-items:center;padding:20px 0;border-bottom:1px solid #e5e9ed}
.payment-product img{width:150px;height:150px;object-fit:contain;border-radius:12px;background:#f5f7fa}
.payment-product h2{color:#123451;margin:8px 0 12px}
.payment-product p{margin:6px 0}
.variant-checkout{color:#627d98}
.variant-checkout strong{color:#123451}
.payment-summary{margin-top:10px;margin-bottom:30px}
.payment-summary-row{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:7px 0;font-size:15px}
.payment-summary-row span{color:#66788a}
.payment-summary-row strong{color:#123451}
.payment-summary-row small{color:#829ab1;font-size:12px}
.payment-summary-row.discount strong{color:#26734d}
.payment-summary-divider{height:1px;background:#e5e9ed;margin:12px 0}
.payment-card hr{margin:25px 0;border:0;border-top:1px solid #ddd}
.payment-total{display:flex;justify-content:space-between;align-items:center;gap:20px;font-size:20px;margin-bottom:30px}
.payment-total strong{color:#123451;font-size:27px}
.payment-method h2{color:#123451;margin-bottom:15px}
.payment-box{display:flex;align-items:center;gap:20px;padding:20px;background:#f5f7fa;border-radius:12px;border:1px solid #ddd}
.payment-box p{margin-top:8px;color:#66788a;line-height:1.5}
.qris-icon{background:#123451;color:#fff;font-weight:bold;font-size:20px;padding:15px 18px;border-radius:10px}
.payment-actions{display:flex;gap:15px;margin-top:30px}
.payment-actions a,.payment-actions button{flex:1;text-align:center;padding:15px;border-radius:10px;text-decoration:none;font-weight:600;cursor:pointer;font-size:16px}
.btn-secondary{background:#eef1f5;color:#123451;border:none}
.btn-primary{background:#123451;color:#fff;border:none}
.qris-modal{display:none;position:fixed;z-index:9999;inset:0;background:rgba(0,0,0,.65);justify-content:center;align-items:center;padding:20px}
.qris-modal-content{position:relative;width:100%;max-width:430px;max-height:95vh;overflow-y:auto;background:#fff;border-radius:18px;padding:30px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.25)}
.qris-modal-content h2{color:#123451;margin-bottom:10px}
.qris-total{color:#66788a;margin-bottom:5px}
.qris-price{display:block;color:#123451;font-size:25px;margin-bottom:20px}
.qris-image{width:280px;height:280px;margin:0 auto 20px;display:flex;justify-content:center;align-items:center;background:#fff;border:1px solid #ddd;border-radius:12px;overflow:hidden}
.qris-image img{width:100%;height:100%;object-fit:contain}
.qris-info{color:#66788a;font-size:14px;line-height:1.5;margin-bottom:20px}
.qris-close{position:absolute;top:12px;right:15px;width:35px;height:35px;border:none;background:transparent;font-size:28px;color:#555;cursor:pointer}
.payment-loading{padding:15px 10px;color:#123451}
.payment-loading strong{display:block;margin-top:12px;font-size:15px}
.payment-loading p{margin-top:5px;color:#66788a;font-size:13px}
.loading-spinner{width:42px;height:42px;margin:auto;border:4px solid #e5e9ed;border-top:4px solid #123451;border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.payment-success{display:none;padding:10px}
.success-icon{width:65px;height:65px;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#123451;color:#fff;font-size:35px;font-weight:bold}
.payment-success h3{color:#123451;margin-bottom:8px;font-size:21px}
.payment-success p{color:#66788a;font-size:14px}
.success-redirect{margin-top:8px;font-size:12px!important}
@media(max-width:768px){
    .section{padding:30px 14px 45px}
    .payment-card{width:100%;padding:22px;margin:20px auto}
    .payment-product{gap:16px}
    .payment-product img{max-width:100%}
    .payment-total strong{font-size:23px}
    .qris-modal{padding:12px}
    .qris-modal-content{padding:24px 16px;max-height:calc(100dvh - 24px)}
    .qris-image{max-width:100%;height:auto;aspect-ratio:1}
    .qris-image img{object-fit:contain}
}
@media(max-width:600px){
    .payment-card{padding:20px}
    .payment-product{flex-direction:column;align-items:flex-start}
    .payment-product img{width:100%;height:220px}
    .payment-summary-row{align-items:flex-start}
    .payment-total{flex-direction:column;align-items:flex-start}
    .payment-actions{flex-direction:column}
    .payment-box{flex-direction:column;align-items:flex-start}
    .qris-image{width:250px;height:250px}
}
</style>

<script>
function bukaQRIS(){
    const modal=document.getElementById("qrisModal");
    const loading=document.getElementById("paymentLoading");
    const success=document.getElementById("paymentSuccess");
    if(!modal)return;
    if(loading)loading.style.display="block";
    if(success)success.style.display="none";
    modal.style.display="flex";
    setTimeout(prosesPembayaranOtomatis,5000);
}
function tutupQRIS(){
    const modal=document.getElementById("qrisModal");
    if(modal)modal.style.display="none";
}
function prosesPembayaranOtomatis(){
    const loading=document.getElementById("paymentLoading");
    const success=document.getElementById("paymentSuccess");
    const form=document.getElementById("paymentForm");
    if(loading)loading.style.display="none";
    if(success)success.style.display="block";
    setTimeout(function(){
        if(form)form.submit();
    },1500);
}
document.getElementById("qrisModal").addEventListener("click",function(event){
    if(event.target===this && document.getElementById("paymentSuccess").style.display!=="block"){
        tutupQRIS();
    }
});
</script>
<?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>