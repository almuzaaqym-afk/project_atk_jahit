<?php

require_once __DIR__ . "/hak_akses.php";
require_once __DIR__ . "/../../config/koneksi.php";

$adminBase = "/project_atk_jahit/admin/";

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

/*
|--------------------------------------------------------------------------
| FUNGSI MENU AKTIF
|--------------------------------------------------------------------------
*/

if (!function_exists('menuActive')) {
    function menuActive($paths)
    {
        global $currentPath, $adminBase;

        if (!is_array($paths)) {
            $paths = [$paths];
        }

        foreach ($paths as $path) {
            $fullPath = $adminBase . ltrim($path, '/');

            if ($currentPath === $fullPath) {
                return 'active';
            }
        }

        return '';
    }
}

/*
|--------------------------------------------------------------------------
| ROLE ADMIN
|--------------------------------------------------------------------------
*/

$roleLogin = getRoleAdmin();

/*
|--------------------------------------------------------------------------
| FUNGSI CEK MENU
|--------------------------------------------------------------------------
*/

if (!function_exists('tampilMenu')) {
    function tampilMenu($menu)
    {
        global $roleLogin;

        if ($roleLogin === 'owner') {
            return true;
        }

        return roleBolehAkses($menu);
    }
}

/*
|--------------------------------------------------------------------------
| JUMLAH ANTREAN CUSTOMER SERVICE
|--------------------------------------------------------------------------
*/

$jumlahAntreanCS = 0;

if (tampilMenu('cs') && isset($conn) && $conn) {
    $queryAntreanCS = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM cs_chat
         WHERE status_chat = 'Menunggu'"
    );

    if ($queryAntreanCS) {
        $dataAntreanCS = mysqli_fetch_assoc($queryAntreanCS);

        $jumlahAntreanCS = (int) ($dataAntreanCS['total'] ?? 0);

        mysqli_free_result($queryAntreanCS);
    }
}

?>

