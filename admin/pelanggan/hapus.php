<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

// Validasi request
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['hapus_pelanggan'])
) {
    header("Location: index.php");
    exit;
}

$id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);

if ($id_pelanggan <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

// Cek pelanggan
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pelanggan
     FROM pelanggan
     WHERE id_pelanggan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pelanggan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pelanggan) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

// Cek apakah pelanggan memiliki pesanan
$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE id_pelanggan = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if ((int)$data['total'] > 0) {
    header("Location: index.php?status=tidak_bisa_hapus");
    exit;
}

// Hapus pelanggan
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM pelanggan
     WHERE id_pelanggan = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);

$berhasil = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

// Catat aktivitas jika penghapusan berhasil
if ($berhasil && $affected > 0) {
    try {
        catatLogAktivitas(
            $conn,
            'pelanggan',
            'Hapus Pelanggan',
            "Menghapus pelanggan dengan ID: {$id_pelanggan}"
        );
    } catch (Throwable $e) {
        // Kegagalan log tidak membatalkan penghapusan pelanggan.
    }

    header("Location: index.php?status=hapus");
} else {
    header("Location: index.php?status=gagal");
}

exit;