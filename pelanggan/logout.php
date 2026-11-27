<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = '/project_atk_jahit';
$redirect_url = $base_url . '/pelanggan/index.php';
$logo_url = $base_url . '/pelanggan/assets/images/logo.png';

/* Hapus sesi pelanggan */
$_SESSION = [];

/* Hapus cookie sesi */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

/* Akhiri sesi login */
session_destroy();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b1c30">

    <title>Logout - Toko ATK &amp; Alat Jahit</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --logout-navy: #0b1c30;
            --logout-gold: #c7a56a;
            --logout-gold-light: #f2d79e;
            --logout-muted: #aab8c8;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            overflow: hidden;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #fff;
            background:
                radial-gradient(
                    ellipse at 50% 37%,
                    rgba(36, 73, 109, .52) 0%,
                    rgba(19, 49, 78, .22) 31%,
                    transparent 58%
                ),
                linear-gradient(
                    135deg,
                    #07172a 0%,
                    #0c2139 53%,
                    #08182b 100%
                );
        }

        /* LAYOUT UTAMA */
        .logout-screen {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 36px 20px;
            overflow: hidden;
            isolation: isolate;
        }

        /* AKSEN SUDUT */
        .corner-art {
            position: absolute;
            z-index: -1;
            width: 43vw;
            height: 30vw;
            max-width: 620px;
            max-height: 390px;
            min-width: 260px;
            min-height: 180px;
            pointer-events: none;
            opacity: .78;
        }

        .corner-art::before,
        .corner-art::after {
            content: "";
            position: absolute;
            width: 140%;
            height: 75%;
            border: 1px solid rgba(199, 165, 106, .68);
            border-color:
                rgba(199, 165, 106, .78)
                transparent
                transparent
                transparent;
            border-radius: 50%;
            transform: rotate(-28deg);
        }

        .corner-art::after {
            top: 19px;
            left: -11px;
            border-top-color: rgba(199, 165, 106, .25);
        }

        .corner-art-top {
            top: -145px;
            left: -180px;
            transform: rotate(-7deg);
        }

        .corner-art-bottom {
            right: -180px;
            bottom: -155px;
            transform: rotate(158deg);
        }

        /* KONTEN */
        .logout-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 570px;
            text-align: center;
            animation: contentEnter .7s cubic-bezier(.2, .7, .25, 1) both;
        }

        /* LOGO ASLI TOKO */
        .logo-wrap {
            position: relative;
            display: grid;
            width: 154px;
            height: 154px;
            place-items: center;
            margin-bottom: 25px;
            animation: logoEnter .8s cubic-bezier(.2, .7, .25, 1) both;
        }

        .logo-wrap::before {
            content: "";
            position: absolute;
            inset: 4px;
            border: 1px solid rgba(199, 165, 106, .8);
            border-radius: 50%;
            box-shadow: 0 0 28px rgba(199, 165, 106, .12);
        }

        .logo-wrap::after {
            content: "";
            position: absolute;
            inset: -6px;
            border: 1px solid rgba(199, 165, 106, .14);
            border-radius: 50%;
        }

        .logo-badge {
            position: relative;
            display: grid;
            width: 138px;
            height: 138px;
            place-items: center;
            overflow: hidden;
            padding: 12px;
            background: #fffdf8;
            border: 2px solid rgba(199, 165, 106, .88);
            border-radius: 50%;
            box-shadow:
                0 0 0 4px rgba(199, 165, 106, .08),
                0 10px 32px rgba(0, 0, 0, .18);
        }

        .logo-badge img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* JUDUL */
        .logout-title {
            margin: 0;
            color: #f8f7f3;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(34px, 5vw, 53px);
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: -.8px;
            animation: titleEnter .65s ease-out .12s both;
        }

        .logout-title span {
            color: var(--logout-gold-light);
        }

        /* PEMBATAS GOLD */
        .gold-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 17px;
            width: 250px;
            max-width: 70%;
            margin: 24px 0 30px;
            opacity: 0;
            animation: fadeIn .5s ease-out .3s forwards;
        }

        .gold-divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                var(--logout-gold)
            );
        }

        .gold-divider-line:last-child {
            background: linear-gradient(
                90deg,
                var(--logout-gold),
                transparent
            );
        }

        .gold-divider-diamond {
            width: 9px;
            height: 9px;
            flex: 0 0 9px;
            background: var(--logout-gold-light);
            transform: rotate(45deg);
            box-shadow: 0 0 10px rgba(199, 165, 106, .2);
        }

        /* PROGRESS BAR */
        .redirect-area {
            width: min(100%, 370px);
            animation: fadeIn .6s ease-out .38s both;
        }

        .progress-track {
            width: 100%;
            height: 9px;
            padding: 1px;
            overflow: hidden;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(187, 202, 220, .24);
            border-radius: 999px;
        }

        .progress-bar {
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                #bd9250 0%,
                #f2d79e 72%,
                #fff0c4 100%
            );
            border-radius: inherit;
            box-shadow: 0 0 12px rgba(242, 215, 158, .34);
            transform: scaleX(0);
            transform-origin: left center;
            animation: progressRun 2.8s cubic-bezier(.25, .1, .25, 1) forwards;
        }

        .redirect-text {
            margin-top: 16px;
            color: var(--logout-muted);
            font-size: 14px;
            font-weight: 400;
            letter-spacing: .1px;
        }

        /* ANIMASI */
        @keyframes contentEnter {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes logoEnter {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes titleEnter {
            from {
                opacity: 0;
                transform: translateY(7px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        @keyframes progressRun {
            to {
                transform: scaleX(1);
            }
        }

        /* RESPONSIVE TABLET */
        @media (max-width: 768px) {
            .logo-wrap {
                width: 136px;
                height: 136px;
                margin-bottom: 23px;
            }

            .logo-badge {
                width: 122px;
                height: 122px;
                padding: 10px;
            }

            .gold-divider {
                margin: 21px 0 26px;
            }

            .redirect-area {
                max-width: 340px;
            }
        }

        /* RESPONSIVE HP */
        @media (max-width: 480px) {
            .logout-screen {
                padding: 24px 20px;
            }

            .logo-wrap {
                width: 112px;
                height: 112px;
                margin-bottom: 23px;
            }

            .logo-badge {
                width: 100px;
                height: 100px;
                padding: 8px;
                border-width: 1px;
            }

            .logout-title {
                font-size: clamp(29px, 8vw, 37px);
                letter-spacing: -.5px;
            }

            .gold-divider {
                width: 205px;
                gap: 13px;
                margin: 20px 0 24px;
            }

            .redirect-area {
                max-width: 290px;
            }

            .progress-track {
                height: 8px;
            }

            .redirect-text {
                margin-top: 13px;
                font-size: 12px;
            }

            .corner-art {
                opacity: .48;
            }
        }

        /* REDUKSI GERAK */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>
    <main class="logout-screen">

        <div class="corner-art corner-art-top" aria-hidden="true"></div>
        <div class="corner-art corner-art-bottom" aria-hidden="true"></div>

        <section class="logout-content" aria-labelledby="logoutTitle">

            <div class="logo-wrap">
                <div class="logo-badge">
                    <img
                        src="<?= htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="Logo Toko ATK & Alat Jahit"
                    >
                </div>
            </div>

            <h1 class="logout-title" id="logoutTitle">
                Logout berhasil<span>!</span>
            </h1>

            <div class="gold-divider" aria-hidden="true">
                <span class="gold-divider-line"></span>
                <span class="gold-divider-diamond"></span>
                <span class="gold-divider-line"></span>
            </div>

            <div class="redirect-area">
                <div class="progress-track" aria-hidden="true">
                    <div class="progress-bar"></div>
                </div>

                <p class="redirect-text">Mengalihkan halaman...</p>
            </div>

        </section>
    </main>

    <script>
        (function () {
            const redirectUrl = <?= json_encode(
                $redirect_url,
                JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
            ); ?>;

            window.setTimeout(function () {
                window.location.replace(redirectUrl);
            }, 2800);
        })();
    </script>
</body>
</html>