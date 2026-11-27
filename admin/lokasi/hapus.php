<?php

session_start();

/* =========================================================
   CEK LOGIN ADMIN
========================================================= */

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================================================
   KONEKSI DATABASE, HAK AKSES, DAN LOG AKTIVITAS
========================================================= */

require_once "../../config/koneksi.php";
require_once "../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekAkses('lokasi');

/* =========================================================
   VALIDASI REQUEST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_lokasi = (int) ($_POST['id_lokasi'] ?? 0);

if ($id_lokasi <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =========================================================
   CEK LOKASI / RAK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_lokasi, kode_rak, nama_lokasi
     FROM lokasi_rak
     WHERE id_lokasi = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_lokasi
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$lokasi = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$lokasi) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

$kode_rak = $lokasi['kode_rak'];

/* =========================================================
   CEK PRODUK DAN VARIAN YANG MENGGUNAKAN RAK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        (
            SELECT COUNT(*)
            FROM produk
            WHERE lokasi_rak = ?
        ) AS jumlah_produk,
        (
            SELECT COUNT(*)
            FROM produk_varian
            WHERE lokasi_rak = ?
        ) AS jumlah_varian"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $kode_rak,
    $kode_rak
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    header("Location: index.php?status=gagal");
    exit;
}

$jumlah_produk = (int) $data['jumlah_produk'];
$jumlah_varian = (int) $data['jumlah_varian'];

/* =========================================================
   TOLAK PENGHAPUSAN JIKA RAK MASIH DIGUNAKAN
========================================================= */

if ($jumlah_produk > 0 || $jumlah_varian > 0) {
    header("Location: index.php?status=digunakan");
    exit;
}

/* =========================================================
   HAPUS LOKASI / RAK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM lokasi_rak
     WHERE id_lokasi = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_lokasi
);

if (mysqli_stmt_execute($stmt)) {
    $barisTerhapus = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    if ($barisTerhapus > 0) {
        /* =================================================
           CATAT LOG HAPUS LOKASI / RAK
        ================================================= */

        $detailLog = 'Menghapus lokasi/rak "' .
            $lokasi['nama_lokasi'] .
            '" dengan kode rak ' . $kode_rak .
            ' (ID lokasi: ' . $id_lokasi . ').';

        try {
            $logBerhasil = catatLogAktivitas(
                $conn,
                'lokasi',
                'Hapus Lokasi/Rak',
                $detailLog
            );

            if (!$logBerhasil) {
                error_log(
                    'Gagal mencatat log hapus lokasi/rak: ID ' .
                    $id_lokasi
                );
            }
        } catch (Throwable $e) {
            error_log(
                'Kesalahan saat mencatat log hapus lokasi/rak ID ' .
                $id_lokasi . ': ' . $e->getMessage()
            );
        }

        header("Location: index.php?status=hapus");
        exit;
    }

    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

mysqli_stmt_close($stmt);

header("Location: index.php?status=gagal");
exit;