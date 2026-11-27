<?php

session_start();

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$id_produk = (int) ($_GET['id'] ?? 0);

if ($id_produk <= 0) {
    header("Location: ../pages/katalog.php");
    exit;
}

/* Cek apakah produk ada */
$cek_produk = mysqli_prepare(
    $conn,
    "SELECT id_produk
     FROM produk
     WHERE id_produk = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $cek_produk,
    "i",
    $id_produk
);

mysqli_stmt_execute($cek_produk);

$result_produk = mysqli_stmt_get_result($cek_produk);

$produk = mysqli_fetch_assoc($result_produk);

mysqli_stmt_close($cek_produk);

if (!$produk) {
    header("Location: ../pages/katalog.php");
    exit;
}

/* Cek apakah produk sudah ada di favorit */
$cek = mysqli_prepare(
    $conn,
    "SELECT id_favorit
     FROM favorit
     WHERE id_pelanggan = ?
     AND id_produk = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $cek,
    "ii",
    $id_pelanggan,
    $id_produk
);

mysqli_stmt_execute($cek);

$result = mysqli_stmt_get_result($cek);

$sudah_favorit = mysqli_num_rows($result) > 0;

mysqli_stmt_close($cek);

/* Jika belum ada, tambahkan ke favorit */
if (!$sudah_favorit) {

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO favorit
        (id_pelanggan, id_produk)
        VALUES (?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_pelanggan,
        $id_produk
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

/* Kembali ke detail produk */
header(
    "Location: ../pages/detail_produk.php?id=" . $id_produk
);

exit;