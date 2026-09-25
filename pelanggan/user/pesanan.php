<?php
session_start();
if (!isset($_SESSION["id_pelanggan"])) { header("Location: ../login.php"); exit; }
require_once "includes/navbar.php";
?>
<main><section class="section"><h1>Pesanan</h1><p class="muted">Fitur pesanan disiapkan untuk tahap berikutnya.</p></section></main>
<?php require_once "includes/footer.php"; ?>
