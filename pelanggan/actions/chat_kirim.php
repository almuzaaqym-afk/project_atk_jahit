
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function chatPesananResponse(
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
    chatPesananResponse(
        false,
        'Metode permintaan tidak diizinkan.',
        [],
        405
    );
}

if (empty($_SESSION['id_pelanggan'])) {
    chatPesananResponse(
        false,
        'Sesi pelanggan tidak ditemukan.',
        [],
        401
    );
}

require_once __DIR__ . '/../../config/koneksi.php';

$idPelanggan = (int) $_SESSION['id_pelanggan'];
$idPesanan = (int) ($_POST['id_pesanan'] ?? 0);
$pesan = trim((string) ($_POST['pesan'] ?? ''));

/*
|--------------------------------------------------------------------------
| VALIDASI PESAN DAN GAMBAR
|--------------------------------------------------------------------------
*/

$fileAda = isset($_FILES['gambar'])
    && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;

if ($idPesanan <= 0) {
    chatPesananResponse(false, 'Pesanan tidak valid.', [], 400);
}

if (mb_strlen($pesan, 'UTF-8') > 1000) {
    chatPesananResponse(
        false,
        'Pesan maksimal 1000 karakter.',
        [],
        400
    );
}

if ($pesan === '' && !$fileAda) {
    chatPesananResponse(
        false,
        'Tulis pesan atau pilih gambar terlebih dahulu.',
        [],
        400
    );
}

if ($fileAda && $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
    chatPesananResponse(
        false,
        'Gambar gagal diunggah. Silakan coba kembali.',
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
    chatPesananResponse(
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
    chatPesananResponse(
        false,
        'Pesanan tidak ditemukan.',
        [],
        404
    );
}

/*
|--------------------------------------------------------------------------
| VALIDASI STATUS PESANAN
|--------------------------------------------------------------------------
*/

if (!in_array(
    $pesanan['status_pesanan'],
    ['Diproses', 'Siap Diambil'],
    true
)) {
    chatPesananResponse(
        false,
        'Chat sudah tidak dapat digunakan karena status pesanan '
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
    chatPesananResponse(false, 'Gagal memeriksa sesi chat.', [], 500);
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
        chatPesananResponse(false, 'Chat gagal dibuat.', [], 500);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ii',
        $idPesanan,
        $idPelanggan
    );

    $berhasilBuatChat = mysqli_stmt_execute($stmt);
    $idChat = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!$berhasilBuatChat || $idChat <= 0) {
        chatPesananResponse(false, 'Chat gagal dibuat.', [], 500);
    }
} else {
    $idChat = (int) $chat['id_chat'];

    if ($chat['status_chat'] !== 'Aktif') {
        chatPesananResponse(false, 'Chat sudah ditutup.');
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

    if ((int) $file['size'] <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
        chatPesananResponse(
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
        chatPesananResponse(
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
        chatPesananResponse(
            false,
            'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            [],
            400
        );
    }

    $infoGambar = @getimagesize($file['tmp_name']);

    if ($infoGambar === false) {
        chatPesananResponse(
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
        chatPesananResponse(
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
        chatPesananResponse(
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

$pengirim = 'Pelanggan';

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

    chatPesananResponse(
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

    error_log('Chat pesanan gagal disimpan: ' . $errorDatabase);

    chatPesananResponse(
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

chatPesananResponse(
    true,
    'Pesan berhasil dikirim.',
    [
        'id_message' => $idMessage,
        'gambar' => $namaGambar
    ]
);
