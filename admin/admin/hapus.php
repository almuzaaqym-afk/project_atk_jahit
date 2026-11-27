<?php

session_start();

require_once "../includes/hak_akses.php";

/*
|--------------------------------------------------------------------------
| HANYA OWNER
|--------------------------------------------------------------------------
*/

$adminLogin = cekOwner();


/*
|--------------------------------------------------------------------------
| HAPUS HARUS MENGGUNAKAN POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    $_SESSION['pesan_error'] =
        "Permintaan hapus admin tidak valid.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| ID ADMIN
|--------------------------------------------------------------------------
*/

$id_admin = (int) ($_POST['id_admin'] ?? 0);

if ($id_admin <= 0) {

    $_SESSION['pesan_error'] =
        "Data admin tidak valid.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| OWNER PERTAMA
|--------------------------------------------------------------------------
*/

$query_owner = mysqli_query(
    $conn,
    "SELECT id_admin
     FROM admin
     ORDER BY id_admin ASC
     LIMIT 1"
);

$data_owner =
    mysqli_fetch_assoc($query_owner);

$id_owner_pertama =
    (int) (
        $data_owner['id_admin']
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| OWNER PERTAMA TIDAK BOLEH DIHAPUS
|--------------------------------------------------------------------------
*/

if ($id_admin === $id_owner_pertama) {

    $_SESSION['pesan_error'] =
        "Owner utama tidak dapat dihapus.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN YANG SEDANG LOGIN TIDAK BOLEH DIHAPUS
|--------------------------------------------------------------------------
*/

if (
    $id_admin ===
    (int) $_SESSION['id_admin']
) {

    $_SESSION['pesan_error'] =
        "Akun yang sedang login tidak dapat dihapus.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK DATA ADMIN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_admin,
        nama,
        role
     FROM admin
     WHERE id_admin = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_admin
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$admin_target =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$admin_target) {

    $_SESSION['pesan_error'] =
        "Admin tidak ditemukan.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| HAPUS ADMIN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM admin
     WHERE id_admin = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_admin
);


if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    $_SESSION['pesan_sukses'] =
        "Admin "
        . $admin_target['nama']
        . " berhasil dihapus.";

    header("Location: index.php");

    exit;
}


mysqli_stmt_close($stmt);


$_SESSION['pesan_error'] =
    "Admin gagal dihapus.";

header("Location: index.php");

exit;