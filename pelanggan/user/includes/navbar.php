<?php
if(session_status()===PHP_SESSION_NONE){
    session_start();
}

$base_url="/project_atk_jahit";

if(!isset($_SESSION["id_pelanggan"])){
    header("Location: {$base_url}/pelanggan/login.php");
    exit;
}

$nama_pelanggan=$_SESSION["nama_pelanggan"]??"Pelanggan";
$nama_tampil=trim($nama_pelanggan);

if($nama_tampil===""){
    $nama_tampil="Pelanggan";
}

$inisial=strtoupper(substr($nama_tampil,0,1));
$global_search=trim($_GET['search']??'');

require_once __DIR__ . "/../../../config/koneksi.php";

$foto_pelanggan="";

$stmt_foto_nav=mysqli_prepare(
    $conn,
    "SELECT foto_profil FROM pelanggan WHERE id_pelanggan = ? LIMIT 1"
);

if($stmt_foto_nav){
    mysqli_stmt_bind_param($stmt_foto_nav,"i",$_SESSION["id_pelanggan"]);
    mysqli_stmt_execute($stmt_foto_nav);
    $result_foto_nav=mysqli_stmt_get_result($stmt_foto_nav);
    $data_foto_nav=mysqli_fetch_assoc($result_foto_nav);

    $foto_pelanggan=trim($data_foto_nav["foto_profil"]??"");

    mysqli_stmt_close($stmt_foto_nav);
}

$foto_pelanggan_url="";

