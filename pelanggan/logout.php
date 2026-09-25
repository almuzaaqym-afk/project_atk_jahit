<?php
session_start();
session_unset();
session_destroy();

header("Location: /project_atk_jahit/pelanggan/index.php");
exit;
?>
