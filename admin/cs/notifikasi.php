<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../includes/hak_akses.php";

/* CEK LOGIN */
if (!isset($_SESSION['id_admin'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);

    exit;
}

/* CEK HAK AKSES CS */
if (!roleBolehAkses('cs')) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Kamu tidak memiliki akses Customer Service.'
    ]);

    exit;
}

/* STATISTIK MENUNGGU */
$waiting = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cs_chat
     WHERE status_chat = 'Menunggu'"
);

if (!$result) {
    error_log("Query antrean CS gagal: " . mysqli_error($conn));

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil antrean CS.'
    ]);

    exit;
}

$row = mysqli_fetch_assoc($result);
$waiting = (int) ($row['total'] ?? 0);

mysqli_free_result($result);

/*
|--------------------------------------------------------------------------
| MODE KHUSUS BADGE SIDEBAR
|--------------------------------------------------------------------------
*/

if (($_GET['badge_only'] ?? '') === '1') {
    echo json_encode([
        'success' => true,
        'waiting' => $waiting,
        'menunggu' => $waiting
    ]);

    exit;
}

/* STATISTIK DILAYANI */
$serving = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cs_chat
     WHERE status_chat = 'Dilayani'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $serving = (int) ($row['total'] ?? 0);

    mysqli_free_result($result);
}

/* STATISTIK SELESAI */
$done = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM cs_chat
     WHERE status_chat = 'Selesai'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $done = (int) ($row['total'] ?? 0);

    mysqli_free_result($result);
}

/* DATA CHAT */
$sql = "
    SELECT
        c.id_chat,
        c.id_pelanggan,
        c.status_chat,
        c.created_at,
        c.ended_at,
        p.nama,

        (
            SELECT cm.pesan
            FROM cs_message cm
            WHERE cm.id_chat = c.id_chat
            ORDER BY cm.id_message DESC
            LIMIT 1
        ) AS pesan_terakhir,

        (
            SELECT cm.created_at
            FROM cs_message cm
            WHERE cm.id_chat = c.id_chat
            ORDER BY cm.id_message DESC
            LIMIT 1
        ) AS waktu_pesan

    FROM cs_chat c
    INNER JOIN pelanggan p
        ON p.id_pelanggan = c.id_pelanggan

    ORDER BY
        CASE c.status_chat
            WHEN 'Menunggu' THEN 1
            WHEN 'Dilayani' THEN 2
            WHEN 'Selesai' THEN 3
            ELSE 4
        END,
        c.id_chat DESC
";

$result_chat = mysqli_query($conn, $sql);

$chats = [];

if ($result_chat) {
    while ($row = mysqli_fetch_assoc($result_chat)) {
        $waktu = $row['waktu_pesan'] ?: $row['created_at'];

        $chats[] = [
            'id_chat' => (int) $row['id_chat'],
            'id_pelanggan' => (int) $row['id_pelanggan'],
            'status_chat' => $row['status_chat'],
            'nama' => $row['nama'],
            'pesan_terakhir' => $row['pesan_terakhir'] ?? '',
            'waktu_pesan' => !empty($waktu)
                ? date('d/m/Y H:i', strtotime($waktu))
                : '-'
        ];
    }

    mysqli_free_result($result_chat);
}

/* RESPONSE */
echo json_encode([
    'success' => true,
    'waiting' => $waiting,
    'serving' => $serving,
    'done' => $done,
    'menunggu' => $waiting,
    'dilayani' => $serving,
    'selesai' => $done,
    'chats' => $chats
], JSON_UNESCAPED_UNICODE);

exit;