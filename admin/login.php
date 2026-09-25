<?php

session_start();

require_once "../config/koneksi.php";

$error = "";

if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = "Email dan password wajib diisi.";

    } else {

        $email_safe = mysqli_real_escape_string($conn, $email);

        $query = mysqli_query(
            $conn,
            "SELECT * FROM admin WHERE email = '$email_safe' LIMIT 1"
        );

        if ($query && mysqli_num_rows($query) === 1) {

            $admin = mysqli_fetch_assoc($query);

            if (password_verify($password, $admin['password'])) {

                $_SESSION['id_admin'] = $admin['id_admin'];
                $_SESSION['nama_admin'] = $admin['nama'];
                $_SESSION['email_admin'] = $admin['email'];
                $_SESSION['role_admin'] = $admin['role'];

                header("Location: dashboard.php");
                exit;

            } else {

                $error = "Email atau password salah.";

            }

        } else {

            $error = "Email atau password salah.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login Admin - Toko ATK & Alat Jahit</title>

    <link rel="stylesheet"
          href="assets/css/admin.css">

    <style>

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fa;
        }

        .login-box {
            width: 420px;
            max-width: calc(100% - 40px);
            background: white;
            padding: 40px;
            border-radius: 15px;
            border: 1px solid #e1e7ec;
            box-shadow: 0 10px 30px rgba(16,47,74,0.08);
        }

        .login-box h1 {
            margin: 0;
            color: #102f4a;
            text-align: center;
            font-size: 27px;
        }

        .login-subtitle {
            text-align: center;
            color: #7890a4;
            margin: 8px 0 30px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #102f4a;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d8e1e8;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #1d4d70;
        }

        .login-button {
            width: 100%;
            border: none;
            background: #102f4a;
            color: white;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-button:hover {
            background: #1d4d70;
        }

        .error {
            background: #fff0f0;
            color: #c62828;
            border: 1px solid #ffd0d0;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

    </style>

</head>

<body>

<div class="login-box">

    <h1>Toko ATK & Alat Jahit</h1>

    <p class="login-subtitle">
        Login Administrator
    </p>


    <?php if ($error !== ''): ?>

        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="form-group">

            <label>Email</label>

            <input
                type="email"
                name="email"
                placeholder="Masukkan email admin"
                required
            >

        </div>


        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                placeholder="Masukkan password"
                required
            >

        </div>


        <button
            type="submit"
            class="login-button">

            Login Admin

        </button>

    </form>

</div>

</body>

</html>