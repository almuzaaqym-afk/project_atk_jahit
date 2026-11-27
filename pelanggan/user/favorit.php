<?php
session_start();

if(!isset($_SESSION["id_pelanggan"])){
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan=(int)$_SESSION["id_pelanggan"];
$produk_favorit=[];

$stmt=mysqli_prepare(
    $conn,
    "SELECT
        produk.id_produk,
        produk.nama_produk,
        produk.harga,
        produk.stok,
        produk.gambar,
        kategori.nama_kategori
     FROM favorit
     INNER JOIN produk
        ON favorit.id_produk=produk.id_produk
     LEFT JOIN kategori
        ON produk.id_kategori=kategori.id_kategori
     WHERE favorit.id_pelanggan=?
     ORDER BY favorit.id_favorit DESC"
);

if($stmt){
    mysqli_stmt_bind_param($stmt,"i",$id_pelanggan);
    mysqli_stmt_execute($stmt);
    $result=mysqli_stmt_get_result($stmt);

    while($row=mysqli_fetch_assoc($result)){
        $produk_favorit[]=$row;
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Favorit - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css">
</head>
<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<main class="favorit-page">
    <section class="favorit-container">

        <div class="favorit-heading">
            <span class="eyebrow">FAVORIT</span>
            <h1>Produk Favorit</h1>
            <p class="muted">Produk yang kamu simpan sebagai favorit.</p>

            <?php if(count($produk_favorit)>0): ?>
                <span class="favorit-count">
                    <?= count($produk_favorit) ?> produk tersimpan
                </span>
            <?php endif; ?>
        </div>

        <?php if(count($produk_favorit)>0): ?>

            <div class="favorit-grid">
                <?php foreach($produk_favorit as $item): ?>
                    <article class="favorit-card">

                        <a
                            href="../pages/detail_produk.php?id=<?= (int)$item["id_produk"] ?>"
                            class="favorit-image"
                            aria-label="Lihat <?= htmlspecialchars($item["nama_produk"]) ?>"
                        >
                            <?php if(!empty($item["gambar"])): ?>
                                <img
                                    src="../../admin/assets/images/products/<?= rawurlencode($item["gambar"]) ?>"
                                    alt="<?= htmlspecialchars($item["nama_produk"]) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <span class="favorit-no-image">
                                    Tidak ada gambar
                                </span>
                            <?php endif; ?>
                        </a>

                        <div class="favorit-info">
                            <span class="favorit-category">
                                <?= htmlspecialchars($item["nama_kategori"]??"-") ?>
                            </span>

                            <h2 class="favorit-name">
                                <?= htmlspecialchars($item["nama_produk"]) ?>
                            </h2>

                            <p class="favorit-price">
                                Rp <?= number_format((float)$item["harga"],0,",",".") ?>
                            </p>

                            <p class="favorit-stock <?= (int)$item["stok"]>0?"tersedia":"habis" ?>">
                                <?= (int)$item["stok"]>0
                                    ?"Stok: ".(int)$item["stok"]
                                    :"Stok habis" ?>
                            </p>

                            <div class="favorit-actions">
                                <a
                                    href="../pages/detail_produk.php?id=<?= (int)$item["id_produk"] ?>"
                                    class="favorit-btn favorit-btn-detail"
                                >
                                    Lihat Detail
                                </a>

                                <a
                                    href="../actions/tambah_keranjang.php?id=<?= (int)$item["id_produk"] ?>"
                                    class="favorit-btn favorit-btn-cart"
                                >
                                    Tambah Keranjang
                                </a>

                                <a
                                    href="../actions/hapus_favorit.php?id=<?= (int)$item["id_produk"] ?>"
                                    class="favorit-btn favorit-btn-remove"
                                    onclick="return confirm('Hapus produk ini dari favorit?')"
                                >
                                    Hapus Favorit
                                </a>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="favorit-empty">
                <div class="favorit-empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"></path>
                    </svg>
                </div>

                <h2>Belum Ada Favorit</h2>

                <p>
                    Kamu belum menyimpan produk ke favorit.
                    Cari produk yang kamu suka di katalog.
                </p>

                <a href="../pages/katalog.php" class="favorit-btn favorit-btn-cart">
                    Lihat Katalog
                </a>
            </div>

        <?php endif; ?>

    </section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<style>
.favorit-page{
    width:100%;
    box-sizing:border-box;
    padding:36px 24px 55px;
    background:#f5f7fa;
    min-height:60vh;
}
.favorit-container{
    width:100%;
    max-width:1400px;
    margin:0 auto;
}
.favorit-heading{
    margin-bottom:28px;
}
.favorit-heading h1{
    margin:8px 0;
    color:#102a43;
    font-size:30px;
    line-height:1.3;
}
.favorit-heading .muted{
    margin:0;
    line-height:1.6;
    font-size:14px;
}
.favorit-count{
    display:inline-block;
    margin-top:14px;
    padding:6px 11px;
    border-radius:20px;
    background:#e8eef5;
    color:#334e68;
    font-size:12px;
    font-weight:600;
}
.favorit-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:20px;
    align-items:stretch;
}
.favorit-card{
    min-width:0;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    border:1px solid #e2e8f0;
    border-radius:12px;
    background:#fff;
    transition:box-shadow .2s ease,transform .2s ease;
}
.favorit-card:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 22px rgba(16,42,67,.09);
}
.favorit-image{
    display:flex;
    align-items:center;
    justify-content:center;
    width:100%;
    aspect-ratio:4/3;
    overflow:hidden;
    background:#f0f3f7;
    text-decoration:none;
}
.favorit-image img{
    width:100%;
    height:100%;
    display:block;
    object-fit:contain;
    transition:transform .25s ease;
}
.favorit-card:hover .favorit-image img{
    transform:scale(1.04);
}
.favorit-no-image{
    color:#829ab1;
    font-size:12px;
    text-align:center;
    padding:12px;
}
.favorit-info{
    flex:1;
    display:flex;
    flex-direction:column;
    min-width:0;
    padding:17px;
}
.favorit-category{
    color:#627d98;
    font-size:11px;
    margin-bottom:7px;
}
.favorit-name{
    min-height:42px;
    margin:0 0 10px;
    color:#102a43;
    font-size:16px;
    line-height:1.4;
    overflow-wrap:anywhere;
}
.favorit-price{
    margin:0 0 7px;
    color:#102a43;
    font-size:17px;
    font-weight:700;
}
.favorit-stock{
    margin:0 0 16px;
    color:#627d98;
    font-size:12px;
}
.favorit-stock.tersedia{
    color:#28734b;
}
.favorit-stock.habis{
    color:#c53030;
}
.favorit-actions{
    display:flex;
    flex-direction:column;
    gap:8px;
    margin-top:auto;
}
.favorit-btn{
    box-sizing:border-box;
    width:100%;
    min-height:39px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:10px 12px;
    border:1px solid transparent;
    border-radius:7px;
    text-align:center;
    text-decoration:none;
    font-family:inherit;
    font-size:12px;
    font-weight:600;
    line-height:1.4;
    overflow-wrap:anywhere;
    transition:background .2s ease,color .2s ease;
}
.favorit-btn-detail{
    border-color:#cbd5e1;
    color:#102a43;
    background:#fff;
}
.favorit-btn-detail:hover{
    background:#f0f4f8;
}
.favorit-btn-cart{
    background:#102a43;
    color:#fff;
}
.favorit-btn-cart:hover{
    background:#183b56;
}
.favorit-btn-remove{
    background:#fff;
    border-color:#f1caca;
    color:#b42318;
}
.favorit-btn-remove:hover{
    background:#fff1f0;
}
.favorit-empty{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    min-height:300px;
    padding:35px 22px;
    border:1px solid #e2e8f0;
    border-radius:14px;
    background:#fff;
    text-align:center;
}
.favorit-empty-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:58px;
    height:58px;
    margin-bottom:16px;
    border-radius:50%;
    background:#f5ead5;
    color:#102a43;
}
.favorit-empty-icon svg{
    width:26px;
    height:26px;
    fill:none;
    stroke:currentColor;
    stroke-width:1.7;
    stroke-linecap:round;
    stroke-linejoin:round;
}
.favorit-empty h2{
    margin:0 0 9px;
    color:#102a43;
    font-size:21px;
}
.favorit-empty p{
    max-width:400px;
    margin:0 0 20px;
    color:#627d98;
    font-size:13px;
    line-height:1.7;
}
.favorit-empty .favorit-btn{
    width:auto;
    min-width:170px;
}
@media(max-width:1150px){
    .favorit-grid{
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:17px;
    }
}
@media(max-width:900px){
    .favorit-page{
        padding:30px 18px 45px;
    }
    .favorit-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:15px;
    }
    .favorit-heading h1{
        font-size:27px;
    }
}
@media(max-width:480px){
    .favorit-page{
        padding:25px 13px 35px;
    }
    .favorit-heading{
        margin-bottom:21px;
    }
    .favorit-heading h1{
        font-size:24px;
    }
    .favorit-heading .muted{
        font-size:13px;
    }
    .favorit-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:11px;
    }
    .favorit-info{
        padding:11px;
    }
    .favorit-name{
        min-height:38px;
        font-size:13px;
        margin-bottom:8px;
    }
    .favorit-price{
        font-size:14px;
    }
    .favorit-stock{
        font-size:11px;
        margin-bottom:12px;
    }
    .favorit-btn{
        min-height:36px;
        padding:8px 6px;
        font-size:10px;
    }
    .favorit-actions{
        gap:6px;
    }
}
@media(max-width:350px){
    .favorit-grid{
        grid-template-columns:1fr;
    }
    .favorit-image{
        aspect-ratio:5/3;
    }
    .favorit-info{
        padding:15px;
    }
    .favorit-name{
        font-size:15px;
    }
    .favorit-price{
        font-size:16px;
    }
    .favorit-btn{
        font-size:12px;
    }
}
</style>
 
</body>
</html>