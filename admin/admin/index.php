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

$data_owner = mysqli_fetch_assoc($query_owner);

$id_owner_pertama = (int) (
    $data_owner['id_admin'] ?? 0
);


/*
|--------------------------------------------------------------------------
| DATA ADMIN
|--------------------------------------------------------------------------
*/

$query_admin = mysqli_query(
    $conn,
    "SELECT
        id_admin,
        nama,
        email,
        role,
        created_at
     FROM admin
     ORDER BY
        id_admin ASC"
);


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$query_total = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin"
);

$total_admin = (int) (
    mysqli_fetch_assoc($query_total)['total']
    ?? 0
);


$query_owner_count = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin
     WHERE LOWER(role) = 'owner'"
);

$total_owner = (int) (
    mysqli_fetch_assoc($query_owner_count)['total']
    ?? 0
);


$query_gudang = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin
     WHERE LOWER(role) = 'gudang'"
);

$total_gudang = (int) (
    mysqli_fetch_assoc($query_gudang)['total']
    ?? 0
);


$query_kasir = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin
     WHERE LOWER(role) = 'kasir'"
);

$total_kasir = (int) (
    mysqli_fetch_assoc($query_kasir)['total']
    ?? 0
);


$query_sales = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin
     WHERE LOWER(role) = 'sales'"
);

$total_sales = (int) (
    mysqli_fetch_assoc($query_sales)['total']
    ?? 0
);


$query_asisten = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM admin
     WHERE LOWER(role) = 'asisten'"
);

$total_asisten = (int) (
    mysqli_fetch_assoc($query_asisten)['total']
    ?? 0
);


/*
|--------------------------------------------------------------------------
| PESAN
|--------------------------------------------------------------------------
*/

$pesan_sukses =
    $_SESSION['pesan_sukses']
    ?? '';

$pesan_error =
    $_SESSION['pesan_error']
    ?? '';

unset($_SESSION['pesan_sukses']);
unset($_SESSION['pesan_error']);


/*
|--------------------------------------------------------------------------
| CLASS ROLE
|--------------------------------------------------------------------------
*/

