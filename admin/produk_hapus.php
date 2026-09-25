<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/koneksi.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: produk.php");
    exit;
}


/* =========================
   CEK PRODUK
========================= */

$query = mysqli_query(
    $conn,
    "SELECT id_produk, nama_produk, gambar
     FROM produk
     WHERE id_produk = $id
     LIMIT 1"
);

if (!$query || mysqli_num_rows($query) == 0) {

    echo "<script>
            alert('Produk tidak ditemukan.');
            window.location='produk.php';
          </script>";

    exit;
}

$produk = mysqli_fetch_assoc($query);


/* =========================
   HAPUS PRODUK
========================= */

$delete = mysqli_query(
    $conn,
    "DELETE FROM produk
     WHERE id_produk = $id"
);


if ($delete) {

    /* =========================
       HAPUS FOTO PRODUK
    ========================= */

    if (!empty($produk['gambar'])) {

        $file_gambar = "../uploads/produk/" . $produk['gambar'];

        if (file_exists($file_gambar)) {
            unlink($file_gambar);
        }
    }


    echo "<script>
            alert('Produk berhasil dihapus.');
            window.location='produk.php';
          </script>";

    exit;

}


/* =========================
   JIKA GAGAL
========================= */

$error = mysqli_error($conn);

echo "<script>
        alert('Produk gagal dihapus.\\n\\n$error');
        window.location='produk.php';
      </script>";

exit;

?>