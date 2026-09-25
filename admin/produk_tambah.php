<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/koneksi.php";

$nama_admin = $_SESSION['nama_admin'] ?? 'Administrator';

$error = "";
$sukses = "";


/* =========================
   PROSES TAMBAH PRODUK
========================= */

if (isset($_POST['simpan'])) {

    $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = (float) ($_POST['harga'] ?? 0);
    $stok        = (int) ($_POST['stok'] ?? 0);
    $lokasi_rak  = trim($_POST['lokasi_rak'] ?? '');

    $nama_gambar = "";

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {

    $file = $_FILES['gambar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Foto produk gagal diupload.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = "Ukuran foto maksimal 2 MB.";
    } else {

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            $error = "Format foto harus JPG, JPEG, PNG atau WEBP.";
        } else {

           $folder = "assets/images/products/";
           
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $nama_gambar = uniqid("produk_") . "." . $extension;

            move_uploaded_file(
                $file['tmp_name'],
                $folder . $nama_gambar
            );
        }
    }
}

    if ($id_kategori <= 0) {

        $error = "Kategori produk wajib dipilih.";

    } elseif ($nama_produk === "") {

        $error = "Nama produk wajib diisi.";

    } elseif ($harga < 0) {

        $error = "Harga tidak valid.";

    } elseif ($stok < 0) {

        $error = "Stok tidak valid.";

    } else {

        $nama_produk_safe = mysqli_real_escape_string(
            $conn,
            $nama_produk
        );

        $deskripsi_safe = mysqli_real_escape_string(
            $conn,
            $deskripsi
        );

        $lokasi_rak_safe = mysqli_real_escape_string(
            $conn,
            $lokasi_rak
        );

        $query = mysqli_query(
    $conn,
    "INSERT INTO produk
    (
        id_kategori,
        nama_produk,
        deskripsi,
        harga,
        stok,
        lokasi_rak,
        gambar
    )
    VALUES
    (
        $id_kategori,
        '$nama_produk_safe',
        '$deskripsi_safe',
        $harga,
        $stok,
        '$lokasi_rak_safe',
        '$nama_gambar'
    )"
);

        if ($query) {

            header("Location: produk.php?status=tambah");
            exit;

        } else {

            $error = "Produk gagal ditambahkan: " . mysqli_error($conn);
        }
    }
}


/* =========================
   AMBIL DATA KATEGORI
========================= */

$query_kategori = mysqli_query(
    $conn,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Tambah Produk - Admin</title>

    <link rel="stylesheet"
          href="assets/css/admin.css">

</head>

<body>

<div class="admin-layout">


    <?php require_once "includes/sidebar.php"; ?>


    <main class="admin-main">


        <header class="admin-topbar">

            <h1>Tambah Produk</h1>

            <div class="admin-user">

                <strong>
                    <?= htmlspecialchars($nama_admin); ?>
                </strong>

                <span>Administrator</span>

            </div>

        </header>


        <section class="admin-content">


            <div class="page-title">

                <h1>Tambah Produk</h1>

                <p>
                    Tambahkan produk ATK atau alat jahit ke katalog.
                </p>

            </div>


            <?php if ($error !== "") : ?>

                <div style="
                    background:#fff1f2;
                    color:#b42318;
                    padding:15px 18px;
                    border-radius:10px;
                    margin-bottom:20px;
                    border:1px solid #fecdd3;
                ">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <div style="
                background:white;
                padding:30px;
                border-radius:14px;
                border:1px solid #e2e8ed;
            ">


               <form method="POST" enctype="multipart/form-data">


                    <div style="margin-bottom:20px;">

                        <label>
                            Kategori
                        </label>

                        <select
                            name="id_kategori"
                            required
                            style="
                                width:100%;
                                padding:13px;
                                margin-top:8px;
                                border:1px solid #d9e2ea;
                                border-radius:8px;
                            "
                        >

                            <option value="">
                                -- Pilih Kategori --
                            </option>

                            <?php while ($kategori = mysqli_fetch_assoc($query_kategori)) : ?>

                                <option
                                    value="<?= $kategori['id_kategori']; ?>"
                                >

                                    <?= htmlspecialchars(
                                        $kategori['nama_kategori']
                                    ); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <div style="margin-bottom:20px;">

                        <label>
                            Nama Produk
                        </label>

                        <input
                            type="text"
                            name="nama_produk"
                            placeholder="Contoh: Buku Tulis"
                            required
                            style="
                                width:100%;
                                padding:13px;
                                margin-top:8px;
                                border:1px solid #d9e2ea;
                                border-radius:8px;
                                box-sizing:border-box;
                            "
                        >

                    </div>


                    <div style="margin-bottom:20px;">

                        <label>
                            Deskripsi
                        </label>

                        <textarea
                            name="deskripsi"
                            rows="4"
                            placeholder="Deskripsi produk..."
                            style="
                                width:100%;
                                padding:13px;
                                margin-top:8px;
                                border:1px solid #d9e2ea;
                                border-radius:8px;
                                box-sizing:border-box;
                            "
                        ></textarea>

                    </div>


                    <div style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:20px;
                        margin-bottom:20px;
                    ">

<div style="margin-bottom:20px;">

    <label>
        Foto Produk
    </label>

    <input
        type="file"
        name="gambar"
        accept="image/*"
        style="
            width:100%;
            padding:13px;
            margin-top:8px;
            border:1px solid #d9e2ea;
            border-radius:8px;
            box-sizing:border-box;
            background:white;
        "
    >

    <small style="
        display:block;
        margin-top:7px;
        color:#7890a4;
    ">
        Format JPG, JPEG, PNG atau WEBP. Maksimal 2 MB.
    </small>

</div>  

                        <div>

                            <label>
                                Harga
                            </label>

                            <input
                                type="number"
                                name="harga"
                                min="0"
                                required
                                placeholder="Contoh: 5000"
                                style="
                                    width:100%;
                                    padding:13px;
                                    margin-top:8px;
                                    border:1px solid #d9e2ea;
                                    border-radius:8px;
                                    box-sizing:border-box;
                                "
                            >

                        </div>


                        <div>

                            <label>
                                Stok
                            </label>

                            <input
                                type="number"
                                name="stok"
                                min="0"
                                value="0"
                                required
                                style="
                                    width:100%;
                                    padding:13px;
                                    margin-top:8px;
                                    border:1px solid #d9e2ea;
                                    border-radius:8px;
                                    box-sizing:border-box;
                                "
                            >

                        </div>


                    </div>


                    <div style="margin-bottom:25px;">

                        <label>
                            Lokasi / Rak
                        </label>

                        <input
                            type="text"
                            name="lokasi_rak"
                            placeholder="Contoh: Rak A1"
                            style="
                                width:100%;
                                padding:13px;
                                margin-top:8px;
                                border:1px solid #d9e2ea;
                                border-radius:8px;
                                box-sizing:border-box;
                            "
                        >

                    </div>


                    <div style="
                        display:flex;
                        gap:10px;
                    ">

                        <button
                            type="submit"
                            name="simpan"
                            style="
                                background:#123451;
                                color:white;
                                border:none;
                                padding:13px 25px;
                                border-radius:8px;
                                font-weight:600;
                                cursor:pointer;
                            "
                        >
                            Simpan Produk
                        </button>


                        <a
                            href="produk.php"
                            style="
                                background:#eef2f6;
                                color:#123451;
                                padding:13px 25px;
                                border-radius:8px;
                                text-decoration:none;
                                font-weight:600;
                            "
                        >
                            Kembali
                        </a>

                    </div>


                </form>


            </div>


        </section>


    </main>


</div>

</body>

</html>