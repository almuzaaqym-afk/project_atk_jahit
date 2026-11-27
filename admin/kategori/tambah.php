<?php

session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../../config/koneksi.php";
require_once __DIR__ . "/../includes/log_aktivitas.php";

$error = "";

/* =========================================================
   PROSES TAMBAH KATEGORI
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
           CEK KATEGORI DUPLIKAT
        ========================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_kategori
             FROM kategori
             WHERE nama_kategori = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Gagal memeriksa kategori.";
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $nama_kategori
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = "Gagal memeriksa kategori.";
                mysqli_stmt_close($stmt);
            } else {
                $hasil = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($hasil) > 0) {
                    $error = "Kategori tersebut sudah ada.";
                    mysqli_stmt_close($stmt);
                } else {
                    mysqli_stmt_close($stmt);

                    /* =========================
                       INSERT KATEGORI
                    ========================= */

                    $stmtInsert = mysqli_prepare(
                        $conn,
                        "INSERT INTO kategori
                         (nama_kategori)
                         VALUES (?)"
                    );

                    if (!$stmtInsert) {
                        $error = "Gagal menyiapkan penyimpanan kategori.";
                    } else {
                        mysqli_stmt_bind_param(
                            $stmtInsert,
                            "s",
                            $nama_kategori
                        );

                        if (mysqli_stmt_execute($stmtInsert)) {

                            $idKategoriBaru = (int) mysqli_insert_id($conn);

                            mysqli_stmt_close($stmtInsert);

                            /* =========================
                               CATAT LOG TAMBAH KATEGORI
                            ========================= */

                            $detailLog = 'Menambahkan kategori "' .
                                $nama_kategori .
                                '" (ID kategori: ' . $idKategoriBaru . ').';

                            try {
                                $logBerhasil = catatLogAktivitas(
                                    $conn,
                                    'kategori',
                                    'Tambah Kategori',
                                    $detailLog
                                );

                                if (!$logBerhasil) {
                                    error_log(
                                        'Gagal mencatat log tambah kategori: ID ' .
                                        $idKategoriBaru
                                    );
                                }
                            } catch (Throwable $e) {
                                error_log(
                                    'Kesalahan saat mencatat log tambah kategori ID ' .
                                    $idKategoriBaru . ': ' . $e->getMessage()
                                );
                            }

                            header(
                                "Location: index.php?status=berhasil"
                            );

                            exit;

                        } else {

                            $error =
                                "Kategori gagal ditambahkan: " .
                                mysqli_stmt_error($stmtInsert);

                            mysqli_stmt_close($stmtInsert);
                        }
                    }
                }
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

    <title>
        Tambah Kategori - Toko ATK &amp; Alat Jahit
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

    <style>

        /* =====================================================
           FORM KATEGORI
        ===================================================== */

        .category-form-card {
            max-width: 700px;
            background: #ffffff;
            border: 1px solid #e0e7ed;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(16, 47, 75, 0.04);
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
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        .category-form-group input:focus {
            border-color: #245b83;
            box-shadow: 0 0 0 3px rgba(36, 91, 131, 0.10);
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

    <!-- =====================================================
         SIDEBAR
    ===================================================== -->

    <?php include "../includes/sidebar.php"; ?>

    <!-- =====================================================
         MAIN
    ===================================================== -->

    <div class="admin-main">

        <!-- =================================================
             NAVBAR
        ================================================= -->

        <?php include "../includes/navbar.php"; ?>

        <!-- =================================================
             CONTENT
        ================================================= -->

        <main class="admin-content">

            <!-- =================================================
                 HEADER
            ================================================= -->

            <div class="page-title">

                <h1>
                    Tambah Kategori
                </h1>

                <p>
                    Tambahkan kategori baru untuk produk toko.
                </p>

            </div>

            <!-- =================================================
                 ERROR
            ================================================= -->

            <?php if ($error !== "") : ?>

                <div class="alert-error-custom" role="alert">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>

            <?php endif; ?>

            <!-- =================================================
                 FORM
            ================================================= -->

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
                                $_POST['nama_kategori'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            maxlength="100"
                            placeholder="Contoh: ATK"
                            required
                            autofocus
                        >

                        <span class="form-help">
                            Contoh kategori:
                            ATK, Alat Jahit, Buku, atau Perlengkapan Kantor.
                        </span>

                    </div>

                    <!-- =================================================
                         ACTION
                    ================================================= -->

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
                            + Simpan Kategori
                        </button>

                    </div>

                </form>

            </div>

        </main>

        <!-- =================================================
             FOOTER
        ================================================= -->

        <?php include "../includes/footer.php"; ?>

    </div>

</div>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="../assets/js/admin.js"></script>

</body>

</html>