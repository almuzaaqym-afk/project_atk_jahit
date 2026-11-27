
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_pelanggan'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$nama_pelanggan = $_SESSION['nama_pelanggan'] ?? 'Pelanggan';

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_chat, id_pelanggan, status_chat, created_at, ended_at
     FROM cs_chat
     WHERE id_pelanggan = ?
       AND status_chat IN ('Menunggu', 'Dilayani')
     ORDER BY id_chat DESC
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, 'i', $id_pelanggan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$id_chat = (int) ($chat['id_chat'] ?? 0);
$status_chat = (string) ($chat['status_chat'] ?? 'Belum');
$queue_number = 0;
$queue_total = 0;

if ($status_chat === 'Menunggu' && $id_chat > 0) {
    $createdAt = $chat['created_at'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total
         FROM cs_chat
         WHERE status_chat = 'Menunggu'
           AND (
                created_at < ?
                OR (created_at = ? AND id_chat <= ?)
           )"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'ssi',
        $createdAt,
        $createdAt,
        $id_chat
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $queue_number = (int) ($row['total'] ?? 0);

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM cs_chat
         WHERE status_chat = 'Menunggu'"
    );

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $queue_total = (int) ($row['total'] ?? 0);
        mysqli_free_result($result);
    }
}

function pusatBantuanEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#102a43">
<title>Pusat Bantuan | Toko ATK &amp; Alat Jahit</title>

