<?php

session_start();


/* =========================================================
   CEK LOGIN
========================================================= */

if (!isset($_SESSION["id_pelanggan"])) {

    header("Location: ../login.php");
    exit;
}


$id_pelanggan = (int) $_SESSION["id_pelanggan"];


/* =========================================================
   KONEKSI DATABASE
========================================================= */

require_once __DIR__ . "/../../config/koneksi.php";


/* =========================================================
   DATA FORM
========================================================= */

$id_produk = (int) ($_POST["id_produk"] ?? 0);

$id_pesanan = (int) ($_POST["id_pesanan"] ?? 0);

$rating = (int) ($_POST["rating"] ?? 0);

$komentar = trim($_POST["komentar"] ?? "");


/* =========================================================
   VALIDASI DASAR
========================================================= */

if ($id_produk <= 0 || $id_pesanan <= 0) {

    header(
        "Location: ../pages/katalog.php"
    );

    exit;
}


if ($rating < 1 || $rating > 5) {

    header(
        "Location: ../pages/detail_produk.php?id="
        . $id_produk
        . "&ulasan=gagal"
    );

    exit;
}


/* =========================================================
   CEK PESANAN MILIK PELANGGAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        detail_pesanan.id_pesanan
     FROM detail_pesanan
     INNER JOIN pesanan
        ON detail_pesanan.id_pesanan = pesanan.id_pesanan
     WHERE detail_pesanan.id_pesanan = ?
     AND detail_pesanan.id_produk = ?
     AND pesanan.id_pelanggan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $id_pesanan,
    $id_produk,
    $id_pelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pesanan_valid =
    mysqli_num_rows($result) > 0;

mysqli_stmt_close($stmt);


if (!$pesanan_valid) {

    header(
        "Location: ../pages/detail_produk.php?id="
        . $id_produk
        . "&ulasan=gagal"
    );

    exit;
}


/* =========================================================
   CEK SUDAH ULASAN ATAU BELUM
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_ulasan
     FROM ulasan
     WHERE id_produk = ?
     AND id_pelanggan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_produk,
    $id_pelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$sudah_ada =
    mysqli_num_rows($result) > 0;

mysqli_stmt_close($stmt);


if ($sudah_ada) {

    header(
        "Location: ../pages/detail_produk.php?id="
        . $id_produk
        . "&ulasan=sudah"
    );

    exit;
}


/* =========================================================
   SIMPAN ULASAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO ulasan
        (
            id_produk,
            id_pelanggan,
            id_pesanan,
            rating,
            komentar
        )
     VALUES
        (?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "iiiis",
    $id_produk,
    $id_pelanggan,
    $id_pesanan,
    $rating,
    $komentar
);

$berhasil =
    mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);


/* =========================================================
   KEMBALI KE DETAIL PRODUK
========================================================= */

if ($berhasil) {

    header(
        "Location: ../pages/detail_produk.php?id="
        . $id_produk
        . "&ulasan=berhasil"
    );

} else {

    header(
        "Location: ../pages/detail_produk.php?id="
        . $id_produk
        . "&ulasan=gagal"
    );
}

exit;