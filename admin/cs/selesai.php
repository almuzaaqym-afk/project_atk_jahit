<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

cekLoginAdmin();
cekAkses('cs');

/*
|--------------------------------------------------------------------------
| AMBIL DATA
|--------------------------------------------------------------------------
*/

$id_chat = (int) ($_POST['id_chat'] ?? 0);

/*
|--------------------------------------------------------------------------
| VALIDASI
|--------------------------------------------------------------------------
*/

if ($id_chat <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID chat tidak valid.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK CHAT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_chat, id_pelanggan, status_chat
     FROM cs_chat
     WHERE id_chat = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_chat);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$chat) {
    echo json_encode([
        'success' => false,
        'message' => 'Chat tidak ditemukan.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK STATUS
|--------------------------------------------------------------------------
*/

if ($chat['status_chat'] === 'Selesai') {
    echo json_encode([
        'success' => false,
        'message' => 'Chat ini sudah selesai.'
    ]);
    exit;
}

if ($chat['status_chat'] !== 'Dilayani') {
    echo json_encode([
        'success' => false,
        'message' => 'Chat belum berstatus Dilayani.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SELESAIKAN CHAT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "UPDATE cs_chat
     SET
        status_chat = 'Selesai',
        ended_at = NOW()
     WHERE id_chat = ?
       AND status_chat = 'Dilayani'"
);

mysqli_stmt_bind_param($stmt, "i", $id_chat);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyelesaikan chat.'
    ]);
    exit;
}

$affected = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| VALIDASI HASIL UPDATE
|--------------------------------------------------------------------------
*/

if ($affected !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Chat gagal diselesaikan.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CATAT LOG AKTIVITAS
|--------------------------------------------------------------------------
*/

try {
    catatLogAktivitas(
        $conn,
        'cs',
        'Selesaikan Chat CS',
        "Admin menyelesaikan chat CS ID: {$id_chat}, ID pelanggan: {$chat['id_pelanggan']}, status: Dilayani menjadi Selesai."
    );
} catch (Throwable $e) {
    // Kegagalan pencatatan log tidak membatalkan penyelesaian chat.
}

/*
|--------------------------------------------------------------------------
| RESPONS BERHASIL
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'message' => 'Chat berhasil diselesaikan.'
]);

exit;