
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function csAntrianResponse(
    bool $success,
    string $message,
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
    csAntrianResponse(
        false,
        'Silakan login terlebih dahulu.',
        [],
        401
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    csAntrianResponse(
        false,
        'Metode permintaan tidak diizinkan.',
        [],
        405
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idPelanggan = (int) $_SESSION['id_pelanggan'];

/*
|--------------------------------------------------------------------------
| MENGHITUNG POSISI ANTREAN
|--------------------------------------------------------------------------
*/

function csHitungPosisiAntrean(
    mysqli $conn,
    int $idChat
): array {
    $hasil = [
        'queue_position' => 0,
        'queue_total' => 0,
        'nomor_antrean' => 0
    ];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT created_at, status_chat
         FROM cs_chat
         WHERE id_chat = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return $hasil;
    }

    mysqli_stmt_bind_param($stmt, 'i', $idChat);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $chat = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$chat || $chat['status_chat'] !== 'Menunggu') {
        return $hasil;
    }

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

    if (!$stmt) {
        return $hasil;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssi',
        $createdAt,
        $createdAt,
        $idChat
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    $posisi = (int) ($row['total'] ?? 0);

    $resultTotal = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM cs_chat
         WHERE status_chat = 'Menunggu'"
    );

    $total = 0;

    if ($resultTotal) {
        $rowTotal = mysqli_fetch_assoc($resultTotal);
        $total = (int) ($rowTotal['total'] ?? 0);

        mysqli_free_result($resultTotal);
    }

    $hasil['queue_position'] = $posisi;
    $hasil['queue_total'] = $total;
    $hasil['nomor_antrean'] = $posisi;

    return $hasil;
}

/*
|--------------------------------------------------------------------------
| CEK APAKAH PELANGGAN SUDAH MEMILIKI CHAT AKTIF
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_chat, status_chat
     FROM cs_chat
     WHERE id_pelanggan = ?
       AND status_chat IN ('Menunggu', 'Dilayani')
     ORDER BY id_chat DESC
     LIMIT 1"
);

if (!$stmt) {
    csAntrianResponse(
        false,
        'Gagal memeriksa sesi Customer Service.',
        [],
        500
    );
}

mysqli_stmt_bind_param($stmt, 'i', $idPelanggan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| SUDAH ADA CHAT AKTIF
|--------------------------------------------------------------------------
*/

if ($chat) {
    $idChat = (int) $chat['id_chat'];
    $queue = csHitungPosisiAntrean($conn, $idChat);

    csAntrianResponse(
        true,
        'Kamu masih memiliki sesi Customer Service.',
        array_merge([
            'id_chat' => $idChat,
            'status_chat' => $chat['status_chat']
        ], $queue)
    );
}

/*
|--------------------------------------------------------------------------
| BUAT CHAT BARU
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO cs_chat
        (id_pelanggan, status_chat)
     VALUES (?, 'Menunggu')"
);

if (!$stmt) {
    csAntrianResponse(
        false,
        'Chat tidak dapat dibuat.',
        [],
        500
    );
}

mysqli_stmt_bind_param($stmt, 'i', $idPelanggan);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    csAntrianResponse(
        false,
        'Gagal masuk antrean Customer Service.',
        [],
        500
    );
}

$idChat = (int) mysqli_insert_id($conn);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| KIRIM NOMOR ANTREAN
|--------------------------------------------------------------------------
*/

$queue = csHitungPosisiAntrean($conn, $idChat);

csAntrianResponse(
    true,
    'Kamu berhasil masuk antrean Customer Service.',
    array_merge([
        'id_chat' => $idChat,
        'status_chat' => 'Menunggu'
    ], $queue)
);
