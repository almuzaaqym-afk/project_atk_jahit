<?php

$adminBase = "/project_atk_jahit/admin/";
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$pageTitles = [
    'index.php' => 'Dashboard',

    // Produk
    'produk/index.php' => 'Produk',
    'produk/tambah.php' => 'Tambah Produk',
    'produk/edit.php' => 'Edit Produk',

    // Kategori
    'kategori/index.php' => 'Kategori',
    'kategori/tambah.php' => 'Tambah Kategori',
    'kategori/edit.php' => 'Edit Kategori',

    // Persediaan
    'stok/index.php' => 'Stok',
    'stok/update.php' => 'Update Stok',
    'lokasi/index.php' => 'Lokasi / Rak',
    'lokasi/tambah.php' => 'Tambah Lokasi',
    'lokasi/edit.php' => 'Edit Lokasi',

    // Supplier
    'supplier/index.php' => 'Supplier',
    'supplier/tambah.php' => 'Tambah Supplier',
    'supplier/edit.php' => 'Edit Supplier',

    // Pembelian
    'pembelian/index.php' => 'Pembelian',
    'pembelian/tambah.php' => 'Tambah Pembelian',
    'pembelian/detail.php' => 'Detail Pembelian',

    // Pesanan
    'pesanan/index.php' => 'Pesanan',
    'pesanan/detail.php' => 'Detail Pesanan',
    'pesanan/status.php' => 'Status Pesanan',
    'pesanan/chat.php' => 'Live Chat Pesanan',

    // Pelanggan
    'pelanggan/index.php' => 'Pelanggan',
    'pelanggan/detail.php' => 'Detail Pelanggan',

    // Pembayaran
    'pembayaran/index.php' => 'Pembayaran',

    // Voucher dan Customer Service
    'voucher/index.php' => 'Voucher / Promo',
    'cs/index.php' => 'Customer Service',
    'cs/layani.php' => 'Layani Customer Service',
    'cs/chat.php' => 'Customer Service',

    // Laporan
    'laporan/penjualan.php' => 'Laporan Penjualan',
    'laporan/pembelian.php' => 'Laporan Pembelian',
    'laporan/stok.php' => 'Laporan Stok',
    'laporan/produk.php' => 'Produk Terlaris',

    // Admin dan Hak Akses
    'admin/index.php' => 'Admin & Hak Akses',
    'admin/tambah.php' => 'Tambah Admin',
    'admin/edit.php' => 'Edit Admin',

    // Sistem
    'pengaturan.php' => 'Pengaturan Toko',
    'log_aktivitas/index.php' => 'Log Aktivitas Admin'
];

/*
|--------------------------------------------------------------------------
| MENENTUKAN JUDUL HALAMAN
|--------------------------------------------------------------------------
*/

$relativePath = '';

if (strpos($currentPath, $adminBase) === 0) {
    $relativePath = substr(
        $currentPath,
        strlen($adminBase)
    );
}

$pageTitle = $pageTitles[$relativePath] ?? 'Dashboard';

/*
|--------------------------------------------------------------------------
| DATA ADMIN
|--------------------------------------------------------------------------
*/

$namaAdmin = $_SESSION['nama_admin'] ?? 'Administrator';

$avatar = strtoupper(
    substr($namaAdmin, 0, 1)
);

?>

<link
    rel="stylesheet"
    href="<?= $adminBase; ?>assets/css/admin-notifikasi.css"
>

<header class="admin-navbar">

    <div class="navbar-left">

        <button
            type="button"
            id="sidebarToggle"
            class="sidebar-toggle"
            aria-label="Buka atau tutup sidebar"
            title="Menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-title">
            <span class="navbar-page-title">
                <?= htmlspecialchars(
                    $pageTitle,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </span>
        </div>

    </div>

    <div class="navbar-right">

        <a
            href="<?= $adminBase; ?>cs/index.php"
            class="navbar-notification"
            id="navbarCsNotification"
            title="Customer Service"
            aria-label="Customer Service"
        >
            <span class="navbar-notification-icon">💬</span>

            <span
                class="navbar-notification-badge"
                id="csNotificationBadge"
                hidden
            >0</span>
        </a>

        <a
            href="<?= $adminBase; ?>pesanan/index.php"
            class="navbar-notification"
            id="navbarChatNotification"
            title="Live Chat Pesanan"
            aria-label="Live Chat Pesanan"
        >
            <span class="navbar-notification-icon">🔔</span>

            <span
                class="navbar-notification-badge"
                id="chatNotificationBadge"
                hidden
            >0</span>
        </a>

        <div class="navbar-user">

            <div class="navbar-user-info">

                <strong>
                    <?= htmlspecialchars(
                        $namaAdmin,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </strong>

                <span>Admin Toko</span>

            </div>

            <div class="navbar-avatar">
                <?= htmlspecialchars(
                    $avatar,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </div>

        </div>

    </div>

</header>

<script>
window.adminNotificationConfig = {
    csEndpoint: "<?= $adminBase; ?>cs/notifikasi.php",
    chatEndpoint: "<?= $adminBase; ?>pesanan/notifikasi_chat.php",
    csUrl: "<?= $adminBase; ?>cs/index.php",
    chatUrl: "<?= $adminBase; ?>pesanan/index.php"
};
</script>

<script src="<?= $adminBase; ?>assets/js/admin-notifikasi.js"></script>
