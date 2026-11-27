<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

/* =========================
   HARUS POST
========================= */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['update_status'])
) {
    header("Location: index.php");
    exit;
}

$id_pesanan = (int) ($_POST['id_pesanan'] ?? 0);

$status_baru = trim(
    $_POST['status_pesanan'] ?? ''
);

/* =========================
   VALIDASI ID
========================= */

if ($id_pesanan <= 0) {
    header("Location: index.php?status=gagal");
    exit;
}

/* =========================
   AMBIL STATUS SAAT INI
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT nomor_pesanan, status_pesanan
     FROM pesanan
     WHERE id_pesanan = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pesanan
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================
   PESANAN TIDAK DITEMUKAN
========================= */

if (!$pesanan) {
    header("Location: index.php?status=gagal");
    exit;
}

$status_lama = $pesanan['status_pesanan'];
$nomor_pesanan = $pesanan['nomor_pesanan'];

/* =========================
   CEK TRANSISI STATUS
========================= */

/*
    Alur yang diperbolehkan:

    Diproses
        -> Siap Diambil
        -> Dibatalkan

    Siap Diambil
        -> Selesai
        -> Dibatalkan

    Selesai
        -> TIDAK BISA DIUBAH

    Dibatalkan
        -> TIDAK BISA DIUBAH

    Menunggu Pembayaran
        -> TIDAK BISA DIUBAH DARI HALAMAN INI

    Menunggu Verifikasi
        -> TIDAK BISA DIUBAH DARI HALAMAN INI
*/

$status_diizinkan = false;

/* =========================
   DARI DIPROSES
========================= */

if ($status_lama === 'Diproses') {

    if (
        $status_baru === 'Siap Diambil' ||
        $status_baru === 'Dibatalkan'
    ) {
        $status_diizinkan = true;
    }

}

/* =========================
   DARI SIAP DIAMBIL
========================= */

elseif ($status_lama === 'Siap Diambil') {

    if (
        $status_baru === 'Selesai' ||
        $status_baru === 'Dibatalkan'
    ) {
        $status_diizinkan = true;
    }

}

/* =========================
   STATUS LAIN
========================= */

else {

    $status_diizinkan = false;

}

/* =========================
   TOLAK JIKA TIDAK DIIZINKAN
========================= */

if (!$status_diizinkan) {

    header(
        "Location: detail.php?id=" .
        $id_pesanan .
        "&status=gagal"
    );

    exit;
}

/* =========================
   UPDATE STATUS
========================= */

$stmt = mysqli_prepare(
    $conn,
    "UPDATE pesanan
     SET status_pesanan = ?
     WHERE id_pesanan = ?
       AND status_pesanan = ?"
);

if (!$stmt) {

    header(
        "Location: detail.php?id=" .
        $id_pesanan .
        "&status=gagal"
    );

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "sis",
    $status_baru,
    $id_pesanan,
    $status_lama
);

$berhasil = mysqli_stmt_execute($stmt);

$barisBerubah = $berhasil
    ? mysqli_stmt_affected_rows($stmt)
    : 0;

mysqli_stmt_close($stmt);

/* =========================
   CATAT LOG JIKA BERHASIL
========================= */

if ($berhasil && $barisBerubah === 1) {

    $detailLog = 'Mengubah status pesanan "' .
        $nomor_pesanan .
        '" (ID pesanan: ' . $id_pesanan .
        ') dari "' . $status_lama .
        '" menjadi "' . $status_baru . '".';

    try {
        $logBerhasil = catatLogAktivitas(
            $conn,
            'pesanan',
            'Perbarui Status Pesanan',
            $detailLog
        );

        if (!$logBerhasil) {
            error_log(
                'Gagal mencatat log perubahan status pesanan: ID ' .
                $id_pesanan
            );
        }
    } catch (Throwable $e) {
        error_log(
            'Kesalahan saat mencatat log status pesanan ID ' .
            $id_pesanan . ': ' . $e->getMessage()
        );
    }

    header(
        "Location: detail.php?id=" .
        $id_pesanan .
        "&status=berhasil"
    );

} else {

    header(
        "Location: detail.php?id=" .
        $id_pesanan .
        "&status=gagal"
    );

}

exit;