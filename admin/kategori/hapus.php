<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

/* =========================================================
   ID KATEGORI
========================================================= */

$id_kategori = (int) ($_GET['id'] ?? 0);

if ($id_kategori <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =========================================================
   CEK KATEGORI
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     WHERE id_kategori = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kategori
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$kategori = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$kategori) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =========================================================
   CEK APAKAH MASIH DIGUNAKAN PRODUK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produk
     WHERE id_kategori = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kategori
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   JIKA MASIH DIGUNAKAN
========================================================= */

if ((int) ($data['total'] ?? 0) > 0) {
    header("Location: index.php?status=digunakan");
    exit;
}

/* =========================================================
   HAPUS KATEGORI
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM kategori
     WHERE id_kategori = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kategori
);

if (mysqli_stmt_execute($stmt)) {
    $barisTerhapus = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    if ($barisTerhapus > 0) {

        /* =================================================
           CATAT LOG HAPUS KATEGORI
        ================================================= */

        $detailLog = 'Menghapus kategori "' .
            $kategori['nama_kategori'] .
            '" (ID kategori: ' . $id_kategori . ').';

        try {
            $logBerhasil = catatLogAktivitas(
                $conn,
                'kategori',
                'Hapus Kategori',
                $detailLog
            );

            if (!$logBerhasil) {
                error_log(
                    'Gagal mencatat log hapus kategori: ID ' .
                    $id_kategori
                );
            }
        } catch (Throwable $e) {
            error_log(
                'Kesalahan saat mencatat log hapus kategori ID ' .
                $id_kategori . ': ' . $e->getMessage()
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