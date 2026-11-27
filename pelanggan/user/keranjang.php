<?php
session_start();

if(!isset($_SESSION["id_pelanggan"])){
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$keranjang=$_SESSION["keranjang"]??[];
$keranjang_varian=$_SESSION["keranjang_varian"]??[];
$items=[];
$total=0;
$jumlah_produk=0;

function prosesItemKeranjang($conn,&$items,&$total,&$jumlah_produk,$id_produk,$jumlah,$id_varian=0){
    $id_produk=(int)$id_produk;
    $jumlah=(int)$jumlah;
    $id_varian=(int)$id_varian;

    if($id_produk<=0)return;
    if($jumlah<1)$jumlah=1;

    $stmt=mysqli_prepare($conn,"SELECT p.id_produk,p.nama_produk,p.harga,p.stok,p.gambar,k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori=k.id_kategori WHERE p.id_produk=? LIMIT 1");
    if(!$stmt)return;

    mysqli_stmt_bind_param($stmt,"i",$id_produk);
    mysqli_stmt_execute($stmt);
    $result=mysqli_stmt_get_result($stmt);
    $item=mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if(!$item){
        if($id_varian>0){
            unset($_SESSION["keranjang_varian"][$id_varian]);
        }else{
            unset($_SESSION["keranjang"][$id_produk]);
        }
        return;
    }

    if($id_varian>0){
        $stmt=mysqli_prepare($conn,"SELECT v.id_varian,v.id_produk,v.nama_varian,v.harga,v.stok,(SELECT g.nama_gambar FROM produk_varian_gambar g WHERE g.id_varian=v.id_varian ORDER BY g.urutan ASC,g.id_gambar_varian ASC LIMIT 1) AS gambar_varian FROM produk_varian v WHERE v.id_varian=? AND v.id_produk=? AND v.status='Aktif' LIMIT 1");
        if(!$stmt)return;

        mysqli_stmt_bind_param($stmt,"ii",$id_varian,$id_produk);
        mysqli_stmt_execute($stmt);
        $result=mysqli_stmt_get_result($stmt);
        $varian=mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if(!$varian){
            unset($_SESSION["keranjang_varian"][$id_varian]);
            return;
        }

        $stok=(int)$varian["stok"];

        if($stok<=0){
            unset($_SESSION["keranjang_varian"][$id_varian]);
            return;
        }

        if($jumlah>$stok){
            $jumlah=$stok;
            $_SESSION["keranjang_varian"][$id_varian]=$jumlah;
        }

        $harga=(float)$varian["harga"];
        $subtotal=$harga*$jumlah;
        $total+=$subtotal;
        $jumlah_produk+=$jumlah;

        $items[]=[
            "id_item"=>"v_".$id_varian,
            "id_produk"=>$id_produk,
            "id_varian"=>$id_varian,
            "nama_produk"=>$item["nama_produk"],
            "nama_varian"=>$varian["nama_varian"],
            "nama_kategori"=>$item["nama_kategori"],
            "harga"=>$harga,
            "stok"=>$stok,
            "gambar"=>!empty($varian["gambar_varian"])?$varian["gambar_varian"]:$item["gambar"],
            "jumlah"=>$jumlah,
            "subtotal"=>$subtotal
        ];
        return;
    }

    $stok=(int)$item["stok"];

    if($stok<=0){
        unset($_SESSION["keranjang"][$id_produk]);
        return;
    }

    if($jumlah>$stok){
        $jumlah=$stok;
        $_SESSION["keranjang"][$id_produk]=$jumlah;
    }

    $harga=(float)$item["harga"];
    $subtotal=$harga*$jumlah;
    $total+=$subtotal;
    $jumlah_produk+=$jumlah;

    $items[]=[
        "id_item"=>"p_".$id_produk,
        "id_produk"=>$id_produk,
        "id_varian"=>0,
        "nama_produk"=>$item["nama_produk"],
        "nama_varian"=>"",
        "nama_kategori"=>$item["nama_kategori"],
        "harga"=>$harga,
        "stok"=>$stok,
        "gambar"=>$item["gambar"],
        "jumlah"=>$jumlah,
        "subtotal"=>$subtotal
    ];
}

foreach($keranjang as $id_produk=>$jumlah){
    prosesItemKeranjang($conn,$items,$total,$jumlah_produk,$id_produk,$jumlah,0);
}

foreach($keranjang_varian as $id_varian=>$jumlah){
    $id_varian=(int)$id_varian;
    if($id_varian<=0)continue;

    $stmt=mysqli_prepare($conn,"SELECT id_produk FROM produk_varian WHERE id_varian=? LIMIT 1");
    if(!$stmt)continue;

    mysqli_stmt_bind_param($stmt,"i",$id_varian);
    mysqli_stmt_execute($stmt);
    $result=mysqli_stmt_get_result($stmt);
    $data=mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if(!$data){
        unset($_SESSION["keranjang_varian"][$id_varian]);
        continue;
    }

    prosesItemKeranjang($conn,$items,$total,$jumlah_produk,(int)$data["id_produk"],$jumlah,$id_varian);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Keranjang Belanja - Toko ATK & Alat Jahit</title>
    <link rel="stylesheet" href="/project_atk_jahit/pelanggan/assets/css/pelanggan.css">

    <style>
    *,*::before,*::after{box-sizing:border-box}
    .cart-page{width:100%;max-width:1240px;margin:0 auto;padding:30px 22px 45px}
    .cart-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px}
    .cart-header h1{margin:7px 0;color:#102a43;font-size:30px;line-height:1.3}
    .cart-header .muted{margin:0;font-size:13px;line-height:1.6}
    .cart-count{flex-shrink:0;padding:8px 13px;background:#eef4f8;color:#486581;border-radius:20px;font-size:12px}
    .cart-alert{padding:12px 15px;border-radius:8px;margin-bottom:18px;font-size:13px;line-height:1.6}
    .cart-success{background:#eefbf3;color:#26734d}
    .cart-danger{background:#fff0f0;color:#c53030}
    .cart-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:22px;align-items:start}
    .cart-list{display:flex;flex-direction:column;gap:14px;min-width:0}
    .cart-item{display:flex;align-items:center;gap:16px;min-width:0;padding:16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px}
    .cart-image{width:105px;height:105px;flex:0 0 105px;overflow:hidden;border-radius:9px;background:#f1f4f7;display:flex;align-items:center;justify-content:center}
    .cart-image img{width:100%;height:100%;object-fit:contain;display:block}
    .no-image{padding:8px;color:#829ab1;font-size:11px;text-align:center}
    .cart-info{flex:1;min-width:0}
    .cart-category{color:#829ab1;font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px}
    .cart-name{color:#102a43;font-size:16px;line-height:1.45;overflow-wrap:anywhere;margin:5px 0 7px}
    .cart-variant{display:inline-flex;max-width:100%;padding:5px 9px;margin:2px 0 8px;border-radius:6px;background:#eef4f8;color:#486581;font-size:11px;font-weight:600;overflow-wrap:anywhere}
    .cart-price{color:#627d98;font-size:12px;line-height:1.6}
    .cart-price strong{color:#102a43}
    .cart-item-bottom{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:13px}
    .cart-quantity{display:inline-flex;align-items:center;flex-shrink:0;overflow:hidden;border:1px solid #d9e2ec;border-radius:7px}
    .cart-quantity button{width:32px;height:34px;border:0;background:#f5f7fa;color:#102a43;font-size:18px;font-weight:bold;cursor:pointer}
    .cart-quantity button:hover{background:#e5ebf0}
    .cart-quantity input{width:42px;height:34px;padding:0 2px;border:0;border-left:1px solid #d9e2ec;border-right:1px solid #d9e2ec;text-align:center;color:#102a43;font-weight:bold;font-size:12px;outline:none;appearance:textfield}
    .cart-quantity input::-webkit-inner-spin-button,.cart-quantity input::-webkit-outer-spin-button{appearance:none;margin:0}
    .cart-subtotal{color:#102a43;font-size:13px;font-weight:bold;overflow-wrap:anywhere}
    .cart-delete{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;margin-left:auto;flex-shrink:0;border:1px solid #f3c5c5;border-radius:7px;background:#fff;color:#c53030;text-decoration:none;font-size:21px;line-height:1}
    .cart-delete:hover{background:#fff0f0}
    .cart-summary{position:sticky;top:90px;min-width:0;padding:21px;background:#fff;border:1px solid #e5e7eb;border-radius:12px}
    .cart-summary h2{color:#102a43;font-size:19px;margin:0 0 17px}
    .summary-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;color:#627d98;font-size:12px}
    .summary-row strong{color:#102a43;text-align:right}
    .summary-total{display:flex;justify-content:space-between;gap:12px;border-top:1px solid #e5e7eb;margin-top:8px;padding-top:15px;color:#102a43;font-size:16px;font-weight:bold}
    #summaryTotal{max-width:60%;text-align:right;overflow-wrap:anywhere}
    .cart-summary .btn-primary{display:flex;align-items:center;justify-content:center;width:100%;min-height:44px;margin-top:20px;text-align:center;text-decoration:none}
    .continue-shopping{display:block;text-align:center;margin-top:13px;color:#486581;text-decoration:none;font-size:12px}
    .continue-shopping:hover{color:#102a43}
    .cart-empty{padding:55px 22px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;text-align:center}
    .cart-empty-icon{margin-bottom:15px;color:#c7a56a;font-size:42px}
    .cart-empty h2{color:#102a43;font-size:21px;margin:0 0 9px}
    .cart-empty p{max-width:400px;margin:0 auto 21px;color:#627d98;font-size:13px;line-height:1.7}
    .cart-empty .btn-primary{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 20px;text-decoration:none}
    .cart-loading{opacity:.6;pointer-events:none}

    @media(max-width:1050px){
        .cart-layout{grid-template-columns:minmax(0,1fr) 285px;gap:16px}
        .cart-item{gap:12px;padding:13px}
        .cart-image{width:88px;height:88px;flex-basis:88px}
        .cart-summary{padding:17px}
    }
    @media(max-width:850px){
        .cart-page{padding:25px 18px 40px}
        .cart-layout{grid-template-columns:minmax(0,1fr)}
        .cart-summary{position:static;width:100%}
        .cart-header h1{font-size:27px}
    }
    @media(max-width:600px){
        .cart-page{padding:20px 12px 32px}
        .cart-header{align-items:flex-start;flex-direction:column;gap:10px;margin-bottom:19px}
        .cart-header h1{font-size:24px}
        .cart-header .muted{font-size:12px}
        .cart-count{padding:6px 10px}
        .cart-layout{gap:15px}
        .cart-list{gap:10px}
        .cart-item{align-items:flex-start;gap:10px;padding:11px;border-radius:10px}
        .cart-image{width:76px;height:76px;flex-basis:76px;border-radius:7px}
        .cart-name{font-size:13px;line-height:1.4;margin:4px 0 6px}
        .cart-category{font-size:9px}
        .cart-variant{font-size:10px;padding:4px 7px}
        .cart-price{font-size:11px}
        .cart-item-bottom{gap:7px;margin-top:10px}
        .cart-quantity button{width:27px;height:31px;font-size:16px}
        .cart-quantity input{width:34px;height:31px;font-size:12px}
        .cart-subtotal{font-size:11px;min-height:31px}
        .cart-delete{width:30px;height:30px;font-size:19px}
        .cart-summary{padding:16px;border-radius:10px}
        .cart-summary h2{font-size:18px}
        .summary-row{font-size:12px}
        .summary-total{font-size:15px}
        .cart-empty{padding:38px 16px}
    }
    @media(max-width:370px){
        .cart-page{padding-left:9px;padding-right:9px}
        .cart-item{gap:8px;padding:9px}
        .cart-image{width:62px;height:62px;flex-basis:62px}
        .cart-name{font-size:12px}
        .cart-item-bottom{gap:5px}
        .cart-quantity button{width:24px}
        .cart-quantity input{width:29px}
        .cart-subtotal{font-size:10px}
        .cart-delete{width:28px;height:28px}
    }
    </style>
</head>

<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<main>
<section class="section">
<div class="cart-page">

    <div class="cart-header">
        <div>
            <span class="eyebrow">KERANJANG</span>
            <h1>Keranjang Belanja</h1>
            <p class="muted">Periksa produk dan jumlah sebelum checkout.</p>
        </div>
        <?php if(!empty($items)): ?>
            <div class="cart-count"><span id="cartCount"><?= $jumlah_produk ?></span> barang</div>
        <?php endif; ?>
    </div>

    <div id="cartMessage"></div>

    <?php if(isset($_GET["error"])): ?>
        <?php if($_GET["error"]==="stok_maksimal"): ?>
            <div class="cart-alert cart-danger">Jumlah produk sudah mencapai batas stok.</div>
        <?php elseif($_GET["error"]==="stok_habis"): ?>
            <div class="cart-alert cart-danger">Stok produk sudah habis.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if(empty($items)): ?>

        <div class="cart-empty">
            <div class="cart-empty-icon">🛒</div>
            <h2>Keranjang masih kosong</h2>
            <p>Yuk pilih produk yang kamu butuhkan dari katalog.</p>
            <a href="../pages/katalog.php" class="btn-primary">Lihat Katalog</a>
        </div>

    <?php else: ?>

        <div class="cart-layout">
            <div class="cart-list" id="cartList">

                <?php foreach($items as $item): ?>
                    <?php
                    $itemId=$item["id_item"];
                    $gambarPath=$item["id_varian"]>0
                        ?"../../admin/assets/images/products/variants/".rawurlencode($item["gambar"]??"")
                        :"../../admin/assets/images/products/".rawurlencode($item["gambar"]??"");
                    ?>

                    <article
                        class="cart-item"
                        id="cartItem<?= htmlspecialchars($itemId) ?>"
                        data-id="<?= (int)$item["id_produk"] ?>"
                        data-varian="<?= (int)$item["id_varian"] ?>"
                        data-item="<?= htmlspecialchars($itemId) ?>"
                        data-price="<?= (float)$item["harga"] ?>"
                        data-stock="<?= (int)$item["stok"] ?>"
                    >
                        <div class="cart-image">
                            <?php if(!empty($item["gambar"])): ?>
                                <img src="<?= htmlspecialchars($gambarPath) ?>" alt="<?= htmlspecialchars($item["nama_produk"]) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">Tidak ada gambar</div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-info">
                            <div class="cart-category"><?= htmlspecialchars($item["nama_kategori"]??"Produk") ?></div>
                            <h2 class="cart-name"><?= htmlspecialchars($item["nama_produk"]) ?></h2>

                            <?php if($item["id_varian"]>0): ?>
                                <div class="cart-variant">Varian: <?= htmlspecialchars($item["nama_varian"]) ?></div>
                            <?php endif; ?>

                            <div class="cart-price">
                                Harga satuan: <strong>Rp <?= number_format($item["harga"],0,",",".") ?></strong>
                            </div>

                            <div class="cart-item-bottom">
                                <div class="cart-quantity">
                                    <button type="button" aria-label="Kurangi jumlah" onclick="ubahJumlah('<?= htmlspecialchars($itemId,ENT_QUOTES) ?>',-1)">−</button>
                                    <input
                                        type="number"
                                        class="jumlah-input"
                                        value="<?= (int)$item["jumlah"] ?>"
                                        min="1"
                                        max="<?= (int)$item["stok"] ?>"
                                        data-id="<?= (int)$item["id_produk"] ?>"
                                        data-varian="<?= (int)$item["id_varian"] ?>"
                                        aria-label="Jumlah produk"
                                        onchange="ubahJumlahInput('<?= htmlspecialchars($itemId,ENT_QUOTES) ?>',this.value)"
                                        onkeydown="handleQuantityKey(event,'<?= htmlspecialchars($itemId,ENT_QUOTES) ?>')"
                                    >
                                    <button type="button" aria-label="Tambah jumlah" onclick="ubahJumlah('<?= htmlspecialchars($itemId,ENT_QUOTES) ?>',1)">+</button>
                                </div>

                                <div class="cart-subtotal" id="subtotal<?= htmlspecialchars($itemId) ?>">
                                    Rp <?= number_format($item["subtotal"],0,",",".") ?>
                                </div>

                                <a
                                    href="../actions/update_keranjang.php?id=<?= (int)$item["id_produk"] ?>&id_varian=<?= (int)$item["id_varian"] ?>&aksi=hapus"
                                    class="cart-delete"
                                    title="Hapus produk"
                                    aria-label="Hapus <?= htmlspecialchars($item["nama_produk"]) ?>"
                                    onclick="return confirm('Hapus produk ini dari keranjang?');"
                                >×</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

            </div>

            <aside class="cart-summary">
                <h2>Ringkasan Belanja</h2>
                <div class="summary-row">
                    <span>Total barang</span>
                    <strong id="summaryCount"><?= $jumlah_produk ?></strong>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong id="summarySubtotal">Rp <?= number_format($total,0,",",".") ?></strong>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span id="summaryTotal">Rp <?= number_format($total,0,",",".") ?></span>
                </div>
                <a href="checkout.php?cart=1" class="btn-primary">Lanjut Checkout</a>
                <a href="../pages/katalog.php" class="continue-shopping">← Lanjut Belanja</a>
            </aside>
        </div>

    <?php endif; ?>

</div>
</section>
</main>

<script>
function formatRupiah(angka){
    return "Rp "+Number(angka).toLocaleString("id-ID");
}

function tampilkanPesan(pesan,tipe="success"){
    const message=document.getElementById("cartMessage");
    if(!message)return;
    message.innerHTML=`<div class="cart-alert cart-${tipe}">${pesan}</div>`;
    setTimeout(()=>{message.innerHTML="";},2500);
}

async function updateServer(id,idVarian,jumlah){
    try{
        const response=await fetch("../actions/update_keranjang.php",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"id="+encodeURIComponent(id)+"&id_varian="+encodeURIComponent(idVarian)+"&jumlah="+encodeURIComponent(jumlah)
        });
        const data=await response.json();
        if(!data.success){
            tampilkanPesan(data.message||"Gagal memperbarui keranjang.","danger");
            return false;
        }
        return data;
    }catch(error){
        tampilkanPesan("Terjadi kesalahan koneksi.","danger");
        return false;
    }
}

function updateTampilan(){
    let total=0;
    let jumlahBarang=0;

    document.querySelectorAll(".cart-item").forEach(item=>{
        const itemId=item.dataset.item;
        const harga=parseFloat(item.dataset.price)||0;
        const input=item.querySelector(".jumlah-input");
        if(!input)return;
        const jumlah=parseInt(input.value)||1;
        const subtotal=harga*jumlah;
        total+=subtotal;
        jumlahBarang+=jumlah;
        const subtotalElement=document.getElementById("subtotal"+itemId);
        if(subtotalElement)subtotalElement.textContent=formatRupiah(subtotal);
    });

    const summarySubtotal=document.getElementById("summarySubtotal");
    const summaryTotal=document.getElementById("summaryTotal");
    const summaryCount=document.getElementById("summaryCount");
    const cartCount=document.getElementById("cartCount");

    if(summarySubtotal)summarySubtotal.textContent=formatRupiah(total);
    if(summaryTotal)summaryTotal.textContent=formatRupiah(total);
    if(summaryCount)summaryCount.textContent=jumlahBarang;
    if(cartCount)cartCount.textContent=jumlahBarang;
}

async function ubahJumlah(itemId,perubahan){
    const item=document.getElementById("cartItem"+itemId);
    if(!item)return;
    const input=item.querySelector(".jumlah-input");
    if(!input)return;

    const stok=parseInt(item.dataset.stock)||0;
    const idProduk=parseInt(item.dataset.id)||0;
    const idVarian=parseInt(item.dataset.varian)||0;
    let jumlah=(parseInt(input.value)||1)+perubahan;

    if(jumlah<1)jumlah=1;
    if(jumlah>stok){
        jumlah=stok;
        tampilkanPesan("Jumlah tidak boleh melebihi stok.","danger");
    }

    input.value=jumlah;
    item.classList.add("cart-loading");
    const hasil=await updateServer(idProduk,idVarian,jumlah);
    item.classList.remove("cart-loading");

    if(hasil){
        updateTampilan();
        tampilkanPesan("Jumlah produk diperbarui.");
    }
}

async function ubahJumlahInput(itemId,nilai){
    const item=document.getElementById("cartItem"+itemId);
    if(!item)return;
    const input=item.querySelector(".jumlah-input");
    if(!input)return;

    const stok=parseInt(item.dataset.stock)||0;
    const idProduk=parseInt(item.dataset.id)||0;
    const idVarian=parseInt(item.dataset.varian)||0;
    let jumlah=parseInt(nilai);

    if(isNaN(jumlah)||jumlah<1)jumlah=1;
    if(jumlah>stok){
        jumlah=stok;
        tampilkanPesan("Jumlah tidak boleh melebihi stok.","danger");
    }

    input.value=jumlah;
    item.classList.add("cart-loading");
    const hasil=await updateServer(idProduk,idVarian,jumlah);
    item.classList.remove("cart-loading");

    if(hasil){
        updateTampilan();
        tampilkanPesan("Jumlah produk diperbarui.");
    }
}

function handleQuantityKey(event,itemId){
    if(event.key==="Enter"){
        event.preventDefault();
        ubahJumlahInput(itemId,event.target.value);
        event.target.blur();
    }
}

updateTampilan();
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

</body>
</html>