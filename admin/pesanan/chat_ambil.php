
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function adminChatPesananResponse(
    bool $success,
    string $message = '',
    array $extra = [],
    int $httpCode = 200
): void {
    http_response_code($httpCode);

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

if (empty($_SESSION['id_admin'])) {
    adminChatPesananResponse(
        false,
        'Silakan login sebagai admin.',
        [],
        401
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idPesanan = (int) ($_GET['id_pesanan'] ?? 0);

if ($idPesanan <= 0) {
    adminChatPesananResponse(
        false,
        'ID pesanan tidak valid.',
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI PESANAN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pesanan, id_pelanggan, status_pesanan
     FROM pesanan
     WHERE id_pesanan = ?
     LIMIT 1"
);

if (!$stmt) {
    adminChatPesananResponse(
        false,
        'Gagal memeriksa data pesanan.',
        [],
        500
    );
}

mysqli_stmt_bind_param($stmt, 'i', $idPesanan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    adminChatPesananResponse(
        false,
        'Pesanan tidak ditemukan.',
        [],
        404
    );
}

$idPelanggan = (int) $pesanan['id_pelanggan'];

$chatAktif = in_array(
    $pesanan['status_pesanan'],
    ['Diproses', 'Siap Diambil'],
    true
);

/*
|--------------------------------------------------------------------------
| AMBIL SESI CHAT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_chat, status_chat
     FROM chat_session
     WHERE id_pesanan = ?
       AND id_pelanggan = ?
     LIMIT 1"
);

if (!$stmt) {
    adminChatPesananResponse(
        false,
        'Gagal memeriksa sesi chat.',
        [],
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $idPesanan,
    $idPelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$chat) {
    adminChatPesananResponse(
        true,
        '',
        [
            'status_chat' => $chatAktif ? 'Belum Ada' : 'Selesai',
            'status_pesanan' => $pesanan['status_pesanan'],
            'messages' => []
        ]
    );
}

$idChat = (int) $chat['id_chat'];

/*
|--------------------------------------------------------------------------
| SINKRONISASI STATUS CHAT
|--------------------------------------------------------------------------
*/

if (!$chatAktif && $chat['status_chat'] !== 'Selesai') {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE chat_session
         SET status_chat = 'Selesai',
             ended_at = NOW()
         WHERE id_chat = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $idChat);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $chat['status_chat'] = 'Selesai';
}

/*
|--------------------------------------------------------------------------
| AMBIL PESAN DAN GAMBAR
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_message,
        pengirim,
        id_pengirim,
        pesan,
        gambar,
        created_at
     FROM chat_message
     WHERE id_chat = ?
     ORDER BY id_message ASC"
);

if (!$stmt) {
    adminChatPesananResponse(
        false,
        'Gagal mengambil percakapan.',
        [],
        500
    );
}

mysqli_stmt_bind_param($stmt, 'i', $idChat);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$messages = [];

while ($row = mysqli_fetch_assoc($result)) {
    $namaGambar = null;
    $gambarUrl = null;

    if (!empty($row['gambar'])) {
        $filename = basename((string) $row['gambar']);

        if (preg_match(
            '/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp|gif)$/i',
            $filename
        )) {
            $pathFile = dirname(__DIR__, 2)
                . '/uploads/chat/'
                . $filename;

            if (is_file($pathFile)) {
                $namaGambar = $filename;
                $gambarUrl = '../../uploads/chat/'
                    . rawurlencode($filename);
            }
        }
    }

    $timestamp = strtotime((string) $row['created_at']);

    $messages[] = [
        'id_message' => (int) $row['id_message'],
        'pengirim' => (string) $row['pengirim'],
        'id_pengirim' => (int) $row['id_pengirim'],
        'pesan' => (string) $row['pesan'],
        'gambar' => $namaGambar,
        'gambar_url' => $gambarUrl,
        'waktu' => $timestamp
            ? date('d/m/Y H:i', $timestamp)
            : ''
    ];
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| RESPONSE JSON
|--------------------------------------------------------------------------
*/

adminChatPesananResponse(
    true,
    '',
    [
        'status_chat' => $chat['status_chat'],
        'status_pesanan' => $pesanan['status_pesanan'],
        'messages' => $messages
    ]
);
