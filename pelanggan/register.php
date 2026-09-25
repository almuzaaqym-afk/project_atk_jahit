<?php
session_start();
require_once "../config/koneksi.php";

if (isset($_SESSION["id_pelanggan"])) {
    header("Location: user/index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nama === "" || $email === "" || $password === "") {
        $error = "Semua data wajib diisi.";
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $nama_safe = mysqli_real_escape_string($conn, $nama);

        $cek = mysqli_query($conn, "SELECT id_pelanggan FROM pelanggan WHERE email='$email_safe' LIMIT 1");

        if (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $hash_safe = mysqli_real_escape_string($conn, $hash);

            mysqli_query(
                $conn,
                "INSERT INTO pelanggan (nama,email,password) VALUES ('$nama_safe','$email_safe','$hash_safe')"
            );

            $success = "Pendaftaran berhasil. Silakan login.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar - Toko ATK & Alat Jahit</title>
<link rel="stylesheet" href="assets/css/pelanggan.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <a class="auth-logo" href="index.php"><img src="assets/images/logo.png" alt="Logo"></a>
    <h1>Daftar Pelanggan</h1>
    <p class="muted">Buat akun pelanggan baru.</p>

    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST">
        <label>Nama</label>
        <input type="text" name="nama" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="btn-primary full" type="submit">Daftar</button>
    </form>

    <p class="auth-bottom">Sudah punya akun? <a href="login.php">Login</a></p>
</div>
</body>
</html>
