<?php
session_start();
$is_login = isset($_SESSION["id_pelanggan"]);
if ($is_login) require_once "../user/includes/navbar.php";
else require_once "../guest/includes/navbar.php";
?>
<main><section class="section"><span class="eyebrow">TENTANG KAMI</span><h1>Toko ATK & Alat Jahit</h1><p class="muted">Toko yang menyediakan kebutuhan ATK dan perlengkapan alat jahit.</p></section></main>
<?php if ($is_login) require_once "../user/includes/footer.php"; else require_once "../guest/includes/footer.php"; ?>
