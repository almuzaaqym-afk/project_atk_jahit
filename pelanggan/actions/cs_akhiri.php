<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_pelanggan'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
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
    "SELECT id_chat, status_chat
     FROM cs_chat
     WHERE id_chat = ?
       AND id_pelanggan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_chat,
    $id_pelanggan
);

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
        'message' => 'Percakapan ini sudah selesai.'
    ]);
    exit;
}

if ($chat['status_chat'] !== 'Dilayani') {
    echo json_encode([
        'success' => false,
        'message' => 'Percakapan belum dapat diakhiri.'
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
       AND id_pelanggan = ?
       AND status_chat = 'Dilayani'"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_chat,
    $id_pelanggan
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengakhiri percakapan.'
    ]);

    exit;
}


$affected = mysqli_stmt_affected_rows($stmt);

mysqli_stmt_close($stmt);


if ($affected <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Percakapan gagal diakhiri.'
    ]);
    exit;
}


echo json_encode([
    'success' => true,
    'message' => 'Percakapan berhasil diakhiri.'
]);

exit;