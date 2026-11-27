<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

$id_produk = (int)($_GET['id'] ?? 0);

if ($id_produk <= 0) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   AMBIL DATA PRODUK
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_produk, nama_produk, gambar
     FROM produk
     WHERE id_produk = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$produk) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =========================================================
   CEK PESANAN
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM detail_pesanan
     WHERE id_produk = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ((int)$data['jumlah'] > 0) {
    header("Location: index.php?status=tidak_bisa_hapus");
    exit;
}

/* =========================================================
   CEK PEMBELIAN
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM detail_pembelian
     WHERE id_produk = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ((int)$data['jumlah'] > 0) {
    header("Location: index.php?status=tidak_bisa_hapus");
    exit;
}

/* =========================================================
   HAPUS PRODUK
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM produk
     WHERE id_produk = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal_hapus");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_produk);

if (mysqli_stmt_execute($stmt)) {
    $jumlahTerhapus = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($jumlahTerhapus > 0) {
        /* CATAT LOG SETELAH PRODUK BERHASIL DIHAPUS */
        $detailLog = 'Menghapus produk "' .
            $produk['nama_produk'] .
            '" (ID: ' . $id_produk . ').';

        $logBerhasil = catatLogAktivitas(
            $conn,
            'produk',
            'Hapus Produk',
            $detailLog
        );

        if (!$logBerhasil) {
            error_log(
                'Gagal mencatat log aktivitas hapus produk ID: ' .
                $id_produk
            );
        }

        /* HAPUS GAMBAR PRODUK */
        if (!empty($produk['gambar'])) {
            $gambarPath =
                __DIR__ .
                "/../assets/images/products/" .
                $produk['gambar'];

            if (is_file($gambarPath)) {
                @unlink($gambarPath);
            }
        }

        header("Location: index.php?status=hapus");
        exit;
    }

    header("Location: index.php?status=gagal_hapus");
    exit;
}

mysqli_stmt_close($stmt);

header("Location: index.php?status=gagal_hapus");
exit;