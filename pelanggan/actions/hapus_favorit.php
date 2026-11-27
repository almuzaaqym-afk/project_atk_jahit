<?php

session_start();

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$id_produk = (int) ($_GET['id'] ?? 0);

if ($id_produk > 0) {

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM favorit
         WHERE id_pelanggan = ?
         AND id_produk = ?"
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