
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function csKirimResponse(
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    csKirimResponse(
        false,
        'Metode permintaan tidak diizinkan.',
        [],
        405
    );
}

if (empty($_SESSION['id_pelanggan'])) {
    csKirimResponse(
        false,
        'Silakan login terlebih dahulu.',
        [],
        401
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idPelanggan = (int) $_SESSION['id_pelanggan'];
$idChat = (int) ($_POST['id_chat'] ?? 0);
$pesan = trim((string) ($_POST['pesan'] ?? ''));

$fileAda = isset($_FILES['gambar'])
    && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;

/*
|--------------------------------------------------------------------------
| VALIDASI INPUT
|--------------------------------------------------------------------------
*/

if ($idChat <= 0) {
    csKirimResponse(
        false,
        'ID chat tidak valid.',
        [],
        400
    );
}

if (mb_strlen($pesan, 'UTF-8') > 10000) {
    csKirimResponse(
        false,
        'Pesan maksimal 10.000 karakter.',
        [],
        400
    );
}

if ($pesan === '' && !$fileAda) {
    csKirimResponse(
        false,
        'Tulis pesan atau pilih gambar terlebih dahulu.',
        [],
        400
    );
}

if (
    $fileAda
    && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_OK
) {
    csKirimResponse(
        false,
        'Gambar gagal diunggah. Silakan coba kembali.',
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI KEPEMILIKAN DAN STATUS CHAT
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

if (!$stmt) {
    csKirimResponse(
        false,
        'Gagal memeriksa sesi chat.',
        [],
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $idChat,
    $idPelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$chat) {
    csKirimResponse(
        false,
        'Percakapan tidak ditemukan.',
        [],
        404
    );
}

if ($chat['status_chat'] !== 'Dilayani') {
    csKirimResponse(
        false,
        'Customer Service belum menerima atau sudah mengakhiri chat ini.'
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI DAN SIMPAN GAMBAR
|--------------------------------------------------------------------------
*/

$namaGambar = null;
$lokasiGambar = null;

if ($fileAda) {
    $file = $_FILES['gambar'];

    if (
        (int) $file['size'] <= 0
        || (int) $file['size'] > 5 * 1024 * 1024
    ) {
        csKirimResponse(
            false,
            'Ukuran gambar maksimal 5 MB.',
            [],
            400
        );
    }

    if (
        empty($file['tmp_name'])
        || !is_uploaded_file($file['tmp_name'])
    ) {
        csKirimResponse(
            false,
            'File gambar tidak valid.',
            [],
            400
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $tipeGambar = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!isset($tipeGambar[$mime])) {
        csKirimResponse(
            false,
            'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            [],
            400
        );
    }

    if (@getimagesize($file['tmp_name']) === false) {
        csKirimResponse(
            false,
            'File yang dipilih bukan gambar yang valid.',
            [],
            400
        );
    }

    $folderUpload = dirname(__DIR__, 2) . '/uploads/chat';

    if (
        !is_dir($folderUpload)
        && !mkdir($folderUpload, 0755, true)
        && !is_dir($folderUpload)
    ) {
        csKirimResponse(
            false,
            'Folder penyimpanan gambar tidak dapat dibuat.',
            [],
            500
        );
    }

    $namaGambar = 'cs_'
        . bin2hex(random_bytes(16))
        . '.'
        . $tipeGambar[$mime];

    $lokasiGambar = $folderUpload . '/' . $namaGambar;

    if (!move_uploaded_file($file['tmp_name'], $lokasiGambar)) {
        csKirimResponse(
            false,
            'Gambar gagal disimpan.',
            [],
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| SIMPAN PESAN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO cs_message
        (id_chat, pengirim, id_pengirim, pesan, gambar)
     VALUES (?, 'Pelanggan', ?, ?, ?)"
);

if (!$stmt) {
    if ($lokasiGambar && is_file($lokasiGambar)) {
        @unlink($lokasiGambar);
    }

    csKirimResponse(
        false,
        'Pesan gagal disiapkan untuk disimpan.',
        [],
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    'iiss',
    $idChat,
    $idPelanggan,
    $pesan,
    $namaGambar
);

$berhasil = mysqli_stmt_execute($stmt);
$idMessage = (int) mysqli_insert_id($conn);
$errorDatabase = mysqli_stmt_error($stmt);

mysqli_stmt_close($stmt);

if (!$berhasil) {
    if ($lokasiGambar && is_file($lokasiGambar)) {
        @unlink($lokasiGambar);
    }

    error_log('[CS Kirim] ' . $errorDatabase);

    csKirimResponse(
        false,
        'Pesan gagal disimpan ke database.',
        [],
        500
    );
}

csKirimResponse(
    true,
    'Pesan berhasil dikirim.',
    [
        'id_message' => $idMessage,
        'gambar' => $namaGambar
    ]
);
