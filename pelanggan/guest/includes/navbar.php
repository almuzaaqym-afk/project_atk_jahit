<?php
$base_url = "/project_atk_jahit";
?>
<link rel="stylesheet" href="<?= $base_url ?>/pelanggan/assets/css/pelanggan.css">

<nav class="navbar guest-navbar">
    <div class="nav-container guest-nav-container">

        <button type="button" class="guest-mobile-menu-button" id="guestMobileMenuButton" aria-label="Buka menu" aria-expanded="false">☰</button>

        <a href="<?= $base_url ?>/pelanggan/index.php" class="brand guest-brand">
            <img src="<?= $base_url ?>/pelanggan/assets/images/logo.png" alt="Logo">
            <span>
                <strong>Toko ATK & Alat Jahit</strong>
                <small>Perlengkapan ATK & Alat Jahit</small>
            </span>
        </a>

        <div class="nav-menu guest-nav-menu" id="guestNavMenu">
            <a href="<?= $base_url ?>/pelanggan/index.php">Beranda</a>
            <a href="<?= $base_url ?>/pelanggan/pages/katalog.php">Katalog</a>
            <a href="<?= $base_url ?>/pelanggan/pages/tentang.php">Tentang Kami</a>
            <a href="<?= $base_url ?>/pelanggan/pages/lokasi.php">Lokasi</a>
        </div>

        <div class="guest-auth-actions">
            <a class="nav-login" href="<?= $base_url ?>/pelanggan/login.php">Login</a>
            <a class="nav-register" href="<?= $base_url ?>/pelanggan/register.php">Daftar</a>
        </div>
    </div>
</nav>

<style>
.guest-nav-container{
    display:grid!important;
    grid-template-columns:1fr auto 1fr;
    grid-template-areas:"brand menu auth";
    align-items:center;
    gap:20px;
}
.guest-brand{grid-area:brand;justify-self:start}
.guest-nav-menu{grid-area:menu;justify-self:center;display:flex;align-items:center;justify-content:center;gap:5px}
.guest-auth-actions{grid-area:auth;justify-self:end;display:flex;align-items:center;gap:8px}
.guest-auth-actions .nav-login,.guest-auth-actions .nav-register{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:38px;
    padding:8px 13px;
    border-radius:7px;
    font-size:12px;
    text-decoration:none;
    white-space:nowrap;
}
.guest-auth-actions .nav-login{background:#102a43!important;color:#fff!important}
.guest-auth-actions .nav-register{background:#fff;color:#102a43!important;border:1px solid #102a43}
.guest-mobile-menu-button{display:none}
@media(max-width:900px){
    .guest-nav-container{grid-template-columns:1fr auto;grid-template-areas:"brand auth" "menu menu";gap:10px}
    .guest-nav-menu{display:flex;justify-self:center}
}
@media(max-width:768px){
    .guest-nav-container{
        grid-template-columns:40px 1fr auto;
        grid-template-areas:"burger brand auth" "menu menu menu";
        gap:8px;
        padding:10px 14px!important;
    }
    .guest-mobile-menu-button{
        grid-area:burger;
        display:flex;
        align-items:center;
        justify-content:center;
        width:40px;
        height:40px;
        border:1px solid #d9e2ec;
        border-radius:8px;
        background:#f4f7fa;
        color:#102a43;
        font-size:21px;
        cursor:pointer;
    }
    .guest-brand{min-width:0;margin:0}
    .guest-brand img{width:40px;height:40px}
    .guest-brand strong{font-size:13px}
    .guest-brand small{font-size:9px}
    .guest-auth-actions{gap:5px}
    .guest-auth-actions .nav-login,.guest-auth-actions .nav-register{min-height:36px;padding:7px 9px;font-size:10px}
    .guest-nav-menu{
        width:100%;
        display:none!important;
        flex-direction:column!important;
        align-items:stretch!important;
        justify-self:stretch;
        margin-top:2px;
        padding:8px;
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:10px;
        box-shadow:0 8px 20px rgba(16,42,67,.08);
    }
    .guest-nav-menu.mobile-open{display:flex!important}
    .guest-nav-menu a{width:100%;padding:11px 12px!important;border-radius:7px;font-size:13px!important;color:#334e68!important}
    .guest-nav-menu a:hover{background:#f0f4f8!important;color:#102a43!important}
}
@media(max-width:480px){
    .guest-nav-container{grid-template-columns:38px 1fr auto;padding:9px 11px!important}
    .guest-mobile-menu-button{width:38px;height:38px;font-size:20px}
    .guest-brand img{width:36px;height:36px}
    .guest-brand strong{font-size:12px}
    .guest-brand small{font-size:8px}
    .guest-auth-actions .nav-login,.guest-auth-actions .nav-register{min-height:35px;padding:7px 8px;font-size:10px}
}
</style>

<script>
const guestMobileMenuButton=document.getElementById("guestMobileMenuButton");
const guestNavMenu=document.getElementById("guestNavMenu");

if(guestMobileMenuButton&&guestNavMenu){
    guestMobileMenuButton.addEventListener("click",function(){
        const opened=guestNavMenu.classList.toggle("mobile-open");
        guestMobileMenuButton.setAttribute("aria-expanded",opened?"true":"false");
        guestMobileMenuButton.innerHTML=opened?"✕":"☰";
    });
}
</script>