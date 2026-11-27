
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function adminChatKirimResponse(
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

if (empty($_SESSION['id_admin'])) {
    adminChatKirimResponse(
        false,
        'Sesi admin tidak ditemukan. Silakan login kembali.',
        [],
        401
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    adminChatKirimResponse(
        false,
        'Metode permintaan tidak diizinkan.',
        [],
        405
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idAdmin = (int) $_SESSION['id_admin'];
$idPesanan = (int) ($_POST['id_pesanan'] ?? 0);
$pesan = trim((string) ($_POST['pesan'] ?? ''));

$fileAda = isset($_FILES['gambar'])
    && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;

/*
|--------------------------------------------------------------------------
| VALIDASI INPUT
|--------------------------------------------------------------------------
*/

if ($idPesanan <= 0) {
    adminChatKirimResponse(
        false,
        'ID pesanan tidak valid.',
        [],
        400
    );
}

if (mb_strlen($pesan, 'UTF-8') > 1000) {
    adminChatKirimResponse(
        false,
        'Pesan maksimal 1000 karakter.',
        [],
        400
    );
}

if ($pesan === '' && !$fileAda) {
    adminChatKirimResponse(
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
    adminChatKirimResponse(
        false,
        'Gambar gagal diunggah. Silakan coba kembali.',
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
    adminChatKirimResponse(
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
    adminChatKirimResponse(
        false,
        'Pesanan tidak ditemukan.',
        [],
        404
    );
}

$idPelanggan = (int) $pesanan['id_pelanggan'];

if (!in_array(
    $pesanan['status_pesanan'],
    ['Diproses', 'Siap Diambil'],
    true
)) {
    adminChatKirimResponse(
        false,
        'Chat sudah ditutup karena status pesanan adalah '
        . $pesanan['status_pesanan'] . '.'
    );
}

/*
|--------------------------------------------------------------------------
| CARI CHAT SESSION
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
    adminChatKirimResponse(
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

/*
|--------------------------------------------------------------------------
| BUAT CHAT JIKA BELUM ADA
|--------------------------------------------------------------------------
*/

if (!$chat) {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO chat_session
            (id_pesanan, id_pelanggan, status_chat)
         VALUES (?, ?, 'Aktif')"
    );

    if (!$stmt) {
        adminChatKirimResponse(
            false,
            'Chat gagal disiapkan.',
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

    $created = mysqli_stmt_execute($stmt);
    $idChat = (int) mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    if (!$created || $idChat <= 0) {
        adminChatKirimResponse(
            false,
            'Chat gagal dibuat.',
            [],
            500
        );
    }
} else {
    $idChat = (int) $chat['id_chat'];

    if ($chat['status_chat'] !== 'Aktif') {
        adminChatKirimResponse(
            false,
            'Chat sudah ditutup.'
        );
    }
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
        adminChatKirimResponse(
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
        adminChatKirimResponse(
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
        adminChatKirimResponse(
            false,
            'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            [],
            400
        );
    }

    if (@getimagesize($file['tmp_name']) === false) {
        adminChatKirimResponse(
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
        adminChatKirimResponse(
            false,
            'Folder penyimpanan gambar tidak dapat dibuat.',
            [],
            500
        );
    }

    $namaGambar = 'pesanan_'
        . bin2hex(random_bytes(16))
        . '.'
        . $tipeGambar[$mime];

    $lokasiGambar = $folderUpload . '/' . $namaGambar;

    if (!move_uploaded_file($file['tmp_name'], $lokasiGambar)) {
        adminChatKirimResponse(
            false,
            'Gambar gagal disimpan.',
            [],
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| SIMPAN PESAN ADMIN
|--------------------------------------------------------------------------
*/

$pengirim = 'Admin';

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO chat_message
        (id_chat, pengirim, id_pengirim, pesan, gambar)
     VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    if ($lokasiGambar && is_file($lokasiGambar)) {
        @unlink($lokasiGambar);
    }

    adminChatKirimResponse(
        false,
        'Pesan gagal disiapkan untuk disimpan.',
        [],
        500
    );
}

mysqli_stmt_bind_param(
    $stmt,
    'isiss',
    $idChat,
    $pengirim,
    $idAdmin,
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

    error_log(
        '[Admin Chat Pesanan] ' . $errorDatabase
    );

    adminChatKirimResponse(
        false,
        'Pesan gagal disimpan ke database.',
        [],
        500
    );
}

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

adminChatKirimResponse(
    true,
    'Balasan berhasil dikirim.',
    [
        'id_message' => $idMessage,
        'gambar' => $namaGambar
    ]
);
