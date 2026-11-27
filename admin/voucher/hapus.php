<?php

session_start();

/* =========================================================
   CEK LOGIN ADMIN
========================================================= */

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

/* =========================================================
   CEK REQUEST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* =========================================================
   CEK TOMBOL HAPUS
========================================================= */

if (!isset($_POST['hapus_voucher'])) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   AMBIL ID VOUCHER
========================================================= */

$id_voucher = isset($_POST['id_voucher'])
    ? (int) $_POST['id_voucher']
    : 0;

if ($id_voucher <= 0) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   CEK VOUCHER
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_voucher, kode_voucher, nama_voucher
     FROM voucher
     WHERE id_voucher = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_voucher
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$voucher = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   VOUCHER TIDAK DITEMUKAN
========================================================= */

if (!$voucher) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   HAPUS VOUCHER
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM voucher
     WHERE id_voucher = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_voucher
);

$berhasil = mysqli_stmt_execute($stmt);
$jumlah_dihapus = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

/* =========================================================
   HASIL DAN LOG AKTIVITAS
========================================================= */

if ($berhasil && $jumlah_dihapus > 0) {

    try {
        catatLogAktivitas(
            $conn,
            'voucher',
            'Hapus Voucher',
            "Menghapus voucher ID: {$voucher['id_voucher']}, kode: {$voucher['kode_voucher']}, nama: {$voucher['nama_voucher']}."
        );
    } catch (Throwable $e) {
        // Kegagalan pencatatan log tidak membatalkan penghapusan voucher.
    }

    header("Location: index.php?status=hapus");
    exit;
}

header("Location: index.php?status=gagal");
exit;