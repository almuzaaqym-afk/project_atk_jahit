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
$jumlah    = (int) ($_GET['jumlah'] ?? 1);

if ($id_produk <= 0) {
    header("Location: ../pages/katalog.php");
    exit;
}

if ($jumlah < 1) {
    $jumlah = 1;
}

/*
|--------------------------------------------------------------------------
| URL KEMBALI KE DETAIL PRODUK
|--------------------------------------------------------------------------
*/

$url_detail = "../pages/detail_produk.php?id=" . $id_produk;

/* =========================================================
   AMBIL DATA PRODUK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_produk,
        nama_produk,
        harga,
        stok
     FROM produk
     WHERE id_produk = ?
     LIMIT 1"
);

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
            "Location: " .
            $url_detail .
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
            "Location: " .
            $url_detail .
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
            "Location: " .
            $url_detail .
            "&error=stok_habis"
        );

        exit;
    }

    /* =====================================================
       SESSION KERANJANG VARIAN
    ===================================================== */

    if (
        !isset(
            $_SESSION['keranjang_varian']
        )
    ) {
        $_SESSION['keranjang_varian'] = [];
    }

    /* =====================================================
       VARIAN SUDAH ADA DI KERANJANG
    ===================================================== */

    if (
        isset(
            $_SESSION['keranjang_varian'][$id_varian]
        )
    ) {

        $jumlah_sekarang = (int) $_SESSION[
            'keranjang_varian'
        ][$id_varian];

        $sisa_stok = $stok - $jumlah_sekarang;

        /* Stok di keranjang sudah maksimal */

        if ($sisa_stok <= 0) {

            header(
                "Location: " .
                $url_detail .
                "&error=stok_maksimal"
            );

            exit;
        }

        /* Jumlah tidak boleh melebihi sisa stok */

        if ($jumlah > $sisa_stok) {
            $jumlah = $sisa_stok;
        }

        $_SESSION[
            'keranjang_varian'
        ][$id_varian] =
            $jumlah_sekarang + $jumlah;

    } else {

        /* Jumlah tidak boleh melebihi stok */

        if ($jumlah > $stok) {
            $jumlah = $stok;
        }

        $_SESSION[
            'keranjang_varian'
        ][$id_varian] = $jumlah;
    }

/* =========================================================
   PRODUK TANPA VARIAN
========================================================= */

} else {

    $stok = (int) $produk['stok'];

    /* =====================================================
       CEK STOK PRODUK
    ===================================================== */

    if ($stok <= 0) {

        header(
            "Location: " .
            $url_detail .
            "&error=stok_habis"
        );

        exit;
    }

    /*
     * Produk tanpa varian
     * tidak boleh menggunakan id_varian
     */

    $id_varian = 0;

    /* =====================================================
       SESSION KERANJANG PRODUK
    ===================================================== */

    if (
        !isset(
            $_SESSION['keranjang']
        )
    ) {
        $_SESSION['keranjang'] = [];
    }

    /* =====================================================
       PRODUK SUDAH ADA DI KERANJANG
    ===================================================== */

    if (
        isset(
            $_SESSION['keranjang'][$id_produk]
        )
    ) {

        $jumlah_sekarang = (int) $_SESSION[
            'keranjang'
        ][$id_produk];

        $sisa_stok = $stok - $jumlah_sekarang;

        /* Stok di keranjang sudah maksimal */

        if ($sisa_stok <= 0) {

            header(
                "Location: " .
                $url_detail .
                "&error=stok_maksimal"
            );

            exit;
        }

        /* Jumlah tidak boleh melebihi sisa stok */

        if ($jumlah > $sisa_stok) {
            $jumlah = $sisa_stok;
        }

        $_SESSION[
            'keranjang'
        ][$id_produk] =
            $jumlah_sekarang + $jumlah;

    } else {

        /* Jumlah tidak boleh melebihi stok */

        if ($jumlah > $stok) {
            $jumlah = $stok;
        }

        $_SESSION[
            'keranjang'
        ][$id_produk] = $jumlah;
    }
}

/* =========================================================
   BERHASIL DITAMBAHKAN
   KEMBALI KE DETAIL PRODUK
========================================================= */

header(
    "Location: " .
    $url_detail .
    "&status=keranjang"
);

exit;