<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekLoginAdmin();
cekAkses('cs');

/*
|--------------------------------------------------------------------------
| AMBIL ID CHAT
|--------------------------------------------------------------------------
*/

$id_chat = (int) ($_GET['id'] ?? 0);

if ($id_chat <= 0) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK CHAT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_chat,
        id_pelanggan,
        status_chat
     FROM cs_chat
     WHERE id_chat = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_chat);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| CHAT TIDAK DITEMUKAN
|--------------------------------------------------------------------------
*/

if (!$chat) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| JIKA SUDAH DILAYANI
|--------------------------------------------------------------------------
*/

if ($chat['status_chat'] === 'Dilayani') {
    header("Location: chat.php?id=" . $id_chat);
    exit;
}

/*
|--------------------------------------------------------------------------
| JIKA SUDAH SELESAI
|--------------------------------------------------------------------------
*/

if ($chat['status_chat'] === 'Selesai') {
    header("Location: chat.php?id=" . $id_chat);
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL CHAT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "UPDATE cs_chat
     SET status_chat = 'Dilayani'
     WHERE id_chat = ?
       AND status_chat = 'Menunggu'"
);

mysqli_stmt_bind_param($stmt, "i", $id_chat);
mysqli_stmt_execute($stmt);

$affected_rows = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| BERHASIL DIAMBIL DAN CATAT AKTIVITAS
|--------------------------------------------------------------------------
*/

if ($affected_rows === 1) {
    try {
        catatLogAktivitas(
            $conn,
            'cs',
            'Ambil Chat CS',
            "Admin mengambil chat CS ID: {$id_chat}, ID pelanggan: {$chat['id_pelanggan']}, status: Menunggu menjadi Dilayani."
        );
    } catch (Throwable $e) {
        // Kegagalan pencatatan log tidak membatalkan proses pengambilan chat.
    }

    header("Location: chat.php?id=" . $id_chat);
    exit;
}

/*
|--------------------------------------------------------------------------
| GAGAL MENGAMBIL CHAT
|--------------------------------------------------------------------------
*/

header("Location: index.php?error=chat_sudah_dilayani");
exit;