if($foto_pelanggan!==""){
    $foto_pelanggan_url=$base_url.
        "/pelanggan/assets/images/profil/".
        rawurlencode($foto_pelanggan);
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">

<nav class="navbar user-navbar">
    <div class="nav-container customer-user-nav">

        <button type="button" class="mobile-menu-button" id="userMobileMenuButton" aria-label="Buka menu" aria-expanded="false">☰</button>

        <a href="<?= $base_url ?>/pelanggan/user/index.php" class="brand user-brand">
            <img src="<?= $base_url ?>/pelanggan/assets/images/logo.png" alt="Logo">
            <span>
                <strong>Toko ATK & Alat Jahit</strong>
                <small>Pelanggan</small>
            </span>
        </a>

        <form action="<?= $base_url ?>/pelanggan/pages/katalog.php" method="GET" class="global-search-form">
            <div class="global-search-box">
                <span class="global-search-icon">🔎</span>
                <input type="text" name="search" value="<?= htmlspecialchars($global_search); ?>" placeholder="Cari produk..." autocomplete="off">
                <?php if($global_search!==''): ?>
                    <a href="<?= $base_url ?>/pelanggan/pages/katalog.php" class="global-search-clear" title="Hapus pencarian">×</a>
                <?php endif; ?>
                <button type="submit" class="global-search-button">Cari</button>
            </div>
        </form>

        <div class="nav-menu user-nav-menu" id="userNavMenu">
            <a href="<?= $base_url ?>/pelanggan/user/index.php">Beranda</a>
            <a href="<?= $base_url ?>/pelanggan/pages/katalog.php">Katalog</a>
            <a href="<?= $base_url ?>/pelanggan/user/keranjang.php">Keranjang</a>
            <a href="<?= $base_url ?>/pelanggan/user/pesanan.php">Pesanan</a>
            <a href="<?= $base_url ?>/pelanggan/pages/lokasi.php">Lokasi</a>
        </div>

        <div class="profile-dropdown">
            <button type="button" class="profile-button" onclick="toggleProfileMenu(event)" aria-label="Menu profil">
                <?php if($foto_pelanggan_url!==""): ?>
                    <img src="<?= htmlspecialchars($foto_pelanggan_url); ?>" alt="Foto Profil" class="profile-avatar profile-avatar-image">
                <?php else: ?>
                    <span class="profile-avatar">
                        <?= htmlspecialchars($inisial); ?>
                    </span>
                <?php endif; ?>

                <span class="profile-name"><?= htmlspecialchars($nama_tampil) ?></span>
                <span class="profile-arrow">▼</span>
            </button>

            <div class="profile-menu" id="profileMenu">

                <div class="profile-menu-header">
                    <?php if($foto_pelanggan_url!==""): ?>
                        <img src="<?= htmlspecialchars($foto_pelanggan_url); ?>" alt="Foto Profil" class="profile-avatar profile-avatar-large profile-avatar-image">
                    <?php else: ?>
                        <span class="profile-avatar profile-avatar-large">
                            <?= htmlspecialchars($inisial); ?>
                        </span>
                    <?php endif; ?>

                    <div>
                        <strong><?= htmlspecialchars($nama_tampil) ?></strong>
                        <small>Pelanggan</small>
                    </div>
                </div>

                <div class="profile-menu-divider"></div>

                <a href="<?= $base_url ?>/pelanggan/user/profil.php" class="profile-menu-link">
                    <span class="profile-menu-icon">👤</span>
                    <span>Profil Saya</span>
                </a>

                <a href="<?= $base_url ?>/pelanggan/user/favorit.php" class="profile-menu-link">
                    <span class="profile-menu-icon">❤️</span>
                    <span>Favorit</span>
                </a>

                <a href="<?= $base_url ?>/pelanggan/user/pusat_bantuan.php" class="profile-menu-link">
                    <span class="profile-menu-icon">💬</span>
                    <span>Pusat Bantuan</span>
                </a>

                <div class="profile-menu-divider"></div>

                <a href="<?= $base_url ?>/pelanggan/logout.php" class="profile-menu-link profile-logout">
                    <span class="profile-menu-icon">🚪</span>
                    <span>Logout</span>
                </a>

            </div>
        </div>

    </div>
</nav>

<style>
.customer-user-nav{
    display:grid!important;
    grid-template-columns:auto minmax(230px,1fr) auto auto;
    grid-template-areas:"brand search menu profile";
    align-items:center;
    gap:18px;
}
.user-brand{grid-area:brand;min-width:0}
.global-search-form{grid-area:search;width:100%;max-width:none;margin:0}
.user-nav-menu{grid-area:menu;display:flex;align-items:center;justify-content:center;gap:4px;min-width:0}
.profile-dropdown{grid-area:profile;position:relative;flex-shrink:0}
.global-search-box{width:100%;height:42px;display:flex;align-items:center;background:#fff;border:1px solid #d9e2ec;border-radius:9px;overflow:hidden}
.global-search-icon{padding-left:12px;color:#627d98;font-size:14px}
.global-search-box input{flex:1;min-width:0;height:100%;border:none;outline:none;padding:0 9px;font-size:12px;color:#102a43;background:#fff}
.global-search-box input::placeholder{color:#9fb3c8}
.global-search-clear{display:flex;align-items:center;justify-content:center;width:24px;height:24px;text-decoration:none;color:#829ab1;font-size:20px}
.global-search-clear:hover{color:#c53030}
.global-search-button{height:100%;padding:0 14px;border:none;background:#c7a56a;color:#102a43;font-size:11px;font-weight:bold;cursor:pointer}
.global-search-button:hover{background:#d6b97e}
.mobile-menu-button{display:none}
.profile-button{display:flex;align-items:center;gap:8px;border:none;background:transparent;color:#fff;cursor:pointer;padding:6px 8px;border-radius:8px;font-family:inherit}
.profile-button:hover{background:#183b56}
.profile-avatar{width:34px;height:34px;min-width:34px;border-radius:50%;background:#c7a56a;color:#102a43;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:bold;overflow:hidden}
.profile-avatar-large{width:42px;height:42px;min-width:42px}
.profile-name{max-width:110px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;font-size:13px;font-weight:bold}
.profile-arrow{font-size:9px;color:#d9e2ec}
.profile-menu{position:absolute;top:calc(100% + 10px);right:0;width:235px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.15);padding:8px;display:none;z-index:3000}
.profile-menu.show{display:block}
.profile-menu-header{display:flex;align-items:center;gap:11px;padding:10px}
.profile-menu-header strong{display:block;color:#102a43!important;font-size:13px;max-width:150px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.profile-menu-header small{display:block;color:#627d98!important;font-size:11px;margin-top:2px}
.profile-menu-link{display:flex!important;align-items:center;gap:10px;width:100%;padding:11px 10px;border-radius:7px;text-decoration:none;background:transparent;color:#334e68!important;font-size:13px;font-weight:500}
.profile-menu-link:hover{background:#f0f4f8;color:#102a43!important}
.profile-menu-icon{width:22px;text-align:center;font-size:16px}
.profile-logout{color:#c53030!important}
.profile-logout:hover{background:#fff0f0;color:#c53030!important}
.profile-menu-divider{height:1px;background:#e5e7eb;margin:5px 0}
.profile-avatar-image{object-fit:cover;border:0}

@media(max-width:1150px){
    .customer-user-nav{grid-template-columns:auto minmax(190px,1fr) auto auto;gap:10px}
    .user-nav-menu{gap:2px}
    .user-nav-menu a{padding:8px 8px;font-size:12px}
    .profile-name{max-width:80px}
}

@media(max-width:900px){
    .customer-user-nav{
        grid-template-columns:auto minmax(180px,1fr) auto;
        grid-template-areas:"brand search profile";
    }
    .user-nav-menu{display:none!important}
    .global-search-form{margin:0}
}

@media(max-width:768px){
    .customer-user-nav{
        grid-template-columns:40px 1fr 40px;
        grid-template-areas:"burger brand profile" "search search search" "menu menu menu";
        gap:8px;
        padding:10px 14px!important;
    }
    .mobile-menu-button{
        grid-area:burger;
        display:flex;
        align-items:center;
        justify-content:center;
        width:40px;
        height:40px;
        border:1px solid rgba(255,255,255,.18);
        border-radius:8px;
        background:#183b56;
        color:#fff;
        font-size:21px;
        cursor:pointer;
    }
    .user-brand{margin:0;min-width:0}
    .user-brand img{width:38px;height:38px}
    .user-brand strong{font-size:12px}
    .user-brand small{font-size:8px}
    .profile-dropdown{margin:0;justify-self:end}
    .profile-name,.profile-arrow{display:none}
    .profile-button{padding:1px}
    .profile-avatar{width:37px;height:37px;min-width:37px}
    .global-search-form{width:100%;margin:0}
    .global-search-box{height:42px}
    .user-nav-menu{
        width:100%;
        display:none!important;
        flex-direction:column!important;
        align-items:stretch!important;
        gap:3px!important;
        margin:0;
        padding:8px;
        background:#183b56;
        border:1px solid rgba(255,255,255,.12);
        border-radius:10px;
    }
    .user-nav-menu.mobile-open{display:flex!important}
    .user-nav-menu a{width:100%;padding:11px 12px!important;border-radius:7px;font-size:13px!important;color:#fff!important}
    .user-nav-menu a:hover{background:#234d6d!important;color:#fff!important}
    .profile-menu{top:calc(100% + 8px);right:0;width:225px}
}

@media(max-width:480px){
    .customer-user-nav{grid-template-columns:38px 1fr 38px;padding:9px 11px!important}
    .mobile-menu-button{width:38px;height:38px;font-size:20px}
    .user-brand img{width:35px;height:35px}
    .user-brand strong{font-size:11px}
    .user-brand small{font-size:8px}
    .profile-avatar{width:35px;height:35px;min-width:35px}
    .global-search-button{padding:0 11px}
    .global-search-icon{padding-left:10px}
    .global-search-box input{font-size:11px}
}
</style>

<script>
function toggleProfileMenu(event){
    event.stopPropagation();
    const menu=document.getElementById("profileMenu");
    if(menu) menu.classList.toggle("show");
}

document.addEventListener("click",function(event){
    const dropdown=document.querySelector(".profile-dropdown");
    const menu=document.getElementById("profileMenu");

    if(dropdown&&menu&&!dropdown.contains(event.target)){
        menu.classList.remove("show");
    }
});

const userMobileMenuButton=document.getElementById("userMobileMenuButton");
const userNavMenu=document.getElementById("userNavMenu");

if(userMobileMenuButton&&userNavMenu){
    userMobileMenuButton.addEventListener("click",function(){
        const opened=userNavMenu.classList.toggle("mobile-open");
        userMobileMenuButton.setAttribute("aria-expanded",opened?"true":"false");
        userMobileMenuButton.innerHTML=opened?"✕":"☰";
    });
}
</script>