```php
<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

function kirimJSON($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$id_produk = (int) ($_GET["id"] ?? 0);

try {
    if ($id_produk > 0) {
        $sql_produk = "SELECT p.id_produk, p.id_kategori, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori, COALESCE(v.jumlah_varian, 0) AS jumlah_varian, COALESCE(v.total_stok, 0) AS total_stok_varian, v.harga_min, v.harga_max FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori LEFT JOIN (SELECT id_produk, COUNT(*) AS jumlah_varian, SUM(stok) AS total_stok, MIN(harga) AS harga_min, MAX(harga) AS harga_max FROM produk_varian WHERE status = 'Aktif' GROUP BY id_produk) v ON p.id_produk = v.id_produk WHERE p.id_produk = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql_produk);
        if (!$stmt) {
            kirimJSON(["success" => false, "message" => "Gagal menyiapkan data produk."], 500);
        }
        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$produk) {
            kirimJSON(["success" => false, "message" => "Produk tidak ditemukan."], 404);
        }

        $memiliki_varian = (int) $produk["jumlah_varian"] > 0;
        $produk["memiliki_varian"] = $memiliki_varian;
        $produk["stok"] = $memiliki_varian
            ? (int) $produk["total_stok_varian"]
            : (int) $produk["stok"];
        $produk["harga"] = (float) $produk["harga"];
        $produk["harga_min"] = $memiliki_varian
            ? (float) $produk["harga_min"]
            : (float) $produk["harga"];
        $produk["harga_max"] = $memiliki_varian
            ? (float) $produk["harga_max"]
            : (float) $produk["harga"];

        $sql_varian = "SELECT v.id_varian, v.nama_varian, v.harga, v.stok, v.lokasi_rak, (SELECT g.nama_gambar FROM produk_varian_gambar g WHERE g.id_varian = v.id_varian ORDER BY g.urutan ASC, g.id_gambar_varian ASC LIMIT 1) AS gambar_varian FROM produk_varian v WHERE v.id_produk = ? AND v.status = 'Aktif' ORDER BY v.id_varian ASC";
        $stmt_varian = mysqli_prepare($conn, $sql_varian);
        if (!$stmt_varian) {
            kirimJSON(["success" => false, "message" => "Gagal menyiapkan data varian."], 500);
        }
        mysqli_stmt_bind_param($stmt_varian, "i", $id_produk);
        mysqli_stmt_execute($stmt_varian);
        $result_varian = mysqli_stmt_get_result($stmt_varian);
        $varian = [];

        while ($row = mysqli_fetch_assoc($result_varian)) {
            $varian[] = [
                "id_varian" => (int) $row["id_varian"],
                "nama_varian" => $row["nama_varian"],
                "harga" => (float) $row["harga"],
                "stok" => (int) $row["stok"],
                "lokasi_rak" => $row["lokasi_rak"] ?? "",
                "gambar_varian" => $row["gambar_varian"] ?? ""
            ];
        }
        mysqli_stmt_close($stmt_varian);

        kirimJSON([
            "success" => true,
            "product" => $produk,
            "variants" => $varian
        ]);
    }

    $sql_produk = "SELECT p.id_produk, p.id_kategori, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori, COALESCE(v.jumlah_varian, 0) AS jumlah_varian, COALESCE(v.total_stok, 0) AS total_stok_varian, v.harga_min, v.harga_max FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori LEFT JOIN (SELECT id_produk, COUNT(*) AS jumlah_varian, SUM(stok) AS total_stok, MIN(harga) AS harga_min, MAX(harga) AS harga_max FROM produk_varian WHERE status = 'Aktif' GROUP BY id_produk) v ON p.id_produk = v.id_produk ORDER BY p.id_produk DESC";
    $result = mysqli_query($conn, $sql_produk);

    if (!$result) {
        kirimJSON(["success" => false, "message" => "Gagal mengambil data produk."], 500);
    }

    $produk_list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $memiliki_varian = (int) $row["jumlah_varian"] > 0;
        $harga_dasar = (float) $row["harga"];

        $produk_list[] = [
            "id_produk" => (int) $row["id_produk"],
            "id_kategori" => (int) ($row["id_kategori"] ?? 0),
            "nama_produk" => $row["nama_produk"],
            "harga" => $harga_dasar,
            "stok" => $memiliki_varian
                ? (int) $row["total_stok_varian"]
                : (int) $row["stok"],
            "gambar" => $row["gambar"] ?? "",
            "nama_kategori" => $row["nama_kategori"] ?? "Produk",
            "memiliki_varian" => $memiliki_varian,
            "harga_min" => $memiliki_varian
                ? (float) $row["harga_min"]
                : $harga_dasar,
            "harga_max" => $memiliki_varian
                ? (float) $row["harga_max"]
                : $harga_dasar
        ];
    }

    kirimJSON([
        "success" => true,
        "products" => $produk_list
    ]);
} catch (Throwable $e) {
    kirimJSON([
        "success" => false,
        "message" => "Terjadi kesalahan saat mengambil data produk."
    ], 500);
}
