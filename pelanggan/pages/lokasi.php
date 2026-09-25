<?php
session_start();
$is_login = isset($_SESSION["id_pelanggan"]);
if ($is_login) require_once "../user/includes/navbar.php";
else require_once "../guest/includes/navbar.php";
?>
<main><section class="section"><span class="eyebrow">LOKASI</span><h1>Lokasi Toko</h1><p class="muted">Lokasi toko dapat diarahkan ke Google Maps pada tahap integrasi lokasi.</p></section></main>
<?php if ($is_login) require_once "../user/includes/footer.php"; else require_once "../guest/includes/footer.php"; ?>