<style>
html,body{width:100%;max-width:100%;overflow-x:hidden}
.cs-page{width:100%;max-width:900px;min-width:0;margin:0 auto}
.cs-header{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;margin-bottom:22px}
.cs-header-left{min-width:0;flex:1}
.cs-header-left h1{color:#102a43;margin:0 0 7px;font-size:26px;line-height:1.35}
.cs-header-left p{color:#627d98;font-size:13px;line-height:1.6;margin:0}
.cs-status{display:inline-flex;align-items:center;justify-content:center;padding:8px 13px;border-radius:20px;font-size:11px;font-weight:700;line-height:1.5}
.chat-none{background:#eef1f5;color:#627d98}
.chat-waiting{background:#fff5d8;color:#9a6b00}
.chat-serving{background:#e8f8ef;color:#28734a}
.chat-ended{background:#fde8e8;color:#b83232}
.cs-card{min-width:0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.cs-bot{padding:22px;background:#f7f9fb;border-bottom:1px solid #e5e7eb}
.cs-bot-top{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.cs-bot-avatar{width:45px;height:45px;min-width:45px;border-radius:50%;background:#102a43;color:#fff;display:flex;align-items:center;justify-content:center;font-size:21px}
.cs-bot-title{color:#102a43;font-size:14px;font-weight:700}
.cs-bot-subtitle{color:#627d98;font-size:11px;margin-top:3px}
.cs-bot-message{color:#334e68;font-size:13px;line-height:1.7;overflow-wrap:anywhere}
.cs-options{padding:20px}
.cs-options-title{color:#102a43;font-size:13px;font-weight:700;margin-bottom:12px}
.cs-option-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.cs-option{display:flex;align-items:center;gap:10px;width:100%;min-width:0;padding:12px;border:1px solid #dfe6ec;border-radius:9px;background:#fff;color:#334e68;cursor:pointer;text-align:left}
.cs-option:hover{border-color:#102a43;background:#f7f9fb}
.cs-option-icon{width:32px;height:32px;min-width:32px;display:flex;align-items:center;justify-content:center;background:#eef3f7;border-radius:7px;font-size:16px}
.cs-option-text{min-width:0;overflow-wrap:anywhere}
.cs-option-text strong{display:block;color:#102a43;font-size:12px;line-height:1.5}
.cs-option-text small{display:block;margin-top:3px;color:#829ab1;font-size:10px;line-height:1.5}
.cs-bot-response{display:none;margin:0 20px 20px;padding:14px 15px;background:#f7f9fb;border:1px solid #e2e8ee;border-radius:10px;color:#334e68;font-size:12px;line-height:1.7;overflow-wrap:anywhere}
.cs-bot-response.show{display:block}
.cs-bot-response strong{color:#102a43}
.cs-contact{margin:0 20px 20px;padding:20px;border:1px solid #dfe6ec;border-radius:12px;background:#fff;text-align:center}
.cs-contact-icon{font-size:30px;margin-bottom:8px}
.cs-contact-title{color:#102a43;font-size:14px;font-weight:700}
.cs-contact-text{color:#627d98;font-size:11px;line-height:1.7;margin:6px auto 14px;max-width:520px}
.cs-contact-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 18px;min-height:42px;border:none;border-radius:8px;background:#102a43;color:#fff;font-size:12px;font-weight:700;cursor:pointer}
.cs-contact-btn:hover{background:#183b56}
.cs-contact-btn:disabled{background:#aebbc7;cursor:not-allowed}
.cs-queue{margin:0 20px 20px;padding:22px;border-radius:12px;background:#fffaf0;border:1px solid #f2dfad;text-align:center}
.cs-queue-icon{font-size:32px;margin-bottom:8px}
.cs-queue-title{color:#7a5800;font-size:15px;font-weight:700;line-height:1.5}
.cs-queue-text{color:#8c7435;font-size:11px;line-height:1.7;margin-top:5px}
.cs-queue-number{margin:14px 0 4px;color:#102a43;font-size:32px;font-weight:800;font-variant-numeric:tabular-nums}
.cs-queue-label{color:#829ab1;font-size:11px}
.cs-serving{margin:0 20px 20px;padding:18px 20px;border-radius:12px;background:#f0fbf4;border:1px solid #cdebd8}
.cs-serving-top{display:flex;align-items:center;gap:10px}
.cs-serving-dot{width:10px;height:10px;flex-shrink:0;background:#32a852;border-radius:50%}
.cs-serving-title{color:#28734a;font-size:13px;font-weight:700;line-height:1.5}
.cs-serving-text{margin-top:7px;color:#527765;font-size:11px;line-height:1.7}
.cs-end-btn{margin-top:14px;padding:9px 14px;min-height:38px;border:1px solid #d9534f;border-radius:8px;background:#fff;color:#c0392b;font-size:11px;font-weight:700;cursor:pointer}
.cs-end-btn:hover{background:#d9534f;color:#fff}
.cs-end-btn:disabled{opacity:.6;cursor:not-allowed}
.cs-chat-section{border-top:1px solid #e5e7eb;min-width:0}
.cs-chat-title{padding:15px 20px;background:#fff;color:#102a43;font-size:13px;font-weight:700}
.cs-box{height:420px;min-height:280px;max-height:58vh;padding:18px;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;background:#f8fafc}
.cs-loading,.cs-empty{text-align:center;color:#829ab1;font-size:13px;line-height:1.7;padding:60px 16px;white-space:pre-line;overflow-wrap:anywhere}
.cs-message{display:flex;min-width:0;margin-bottom:14px}
.cs-message.customer{justify-content:flex-end}
.cs-message.admin{justify-content:flex-start}
.cs-bubble{width:fit-content;max-width:78%;min-width:0;padding:11px 14px;border-radius:13px;font-size:13px;line-height:1.6;overflow-wrap:anywhere;word-break:break-word}
.cs-message.customer .cs-bubble{background:#102a43;color:#fff;border-bottom-right-radius:4px}
.cs-message.admin .cs-bubble{background:#fff;color:#334e68;border:1px solid #e1e7ec;border-bottom-left-radius:4px}
.cs-sender{display:block;font-size:10px;font-weight:700;margin-bottom:4px;opacity:.75}
.cs-text{white-space:pre-wrap;overflow-wrap:anywhere}
.cs-time{display:block;margin-top:6px;font-size:9px;opacity:.65;text-align:right}
.cs-image-link{display:block;max-width:300px;margin-top:7px}
.cs-image{display:block;width:100%;max-height:280px;object-fit:contain;border-radius:8px;background:#eef2f6}
.cs-form{display:block;width:100%;min-width:0;padding:12px;border-top:1px solid #e5e7eb;background:#fff}
.cs-compose{display:flex;align-items:flex-end;gap:8px;width:100%;min-width:0}
.cs-input{display:block;flex:1 1 auto;width:0;min-width:0;min-height:48px;max-height:160px;padding:12px 14px;border:1px solid #d8e0e7;border-radius:10px;outline:none;font-family:inherit;font-size:13px;line-height:1.55;color:#334e68;resize:vertical;overflow-y:auto}
.cs-input:focus{border-color:#102a43;box-shadow:0 0 0 2px rgba(16,42,67,.08)}
.cs-input:disabled{background:#f4f7fa}
.cs-attach{flex:0 0 42px;width:42px;height:48px;display:flex;align-items:center;justify-content:center;border:1px solid #d8e0e7;border-radius:10px;background:#fff;color:#334e68;font-size:21px;cursor:pointer}
.cs-attach:hover{background:#f2f5f8}
.cs-attach:disabled{opacity:.5;cursor:not-allowed}
.cs-send{flex:0 0 auto;min-width:76px;height:48px;padding:0 16px;border:0;border-radius:10px;background:#102a43;color:#fff;font-size:12px;font-weight:700;cursor:pointer}
.cs-send:hover{background:#183b56}
.cs-send:disabled{background:#aebbc7;cursor:not-allowed}
.cs-hint{display:block;padding:7px 3px 0;color:#8a9bab;font-size:10px;line-height:1.5}
.cs-preview{display:none;align-items:center;gap:10px;padding:0 3px 12px}
.cs-preview.visible{display:flex}
.cs-preview img{width:62px;height:62px;object-fit:cover;border-radius:8px;border:1px solid #e1e7ec;background:#f4f7fa}
.cs-preview-info{flex:1;min-width:0}
.cs-preview-name{color:#334e68;font-size:11px;overflow-wrap:anywhere}
.cs-preview-size{margin-top:4px;color:#829ab1;font-size:10px}
.cs-remove-image{width:31px;height:31px;border:0;border-radius:7px;background:#fde8e8;color:#b83232;font-size:18px;cursor:pointer}
.cs-message-status{display:block;min-height:0;padding:0 3px;color:#627d98;font-size:11px;line-height:1.6;overflow-wrap:anywhere}
.cs-message-status:not(:empty){padding-top:8px}
.cs-info{padding:14px 20px;background:#f7f9fb;border-top:1px solid #e5e7eb;color:#627d98;font-size:11px;line-height:1.7;overflow-wrap:anywhere}
.cs-info strong{color:#102a43}
.cs-end-state{margin:0 20px 20px;padding:20px;border:1px solid #dfe6ec;border-radius:12px;background:#f7f9fb;color:#627d98;text-align:center;font-size:12px;line-height:1.7}
.cs-end-state strong{color:#334e68}
.btn-back-cs{display:flex;align-items:center;justify-content:center;max-width:100%;margin-top:18px;padding:10px 15px;min-height:42px;background:#eef1f5;color:#102a43;text-decoration:none;border-radius:8px;font-size:12px;font-weight:700;text-align:center}
@media(max-width:700px){
    .cs-header{flex-direction:column;gap:12px}
    .cs-header-left h1{font-size:23px}
    .cs-option-grid{grid-template-columns:minmax(0,1fr)}
    .cs-bot{padding:17px}
    .cs-options{padding:16px}
    .cs-bot-response{margin:0 14px 15px}
    .cs-contact,.cs-queue,.cs-serving,.cs-end-state{margin:0 14px 15px;padding:16px}
    .cs-contact-btn{width:100%;max-width:320px}
    .cs-chat-title{padding:14px 15px}
    .cs-box{height:420px;max-height:55vh;padding:12px}
    .cs-bubble{max-width:88%;padding:10px 12px;font-size:12px}
    .cs-form{display:block;padding:10px}
    .cs-compose{display:flex;gap:6px;width:100%}
    .cs-input{width:0;min-width:0;min-height:48px;padding:10px;font-size:12px}
    .cs-attach{width:38px;flex-basis:38px;height:48px}
    .cs-send{min-width:60px;height:48px;padding:0 10px;font-size:11px}
    .cs-image-link{max-width:240px}
    .cs-image{max-height:230px}
    .cs-info{padding:12px 14px;font-size:11px}
    .btn-back-cs{width:100%;margin-top:14px}
}
@media(max-width:380px){
    .cs-box{height:390px;max-height:52vh;padding:9px}
    .cs-form{padding:8px}
    .cs-compose{gap:5px}
    .cs-input{padding:9px;font-size:12px}
    .cs-attach{width:35px;flex-basis:35px}
    .cs-send{min-width:52px;padding:0 7px}
}
</style>
</head>
<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<main>
<section class="section">
<div class="cs-page">

    <div class="cs-header">
        <div class="cs-header-left">
            <h1>Pusat Bantuan</h1>
            <p>Temukan jawaban atau hubungi Customer Service.</p>
        </div>
        <span class="cs-status chat-none" id="csHeaderStatus">
            Belum Menghubungi CS
        </span>
    </div>

    <div class="cs-card">

        <div class="cs-bot">
            <div class="cs-bot-top">
                <div class="cs-bot-avatar">🤖</div>
                <div>
                    <div class="cs-bot-title">Asisten Toko</div>
                    <div class="cs-bot-subtitle">Bantuan otomatis</div>
                </div>
            </div>

            <div class="cs-bot-message">
                Halo <strong><?= pusatBantuanEscape($nama_pelanggan); ?></strong> 👋
                <br>
                Ada yang bisa kami bantu? Silakan pilih kategori bantuan di bawah.
            </div>
        </div>

        <div class="cs-options">
            <div class="cs-options-title">Pilih bantuan</div>

            <div class="cs-option-grid">
                <button type="button" class="cs-option" onclick="showBotAnswer('pesanan')">
                    <span class="cs-option-icon">📦</span>
                    <span class="cs-option-text">
                        <strong>Pesanan Saya</strong>
                        <small>Cek status dan pengambilan</small>
                    </span>
                </button>

                <button type="button" class="cs-option" onclick="showBotAnswer('produk')">
                    <span class="cs-option-icon">🛍️</span>
                    <span class="cs-option-text">
                        <strong>Produk &amp; Stok</strong>
                        <small>Informasi produk dan stok</small>
                    </span>
                </button>

                <button type="button" class="cs-option" onclick="showBotAnswer('voucher')">
                    <span class="cs-option-icon">🎟️</span>
                    <span class="cs-option-text">
                        <strong>Voucher / Promo</strong>
                        <small>Informasi promo dan voucher</small>
                    </span>
                </button>

                <button type="button" class="cs-option" onclick="showBotAnswer('pickup')">
                    <span class="cs-option-icon">🏪</span>
                    <span class="cs-option-text">
                        <strong>Pickup Pesanan</strong>
                        <small>Cara mengambil pesanan</small>
                    </span>
                </button>
            </div>
        </div>

        <div class="cs-bot-response" id="botResponse"></div>

        <div class="cs-contact" id="csContactPanel">
            <div class="cs-contact-icon">👨‍💼</div>
            <div class="cs-contact-title">Butuh bantuan langsung?</div>
            <div class="cs-contact-text">
                Hubungi Customer Service untuk mendapatkan bantuan dari petugas toko.
            </div>

            <button type="button" class="cs-contact-btn" id="btnMasukAntrian">
                💬 Hubungi Customer Service
            </button>
        </div>

        <div class="cs-queue" id="csQueuePanel" style="display:none;">
            <div class="cs-queue-icon">⏳</div>

            <div class="cs-queue-title" id="csQueueTitle">
                Kamu sedang menunggu Customer Service
            </div>

            <div class="cs-queue-text" id="csQueueText">
                Permintaan bantuan kamu sudah masuk.
            </div>

            <div class="cs-queue-number" id="csQueueNumber">—</div>

            <div class="cs-queue-label" id="csQueueLabel">
                Nomor antrean
            </div>
        </div>

        <div class="cs-serving" id="csServingPanel" style="display:none;">
            <div class="cs-serving-top">
                <span class="cs-serving-dot"></span>
                <span class="cs-serving-title">Customer Service sedang melayani kamu</span>
            </div>

            <div class="cs-serving-text">
                Kamu sudah dapat mengirim pesan kepada Customer Service.
            </div>

            <button type="button" class="cs-end-btn" id="btnAkhiriChat">
                ✓ Akhiri Percakapan
            </button>
        </div>

        <div class="cs-chat-section" id="csChatSection" style="display:none;">
            <div class="cs-chat-title">💬 Chat Customer Service</div>

            <div class="cs-box" id="csBox" aria-live="polite">
                <div class="cs-loading">Memuat percakapan...</div>
            </div>

            <form class="cs-form" id="csForm" enctype="multipart/form-data" autocomplete="off">
                <div class="cs-preview" id="csPreview">
                    <img id="csPreviewImage" src="" alt="Pratinjau gambar">

                    <div class="cs-preview-info">
                        <div class="cs-preview-name" id="csPreviewName"></div>
                        <div class="cs-preview-size" id="csPreviewSize"></div>
                    </div>

                    <button type="button" class="cs-remove-image" id="csRemoveImage" aria-label="Hapus gambar">×</button>
                </div>

                <div class="cs-compose">
                    <input
                        type="file"
                        name="gambar"
                        id="csImage"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        hidden
                    >

                    <button type="button" class="cs-attach" id="csAttach" title="Kirim gambar">＋</button>

                    <textarea
                        name="pesan"
                        id="csInput"
                        class="cs-input"
                        placeholder="Tulis pesan..."
                        maxlength="10000"
                        rows="1"
                        aria-label="Tulis pesan"
                    ></textarea>

                    <button type="submit" class="cs-send" id="csSend" disabled>Kirim</button>
                </div>

                <div class="cs-hint">
                    Enter untuk mengirim · Shift+Enter untuk baris baru · Gambar maksimal 5 MB
                </div>

                <div class="cs-message-status" id="csMessageStatus" role="status"></div>
            </form>

            <div class="cs-info">
                <strong>Customer Service</strong> · Jangan bagikan kata sandi atau informasi rahasia melalui chat.
            </div>
        </div>

        <div class="cs-end-state" id="csEndPanel" style="display:none;">
            <strong>Percakapan telah selesai.</strong>
            <br>
            Kamu dapat menghubungi Customer Service kembali jika membutuhkan bantuan.
        </div>

    </div>

    <a href="index.php" class="btn-back-cs">← Kembali</a>
</div>
</section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<script>
function showBotAnswer(type) {
    const answers = {
        pesanan: {
            title: '📦 Pesanan Saya',
            answer: 'Buka menu Pesanan untuk memeriksa nomor, produk, total, dan status pesanan. Pesanan dapat diambil ketika status berubah menjadi Siap Diambil.'
        },
        produk: {
            title: '🛍️ Produk & Stok',
            answer: 'Informasi produk dan stok tersedia di menu Katalog. Jika memerlukan informasi lebih lanjut, hubungi Customer Service.'
        },
        voucher: {
            title: '🎟️ Voucher / Promo',
            answer: 'Voucher memiliki ketentuan seperti minimal belanja, periode berlaku, maksimal diskon, dan kuota. Customer Service dapat membantu jika voucher bermasalah.'
        },
        pickup: {
            title: '🏪 Pickup Pesanan',
            answer: 'Pesanan dapat diambil setelah status berubah menjadi Siap Diambil. Tunjukkan nomor pesanan kepada petugas toko.'
        }
    };

    const item = answers[type];
    const target = document.getElementById('botResponse');

    if (!item || !target) return;

    target.replaceChildren();

    const title = document.createElement('strong');
    title.textContent = item.title;

    const answer = document.createElement('div');
    answer.textContent = item.answer;
    answer.style.marginTop = '5px';

    target.append(title, answer);
    target.classList.add('show');
}

(function () {
    const btnMasuk = document.getElementById('btnMasukAntrian');
    const btnAkhiri = document.getElementById('btnAkhiriChat');

    const headerStatus = document.getElementById('csHeaderStatus');
    const contactPanel = document.getElementById('csContactPanel');
    const queuePanel = document.getElementById('csQueuePanel');
    const servingPanel = document.getElementById('csServingPanel');
    const chatSection = document.getElementById('csChatSection');
    const endPanel = document.getElementById('csEndPanel');

    const queueNumber = document.getElementById('csQueueNumber');
    const queueText = document.getElementById('csQueueText');
    const queueTitle = document.getElementById('csQueueTitle');
    const queueLabel = document.getElementById('csQueueLabel');

    const csBox = document.getElementById('csBox');
    const csForm = document.getElementById('csForm');
    const csInput = document.getElementById('csInput');
    const csSend = document.getElementById('csSend');
    const csImage = document.getElementById('csImage');
    const csAttach = document.getElementById('csAttach');
    const csPreview = document.getElementById('csPreview');
    const csPreviewImage = document.getElementById('csPreviewImage');
    const csPreviewName = document.getElementById('csPreviewName');
    const csPreviewSize = document.getElementById('csPreviewSize');
    const csRemoveImage = document.getElementById('csRemoveImage');
    const csMessageStatus = document.getElementById('csMessageStatus');

    let idChat = <?= (int) $id_chat; ?>;
    let statusChat = <?= json_encode($status_chat); ?>;
    let queuePosition = <?= (int) $queue_number; ?>;
    let queueTotal = <?= (int) $queue_total; ?>;

    let pollingTimer = null;
    let loading = false;
    let sending = false;
    let firstLoad = true;
    let lastMessageKey = '';
    let previewUrl = '';

    function el(tag, cls, text) {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = String(text);
        return node;
    }

    function setStatusMessage(text, error = false) {
        csMessageStatus.textContent = text || '';
        csMessageStatus.style.color = error ? '#b83232' : '#627d98';
    }

    function updateQueue(position, total) {
        queuePosition = Number(position || 0);
        queueTotal = Number(total || 0);

        if (queueNumber) {
            queueNumber.textContent = queuePosition > 0
                ? '#' + queuePosition
                : '—';
        }

        if (queueLabel) {
            queueLabel.textContent = queueTotal > 0
                ? 'Nomor antrean · ' + queueTotal + ' pelanggan menunggu'
                : 'Nomor antrean';
        }

        if (queueText) {
            queueText.textContent = queuePosition > 1
                ? 'Masih ada ' + (queuePosition - 1) +
                  ' pelanggan di depan kamu. Halaman akan memperbarui status otomatis.'
                : 'Kamu berada di urutan berikutnya. Halaman akan memperbarui status otomatis.';
        }
    }

    function setChatState(status) {
        statusChat = status;

        if (status === 'Menunggu') {
            headerStatus.textContent = 'Menunggu Customer Service';
            headerStatus.className = 'cs-status chat-waiting';

            contactPanel.style.display = 'none';
            queuePanel.style.display = 'block';
            servingPanel.style.display = 'none';
            chatSection.style.display = 'none';
            endPanel.style.display = 'none';

        } else if (status === 'Dilayani') {
            headerStatus.textContent = 'Sedang Dilayani';
            headerStatus.className = 'cs-status chat-serving';

            contactPanel.style.display = 'none';
            queuePanel.style.display = 'none';
            servingPanel.style.display = 'block';
            chatSection.style.display = 'block';
            endPanel.style.display = 'none';

        } else if (status === 'Selesai') {
            headerStatus.textContent = 'Percakapan Selesai';
            headerStatus.className = 'cs-status chat-ended';

            contactPanel.style.display = 'block';
            queuePanel.style.display = 'none';
            servingPanel.style.display = 'none';
            chatSection.style.display = 'none';
            endPanel.style.display = 'block';

            stopPolling();

        } else {
            headerStatus.textContent = 'Belum Menghubungi CS';
            headerStatus.className = 'cs-status chat-none';

            contactPanel.style.display = 'block';
            queuePanel.style.display = 'none';
            servingPanel.style.display = 'none';
            chatSection.style.display = 'none';
            endPanel.style.display = 'none';
        }

        updateSendButton();
    }

    function startPolling() {
        if (pollingTimer) clearInterval(pollingTimer);
        refreshChat();
        pollingTimer = setInterval(refreshChat, 3000);
    }

    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
    }

    function renderMessages(messages) {
        const nearBottom =
            csBox.scrollHeight - csBox.scrollTop - csBox.clientHeight < 120;

        const shouldScroll = firstLoad || nearBottom;
        const fragment = document.createDocumentFragment();

        messages.forEach(function (message) {
            const customer = message.pengirim === 'Pelanggan';
            const row = el('div', 'cs-message ' + (customer ? 'customer' : 'admin'));
            const bubble = el('div', 'cs-bubble');

            bubble.appendChild(
                el('span', 'cs-sender', customer ? 'Kamu' : 'Customer Service')
            );

            if (message.pesan) {
                bubble.appendChild(el('div', 'cs-text', message.pesan));
            }

            if (message.gambar_url) {
                const link = el('a', 'cs-image-link');
                link.href = message.gambar_url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';

                const image = el('img', 'cs-image');
                image.src = message.gambar_url;
                image.alt = 'Gambar percakapan';
                image.loading = 'lazy';
                image.onerror = function () { link.remove(); };

                link.appendChild(image);
                bubble.appendChild(link);
            }

            bubble.appendChild(el('span', 'cs-time', message.waktu || ''));

            row.appendChild(bubble);
            fragment.appendChild(row);
        });

        csBox.replaceChildren(fragment);

        if (shouldScroll) csBox.scrollTop = csBox.scrollHeight;

        firstLoad = false;
    }

    async function refreshChat() {
        if (!idChat || loading || document.visibilityState === 'hidden') return;

        loading = true;

        try {
            const response = await fetch(
                '../actions/cs_ambil.php?id_chat=' + encodeURIComponent(idChat),
                {
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                if (firstLoad) {
                    csBox.replaceChildren(
                        el('div', 'cs-empty', data.message || 'Pesan tidak dapat dimuat.')
                    );
                }
                return;
            }

            const newStatus = data.chat?.status_chat || statusChat;

            if (newStatus !== statusChat) {
                setChatState(newStatus);
            }

            if (newStatus === 'Menunggu') {
                updateQueue(data.queue_position, data.queue_total);
                return;
            }

            if (newStatus !== 'Dilayani') return;

            const messages = data.messages || [];

            const key = JSON.stringify(messages.map(function (message) {
                return [
                    message.id_message,
                    message.pengirim,
                    message.pesan,
                    message.gambar,
                    message.waktu
                ];
            }));

            if (key === lastMessageKey) {
                firstLoad = false;
                return;
            }

            if (messages.length === 0) {
                csBox.replaceChildren(
                    el('div', 'cs-empty', 'Belum ada pesan.\nSilakan mulai percakapan dengan Customer Service.')
                );

                lastMessageKey = key;
                firstLoad = false;
                return;
            }

            renderMessages(messages);
            lastMessageKey = key;

        } catch (error) {
            if (firstLoad) {
                csBox.replaceChildren(
                    el('div', 'cs-empty', 'Gagal memuat chat. Periksa koneksi, lalu coba lagi.')
                );
            }
        } finally {
            loading = false;
        }
    }

    async function enterQueue() {
        btnMasuk.disabled = true;
        btnMasuk.textContent = 'Memproses...';

        try {
            const response = await fetch(
                '../actions/cs_masuk_antrian.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'masuk_antrian=1',
                    cache: 'no-store'
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal masuk antrean.');
            }

            idChat = Number(data.id_chat || 0);
            if (!idChat) throw new Error('ID chat tidak diterima server.');

            lastMessageKey = '';
            firstLoad = true;

            updateQueue(data.queue_position, data.queue_total);
            setChatState(data.status_chat || 'Menunggu');
            startPolling();

        } catch (error) {
            alert(error.message || 'Gagal masuk antrean.');
        } finally {
            btnMasuk.disabled = false;
            btnMasuk.textContent = '💬 Hubungi Customer Service';
        }
    }

    async function endConversation() {
        if (!idChat || statusChat !== 'Dilayani') return;

        if (!confirm('Apakah kamu yakin ingin mengakhiri percakapan?')) return;

        btnAkhiri.disabled = true;
        btnAkhiri.textContent = 'Mengakhiri...';

        try {
            const formData = new FormData();
            formData.append('id_chat', String(idChat));

            const response = await fetch(
                '../actions/cs_akhiri.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store'
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal mengakhiri percakapan.');
            }

            setChatState('Selesai');
            idChat = 0;
            setStatusMessage('');

        } catch (error) {
            alert(error.message || 'Gagal mengakhiri percakapan.');
        } finally {
            btnAkhiri.disabled = false;
            btnAkhiri.textContent = '✓ Akhiri Percakapan';
        }
    }

    function selectedFile() {
        return csImage.files && csImage.files.length ? csImage.files[0] : null;
    }

    function updateSendButton() {
        csSend.disabled = sending ||
            statusChat !== 'Dilayani' ||
            (csInput.value.trim() === '' && !selectedFile());
    }

    function clearImage() {
        csImage.value = '';

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = '';
        }

        csPreview.classList.remove('visible');
        csPreviewImage.removeAttribute('src');
        csPreviewName.textContent = '';
        csPreviewSize.textContent = '';
        updateSendButton();
    }

    btnMasuk.addEventListener('click', enterQueue);

    if (btnAkhiri) {
        btnAkhiri.addEventListener('click', endConversation);
    }

    csAttach.addEventListener('click', function () {
        if (statusChat === 'Dilayani') csImage.click();
    });

    csImage.addEventListener('change', function () {
        setStatusMessage('');

        const file = selectedFile();

        if (!file) {
            updateSendButton();
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!allowed.includes(file.type)) {
            clearImage();
            setStatusMessage('Pilih gambar JPG, PNG, WEBP, atau GIF.', true);
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            clearImage();
            setStatusMessage('Ukuran gambar maksimal 5 MB.', true);
            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);

        previewUrl = URL.createObjectURL(file);
        csPreviewImage.src = previewUrl;
        csPreviewName.textContent = file.name;
        csPreviewSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

        csPreview.classList.add('visible');
        updateSendButton();
    });

    csRemoveImage.addEventListener('click', function () {
        clearImage();
        setStatusMessage('');
    });

    csInput.addEventListener('input', updateSendButton);

    csInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            if (!csSend.disabled) csForm.requestSubmit();
        }
    });

    csForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (sending || !idChat || statusChat !== 'Dilayani') return;

        const text = csInput.value.trim();
        const file = selectedFile();

        if (!text && !file) return;

        sending = true;
        updateSendButton();

        csSend.textContent = '...';
        csInput.disabled = true;
        csAttach.disabled = true;
        setStatusMessage('Mengirim pesan...');

        const formData = new FormData();
        formData.append('id_chat', String(idChat));
        formData.append('pesan', text);
        if (file) formData.append('gambar', file);

        try {
            const response = await fetch(
                '../actions/cs_kirim.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store'
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Pesan gagal dikirim.');
            }

            csInput.value = '';
            clearImage();
            setStatusMessage('Pesan terkirim.');

            await refreshChat();
            csBox.scrollTop = csBox.scrollHeight;
            csInput.focus();

        } catch (error) {
            setStatusMessage(error.message || 'Terjadi kesalahan saat mengirim.', true);
        } finally {
            sending = false;
            csInput.disabled = false;
            csAttach.disabled = false;
            csSend.textContent = 'Kirim';
            updateSendButton();
        }
    });

    setChatState(statusChat);
    updateQueue(queuePosition, queueTotal);
    updateSendButton();

    if (idChat > 0) startPolling();

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && idChat > 0) {
            refreshChat();
        }
    });

    window.addEventListener('pagehide', function () {
        stopPolling();

        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });

    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
    }
})();
</script>

</body>
</html>
