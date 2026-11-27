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
| AMBIL ID ADMIN
|--------------------------------------------------------------------------
*/

$id_admin = (int) ($_GET['id'] ?? 0);

if ($id_admin <= 0) {

    $_SESSION['pesan_error'] =
        "Data admin tidak valid.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| DATA OWNER PERTAMA
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


$isOwnerPertama =
    $id_admin === $id_owner_pertama;


/*
|--------------------------------------------------------------------------
| AMBIL DATA ADMIN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_admin,
        nama,
        email,
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

$admin =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$admin) {

    $_SESSION['pesan_error'] =
        "Data admin tidak ditemukan.";

    header("Location: index.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| PROSES UPDATE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama =
        trim($_POST['nama'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $role =
        trim($_POST['role'] ?? '');

    $password =
        $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDASI DASAR
    |--------------------------------------------------------------------------
    */

    if (
        $nama === '' ||
        $email === '' ||
        $role === ''
    ) {

        $_SESSION['pesan_error'] =
            "Nama, email, dan role wajib diisi.";

        header(
            "Location: edit.php?id="
            . $id_admin
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI EMAIL
    |--------------------------------------------------------------------------
    */

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $_SESSION['pesan_error'] =
            "Format email tidak valid.";

        header(
            "Location: edit.php?id="
            . $id_admin
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ROLE VALID
    |--------------------------------------------------------------------------
    */

    $role_valid = [

        'Owner',
        'Gudang',
        'Kasir',
        'Sales',
        'Asisten'

    ];


    if (
        !in_array(
            $role,
            $role_valid,
            true
        )
    ) {

        $_SESSION['pesan_error'] =
            "Role yang dipilih tidak valid.";

        header(
            "Location: edit.php?id="
            . $id_admin
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | OWNER PERTAMA HARUS TETAP OWNER
    |--------------------------------------------------------------------------
    */

    if (
        $isOwnerPertama &&
        $role !== 'Owner'
    ) {

        $_SESSION['pesan_error'] =
            "Owner utama tidak dapat diganti role.";

        header(
            "Location: edit.php?id="
            . $id_admin
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CEK EMAIL ADMIN LAIN
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_admin
         FROM admin
         WHERE email = ?
         AND id_admin != ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $email,
        $id_admin
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $emailSudahAda =
        mysqli_num_rows($result) > 0;

    mysqli_stmt_close($stmt);


    if ($emailSudahAda) {

        $_SESSION['pesan_error'] =
            "Email sudah digunakan admin lain.";

        header(
            "Location: edit.php?id="
            . $id_admin
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI PASSWORD JIKA DIISI
    |--------------------------------------------------------------------------
    */

    if (
        $password !== '' &&
        strlen($password) < 6
    ) {

        $_SESSION['pesan_error'] =
            "Password minimal 6 karakter.";

        header(
            "Location: edit.php?id="
            . $id_admin
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DENGAN PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($password !== '') {

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $stmt = mysqli_prepare(
            $conn,
            "UPDATE admin
             SET
                nama = ?,
                email = ?,
                password = ?,
                role = ?
             WHERE id_admin = ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $nama,
            $email,
            $passwordHash,
            $role,
            $id_admin
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | UPDATE TANPA PASSWORD
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE admin
             SET
                nama = ?,
                email = ?,
                role = ?
             WHERE id_admin = ?"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $nama,
            $email,
            $role,
            $id_admin
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EKSEKUSI
    |--------------------------------------------------------------------------
    */

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        $_SESSION['pesan_sukses'] =
            "Data admin berhasil diperbarui.";

        header("Location: index.php");

        exit;
    }


    mysqli_stmt_close($stmt);


    $_SESSION['pesan_error'] =
        "Data admin gagal diperbarui.";

    header(
        "Location: edit.php?id="
        . $id_admin
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| PESAN ERROR
|--------------------------------------------------------------------------
*/

$pesan_error =
    $_SESSION['pesan_error']
    ?? '';

unset($_SESSION['pesan_error']);

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
        Edit Admin - Toko ATK & Alat Jahit
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .admin-form-page {

            max-width: 900px;

        }


        /* =====================================================
           ALERT
        ===================================================== */

        .admin-alert {

            padding: 14px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            font-weight: 600;

        }


        .admin-alert.error {

            background: #fde7e7;

            color: #b23b3b;

            border:
                1px solid #f3cccc;

        }


        /* =====================================================
           OWNER INFO
        ===================================================== */

        .owner-protected-info {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding:
                14px 16px;

            margin-bottom: 20px;

            background: #fff7df;

            border:
                1px solid #f0dfaa;

            border-radius: 10px;

            color: #806400;

            font-size: 13px;

            line-height: 1.5;

        }


        /* =====================================================
           CARD
        ===================================================== */

        .admin-form-card {

            background: #ffffff;

            border:
                1px solid #e0e7ed;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(16,47,75,.04);

            overflow: hidden;

        }


        .admin-form-header {

            padding:
                22px 25px;

            border-bottom:
                1px solid #e6edf2;

        }


        .admin-form-header h2 {

            color: #102f4b;

            font-size: 20px;

            margin:
                0 0 6px;

        }


        .admin-form-header p {

            color: #7890a4;

            font-size: 13px;

            margin: 0;

        }


        /* =====================================================
           FORM
        ===================================================== */

        .admin-form-body {

            padding: 25px;

        }


        .form-row {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px;

        }


        .form-group {

            margin-bottom: 20px;

        }


        .form-group.full {

            grid-column:
                1 / -1;

        }


        .form-group label {

            display: block;

            color: #102f4b;

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 7px;

        }


        .form-group input,
        .form-group select {

            width: 100%;

            height: 44px;

            padding:
                0 13px;

            border:
                1px solid #d6e0e8;

            border-radius: 8px;

            font-size: 14px;

            color: #334f66;

            background: #ffffff;

            outline: none;

            box-sizing: border-box;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #20567d;

        }


        .form-help {

            display: block;

            margin-top: 6px;

            color: #9aafbf;

            font-size: 12px;

        }


        /* =====================================================
           ROLE OWNER
        ===================================================== */

        .owner-role-locked {

            background: #f5f7f9 !important;

            color: #806400 !important;

            cursor: not-allowed;

        }


        /* =====================================================
           ROLE INFO
        ===================================================== */

        .role-info {

            margin-top: 5px;

            padding:
                16px 18px;

            background: #f4f7fa;

            border:
                1px solid #e0e7ed;

            border-radius: 10px;

        }


        .role-info-title {

            color: #102f4b;

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 10px;

        }


        .role-info ul {

            margin: 0;

            padding-left: 20px;

            color: #506d84;

            font-size: 12px;

            line-height: 1.8;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .admin-form-footer {

            padding:
                17px 25px;

            background: #f8fafc;

            border-top:
                1px solid #e6edf2;

            display: flex;

            justify-content: flex-end;

            gap: 10px;

        }


        .btn-kembali {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                10px 17px;

            border:
                1px solid #d6e0e8;

            border-radius: 8px;

            background: #ffffff;

            color: #506d84;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

        }


        .btn-kembali:hover {

            background: #f2f5f7;

        }


        .btn-simpan {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                10px 18px;

            border: none;

            border-radius: 8px;

            background: #102f4b;

            color: #ffffff;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

        }


        .btn-simpan:hover {

            background: #1c496b;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 700px) {

            .form-row {

                grid-template-columns: 1fr;

            }


            .form-group.full {

                grid-column: auto;

            }


            .admin-form-footer {

                flex-direction: column;

            }


            .btn-kembali,
            .btn-simpan {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php require_once "../includes/sidebar.php"; ?>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="admin-main">


        <!-- =================================================
             NAVBAR
        ================================================== -->

        <?php require_once "../includes/navbar.php"; ?>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <section class="admin-content">


            <div class="admin-form-page">


                <!-- =================================================
                     JUDUL
                ================================================== -->

                <div class="page-title">

                    <h1>
                        Edit Admin
                    </h1>

                    <p>
                        Mengubah data dan hak akses akun admin.
                    </p>

                </div>


                <!-- =================================================
                     ALERT
                ================================================== -->

                <?php if ($pesan_error !== '') : ?>

                    <div class="admin-alert error">

                        <?= htmlspecialchars(
                            $pesan_error
                        ); ?>

                    </div>

                <?php endif; ?>


                <?php if ($isOwnerPertama) : ?>

                    <div class="owner-protected-info">

                        <span>
                            🔒
                        </span>

                        <div>

                            <strong>
                                Owner Utama
                            </strong>

                            <br>

                            Akun ini merupakan akun Owner utama perusahaan.
                            Data dapat diedit, tetapi role tidak dapat diganti
                            dan akun tidak dapat dihapus.

                        </div>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     CARD
                ================================================== -->

                <div class="admin-form-card">


                    <div class="admin-form-header">

                        <h2>
                            Data Admin
                        </h2>

                        <p>
                            Perbarui informasi akun admin.
                        </p>

                    </div>


                    <form
                        method="POST"
                        autocomplete="off"
                    >


                        <div class="admin-form-body">


                            <div class="form-row">


                                <!-- NAMA -->

                                <div class="form-group">

                                    <label>
                                        Nama Admin
                                    </label>

                                    <input
                                        type="text"
                                        name="nama"
                                        value="<?= htmlspecialchars(
                                            $admin['nama']
                                        ); ?>"
                                        required
                                    >

                                </div>


                                <!-- EMAIL -->

                                <div class="form-group">

                                    <label>
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        value="<?= htmlspecialchars(
                                            $admin['email']
                                        ); ?>"
                                        required
                                    >

                                </div>


                                <!-- PASSWORD -->

                                <div class="form-group">

                                    <label>
                                        Password Baru
                                    </label>

                                    <input
                                        type="password"
                                        name="password"
                                        placeholder="Kosongkan jika tidak ingin mengganti"
                                        minlength="6"
                                    >

                                    <span class="form-help">
                                        Kosongkan jika password tidak ingin diubah.
                                    </span>

                                </div>


                                <!-- ROLE -->

                                <div class="form-group">

                                    <label>
                                        Role / Hak Akses
                                    </label>


                                    <?php if (
                                        $isOwnerPertama
                                    ) : ?>


                                        <select
                                            name="role"
                                            class="owner-role-locked"
                                            disabled
                                        >

                                            <option
                                                value="Owner"
                                                selected
                                            >
                                                Owner
                                            </option>

                                        </select>


                                        <!-- tetap kirim role Owner -->

                                        <input
                                            type="hidden"
                                            name="role"
                                            value="Owner"
                                        >


                                        <span class="form-help">
                                            Role Owner utama tidak dapat diubah.
                                        </span>


                                    <?php else : ?>


                                        <select
                                            name="role"
                                            required
                                        >

                                            <option
                                                value="Owner"
                                                <?= $admin['role'] === 'Owner'
                                                    ? 'selected'
                                                    : ''; ?>
                                            >
                                                Owner
                                            </option>

                                            <option
                                                value="Gudang"
                                                <?= $admin['role'] === 'Gudang'
                                                    ? 'selected'
                                                    : ''; ?>
                                            >
                                                Gudang
                                            </option>

                                            <option
                                                value="Kasir"
                                                <?= $admin['role'] === 'Kasir'
                                                    ? 'selected'
                                                    : ''; ?>
                                            >
                                                Kasir
                                            </option>

                                            <option
                                                value="Sales"
                                                <?= $admin['role'] === 'Sales'
                                                    ? 'selected'
                                                    : ''; ?>
                                            >
                                                Sales
                                            </option>

                                            <option
                                                value="Asisten"
                                                <?= $admin['role'] === 'Asisten'
                                                    ? 'selected'
                                                    : ''; ?>
                                            >
                                                Asisten
                                            </option>

                                        </select>


                                    <?php endif; ?>

                                </div>


                            </div>


                            <!-- =================================================
                                 INFORMASI ROLE
                            ================================================== -->

                            <div class="role-info">

                                <div class="role-info-title">
                                    Informasi Hak Akses
                                </div>

                                <ul>

                                    <li>
                                        <strong>Owner</strong> —
                                        Semua modul dan pengaturan admin.
                                    </li>

                                    <li>
                                        <strong>Gudang</strong> —
                                        Produk, kategori, stok, lokasi, supplier, dan pembelian.
                                    </li>

                                    <li>
                                        <strong>Kasir</strong> —
                                        Pesanan, pelanggan, dan pembayaran.
                                    </li>

                                    <li>
                                        <strong>Sales</strong> —
                                        Pelanggan, voucher/promo, dan banner.
                                    </li>

                                    <li>
                                        <strong>Asisten</strong> —
                                        Dashboard dan laporan.
                                    </li>

                                </ul>

                            </div>


                        </div>


                        <!-- =================================================
                             FOOTER
                        ================================================== -->

                        <div class="admin-form-footer">


                            <a
                                href="index.php"
                                class="btn-kembali"
                            >
                                Kembali
                            </a>


                            <button
                                type="submit"
                                class="btn-simpan"
                            >
                                Simpan Perubahan
                            </button>


                        </div>


                    </form>


                </div>


            </div>


        </section>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <?php require_once "../includes/footer.php"; ?>


    </main>


</div>


<script
    src="../assets/js/admin.js"
></script>


</body>

</html>