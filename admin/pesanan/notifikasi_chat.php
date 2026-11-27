<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_admin'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Akses admin diperlukan.'
    ]);

    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";


/* =========================================================
   AMBIL CHAT YANG BELUM DIBALAS ADMIN
========================================================= */

$sql = "
    SELECT
        cs.id_pesanan,
        cs.id_chat,
        cm.id_message

    FROM chat_session cs

    INNER JOIN chat_message cm
        ON cm.id_chat = cs.id_chat

    WHERE cs.status_chat = 'Aktif'

      AND cm.pengirim = 'Pelanggan'

      AND cm.id_message = (
          SELECT MAX(cm2.id_message)
          FROM chat_message cm2
          WHERE cm2.id_chat = cs.id_chat
      )

    ORDER BY
        cm.id_message DESC
";


$result = mysqli_query(
    $conn,
    $sql
);


$pesanan_belum_dibalas = [];


if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $pesanan_belum_dibalas[] = [
            'id_pesanan' => (int) $row['id_pesanan'],
            'id_chat' => (int) $row['id_chat'],
            'id_message' => (int) $row['id_message']
        ];

    }

}


echo json_encode([
    'success' => true,
    'total' => count($pesanan_belum_dibalas),
    'pesanan' => $pesanan_belum_dibalas
]);

exit;