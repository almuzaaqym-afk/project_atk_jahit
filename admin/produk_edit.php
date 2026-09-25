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
   AMBIL DATA PRODUK
========================= */

$query = mysqli_query(
    $conn,
    "SELECT *
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
   AMBIL KATEGORI
========================= */

$query_kategori = mysqli_query(
    $conn,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);


/* =========================
   PROSES UPDATE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = (float)($_POST['harga'] ?? 0);
    $lokasi_rak  = trim($_POST['lokasi_rak'] ?? '');

    $gambar_lama = $produk['gambar'] ?? '';
    $gambar_baru = $gambar_lama;

    if ($nama_produk === '') {

        $error = "Nama produk wajib diisi.";

    } elseif ($id_kategori <= 0) {

        $error = "Kategori wajib dipilih.";

    } elseif ($harga < 0) {

        $error = "Harga tidak boleh kurang dari 0.";

    } else {

        /* =========================
           UPLOAD FOTO BARU
        ========================= */

        if (
            isset($_FILES['gambar']) &&
            $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {

                $error = "Foto gagal diupload.";

            } else {

                $nama_file_asli = $_FILES['gambar']['name'];
                $tmp_file       = $_FILES['gambar']['tmp_name'];
                $ukuran_file    = $_FILES['gambar']['size'];

                $extension = strtolower(
                    pathinfo($nama_file_asli, PATHINFO_EXTENSION)
                );

                $extension_diizinkan = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                if (!in_array($extension, $extension_diizinkan)) {

                    $error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";

                } elseif ($ukuran_file > 5 * 1024 * 1024) {

                    $error = "Ukuran foto maksimal 5 MB.";

                } else {

                    /* =========================
                       FOLDER GAMBAR
                    ========================= */

                    $folder_upload = __DIR__ . "/assets/images/products/";

                    if (!is_dir($folder_upload)) {
                        mkdir($folder_upload, 0777, true);
                    }

                    /* =========================
                       NAMA FILE BARU
                    ========================= */

                    $nama_file_baru =
                        'produk_' .
                        $id .
                        '_' .
                        time() .
                        '_' .
                        bin2hex(random_bytes(3)) .
                        '.' .
                        $extension;

                    $path_file_baru =
                        $folder_upload . $nama_file_baru;


                    /* =========================
                       PINDAHKAN FOTO
                    ========================= */

                    if (move_uploaded_file(
                        $tmp_file,
                        $path_file_baru
                    )) {

                        $gambar_baru = $nama_file_baru;

                    } else {

                        $error = "Foto gagal disimpan ke folder.";

                    }
                }
            }
        }


        /* =========================
           UPDATE DATABASE
        ========================= */

        if (!isset($error)) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE produk
                 SET
                    nama_produk = ?,
                    id_kategori = ?,
                    deskripsi = ?,
                    harga = ?,
                    lokasi_rak = ?,
                    gambar = ?
                 WHERE id_produk = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sisdssi",
                $nama_produk,
                $id_kategori,
                $deskripsi,
                $harga,
                $lokasi_rak,
                $gambar_baru,
                $id
            );


            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);


                /* =========================
                   HAPUS FOTO LAMA
                   JIKA FOTO DIGANTI
                ========================= */

                if (
                    !empty($gambar_lama) &&
                    $gambar_baru !== $gambar_lama
                ) {

                    $path_foto_lama =
                        __DIR__ .
                        "/assets/images/products/" .
                        $gambar_lama;

                    if (
                        file_exists($path_foto_lama) &&
                        is_file($path_foto_lama)
                    ) {

                        unlink($path_foto_lama);

                    }
                }


                echo "<script>
                        alert('Produk berhasil diperbarui.');
                        window.location='produk.php';
                      </script>";

                exit;

            } else {

                /*
                 * Jika database gagal diupdate,
                 * hapus foto baru yang sudah terupload
                 */

                if (
                    $gambar_baru !== $gambar_lama &&
                    !empty($gambar_baru)
                ) {

                    $path_foto_baru =
                        __DIR__ .
                        "/assets/images/products/" .
                        $gambar_baru;

                    if (
                        file_exists($path_foto_baru) &&
                        is_file($path_foto_baru)
                    ) {

                        unlink($path_foto_baru);

                    }
                }

                $error =
                    "Gagal memperbarui produk: " .
                    mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Produk - Admin</title>

    <link
        rel="stylesheet"
        href="assets/css/admin.css"
    >

</head>


<body>

<div class="admin-layout">


    <?php require_once "includes/sidebar.php"; ?>


    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <h1>Edit Produk</h1>

            <div class="admin-user">

                <strong>
                    <?= htmlspecialchars(
                        $_SESSION['nama_admin'] ?? 'Administrator'
                    ); ?>
                </strong>

                <span>Administrator</span>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="admin-content">


            <div class="page-title">

                <h1>Edit Produk</h1>

                <p>
                    Ubah informasi produk.
                </p>

            </div>


            <div
                class="content-card"
                style="
                    max-width:800px;
                "
            >


                <?php if (isset($error)) : ?>

                    <div
                        style="
                            background:#fce8e8;
                            color:#a33;
                            padding:14px 16px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >

                        <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <!-- NAMA PRODUK -->

                    <div style="margin-bottom:20px;">

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                font-weight:600;
                                color:#123451;
                            "
                        >
                            Nama Produk
                        </label>

                        <input
                            type="text"
                            name="nama_produk"
                            value="<?= htmlspecialchars(
                                $produk['nama_produk']
                            ); ?>"
                            required
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:12px 14px;
                                border:1px solid #d7e0e8;
                                border-radius:8px;
                                font-size:15px;
                            "
                        >

                    </div>


                    <!-- KATEGORI -->

                    <div style="margin-bottom:20px;">

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                font-weight:600;
                                color:#123451;
                            "
                        >
                            Kategori
                        </label>

                        <select
                            name="id_kategori"
                            required
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:12px 14px;
                                border:1px solid #d7e0e8;
                                border-radius:8px;
                                font-size:15px;
                                background:white;
                            "
                        >

                            <option value="">
                                -- Pilih Kategori --
                            </option>

                            <?php while (
                                $kategori = mysqli_fetch_assoc(
                                    $query_kategori
                                )
                            ) : ?>

                                <option
                                    value="<?= $kategori['id_kategori']; ?>"
                                    <?= (
                                        $kategori['id_kategori']
                                        == $produk['id_kategori']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $kategori['nama_kategori']
                                    ); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- HARGA -->

                    <div style="margin-bottom:20px;">

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                font-weight:600;
                                color:#123451;
                            "
                        >
                            Harga
                        </label>

                        <input
                            type="number"
                            name="harga"
                            value="<?= htmlspecialchars(
                                $produk['harga']
                            ); ?>"
                            min="0"
                            required
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:12px 14px;
                                border:1px solid #d7e0e8;
                                border-radius:8px;
                                font-size:15px;
                            "
                        >

                    </div>


                    <!-- DESKRIPSI -->

                    <div style="margin-bottom:20px;">

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                font-weight:600;
                                color:#123451;
                            "
                        >
                            Deskripsi
                        </label>

                        <textarea
                            name="deskripsi"
                            rows="5"
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:12px 14px;
                                border:1px solid #d7e0e8;
                                border-radius:8px;
                                font-size:15px;
                                resize:vertical;
                            "
                        ><?= htmlspecialchars(
                            $produk['deskripsi'] ?? ''
                        ); ?></textarea>

                    </div>


                    <!-- LOKASI RAK -->

                    <div style="margin-bottom:20px;">

                        <label
                            style="
                                display:block;
                                margin-bottom:8px;
                                font-weight:600;
                                color:#123451;
                            "
                        >
                            Lokasi / Rak
                        </label>

                        <input
                            type="text"
                            name="lokasi_rak"
                            value="<?= htmlspecialchars(
                                $produk['lokasi_rak'] ?? ''
                            ); ?>"
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:12px 14px;
                                border:1px solid #d7e0e8;
                                border-radius:8px;
                                font-size:15px;
                            "
                        >

                    </div>


                    <!-- FOTO PRODUK -->

                    <div style="margin-bottom:25px;">

                        <label
                            style="
                                display:block;
                                margin-bottom:10px;
                                font-weight:600;
                                color:#123451;
                            "
                        >
                            Foto Produk
                        </label>


                        <?php if (!empty($produk['gambar'])) : ?>

                            <div
                                style="
                                    margin-bottom:15px;
                                "
                            >

                                <p
                                    style="
                                        margin:0 0 8px 0;
                                        color:#7890a4;
                                        font-size:14px;
                                    "
                                >
                                    Foto saat ini:
                                </p>

                                <img
                                    src="assets/images/products/<?= htmlspecialchars(
                                        $produk['gambar']
                                    ); ?>"
                                    alt="<?= htmlspecialchars(
                                        $produk['nama_produk']
                                    ); ?>"
                                    style="
                                        width:180px;
                                        height:140px;
                                        object-fit:cover;
                                        border-radius:10px;
                                        border:1px solid #d7e0e8;
                                        display:block;
                                    "
                                >

                            </div>

                        <?php else : ?>

                            <div
                                style="
                                    width:180px;
                                    height:140px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    text-align:center;
                                    background:#f4f7fa;
                                    border:1px dashed #d7e0e8;
                                    border-radius:10px;
                                    color:#7890a4;
                                    margin-bottom:15px;
                                "
                            >
                                Belum ada foto
                            </div>

                        <?php endif; ?>


                        <input
                            type="file"
                            name="gambar"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            style="
                                width:100%;
                                box-sizing:border-box;
                                padding:12px;
                                border:1px solid #d7e0e8;
                                border-radius:8px;
                                background:white;
                                font-size:14px;
                            "
                        >


                        <small
                            style="
                                display:block;
                                margin-top:8px;
                                color:#7890a4;
                            "
                        >
                            Pilih foto baru jika ingin mengganti foto.
                            Format JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB.
                        </small>

                    </div>


                    <!-- STOK -->

                    <div
                        style="
                            background:#f4f7fa;
                            padding:14px 16px;
                            border-radius:8px;
                            margin-bottom:25px;
                            color:#456;
                        "
                    >

                        <strong>Stok saat ini:</strong>

                        <?= htmlspecialchars(
                            $produk['stok']
                        ); ?>

                        <br>

                        <small>
                            Stok tidak diubah melalui halaman Edit Produk.
                        </small>

                    </div>


                    <!-- BUTTON -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                        "
                    >

                        <button
                            type="submit"
                            style="
                                background:#123451;
                                color:white;
                                border:none;
                                padding:13px 22px;
                                border-radius:8px;
                                font-weight:600;
                                cursor:pointer;
                            "
                        >
                            Simpan Perubahan
                        </button>


                        <a
                            href="produk.php"
                            style="
                                background:#eef2f5;
                                color:#123451;
                                padding:13px 22px;
                                border-radius:8px;
                                text-decoration:none;
                                font-weight:600;
                            "
                        >
                            Batal
                        </a>

                    </div>


                </form>


            </div>


        </section>


    </main>

</div>

</body>

</html>