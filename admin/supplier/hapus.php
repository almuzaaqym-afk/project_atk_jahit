<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('supplier');

/* =====================================================
   VALIDASI REQUEST
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['hapus_supplier'])
) {
    header("Location: index.php");
    exit;
}

$id_supplier = (int)($_POST['id_supplier'] ?? 0);

if ($id_supplier <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   CEK DATA SUPPLIER
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_supplier,
        kode_supplier,
        nama_supplier
     FROM supplier
     WHERE id_supplier = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_supplier);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$supplier = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$supplier) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =====================================================
   CEK PENGGUNAAN PADA PEMBELIAN
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM pembelian
     WHERE id_supplier = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_supplier);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

$jumlah_pembelian = (int)($data['jumlah'] ?? 0);

/* =====================================================
   JIKA SUPPLIER SUDAH DIGUNAKAN
===================================================== */

if ($jumlah_pembelian > 0) {
    header("Location: index.php?status=digunakan");
    exit;
}

/* =====================================================
   HAPUS SUPPLIER
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM supplier
     WHERE id_supplier = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_supplier);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$affected_rows = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

/* =====================================================
   HASIL PENGHAPUSAN DAN LOG AKTIVITAS
===================================================== */

if ($affected_rows > 0) {
    $detailLog = 'Menghapus supplier "' .
        $supplier['nama_supplier'] .
        '" (Kode: ' . $supplier['kode_supplier'] .
        ', ID: ' . $id_supplier . ').';

    $logBerhasil = catatLogAktivitas(
        $conn,
        'supplier',
        'Hapus Supplier',
        $detailLog
    );

    if (!$logBerhasil) {
        error_log(
            'Gagal mencatat log aktivitas hapus supplier ID: ' .
            $id_supplier
        );
    }

    header("Location: index.php?status=hapus");
    exit;
}

header("Location: index.php?status=tidak_ditemukan");
exit;