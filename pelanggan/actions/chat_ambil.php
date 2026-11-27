
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function chatAmbilResponse(
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

if (empty($_SESSION['id_pelanggan'])) {
    chatAmbilResponse(
        false,
        'Sesi pelanggan tidak ditemukan.',
        [],
        401
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idPelanggan = (int) $_SESSION['id_pelanggan'];
$idPesanan = (int) ($_GET['id_pesanan'] ?? 0);

if ($idPesanan <= 0) {
    chatAmbilResponse(
        false,
        'Pesanan tidak valid.',
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI PESANAN MILIK PELANGGAN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pesanan, status_pesanan
     FROM pesanan
     WHERE id_pesanan = ?
       AND id_pelanggan = ?
     LIMIT 1"
);

if (!$stmt) {
    chatAmbilResponse(
        false,
        'Gagal memeriksa pesanan.',
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
$pesanan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    chatAmbilResponse(
        false,
        'Pesanan tidak ditemukan.',
        [],
        404
    );
}

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
    chatAmbilResponse(
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
    chatAmbilResponse(
        true,
        'Belum ada percakapan.',
        [
            'status_chat' => 'Belum Ada',
            'messages' => []
        ]
    );
}

$idChat = (int) $chat['id_chat'];

/*
|--------------------------------------------------------------------------
| AMBIL PESAN TEKS DAN GAMBAR
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
    chatAmbilResponse(
        false,
        'Gagal menyiapkan daftar pesan.',
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
        /*
         * Hanya izinkan nama file, bukan path arbitrer.
         */
        $namaFile = basename((string) $row['gambar']);

        if (
            preg_match(
                '/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp|gif)$/i',
                $namaFile
            )
        ) {
            $namaGambar = $namaFile;

            /*
             * URL relatif ini dapat dipakai oleh halaman
             * pelanggan/user dan admin/pesanan.
             */
            $gambarUrl = '../../uploads/chat/'
                . rawurlencode($namaFile);
        }
    }

    $timestamp = strtotime($row['created_at']);

    $messages[] = [
        'id_message' => (int) $row['id_message'],
        'pengirim' => (string) $row['pengirim'],
        'id_pengirim' => (int) $row['id_pengirim'],
        'pesan' => (string) $row['pesan'],
        'gambar' => $namaGambar,
        'gambar_url' => $gambarUrl,
        'waktu' => $timestamp
            ? date('d/m/Y H:i', $timestamp)
            : '',
        'timestamp' => $timestamp ?: 0
    ];
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

chatAmbilResponse(
    true,
    '',
    [
        'status_chat' => (string) $chat['status_chat'],
        'messages' => $messages
    ]
);
