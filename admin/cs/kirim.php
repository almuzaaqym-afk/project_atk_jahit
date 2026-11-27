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

function adminCsKirimResponse(
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

/* =========================================================
   VALIDASI REQUEST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    adminCsKirimResponse(
        false,
        'Metode permintaan tidak diizinkan.',
        [],
        405
    );
}

$idAdmin = (int) ($_SESSION['id_admin'] ?? 0);
$idChat = (int) ($_POST['id_chat'] ?? 0);
$pesan = trim((string) ($_POST['pesan'] ?? ''));

$fileAda = isset($_FILES['gambar'])
    && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;

if ($idAdmin <= 0) {
    adminCsKirimResponse(
        false,
        'Sesi admin tidak valid.',
        [],
        401
    );
}

if ($idChat <= 0) {
    adminCsKirimResponse(
        false,
        'ID chat tidak valid.',
        [],
        400
    );
}

$panjangPesan = function_exists('mb_strlen')
    ? mb_strlen($pesan, 'UTF-8')
    : strlen($pesan);

if ($panjangPesan > 10000) {
    adminCsKirimResponse(
        false,
        'Pesan maksimal 10.000 karakter.',
        [],
        400
    );
}

if ($pesan === '' && !$fileAda) {
    adminCsKirimResponse(
        false,
        'Tulis pesan atau pilih gambar terlebih dahulu.',
        [],
        400
    );
}

/* =========================================================
   VALIDASI CHAT
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_chat, status_chat
     FROM cs_chat
     WHERE id_chat = ?
     LIMIT 1"
);

if (!$stmt) {
    adminCsKirimResponse(
        false,
        'Gagal memeriksa percakapan.',
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
    adminCsKirimResponse(
        false,
        'Chat tidak ditemukan.',
        [],
        404
    );
}

if ($chat['status_chat'] !== 'Dilayani') {
    adminCsKirimResponse(
        false,
        'Chat belum berstatus Dilayani atau sudah ditutup.',
        [],
        400
    );
}

/* =========================================================
   VALIDASI STATUS UPLOAD
========================================================= */

if (
    $fileAda &&
    (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_OK
) {
    $uploadError = (int) $_FILES['gambar']['error'];

    if (
        $uploadError === UPLOAD_ERR_INI_SIZE ||
        $uploadError === UPLOAD_ERR_FORM_SIZE
    ) {
        $pesanError = 'Ukuran gambar melebihi batas upload server.';
    } elseif ($uploadError === UPLOAD_ERR_NO_TMP_DIR) {
        $pesanError = 'Folder sementara upload tidak tersedia.';
    } elseif ($uploadError === UPLOAD_ERR_CANT_WRITE) {
        $pesanError = 'Server gagal menulis file gambar.';
    } else {
        $pesanError = 'Gambar gagal diunggah. Silakan coba kembali.';
    }

    adminCsKirimResponse(
        false,
        $pesanError,
        [],
        400
    );
}

/* =========================================================
   PERSIAPAN GAMBAR
========================================================= */

$namaGambar = null;
$lokasiGambar = null;
$gambarUrl = null;

if ($fileAda) {

    $file = $_FILES['gambar'];

    if (
        empty($file['tmp_name']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        adminCsKirimResponse(
            false,
            'File gambar tidak valid.',
            [],
            400
        );
    }

    if (
        (int) $file['size'] <= 0 ||
        (int) $file['size'] > 5 * 1024 * 1024
    ) {
        adminCsKirimResponse(
            false,
            'Ukuran gambar harus lebih dari 0 dan maksimal 5 MB.',
            [],
            400
        );
    }

    /* =====================================================
       VALIDASI JENIS GAMBAR
    ===================================================== */

    if (!class_exists('finfo')) {
        adminCsKirimResponse(
            false,
            'Server belum mendukung pemeriksaan jenis gambar.',
            [],
            500
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
        adminCsKirimResponse(
            false,
            'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            [],
            400
        );
    }

    $infoGambar = @getimagesize($file['tmp_name']);

    if ($infoGambar === false) {
        adminCsKirimResponse(
            false,
            'File yang dipilih bukan gambar yang valid.',
            [],
            400
        );
    }

    if (
        !isset($infoGambar['mime']) ||
        $infoGambar['mime'] !== $mime
    ) {
        adminCsKirimResponse(
            false,
            'Jenis file gambar tidak cocok.',
            [],
            400
        );
    }

    /* =====================================================
       SIMPAN GAMBAR KE FOLDER ROOT PROYEK
    ===================================================== */

    // Lokasi fisik: project_atk_jahit/uploads/chat/
    $folderUpload = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . 'uploads'
        . DIRECTORY_SEPARATOR
        . 'chat';

    if (
        !is_dir($folderUpload) &&
        !mkdir($folderUpload, 0755, true) &&
        !is_dir($folderUpload)
    ) {
        adminCsKirimResponse(
            false,
            'Folder penyimpanan gambar tidak dapat dibuat.',
            [],
            500
        );
    }

    if (!is_writable($folderUpload)) {
        adminCsKirimResponse(
            false,
            'Folder penyimpanan gambar tidak memiliki izin tulis.',
            [],
            500
        );
    }

    $namaGambar = 'cs_'
        . bin2hex(random_bytes(16))
        . '.'
        . $tipeGambar[$mime];

    $lokasiGambar = $folderUpload
        . DIRECTORY_SEPARATOR
        . $namaGambar;

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $lokasiGambar
        )
    ) {
        adminCsKirimResponse(
            false,
            'Gambar gagal disimpan.',
            [],
            500
        );
    }

    /* =====================================================
       BENTUK URL GAMBAR
    ===================================================== */

    $scriptPath = str_replace(
        '\\',
        '/',
        (string) ($_SERVER['SCRIPT_NAME'] ?? '')
    );

    // /project_atk_jahit/admin/cs/kirim.php
    // menjadi /project_atk_jahit
    $projectRoot = dirname(
        dirname(
            dirname($scriptPath)
        )
    );

    if ($projectRoot === '/' || $projectRoot === '.') {
        $projectRoot = '';
    }

    $projectRoot = rtrim($projectRoot, '/');

    $gambarUrl = $projectRoot
        . '/uploads/chat/'
        . rawurlencode($namaGambar);
}

/* =========================================================
   SIMPAN PESAN KE DATABASE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO cs_message
        (id_chat, pengirim, id_pengirim, pesan, gambar)
     VALUES (?, 'Admin', ?, ?, ?)"
);

if (!$stmt) {

    if ($lokasiGambar && is_file($lokasiGambar)) {
        @unlink($lokasiGambar);
    }

    adminCsKirimResponse(
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
    $idAdmin,
    $pesan,
    $namaGambar
);

$berhasil = mysqli_stmt_execute($stmt);
$idMessage = (int) mysqli_insert_id($conn);
$errorDatabase = mysqli_stmt_error($stmt);

mysqli_stmt_close($stmt);

/* =========================================================
   TANGANI KEGAGALAN DATABASE
========================================================= */

if (!$berhasil) {

    if ($lokasiGambar && is_file($lokasiGambar)) {
        @unlink($lokasiGambar);
    }

    error_log('[Admin CS Kirim] ' . $errorDatabase);

    adminCsKirimResponse(
        false,
        'Pesan gagal disimpan ke database.',
        [],
        500
    );
}

/* =========================================================
   RESPONSE BERHASIL
========================================================= */

adminCsKirimResponse(
    true,
    'Pesan berhasil dikirim.',
    [
        'id_message' => $idMessage,
        'gambar' => $namaGambar,
        'gambar_url' => $gambarUrl
    ]
);