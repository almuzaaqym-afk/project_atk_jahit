<?php

/*
|--------------------------------------------------------------------------
| SISTEM HAK AKSES ADMIN
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/koneksi.php";

/*
|--------------------------------------------------------------------------
| CEK LOGIN ADMIN
|--------------------------------------------------------------------------
*/

function cekLoginAdmin()
{
    if (!isset($_SESSION['id_admin'])) {
        header("Location: /project_atk_jahit/admin/login.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA ADMIN YANG LOGIN
|--------------------------------------------------------------------------
*/

function getAdminLogin()
{
    global $conn;

    cekLoginAdmin();

    $id_admin = (int) $_SESSION['id_admin'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_admin, nama, email, role
         FROM admin
         WHERE id_admin = ?
         LIMIT 1"
    );

    if (!$stmt) {
        error_log("Gagal menyiapkan query admin: " . mysqli_error($conn));

        http_response_code(500);
        exit("Terjadi kesalahan sistem.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_admin);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$admin) {
        session_unset();
        session_destroy();

        header("Location: /project_atk_jahit/admin/login.php");
        exit;
    }

    return $admin;
}

/*
|--------------------------------------------------------------------------
| AMBIL ROLE ADMIN
|--------------------------------------------------------------------------
*/

function getRoleAdmin()
{
    $admin = getAdminLogin();

    return strtolower(trim($admin['role']));
}

/*
|--------------------------------------------------------------------------
| CEK OWNER
|--------------------------------------------------------------------------
*/

function cekOwner()
{
    $admin = getAdminLogin();

    $role = strtolower(trim($admin['role']));

    if ($role !== 'owner') {
        header("Location: /project_atk_jahit/admin/index.php");
        exit;
    }

    return $admin;
}

/*
|--------------------------------------------------------------------------
| CEK ROLE
|--------------------------------------------------------------------------
*/

function cekRole($roles)
{
    $admin = getAdminLogin();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    $roleLogin = strtolower(trim($admin['role']));

    foreach ($roles as $role) {
        if ($roleLogin === strtolower(trim($role))) {
            return $admin;
        }
    }

    header("Location: /project_atk_jahit/admin/index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DAFTAR HAK AKSES
|--------------------------------------------------------------------------
*/

function roleBolehAkses($menu)
{
    $role = getRoleAdmin();

    /* OWNER: SEMUA AKSES */
    if ($role === 'owner') {
        return true;
    }

    /* GUDANG */
    if ($role === 'gudang') {
        $akses = [
            'dashboard',
            'produk',
            'kategori',
            'stok',
            'lokasi',
            'supplier',
            'pembelian'
        ];

        return in_array($menu, $akses, true);
    }

    /* KASIR */
    if ($role === 'kasir') {
        $akses = [
            'dashboard',
            'pesanan',
            'pelanggan',
            'pembayaran',
            'cs'
        ];

        return in_array($menu, $akses, true);
    }

    /* SALES */
    if ($role === 'sales') {
        $akses = [
            'dashboard',
            'pelanggan',
            'voucher',
            'banner',
            'cs'
        ];

        return in_array($menu, $akses, true);
    }

    /* ASISTEN */
    if ($role === 'asisten') {
        $akses = [
            'dashboard',
            'laporan'
        ];

        return in_array($menu, $akses, true);
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| CEK AKSES HALAMAN
|--------------------------------------------------------------------------
*/

function cekAkses($menu)
{
    cekLoginAdmin();

    if (!roleBolehAkses($menu)) {
        header("Location: /project_atk_jahit/admin/index.php");
        exit;
    }

    return getAdminLogin();
}