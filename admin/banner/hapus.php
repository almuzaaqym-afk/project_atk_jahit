<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

/* =========================================================
   VALIDASI REQUEST
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['hapus_banner'])
) {
    header("Location: index.php");
    exit;
}

$id_banner = (int) ($_POST['id_banner'] ?? 0);

if ($id_banner <= 0) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   AMBIL DATA BANNER
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_banner, judul, gambar
     FROM banner
     WHERE id_banner = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_banner);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$banner = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$banner) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================================================
   HAPUS DATABASE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM banner
     WHERE id_banner = ?"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_banner);

$berhasil = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

/* =========================================================
   HASIL PENGHAPUSAN
========================================================= */

if ($berhasil && $affected > 0) {

    /* CATAT LOG AKTIVITAS */

    try {
        catatLogAktivitas(
            $conn,
            'banner',
            'Hapus Banner',
            "Menghapus banner ID: {$banner['id_banner']}, judul: {$banner['judul']}, nama gambar: {$banner['gambar']}."
        );
    } catch (Throwable $e) {
        // Kegagalan log tidak membatalkan penghapusan banner.
    }

    /* HAPUS FILE GAMBAR */

    if (!empty($banner['gambar'])) {

        $file = __DIR__
            . "/../assets/images/banners/"
            . basename($banner['gambar']);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    header("Location: index.php?status=hapus");
    exit;
}

header("Location: index.php?status=gagal");
exit;