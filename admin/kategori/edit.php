<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

$id_kategori = (int) ($_GET['id'] ?? 0);

if ($id_kategori <= 0) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

$error = "";

/* =========================================================
   AMBIL DATA KATEGORI
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     WHERE id_kategori = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kategori
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: index.php?status=gagal");
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$kategori = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$kategori) {
    header("Location: index.php?status=tidak_ditemukan");
    exit;
}

/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_kategori = trim(
        $_POST['nama_kategori'] ?? ''
    );

    /* =========================
       VALIDASI
    ========================= */

    if ($nama_kategori === '') {

        $error = "Nama kategori wajib diisi.";

    } else {

        /* =========================
           CEK DUPLIKAT
        ========================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_kategori
             FROM kategori
             WHERE nama_kategori = ?
               AND id_kategori != ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Gagal memeriksa nama kategori.";
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $nama_kategori,
                $id_kategori
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = "Gagal memeriksa nama kategori.";
                mysqli_stmt_close($stmt);
            } else {
                $hasil = mysqli_stmt_get_result($stmt);
                $kategoriDuplikat = mysqli_fetch_assoc($hasil);

                mysqli_stmt_close($stmt);

                if ($kategoriDuplikat) {

                    $error = "Kategori tersebut sudah ada.";

                } else {

                    /* =========================
                       UPDATE KATEGORI
                    ========================= */

                    $nama_kategori_lama = $kategori['nama_kategori'];

                    $stmtUpdate = mysqli_prepare(
                        $conn,
                        "UPDATE kategori
                         SET nama_kategori = ?
                         WHERE id_kategori = ?"
                    );

                    if (!$stmtUpdate) {
                        $error = "Gagal menyiapkan perubahan kategori.";
                    } else {
                        mysqli_stmt_bind_param(
                            $stmtUpdate,
                            "si",
                            $nama_kategori,
                            $id_kategori
                        );

                        if (mysqli_stmt_execute($stmtUpdate)) {

                            mysqli_stmt_close($stmtUpdate);

                            /* =========================
                               CATAT LOG EDIT KATEGORI
                            ========================= */

                            $detailLog = 'Mengubah nama kategori "' .
                                $nama_kategori_lama .
                                '" menjadi "' . $nama_kategori .
                                '" (ID kategori: ' . $id_kategori . ').';

                            try {
                                $logBerhasil = catatLogAktivitas(
                                    $conn,
                                    'kategori',
                                    'Edit Kategori',
                                    $detailLog
                                );

                                if (!$logBerhasil) {
                                    error_log(
                                        'Gagal mencatat log edit kategori: ID ' .
                                        $id_kategori
                                    );
                                }
                            } catch (Throwable $e) {
                                error_log(
                                    'Kesalahan log edit kategori ID ' .
                                    $id_kategori . ': ' . $e->getMessage()
                                );
                            }

                            header(
                                "Location: index.php?status=edit"
                            );

                            exit;

                        } else {

                            $error =
                                "Kategori gagal diperbarui: " .
                                mysqli_error($conn);

                            mysqli_stmt_close($stmtUpdate);
                        }
                    }
                }
            }
        }
    }

    /* Tampilkan kembali input terakhir */
    $kategori['nama_kategori'] = $nama_kategori;
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

    <title>
        Edit Kategori - Toko ATK &amp; Alat Jahit
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>
        .category-form-card {
            max-width: 700px;
            background: #ffffff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            padding: 25px;
            box-shadow:
                0 4px 15px
                rgba(16,47,75,0.04);
        }

        .category-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        .category-form-group label {
            color: #102f4b;
            font-size: 14px;
            font-weight: 600;
        }

        .category-form-group input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #d1dbe3;
            border-radius: 8px;
            background: #ffffff;
            color: #102f4b;
            font-size: 14px;
            outline: none;
            transition:
                border 0.2s ease,
                box-shadow 0.2s ease;
        }

        .category-form-group input:focus {
            border-color: #245b83;
            box-shadow:
                0 0 0 3px
                rgba(36,91,131,0.10);
        }

        .form-help {
            color: #7890a4;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert-error-custom {
            background: #fff0f0;
            border: 1px solid #f0c2c2;
            color: #b52b2b;
            padding: 14px 18px;
            border-radius: 9px;
            margin-bottom: 20px;
        }

        .category-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #e6edf2;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            background: #eef2f5;
            color: #506d84;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-cancel:hover {
            background: #e3e9ee;
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #102f4b;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-save:hover {
            background: #1c496b;
        }

        @media (max-width: 600px) {
            .category-form-card {
                padding: 20px;
            }

            .category-form-actions {
                flex-direction: column;
            }

            .btn-cancel,
            .btn-save {
                width: 100%;
            }
        }
    </style>

</head>

<body>

<div class="admin-layout">

    <?php include "../includes/sidebar.php"; ?>

    <div class="admin-main">

        <?php include "../includes/navbar.php"; ?>

        <main class="admin-content">

            <div class="page-title">

                <h1>
                    Edit Kategori
                </h1>

                <p>
                    Perbarui nama kategori produk.
                </p>

            </div>

            <?php if ($error !== "") : ?>

                <div class="alert-error-custom" role="alert">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>

            <?php endif; ?>

            <div class="category-form-card">

                <form
                    method="POST"
                    autocomplete="off"
                >

                    <div class="category-form-group">

                        <label for="nama_kategori">
                            Nama Kategori *
                        </label>

                        <input
                            type="text"
                            id="nama_kategori"
                            name="nama_kategori"
                            value="<?= htmlspecialchars(
                                $kategori['nama_kategori'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            maxlength="100"
                            required
                            autofocus
                        >

                        <span class="form-help">
                            Pastikan nama kategori belum digunakan
                            oleh kategori lainnya.
                        </span>

                    </div>

                    <div class="category-form-actions">

                        <a
                            href="index.php"
                            class="btn-cancel"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn-save"
                        >
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </main>

        <?php include "../includes/footer.php"; ?>

    </div>

</div>

<script src="../assets/js/admin.js"></script>

</body>
</html>