function roleClass($role)
{
    $role = strtolower(trim($role));

    switch ($role) {

        case 'owner':
            return 'role-owner';

        case 'gudang':
            return 'role-gudang';

        case 'kasir':
            return 'role-kasir';

        case 'sales':
            return 'role-sales';

        case 'asisten':
            return 'role-asisten';

        default:
            return 'role-default';
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
        Admin & Hak Akses - Toko ATK & Alat Jahit
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .admin-access-page {

            max-width: 1500px;

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


        .admin-alert.success {

            background: #e5f5e9;

            color: #27804a;

            border: 1px solid #ccebd6;

        }


        .admin-alert.error {

            background: #fde7e7;

            color: #b23b3b;

            border: 1px solid #f3cccc;

        }


        /* =====================================================
           STATISTIK
        ===================================================== */

        .admin-role-stat {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 25px;

        }


        .admin-role-card {

            background: #ffffff;

            border:
                1px solid #e0e7ed;

            border-radius: 14px;

            padding: 22px;

            box-shadow:
                0 4px 15px
                rgba(16,47,75,.04);

        }


        .admin-role-label {

            display: block;

            color: #7890a4;

            font-size: 12px;

            margin-bottom: 9px;

            text-transform: uppercase;

            font-weight: 700;

        }


        .admin-role-number {

            display: block;

            color: #102f4b;

            font-size: 28px;

            font-weight: 800;

        }


        /* =====================================================
           LIST CARD
        ===================================================== */

        .admin-list-card {

            background: #ffffff;

            border:
                1px solid #e0e7ed;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(16,47,75,.04);

            overflow: hidden;

        }


        .admin-list-header {

            padding:
                22px 24px;

            border-bottom:
                1px solid #e6edf2;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

        }


        .admin-list-header h2 {

            color: #102f4b;

            font-size: 20px;

            margin:
                0 0 6px;

        }


        .admin-list-header p {

            color: #7890a4;

            font-size: 13px;

            margin: 0;

        }


        .btn-tambah-admin {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #102f4b;

            color: #ffffff;

            text-decoration: none;

            border: none;

            border-radius: 8px;

            padding:
                11px 17px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

        }


        .btn-tambah-admin:hover {

            background: #1c496b;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .admin-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .admin-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

        }


        .admin-table th {

            background: #f4f7fa;

            color: #506d84;

            text-align: left;

            padding:
                14px 15px;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;

        }


        .admin-table td {

            padding:
                15px;

            border-top:
                1px solid #e5ebf0;

            color: #334f66;

            font-size: 13px;

            vertical-align: middle;

        }


        .admin-table tbody tr:hover {

            background: #fafcfd;

        }


        .nama-admin {

            color: #102f4b;

            font-weight: 700;

        }


        .email-admin {

            color: #7890a4;

        }


        /* =====================================================
           ROLE BADGE
        ===================================================== */

        .role-badge {

            display: inline-flex;

            align-items: center;

            padding:
                6px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;

        }


        .role-owner {

            background: #fff1d9;

            color: #9a6800;

        }


        .role-gudang {

            background: #e7f3ed;

            color: #28734a;

        }


        .role-kasir {

            background: #e7eef8;

            color: #315b8a;

        }


        .role-sales {

            background: #f5eafa;

            color: #74428a;

        }


        .role-asisten {

            background: #e9eef2;

            color: #536b7d;

        }


        .role-default {

            background: #eef3f7;

            color: #506d84;

        }


        /* =====================================================
           OWNER UTAMA
        ===================================================== */

        .owner-utama {

            display: inline-flex;

            align-items: center;

            margin-left: 7px;

            padding:
                4px 7px;

            border-radius: 5px;

            background: #fff4d6;

            color: #946d00;

            font-size: 10px;

            font-weight: 700;

        }


        /* =====================================================
           ACTION
        ===================================================== */

        .aksi-admin {

            display: flex;

            gap: 7px;

            flex-wrap: wrap;

        }


        .btn-edit-admin {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                8px 12px;

            border-radius: 7px;

            background: #edf3f7;

            color: #20567d;

            border:
                1px solid #dce5ec;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

        }


        .btn-edit-admin:hover {

            background: #e1e9ef;

        }


        .btn-hapus-admin {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                8px 12px;

            border-radius: 7px;

            background: #fde7e7;

            color: #b23b3b;

            border:
                1px solid #f3cccc;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

        }


        .btn-hapus-admin:hover {

            background: #fbd9d9;

        }


        .akun-terlindungi {

            display: inline-flex;

            align-items: center;

            padding:
                8px 10px;

            border-radius: 7px;

            background: #fff4d6;

            color: #946d00;

            font-size: 11px;

            font-weight: 700;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .admin-empty {

            text-align: center;

            padding:
                50px 20px !important;

            color: #7890a4 !important;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .admin-role-stat {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 800px) {

            .admin-role-stat {

                grid-template-columns: 1fr;

            }


            .admin-list-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .btn-tambah-admin {

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


            <div class="admin-access-page">


                <!-- =================================================
                     JUDUL
                ================================================== -->

                <div class="page-title">

                    <h1>
                        Admin & Hak Akses
                    </h1>

                    <p>
                        Mengelola akun admin dan hak akses sistem.
                    </p>

                </div>


                <!-- =================================================
                     ALERT
                ================================================== -->

                <?php if ($pesan_sukses !== '') : ?>

                    <div class="admin-alert success">

                        <?= htmlspecialchars(
                            $pesan_sukses
                        ); ?>

                    </div>

                <?php endif; ?>


                <?php if ($pesan_error !== '') : ?>

                    <div class="admin-alert error">

                        <?= htmlspecialchars(
                            $pesan_error
                        ); ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     STATISTIK
                ================================================== -->

                <div class="admin-role-stat">


                    <div class="admin-role-card">

                        <span class="admin-role-label">
                            Total Admin
                        </span>

                        <span class="admin-role-number">

                            <?= number_format(
                                $total_admin,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                    <div class="admin-role-card">

                        <span class="admin-role-label">
                            Owner
                        </span>

                        <span class="admin-role-number">

                            <?= number_format(
                                $total_owner,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                    <div class="admin-role-card">

                        <span class="admin-role-label">
                            Gudang
                        </span>

                        <span class="admin-role-number">

                            <?= number_format(
                                $total_gudang,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                    <div class="admin-role-card">

                        <span class="admin-role-label">
                            Kasir
                        </span>

                        <span class="admin-role-number">

                            <?= number_format(
                                $total_kasir,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                    <div class="admin-role-card">

                        <span class="admin-role-label">
                            Sales
                        </span>

                        <span class="admin-role-number">

                            <?= number_format(
                                $total_sales,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                    <div class="admin-role-card">

                        <span class="admin-role-label">
                            Asisten
                        </span>

                        <span class="admin-role-number">

                            <?= number_format(
                                $total_asisten,
                                0,
                                ',',
                                '.'
                            ); ?>

                        </span>

                    </div>


                </div>


                <!-- =================================================
                     LIST ADMIN
                ================================================== -->

                <div class="admin-list-card">


                    <div class="admin-list-header">


                        <div>

                            <h2>
                                Daftar Admin
                            </h2>

                            <p>
                                Hanya Owner yang dapat mengatur akun dan hak akses admin.
                            </p>

                        </div>


                        <a
                            href="tambah.php"
                            class="btn-tambah-admin"
                        >
                            + Tambah Admin
                        </a>


                    </div>


                    <!-- =================================================
                         TABLE
                    ================================================== -->

                    <div class="admin-table-wrapper">

                        <table class="admin-table">


                            <thead>

                                <tr>

                                    <th>
                                        No
                                    </th>

                                    <th>
                                        Nama
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Role
                                    </th>

                                    <th>
                                        Dibuat
                                    </th>

                                    <th>
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (
                                $query_admin
                                &&
                                mysqli_num_rows(
                                    $query_admin
                                ) > 0
                            ) : ?>


                                <?php $no = 1; ?>


                                <?php while (
                                    $admin =
                                    mysqli_fetch_assoc(
                                        $query_admin
                                    )
                                ) : ?>


                                    <?php

                                    $id =
                                        (int)
                                        $admin[
                                            'id_admin'
                                        ];

                                    $role =
                                        trim(
                                            $admin[
                                                'role'
                                            ]
                                        );

                                    $isOwnerPertama =
                                        (
                                            $id
                                            ===
                                            $id_owner_pertama
                                        );

                                    ?>


                                    <tr>


                                        <!-- NO -->

                                        <td>

                                            <?= $no++; ?>

                                        </td>


                                        <!-- NAMA -->

                                        <td>

                                            <span
                                                class="nama-admin"
                                            >

                                                <?= htmlspecialchars(
                                                    $admin[
                                                        'nama'
                                                    ]
                                                ); ?>

                                            </span>


                                            <?php if (
                                                $isOwnerPertama
                                            ) : ?>

                                                <span
                                                    class="owner-utama"
                                                >
                                                    OWNER UTAMA
                                                </span>

                                            <?php endif; ?>


                                        </td>


                                        <!-- EMAIL -->

                                        <td>

                                            <span
                                                class="email-admin"
                                            >

                                                <?= htmlspecialchars(
                                                    $admin[
                                                        'email'
                                                    ]
                                                ); ?>

                                            </span>

                                        </td>


                                        <!-- ROLE -->

                                        <td>

                                            <span
                                                class="
                                                    role-badge
                                                    <?= roleClass(
                                                        $role
                                                    ); ?>
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $role
                                                ); ?>

                                            </span>

                                        </td>


                                        <!-- CREATED -->

                                        <td>

                                            <?= !empty(
                                                $admin[
                                                    'created_at'
                                                ]
                                            )
                                                ? date(
                                                    'd/m/Y H:i',
                                                    strtotime(
                                                        $admin[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                                : '-';
                                            ?>

                                        </td>


                                        <!-- AKSI -->

                                        <td>

                                            <div
                                                class="aksi-admin"
                                            >


                                                <!-- EDIT -->

                                                <a
                                                    href="edit.php?id=<?= $id; ?>"
                                                    class="btn-edit-admin"
                                                >
                                                    Edit
                                                </a>


                                                <?php if (
                                                    $isOwnerPertama
                                                ) : ?>


                                                    <span
                                                        class="akun-terlindungi"
                                                    >
                                                        🔒 Dilindungi
                                                    </span>


                                                <?php else : ?>


                                                    <a
                                                        href="hapus.php?id=<?= $id; ?>"
                                                        class="btn-hapus-admin"
                                                        onclick="
                                                            return confirm(
                                                                'Yakin ingin menghapus admin ini?'
                                                            );
                                                        "
                                                    >
                                                        Hapus
                                                    </a>


                                                <?php endif; ?>


                                            </div>

                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else : ?>


                                <tr>

                                    <td
                                        colspan="6"
                                        class="admin-empty"
                                    >

                                        Belum ada data admin.

                                    </td>

                                </tr>


                            <?php endif; ?>


                            </tbody>


                        </table>

                    </div>


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