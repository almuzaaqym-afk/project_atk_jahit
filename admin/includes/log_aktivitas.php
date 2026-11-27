
<?php

/*
|--------------------------------------------------------------------------
| HELPER LOG AKTIVITAS ADMIN
|--------------------------------------------------------------------------
| Lokasi: admin/includes/log_aktivitas.php
|
| Digunakan untuk mencatat aktivitas admin ke tabel
| admin_log_aktivitas.
|--------------------------------------------------------------------------
*/

if (!function_exists('catatLogAktivitas')) {

    function catatLogAktivitas(
        mysqli $conn,
        string $modul,
        string $aktivitas,
        ?string $detail = null
    ): bool {

        // Pastikan hanya aktivitas admin yang dicatat.
        $idAdmin = isset($_SESSION['id_admin'])
            ? (int) $_SESSION['id_admin']
            : 0;

        if ($idAdmin <= 0) {
            return false;
        }

        // Ambil nama admin dari session.
        $namaAdmin = $_SESSION['nama_admin']
            ?? $_SESSION['username_admin']
            ?? $_SESSION['username']
            ?? ('Admin #' . $idAdmin);

        // Batasi panjang data sesuai struktur tabel.
        $namaAdmin = substr((string) $namaAdmin, 0, 100);
        $modul = substr(trim($modul), 0, 50);
        $aktivitas = substr(trim($aktivitas), 0, 100);

        // Informasi tambahan untuk audit.
        $detail = $detail !== null
            ? substr($detail, 0, 10000)
            : null;

        $ipAddress = substr(
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            0,
            45
        );

        $userAgent = substr(
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            0,
            255
        );

        // Validasi dasar.
        if ($modul === '' || $aktivitas === '') {
            return false;
        }

        // Simpan menggunakan prepared statement.
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO admin_log_aktivitas (
                id_admin,
                nama_admin,
                modul,
                aktivitas,
                detail,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            error_log(
                'Log aktivitas gagal disiapkan: ' .
                mysqli_error($conn)
            );

            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "issssss",
            $idAdmin,
            $namaAdmin,
            $modul,
            $aktivitas,
            $detail,
            $ipAddress,
            $userAgent
        );

        $berhasil = mysqli_stmt_execute($stmt);

        if (!$berhasil) {
            error_log(
                'Log aktivitas gagal disimpan: ' .
                mysqli_stmt_error($stmt)
            );
        }

        mysqli_stmt_close($stmt);

        return $berhasil;
    }
}