<aside class="sidebar" id="adminSidebar">

    <!-- LOGO -->
    <div class="sidebar-logo-area">

        <div class="sidebar-logo">
            <img
                src="<?= $adminBase; ?>../pelanggan/assets/images/logo.png"
                alt="Logo Toko"
            >
        </div>

        <div class="sidebar-brand">
            <strong>Toko ATK &amp; Alat Jahit</strong>
            <span>Sistem ERP Toko</span>
        </div>

    </div>

    <!-- MENU -->
    <nav class="sidebar-menu">

        <!-- UTAMA -->
        <div class="menu-group">

            <div class="menu-label">UTAMA</div>

            <?php if (tampilMenu('dashboard')): ?>
                <a
                    href="<?= $adminBase; ?>index.php"
                    class="<?= menuActive('index.php'); ?>"
                    title="Dashboard"
                >
                    <span class="menu-icon">⌂</span>
                    <span class="menu-text">Dashboard</span>
                </a>
            <?php endif; ?>

        </div>

        <!-- PENJUALAN -->
        <?php if (
            tampilMenu('pesanan') ||
            tampilMenu('pelanggan') ||
            tampilMenu('cs') ||
            tampilMenu('voucher')
        ): ?>

            <div class="menu-group">

                <div class="menu-label">PENJUALAN</div>

                <!-- PESANAN -->
                <?php if (tampilMenu('pesanan')): ?>
                    <a
                        href="<?= $adminBase; ?>pesanan/index.php"
                        class="<?= menuActive([
                            'pesanan/index.php',
                            'pesanan/detail.php',
                            'pesanan/status.php'
                        ]); ?>"
                        title="Pesanan"
                    >
                        <span class="menu-icon">▱</span>
                        <span class="menu-text">Pesanan</span>
                    </a>
                <?php endif; ?>

                <!-- PELANGGAN -->
                <?php if (tampilMenu('pelanggan')): ?>
                    <a
                        href="<?= $adminBase; ?>pelanggan/index.php"
                        class="<?= menuActive([
                            'pelanggan/index.php',
                            'pelanggan/detail.php'
                        ]); ?>"
                        title="Pelanggan"
                    >
                        <span class="menu-icon">♙</span>
                        <span class="menu-text">Pelanggan</span>
                    </a>
                <?php endif; ?>

                <!-- CUSTOMER SERVICE -->
                <?php if (tampilMenu('cs')): ?>
                    <a
                        href="<?= $adminBase; ?>cs/index.php"
                        class="<?= menuActive([
                            'cs/index.php',
                            'cs/layani.php',
                            'cs/chat.php'
                        ]); ?>"
                        title="Customer Service"
                    >
                        <span class="menu-icon">💬</span>

                        <span class="menu-text">
                            Customer Service

                            <span
                                id="csQueueBadge"
                                aria-label="Jumlah antrean Customer Service"
                                style="
                                    display: <?= $jumlahAntreanCS > 0
                                        ? 'inline-flex'
                                        : 'none'; ?>;
                                    align-items: center;
                                    justify-content: center;
                                    min-width: 20px;
                                    height: 20px;
                                    padding: 0 6px;
                                    margin-left: 6px;
                                    border-radius: 10px;
                                    background: #d9534f;
                                    color: #ffffff;
                                    font-size: 10px;
                                    font-weight: 700;
                                    line-height: 1;
                                    vertical-align: middle;
                                "
                            ><?= $jumlahAntreanCS; ?></span>
                        </span>
                    </a>
                <?php endif; ?>

                <!-- VOUCHER -->
                <?php if (tampilMenu('voucher')): ?>
                    <a
                        href="<?= $adminBase; ?>voucher/index.php"
                        class="<?= menuActive('voucher/index.php'); ?>"
                        title="Voucher / Promo"
                    >
                        <span class="menu-icon">%</span>
                        <span class="menu-text">Voucher / Promo</span>
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- PRODUK -->
        <?php if (
            tampilMenu('produk') ||
            tampilMenu('kategori')
        ): ?>

            <div class="menu-group">

                <div class="menu-label">PRODUK</div>

                <?php if (tampilMenu('produk')): ?>
                    <a
                        href="<?= $adminBase; ?>produk/index.php"
                        class="<?= menuActive([
                            'produk/index.php',
                            'produk/tambah.php',
                            'produk/edit.php'
                        ]); ?>"
                        title="Produk"
                    >
                        <span class="menu-icon">□</span>
                        <span class="menu-text">Produk</span>
                    </a>
                <?php endif; ?>

                <?php if (tampilMenu('kategori')): ?>
                    <a
                        href="<?= $adminBase; ?>kategori/index.php"
                        class="<?= menuActive([
                            'kategori/index.php',
                            'kategori/tambah.php',
                            'kategori/edit.php'
                        ]); ?>"
                        title="Kategori"
                    >
                        <span class="menu-icon">▦</span>
                        <span class="menu-text">Kategori</span>
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- PERSEDIAAN -->
        <?php if (
            tampilMenu('stok') ||
            tampilMenu('lokasi')
        ): ?>

            <div class="menu-group">

                <div class="menu-label">PERSEDIAAN</div>

                <?php if (tampilMenu('stok')): ?>
                    <a
                        href="<?= $adminBase; ?>stok/index.php"
                        class="<?= menuActive([
                            'stok/index.php',
                            'stok/update.php'
                        ]); ?>"
                        title="Stok"
                    >
                        <span class="menu-icon">▤</span>
                        <span class="menu-text">Stok</span>
                    </a>
                <?php endif; ?>

                <?php if (tampilMenu('lokasi')): ?>
                    <a
                        href="<?= $adminBase; ?>lokasi/index.php"
                        class="<?= menuActive([
                            'lokasi/index.php',
                            'lokasi/tambah.php',
                            'lokasi/edit.php'
                        ]); ?>"
                        title="Lokasi / Rak"
                    >
                        <span class="menu-icon">⌖</span>
                        <span class="menu-text">Lokasi / Rak</span>
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- PENGADAAN -->
        <?php if (
            tampilMenu('supplier') ||
            tampilMenu('pembelian')
        ): ?>

            <div class="menu-group">

                <div class="menu-label">PENGADAAN</div>

                <?php if (tampilMenu('supplier')): ?>
                    <a
                        href="<?= $adminBase; ?>supplier/index.php"
                        class="<?= menuActive([
                            'supplier/index.php',
                            'supplier/tambah.php',
                            'supplier/edit.php'
                        ]); ?>"
                        title="Supplier"
                    >
                        <span class="menu-icon">♙</span>
                        <span class="menu-text">Supplier</span>
                    </a>
                <?php endif; ?>

                <?php if (tampilMenu('pembelian')): ?>
                    <a
                        href="<?= $adminBase; ?>pembelian/index.php"
                        class="<?= menuActive([
                            'pembelian/index.php',
                            'pembelian/tambah.php',
                            'pembelian/detail.php'
                        ]); ?>"
                        title="Pembelian"
                    >
                        <span class="menu-icon">▣</span>
                        <span class="menu-text">Pembelian</span>
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- LAPORAN -->
        <?php if (tampilMenu('laporan')): ?>

            <div class="menu-group">

                <div class="menu-label">LAPORAN</div>

                <a
                    href="<?= $adminBase; ?>laporan/penjualan.php"
                    class="<?= menuActive('laporan/penjualan.php'); ?>"
                    title="Laporan Penjualan"
                >
                    <span class="menu-icon">▥</span>
                    <span class="menu-text">Penjualan</span>
                </a>

                <a
                    href="<?= $adminBase; ?>laporan/pembelian.php"
                    class="<?= menuActive('laporan/pembelian.php'); ?>"
                    title="Laporan Pembelian"
                >
                    <span class="menu-icon">▤</span>
                    <span class="menu-text">Pembelian</span>
                </a>

                <a
                    href="<?= $adminBase; ?>laporan/stok.php"
                    class="<?= menuActive('laporan/stok.php'); ?>"
                    title="Laporan Stok"
                >
                    <span class="menu-icon">▦</span>
                    <span class="menu-text">Stok</span>
                </a>

                <a
                    href="<?= $adminBase; ?>laporan/produk.php"
                    class="<?= menuActive('laporan/produk.php'); ?>"
                    title="Produk Terlaris"
                >
                    <span class="menu-icon">★</span>
                    <span class="menu-text">Produk Terlaris</span>
                </a>

            </div>

        <?php endif; ?>

        <!-- SISTEM -->
        <?php if (
            tampilMenu('banner') ||
            $roleLogin === 'owner'
        ): ?>

            <div class="menu-group">

                <div class="menu-label">SISTEM</div>

                <?php if (tampilMenu('banner')): ?>
                    <a
                        href="<?= $adminBase; ?>banner/index.php"
                        class="<?= menuActive([
                            'banner/index.php',
                            'banner/tambah.php',
                            'banner/edit.php',
                            'banner/hapus.php'
                        ]); ?>"
                        title="Banner"
                    >
                        <span class="sidebar-icon">🖼️</span>
                        <span class="sidebar-text">Banner</span>
                    </a>
                <?php endif; ?>

                <?php if ($roleLogin === 'owner'): ?>
                    <a
                        href="<?= $adminBase; ?>admin/index.php"
                        class="<?= menuActive([
                            'admin/index.php',
                            'admin/tambah.php',
                            'admin/edit.php'
                        ]); ?>"
                        title="Admin & Hak Akses"
                    >
                        <span class="menu-icon">♙</span>
                        <span class="menu-text">Admin &amp; Hak Akses</span>
                    </a>

                    <a
                        href="<?= $adminBase; ?>log_aktivitas/index.php"
                        class="<?= menuActive('log_aktivitas/index.php'); ?>"
                        title="Log Aktivitas Admin"
                    >
                        <span class="menu-icon">◷</span>

                        <span class="menu-text">
                            Log Aktivitas Admin
                        </span>
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- LOGOUT -->
        <div class="menu-group menu-logout">

            <a
                href="<?= $adminBase; ?>logout.php"
                class="logout-link"
                title="Logout"
            >
                <span class="menu-icon">↪</span>
                <span class="menu-text">Logout</span>
            </a>

        </div>

    </nav>

</aside>

<!-- JAVASCRIPT CS MENGGUNAKAN FILE YANG SUDAH ADA -->
<?php if (tampilMenu('cs')): ?>
    <script
        defer
        src="<?= $adminBase; ?>assets/js/customer-service.js"
    ></script>
<?php endif; ?>