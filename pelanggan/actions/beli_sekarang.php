<?php
session_start();

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

/* =========================================================
   ID PRODUK, VARIAN & JUMLAH
========================================================= */

$id_produk = (int) ($_GET['id'] ?? 0);
$id_varian = (int) ($_GET['id_varian'] ?? 0);
$jumlah = (int) ($_GET['jumlah'] ?? 1);

if ($id_produk <= 0) {
    header("Location: ../pages/katalog.php");
    exit;
}

if ($jumlah < 1) {
    $jumlah = 1;
}

/* =========================================================
   MULAI CHECKOUT BARU
========================================================= */

unset($_SESSION['voucher_checkout']);

/* =========================================================
   AMBIL DATA PRODUK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        produk.id_produk,
        produk.nama_produk,
        produk.harga,
        produk.stok,
        produk.gambar,
        kategori.nama_kategori
     FROM produk
     LEFT JOIN kategori
        ON produk.id_kategori = kategori.id_kategori
     WHERE produk.id_produk = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: ../pages/katalog.php");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_produk
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$produk = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   PRODUK TIDAK DITEMUKAN
========================================================= */

if (!$produk) {
    header("Location: ../pages/katalog.php");
    exit;
}

/* =========================================================
   CEK PRODUK MEMILIKI VARIAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM produk_varian
     WHERE id_produk = ?
       AND status = 'Aktif'"
);

if (!$stmt) {
    header("Location: ../pages/detail_produk.php?id=" . $id_produk);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_produk
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data_varian = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

$jumlah_varian_aktif = (int) (
    $data_varian['jumlah'] ?? 0
);

$memiliki_varian = $jumlah_varian_aktif > 0;

/* =========================================================
   PRODUK BERVARIAN
========================================================= */

if ($memiliki_varian) {

    /* =====================================================
       VARIAN WAJIB DIPILIH
    ===================================================== */

    if ($id_varian <= 0) {
        header(
            "Location: ../pages/detail_produk.php?id=" .
            $id_produk .
            "&error=pilih_varian"
        );
        exit;
    }

    /* =====================================================
       AMBIL DATA VARIAN
    ===================================================== */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            id_varian,
            id_produk,
            nama_varian,
            harga,
            stok
         FROM produk_varian
         WHERE id_varian = ?
           AND id_produk = ?
           AND status = 'Aktif'
         LIMIT 1"
    );

    if (!$stmt) {
        header(
            "Location: ../pages/detail_produk.php?id=" .
            $id_produk .
            "&error=varian_tidak_ditemukan"
        );
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_varian,
        $id_produk
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $varian = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    /* =====================================================
       VARIAN TIDAK DITEMUKAN
    ===================================================== */

    if (!$varian) {
        header(
            "Location: ../pages/detail_produk.php?id=" .
            $id_produk .
            "&error=varian_tidak_ditemukan"
        );
        exit;
    }

    /* =====================================================
       CEK STOK VARIAN
    ===================================================== */

    $stok = (int) $varian['stok'];

    if ($stok <= 0) {
        header(
            "Location: ../pages/detail_produk.php?id=" .
            $id_produk .
            "&error=stok_habis"
        );
        exit;
    }

    if ($jumlah > $stok) {
        $jumlah = $stok;
    }

    /* =====================================================
       SIMPAN CHECKOUT LANGSUNG
    ===================================================== */

    $_SESSION['checkout_items'] = [
        [
            'id_produk' => $id_produk,
            'id_varian' => $id_varian,
            'nama_varian' => $varian['nama_varian'],
            'harga' => (float) $varian['harga'],
            'jumlah' => $jumlah
        ]
    ];

    $_SESSION['checkout_mode'] = 'langsung';

} else {

    /* =====================================================
       PRODUK TANPA VARIAN
    ===================================================== */

    $stok = (int) $produk['stok'];

    if ($stok <= 0) {
        header(
            "Location: ../pages/detail_produk.php?id=" .
            $id_produk .
            "&error=stok_habis"
        );
        exit;
    }

    if ($jumlah > $stok) {
        $jumlah = $stok;
    }

    /* =====================================================
       SIMPAN CHECKOUT PRODUK BIASA
    ===================================================== */

    $_SESSION['checkout_items'] = [
        [
            'id_produk' => $id_produk,
            'id_varian' => 0,
            'nama_varian' => '',
            'harga' => (float) $produk['harga'],
            'jumlah' => $jumlah
        ]
    ];

    $_SESSION['checkout_mode'] = 'langsung';
}

/* =========================================================
   KE CHECKOUT TERLEBIH DAHULU
========================================================= */

header("Location: ../user/checkout.php");
exit;