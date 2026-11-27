
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function csAmbilResponse(
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
    csAmbilResponse(
        false,
        'Silakan login terlebih dahulu.',
        [],
        401
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idPelanggan = (int) $_SESSION['id_pelanggan'];
$idChat = (int) ($_GET['id_chat'] ?? 0);

if ($idChat <= 0) {
    csAmbilResponse(
        false,
        'ID chat tidak valid.',
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI CHAT MILIK PELANGGAN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_chat, id_pelanggan, status_chat, created_at, ended_at
     FROM cs_chat
     WHERE id_chat = ?
       AND id_pelanggan = ?
     LIMIT 1"
);

if (!$stmt) {
    csAmbilResponse(false, 'Gagal memeriksa chat.', [], 500);
}

mysqli_stmt_bind_param($stmt, 'ii', $idChat, $idPelanggan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$chat) {
    csAmbilResponse(false, 'Chat tidak ditemukan.', [], 404);
}

/*
|--------------------------------------------------------------------------
| DATA NOMOR ANTREAN
|--------------------------------------------------------------------------
*/

$queuePosition = 0;
$queueTotal = 0;

if ($chat['status_chat'] === 'Menunggu') {
    $createdAt = $chat['created_at'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total
         FROM cs_chat
         WHERE status_chat = 'Menunggu'
           AND (
                created_at < ?
                OR (
                    created_at = ?
                    AND id_chat <= ?
                )
           )"
    );

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            'ssi',
            $createdAt,
            $createdAt,
            $idChat
        );

        mysqli_stmt_execute($stmt);

        $queueResult = mysqli_stmt_get_result($stmt);
        $queueRow = mysqli_fetch_assoc($queueResult);

        mysqli_stmt_close($stmt);

        $queuePosition = (int) ($queueRow['total'] ?? 0);
    }

    $resultTotal = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM cs_chat
         WHERE status_chat = 'Menunggu'"
    );

    if ($resultTotal) {
        $queueRow = mysqli_fetch_assoc($resultTotal);
        $queueTotal = (int) ($queueRow['total'] ?? 0);

        mysqli_free_result($resultTotal);
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL PESAN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_message, pengirim, id_pengirim,
            pesan, gambar, created_at
     FROM cs_message
     WHERE id_chat = ?
     ORDER BY id_message ASC"
);

if (!$stmt) {
    csAmbilResponse(false, 'Gagal mengambil pesan.', [], 500);
}

mysqli_stmt_bind_param($stmt, 'i', $idChat);
mysqli_stmt_execute($stmt);

$messageResult = mysqli_stmt_get_result($stmt);
$messages = [];

$scriptPath = str_replace(
    '\\',
    '/',
    (string) ($_SERVER['SCRIPT_NAME'] ?? '')
);

/*
 * /project_atk_jahit/pelanggan/actions/cs_ambil.php
 * Hasil dirname 3 tingkat: /project_atk_jahit
 */
$projectRoot = str_replace(
    '\\',
    '/',
    dirname(dirname(dirname($scriptPath)))
);

$projectRoot = rtrim($projectRoot, '/');

if ($projectRoot === '' || $projectRoot === '.') {
    $projectRoot = '/project_atk_jahit';
}

$uploadFolder = dirname(__DIR__, 2) . '/uploads/chat';

while ($row = mysqli_fetch_assoc($messageResult)) {
    $filename = null;
    $imageUrl = null;

    if (!empty($row['gambar'])) {
        $candidate = basename((string) $row['gambar']);

        if (
            preg_match(
                '/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp|gif)$/i',
                $candidate
            )
            && is_file($uploadFolder . '/' . $candidate)
        ) {
            $filename = $candidate;

            $imageUrl = $projectRoot
                . '/uploads/chat/'
                . rawurlencode($candidate);
        }
    }

    $timestamp = strtotime((string) $row['created_at']);

    $messages[] = [
        'id_message' => (int) $row['id_message'],
        'pengirim' => (string) $row['pengirim'],
        'id_pengirim' => (int) $row['id_pengirim'],
        'pesan' => (string) $row['pesan'],
        'gambar' => $filename,
        'gambar_url' => $imageUrl,
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

csAmbilResponse(
    true,
    '',
    [
        'chat' => [
            'id_chat' => (int) $chat['id_chat'],
            'id_pelanggan' => (int) $chat['id_pelanggan'],
            'status_chat' => (string) $chat['status_chat'],
            'created_at' => $chat['created_at'],
            'ended_at' => $chat['ended_at']
        ],
        'queue_position' => $queuePosition,
        'queue_total' => $queueTotal,
        'messages' => $messages
    ]
);
