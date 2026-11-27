<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../includes/hak_akses.php';

cekLoginAdmin();
cekAkses('cs');

/* =========================================================
   RESPONSE JSON
========================================================= */

function adminCsAmbilResponse(
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

/* =========================================================
   VALIDASI ID CHAT
========================================================= */

$idChat = (int) ($_GET['id_chat'] ?? 0);

if ($idChat <= 0) {
    adminCsAmbilResponse(
        false,
        'ID chat tidak valid.',
        [],
        400
    );
}

/* =========================================================
   VALIDASI CHAT
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_chat,
        id_pelanggan,
        status_chat,
        created_at,
        ended_at
     FROM cs_chat
     WHERE id_chat = ?
     LIMIT 1"
);

if (!$stmt) {
    adminCsAmbilResponse(
        false,
        'Gagal menyiapkan data percakapan.',
        [],
        500
    );
}

mysqli_stmt_bind_param($stmt, 'i', $idChat);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$chat) {
    adminCsAmbilResponse(
        false,
        'Percakapan tidak ditemukan.',
        [],
        404
    );
}

/* =========================================================
   AMBIL PESAN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_message,
        pengirim,
        id_pengirim,
        pesan,
        gambar,
        created_at
     FROM cs_message
     WHERE id_chat = ?
     ORDER BY id_message ASC"
);

if (!$stmt) {
    adminCsAmbilResponse(
        false,
        'Gagal menyiapkan daftar pesan.',
        [],
        500
    );
}

mysqli_stmt_bind_param($stmt, 'i', $idChat);
mysqli_stmt_execute($stmt);

$resultMessages = mysqli_stmt_get_result($stmt);
$messages = [];

/* =========================================================
   LOKASI FILE GAMBAR DI SERVER
========================================================= */

// Lokasi fisik: root-proyek/uploads/chat/
$folderUpload = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR
    . 'uploads'
    . DIRECTORY_SEPARATOR
    . 'chat';

/* =========================================================
   URL GAMBAR
========================================================= */

// Contoh SCRIPT_NAME:
// /project_atk_jahit/admin/cs/ambil.php
//
// Root URL proyek harus:
// /project_atk_jahit
//
// Gunakan tiga tingkat dirname, bukan dua.

$scriptPath = str_replace(
    '\\',
    '/',
    (string) ($_SERVER['SCRIPT_NAME'] ?? '')
);

$projectRoot = str_replace(
    '\\',
    '/',
    dirname(dirname(dirname($scriptPath)))
);

if ($projectRoot === '/' || $projectRoot === '.') {
    $projectRoot = '';
}

$projectRoot = rtrim($projectRoot, '/');

/* =========================================================
   FORMAT PESAN DAN GAMBAR
========================================================= */

while ($row = mysqli_fetch_assoc($resultMessages)) {

    $filename = null;
    $gambarUrl = null;

    if (!empty($row['gambar'])) {

        // Pastikan hanya nama file yang digunakan.
        $candidate = basename((string) $row['gambar']);

        $physicalImage = $folderUpload
            . DIRECTORY_SEPARATOR
            . $candidate;

        // Validasi ekstensi yang diperbolehkan.
        $namaFileValid = preg_match(
            '/^[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp|gif)$/i',
            $candidate
        ) === 1;

        if (
            $namaFileValid &&
            is_file($physicalImage) &&
            is_readable($physicalImage)
        ) {

            $filename = $candidate;

            // URL publik yang benar untuk browser.
            $gambarUrl = $projectRoot
                . '/uploads/chat/'
                . rawurlencode($candidate);
        }
    }

    $timestamp = strtotime(
        (string) ($row['created_at'] ?? '')
    );

    $messages[] = [
        'id_message' => (int) $row['id_message'],
        'pengirim' => (string) $row['pengirim'],
        'id_pengirim' => (int) $row['id_pengirim'],
        'pesan' => (string) ($row['pesan'] ?? ''),
        'gambar' => $filename,
        'gambar_url' => $gambarUrl,
        'waktu' => $timestamp
            ? date('d/m/Y H:i', $timestamp)
            : '',
        'timestamp' => $timestamp ?: 0
    ];
}

mysqli_stmt_close($stmt);

/* =========================================================
   RESPONSE
========================================================= */

adminCsAmbilResponse(
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
        'messages' => $messages
    ]
);