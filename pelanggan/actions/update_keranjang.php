<?php

session_start();

header(
    "Content-Type: application/json; charset=UTF-8"
);

if (!isset($_SESSION["id_pelanggan"])) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Silakan login terlebih dahulu."
    ]);

    exit;
}

require_once __DIR__ .
    "/../../config/koneksi.php";

/* =========================================================
   DATA
========================================================= */

$id_produk =
    (int) ($_POST["id"] ??
           $_GET["id"] ??
           0);

$id_varian =
    (int) ($_POST["id_varian"] ??
           $_GET["id_varian"] ??
           0);

$jumlah =
    (int) ($_POST["jumlah"] ??
           0);

$aksi =
    $_POST["aksi"] ??
    $_GET["aksi"] ??
    "";

/* =========================================================
   VALIDASI PRODUK
========================================================= */

if ($id_produk <= 0) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Produk tidak valid."
    ]);

    exit;
}

/* =========================================================
   HAPUS ITEM
========================================================= */

if ($aksi === "hapus") {

    if ($id_varian > 0) {

        unset(
            $_SESSION[
                "keranjang_varian"
            ][$id_varian]
        );

    } else {

        unset(
            $_SESSION[
                "keranjang"
            ][$id_produk]
        );
    }

    /* Kalau datang dari link biasa */

    if (
        $_SERVER["REQUEST_METHOD"] !==
        "POST"
    ) {

        header(
            "Location: ../user/keranjang.php"
        );

        exit;
    }

    echo json_encode([
        "success" => true,
        "message" =>
            "Produk berhasil dihapus."
    ]);

    exit;
}

/* =========================================================
   VALIDASI JUMLAH
========================================================= */

if ($jumlah < 1) {

    $jumlah = 1;
}

/* =========================================================
   UPDATE VARIAN
========================================================= */

if ($id_varian > 0) {

    $stmt =
        mysqli_prepare(
            $conn,
            "SELECT
                id_varian,
                id_produk,
                stok
             FROM produk_varian
             WHERE id_varian = ?
               AND id_produk = ?
               AND status = 'Aktif'
             LIMIT 1"
        );

    if (!$stmt) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Gagal memeriksa varian."
        ]);

        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_varian,
        $id_produk
    );

    mysqli_stmt_execute(
        $stmt
    );

    $result =
        mysqli_stmt_get_result(
            $stmt
        );

    $varian =
        mysqli_fetch_assoc(
            $result
        );

    mysqli_stmt_close(
        $stmt
    );

    if (!$varian) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Varian tidak ditemukan."
        ]);

        exit;
    }

    $stok =
        (int) $varian["stok"];

    if ($stok <= 0) {

        unset(
            $_SESSION[
                "keranjang_varian"
            ][$id_varian]
        );

        echo json_encode([
            "success" => false,
            "message" =>
                "Stok varian sudah habis."
        ]);

        exit;
    }

    if ($jumlah > $stok) {

        $jumlah = $stok;

        $_SESSION[
            "keranjang_varian"
        ][$id_varian] =
            $jumlah;

        echo json_encode([
            "success" => true,
            "message" =>
                "Jumlah disesuaikan dengan stok.",
            "jumlah" =>
                $jumlah
        ]);

        exit;
    }

    if (
        !isset(
            $_SESSION[
                "keranjang_varian"
            ]
        )
    ) {

        $_SESSION[
            "keranjang_varian"
        ] = [];
    }

    $_SESSION[
        "keranjang_varian"
    ][$id_varian] =
        $jumlah;

    echo json_encode([
        "success" => true,
        "message" =>
            "Jumlah produk diperbarui.",
        "jumlah" =>
            $jumlah
    ]);

    exit;
}

/* =========================================================
   UPDATE PRODUK TANPA VARIAN
========================================================= */

$stmt =
    mysqli_prepare(
        $conn,
        "SELECT
            id_produk,
            stok
         FROM produk
         WHERE id_produk = ?
         LIMIT 1"
    );

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Gagal memeriksa produk."
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_produk
);

mysqli_stmt_execute(
    $stmt
);

$result =
    mysqli_stmt_get_result(
        $stmt
    );

$produk =
    mysqli_fetch_assoc(
        $result
    );

mysqli_stmt_close(
    $stmt
);

if (!$produk) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Produk tidak ditemukan."
    ]);

    exit;
}

$stok =
    (int) $produk["stok"];

if ($stok <= 0) {

    unset(
        $_SESSION[
            "keranjang"
        ][$id_produk]
    );

    echo json_encode([
        "success" => false,
        "message" =>
            "Stok produk sudah habis."
    ]);

    exit;
}

if ($jumlah > $stok) {

    $jumlah = $stok;
}

if (
    !isset(
        $_SESSION["keranjang"]
    )
) {

    $_SESSION["keranjang"] =
        [];
}

$_SESSION[
    "keranjang"
][$id_produk] =
    $jumlah;

echo json_encode([
    "success" => true,
    "message" =>
        "Jumlah produk diperbarui.",
    "jumlah" =>
        $jumlah
]);

exit;