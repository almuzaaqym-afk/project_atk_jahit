
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$id_pesanan = (int) ($_GET['id'] ?? 0);

if ($id_pesanan <= 0) {
    header("Location: pesanan.php");
    exit;
}

/* DATA PESANAN */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id_pesanan,
        p.nomor_pesanan,
        p.status_pesanan,
        p.id_pelanggan,
        pl.nama
     FROM pesanan p
     LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
     WHERE p.id_pesanan = ?
       AND p.id_pelanggan = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: pesanan.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $id_pesanan, $id_pelanggan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    header("Location: pesanan.php");
    exit;
}

/* STATUS CHAT BERDASARKAN PESANAN */
$status_pesanan = (string) $pesanan['status_pesanan'];

$chat_aktif = in_array(
    $status_pesanan,
    ['Diproses', 'Siap Diambil'],
    true
);

/* AMBIL SESI CHAT */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_chat,
        id_pesanan,
        id_pelanggan,
        status_chat,
        created_at,
        ended_at
     FROM chat_session
     WHERE id_pesanan = ?
       AND id_pelanggan = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Chat pesanan tidak dapat dimuat.");
}

mysqli_stmt_bind_param($stmt, "ii", $id_pesanan, $id_pelanggan);
mysqli_stmt_execute($stmt);

$result_chat = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result_chat);

mysqli_stmt_close($stmt);

/* BUAT CHAT JIKA BELUM ADA */
if (!$chat && $chat_aktif) {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO chat_session
            (id_pesanan, id_pelanggan, status_chat)
         VALUES (?, ?, 'Aktif')"
    );

    if (!$stmt) {
        die("Chat pesanan tidak dapat dibuat.");
    }

    mysqli_stmt_bind_param($stmt, "ii", $id_pesanan, $id_pelanggan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            id_chat,
            id_pesanan,
            id_pelanggan,
            status_chat,
            created_at,
            ended_at
         FROM chat_session
         WHERE id_pesanan = ?
           AND id_pelanggan = ?
         LIMIT 1"
    );

    if (!$stmt) {
        die("Chat pesanan tidak dapat dimuat.");
    }

    mysqli_stmt_bind_param($stmt, "ii", $id_pesanan, $id_pelanggan);
    mysqli_stmt_execute($stmt);

    $result_chat = mysqli_stmt_get_result($stmt);
    $chat = mysqli_fetch_assoc($result_chat);

    mysqli_stmt_close($stmt);
}

$id_chat = (int) ($chat['id_chat'] ?? 0);

/* TUTUP CHAT JIKA PESANAN SUDAH SELESAI */
if ($chat && !$chat_aktif && $chat['status_chat'] !== 'Selesai') {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE chat_session
         SET status_chat = 'Selesai',
             ended_at = NOW()
         WHERE id_chat = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_chat);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $chat['status_chat'] = 'Selesai';
}

/* CHAT HANYA AKTIF JIKA PESANAN AKTIF DAN SESSION AKTIF */
$chat_bisa_dipakai = $chat_aktif
    && $id_chat > 0
    && ($chat['status_chat'] ?? '') === 'Aktif';

$status_chat_text = $chat_bisa_dipakai
    ? 'Chat Aktif'
    : 'Chat Selesai';

$status_chat_class = $chat_bisa_dipakai
    ? 'chat-active'
    : 'chat-ended';

function chatPesananEscape($value): string
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

<title>Live Chat Pesanan | Toko ATK &amp; Alat Jahit</title>

<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0;width:100%;max-width:100%;overflow-x:hidden}
body{min-width:0;background:#fff}
.section{width:100%;max-width:1200px;margin:0 auto;padding:35px 20px 50px}
.chat-page{width:100%;max-width:900px;margin:0 auto;min-width:0}
.chat-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:20px}
.chat-header-left{min-width:0;flex:1}
.chat-header-left h1{color:#102a43;margin:0 0 7px;font-size:25px;line-height:1.35;overflow-wrap:anywhere}
.chat-header-left p{color:#627d98;font-size:13px;line-height:1.6;margin:0}
.chat-header-right{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;min-width:0}
.chat-order-number{max-width:100%;padding:9px 12px;background:#f4f7fa;border:1px solid #e1e7ec;border-radius:8px;color:#102a43;font-size:12px;font-weight:700;overflow-wrap:anywhere}
.chat-status{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.chat-active{background:#e8f8ef;color:#28734a}
.chat-ended{background:#fde8e8;color:#b83232}
.chat-card{width:100%;min-width:0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.chat-info{padding:15px 20px;background:#f7f9fb;border-bottom:1px solid #e5e7eb;color:#627d98;font-size:12px;line-height:1.8;overflow-wrap:anywhere}
.chat-info strong{color:#102a43}
.chat-box{height:500px;min-height:280px;max-height:65vh;padding:20px;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;background:#f8fafc;scrollbar-width:thin;scrollbar-color:#cbd5df transparent}
.chat-loading,.chat-empty{text-align:center;color:#829ab1;font-size:13px;line-height:1.7;padding:30px 16px;overflow-wrap:anywhere}
.chat-empty{padding-top:100px;padding-bottom:100px}
.chat-message{display:flex;width:100%;margin-bottom:14px;min-width:0}
.chat-message.customer{justify-content:flex-end}
.chat-message.admin{justify-content:flex-start}
.chat-bubble{width:fit-content;max-width:75%;min-width:0;padding:11px 14px;border-radius:13px;font-size:13px;line-height:1.6;overflow-wrap:anywhere;word-break:break-word;white-space:normal}
.chat-message.customer .chat-bubble{background:#102a43;color:#fff;border-bottom-right-radius:4px}
.chat-message.admin .chat-bubble{background:#fff;color:#334e68;border:1px solid #e1e7ec;border-bottom-left-radius:4px}
.chat-sender{display:block;font-size:10px;font-weight:700;margin-bottom:4px;opacity:.8}
.chat-text{white-space:pre-wrap;overflow-wrap:anywhere}
.chat-time{display:block;margin-top:6px;font-size:9px;opacity:.7;text-align:right;overflow-wrap:anywhere}
.chat-image-link{display:block;margin-top:5px;max-width:320px}
.chat-image{display:block;width:100%;max-height:300px;object-fit:contain;border-radius:8px;background:#eef2f6}
.chat-image:first-child{margin-top:0}
.chat-form{padding:12px;border-top:1px solid #e5e7eb;background:#fff;min-width:0}
.chat-compose{display:flex;align-items:flex-end;gap:9px;min-width:0}
.chat-input{flex:1;width:100%;min-width:0;min-height:44px;max-height:140px;resize:vertical;padding:12px;border:1px solid #d8e0e7;border-radius:10px;outline:none;font:inherit;font-size:13px;color:#334e68;background:#fff;line-height:1.5}
.chat-input:focus{border-color:#102a43;box-shadow:0 0 0 2px rgba(16,42,67,.08)}
.chat-input:disabled{background:#f5f7f9}
.chat-attach{flex:0 0 43px;width:43px;height:43px;display:flex;align-items:center;justify-content:center;border:1px solid #d8e0e7;border-radius:10px;background:#fff;color:#334e68;cursor:pointer;font-size:21px}
.chat-attach:hover{background:#f2f5f8}
.chat-attach:disabled{opacity:.45;cursor:not-allowed}
.chat-send{flex:0 0 auto;height:44px;min-width:76px;padding:0 17px;border:none;border-radius:9px;background:#102a43;color:#fff;font-size:12px;font-weight:700;cursor:pointer;transition:background .2s}
.chat-send:hover{background:#183b56}
.chat-send:disabled{background:#aebbc7;cursor:not-allowed}
.chat-hint{padding:7px 3px 0;color:#8a9bab;font-size:10px;line-height:1.5}
.chat-preview{display:none;align-items:center;gap:10px;padding:10px 3px 12px}
.chat-preview.visible{display:flex}
.chat-preview img{width:65px;height:65px;object-fit:cover;border-radius:8px;border:1px solid #e1e7ec;background:#f5f7fa}
.chat-preview-info{min-width:0;flex:1}
.chat-preview-name{font-size:11px;color:#334e68;overflow-wrap:anywhere}
.chat-preview-size{font-size:10px;color:#829ab1;margin-top:4px}
.chat-remove-image{border:0;background:#fde8e8;color:#b83232;border-radius:7px;width:31px;height:31px;cursor:pointer;font-size:18px}
.chat-message-status{min-height:0;color:#b83232;font-size:11px;padding:0 3px}
.chat-message-status:not(:empty){padding-top:8px}
.chat-ended-box{padding:16px;background:#f7f9fb;border-top:1px solid #e5e7eb;text-align:center;color:#829ab1;font-size:12px;line-height:1.7;overflow-wrap:anywhere}
.chat-ended-box strong{color:#627d98}
.btn-back-chat{display:inline-flex;align-items:center;justify-content:center;max-width:100%;margin-top:18px;min-height:40px;padding:10px 15px;background:#eef1f5;color:#102a43;text-decoration:none;border-radius:8px;font-size:12px;font-weight:700;text-align:center;line-height:1.5}
.btn-back-chat:hover{background:#e1e7ec}
@media(max-width:700px){
    .section{padding:26px 15px 38px}
    .chat-header{flex-direction:column;gap:13px}
    .chat-header-left h1{font-size:23px}
    .chat-header-right{width:100%;justify-content:flex-start}
    .chat-box{height:450px;max-height:58vh;min-height:270px;padding:14px}
    .chat-bubble{max-width:85%;padding:10px 12px}
    .chat-form{padding:10px}
    .chat-input{font-size:13px}
    .chat-info{padding:13px 15px}
}
@media(max-width:480px){
    .section{padding:22px 12px 32px}
    .chat-header{margin-bottom:16px}
    .chat-header-left h1{font-size:21px}
    .chat-header-left p{font-size:12px}
    .chat-header-right{gap:7px}
    .chat-order-number{font-size:11px;padding:8px 10px}
    .chat-status{font-size:10px;padding:8px 10px}
    .chat-card{border-radius:11px}
    .chat-info{font-size:11px;line-height:1.8;padding:12px}
    .chat-box{height:420px;min-height:250px;max-height:55vh;padding:11px}
    .chat-message{margin-bottom:12px}
    .chat-bubble{max-width:88%;padding:9px 11px;font-size:12px}
    .chat-form{padding:9px}
    .chat-compose{gap:6px}
    .chat-input{padding:10px;font-size:12px;min-height:42px}
    .chat-attach{width:39px;height:42px;flex-basis:39px;font-size:19px}
    .chat-send{height:42px;min-width:57px;padding:0 10px;font-size:11px}
    .chat-image-link{max-width:240px}
    .chat-image{max-height:230px}
    .chat-empty{padding:65px 12px;font-size:12px}
    .chat-ended-box{padding:14px 12px;font-size:11px}
    .btn-back-chat{width:100%;margin-top:14px;min-height:42px}
}
@media(max-width:350px){
    .section{padding-left:9px;padding-right:9px}
    .chat-header-left h1{font-size:19px}
    .chat-box{padding:9px}
    .chat-bubble{max-width:92%;font-size:12px;padding:8px 10px}
    .chat-form{padding:8px}
    .chat-input{padding:9px;font-size:12px}
    .chat-attach{width:35px;flex-basis:35px}
    .chat-send{min-width:51px;padding:0 8px}
}
</style>
</head>

<body>

<?php require_once __DIR__ . "/includes/navbar.php"; ?>

<main>
<section class="section">
<div class="chat-page">

    <div class="chat-header">
        <div class="chat-header-left">
            <h1>Live Chat Pesanan</h1>
            <p>Hubungi admin mengenai pesanan kamu.</p>
        </div>

        <div class="chat-header-right">
            <span class="chat-order-number">
                <?= chatPesananEscape($pesanan['nomor_pesanan']); ?>
            </span>

            <span class="chat-status <?= $status_chat_class; ?>">
                <?= chatPesananEscape($status_chat_text); ?>
            </span>
        </div>
    </div>

    <div class="chat-card">

        <div class="chat-info">
            Pesanan:
            <strong><?= chatPesananEscape($pesanan['nomor_pesanan']); ?></strong>
            &nbsp; • &nbsp;
            Status:
            <strong><?= chatPesananEscape($status_pesanan); ?></strong>
            <br>
            Gunakan chat ini untuk berkomunikasi dengan admin terkait pesanan.
        </div>

        <div class="chat-box" id="chatBox" aria-live="polite">
            <div class="chat-loading">Memuat pesan...</div>
        </div>

        <?php if ($chat_bisa_dipakai): ?>

        <form
            class="chat-form"
            id="chatForm"
            enctype="multipart/form-data"
            autocomplete="off"
        >
            <div class="chat-preview" id="chatPreview">
                <img id="chatPreviewImage" src="" alt="Pratinjau gambar">

                <div class="chat-preview-info">
                    <div class="chat-preview-name" id="chatPreviewName"></div>
                    <div class="chat-preview-size" id="chatPreviewSize"></div>
                </div>

                <button
                    type="button"
                    class="chat-remove-image"
                    id="removeImage"
                    aria-label="Hapus gambar"
                    title="Hapus gambar"
                >×</button>
            </div>

            <div class="chat-compose">
                <input
                    type="file"
                    id="chatImage"
                    name="gambar"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    hidden
                >

                <button
                    type="button"
                    class="chat-attach"
                    id="attachImage"
                    aria-label="Pilih gambar"
                    title="Kirim gambar"
                >＋</button>

                <textarea
                    name="pesan"
                    id="chatInput"
                    class="chat-input"
                    placeholder="Tulis pesan..."
                    maxlength="1000"
                    rows="1"
                    aria-label="Tulis pesan"
                ></textarea>

                <button
                    type="submit"
                    class="chat-send"
                    id="chatSend"
                    disabled
                >Kirim</button>
            </div>

            <div class="chat-hint">
                Enter untuk mengirim · Shift+Enter untuk baris baru · Gambar maksimal 5 MB
            </div>

            <div class="chat-message-status" id="chatMessageStatus" role="status"></div>
        </form>

        <?php else: ?>

        <div class="chat-ended-box">
            Chat sudah tidak aktif karena status pesanan adalah
            <strong><?= chatPesananEscape($status_pesanan); ?></strong>.
        </div>

        <?php endif; ?>

    </div>

    <a
        href="detail_pesanan.php?id=<?= $id_pesanan; ?>"
        class="btn-back-chat"
    >← Kembali ke Detail Pesanan</a>

</div>
</section>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<?php if ($chat_bisa_dipakai): ?>
<script>
(function () {
    const idPesanan = <?= (int) $id_pesanan; ?>;

    const chatBox = document.getElementById('chatBox');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatImage = document.getElementById('chatImage');
    const attachImage = document.getElementById('attachImage');
    const chatPreview = document.getElementById('chatPreview');
    const chatPreviewImage = document.getElementById('chatPreviewImage');
    const chatPreviewName = document.getElementById('chatPreviewName');
    const chatPreviewSize = document.getElementById('chatPreviewSize');
    const removeImage = document.getElementById('removeImage');
    const chatMessageStatus = document.getElementById('chatMessageStatus');

    let firstLoad = true;
    let lastMessageKey = '';
    let loading = false;
    let sending = false;
    let previewUrl = '';

    function setStatus(text, isError = false) {
        chatMessageStatus.textContent = text || '';
        chatMessageStatus.style.color = isError ? '#b83232' : '#627d98';
    }

    function selectedFile() {
        return chatImage.files && chatImage.files.length
            ? chatImage.files[0]
            : null;
    }

    function updateSendButton() {
        const hasText = chatInput.value.trim() !== '';
        const hasImage = !!selectedFile();

        chatSend.disabled = sending || (!hasText && !hasImage);
    }

    function updatePreview() {
        const file = selectedFile();

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = '';
        }

        if (!file) {
            chatPreview.classList.remove('visible');
            chatPreviewImage.removeAttribute('src');
            chatPreviewName.textContent = '';
            chatPreviewSize.textContent = '';
            updateSendButton();
            return;
        }

        previewUrl = URL.createObjectURL(file);
        chatPreviewImage.src = previewUrl;
        chatPreviewName.textContent = file.name;
        chatPreviewSize.textContent =
            (file.size / 1024 / 1024).toFixed(2) + ' MB';

        chatPreview.classList.add('visible');
        updateSendButton();
    }

    function clearSelectedImage() {
        chatImage.value = '';
        updatePreview();
    }

    function makeElement(tag, className, text) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null) {
            element.textContent = String(text);
        }

        return element;
    }

    function renderMessages(messages) {
        const shouldScroll = firstLoad
            || (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight) < 120;

        const fragment = document.createDocumentFragment();

        messages.forEach(function (message) {
            const isCustomer = message.pengirim === 'Pelanggan';

            const messageRow = makeElement(
                'div',
                'chat-message ' + (isCustomer ? 'customer' : 'admin')
            );

            const bubble = makeElement('div', 'chat-bubble');

            bubble.appendChild(
                makeElement(
                    'span',
                    'chat-sender',
                    isCustomer ? 'Kamu' : 'Admin'
                )
            );

            if (message.pesan) {
                bubble.appendChild(
                    makeElement('div', 'chat-text', message.pesan)
                );
            }

            if (message.gambar && message.gambar_url) {
                const imageLink = makeElement('a', 'chat-image-link');
                imageLink.href = message.gambar_url;
                imageLink.target = '_blank';
                imageLink.rel = 'noopener noreferrer';

                const image = makeElement('img', 'chat-image');
                image.src = message.gambar_url;
                image.alt = 'Gambar dalam percakapan';
                image.loading = 'lazy';

                image.onerror = function () {
                    imageLink.remove();
                };

                imageLink.appendChild(image);
                bubble.appendChild(imageLink);
            }

            bubble.appendChild(
                makeElement('span', 'chat-time', message.waktu || '')
            );

            messageRow.appendChild(bubble);
            fragment.appendChild(messageRow);
        });

        chatBox.replaceChildren(fragment);

        if (shouldScroll) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        firstLoad = false;
    }

    async function loadChat() {
        if (loading || document.visibilityState === 'hidden') {
            return;
        }

        loading = true;

        try {
            const response = await fetch(
                '../actions/chat_ambil.php?id_pesanan='
                    + encodeURIComponent(idPesanan),
                {
                    method: 'GET',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                if (firstLoad) {
                    chatBox.replaceChildren(
                        makeElement(
                            'div',
                            'chat-empty',
                            data.message || 'Pesan tidak dapat dimuat.'
                        )
                    );
                }
                return;
            }

            const messages = data.messages || [];

            const messageKey = JSON.stringify(
                messages.map(function (message) {
                    return [
                        message.id_message,
                        message.pengirim,
                        message.pesan,
                        message.gambar,
                        message.waktu
                    ];
                })
            );

            if (messageKey === lastMessageKey) {
                firstLoad = false;
                return;
            }

            if (messages.length === 0) {
                chatBox.replaceChildren(
                    makeElement(
                        'div',
                        'chat-empty',
                        'Belum ada pesan.\nSilakan mulai percakapan dengan admin.'
                    )
                );

                lastMessageKey = messageKey;
                firstLoad = false;
                return;
            }

            renderMessages(messages);
            lastMessageKey = messageKey;

        } catch (error) {
            if (firstLoad) {
                chatBox.replaceChildren(
                    makeElement(
                        'div',
                        'chat-empty',
                        'Gagal memuat chat. Periksa koneksi lalu coba lagi.'
                    )
                );
            }
        } finally {
            loading = false;
        }
    }

    attachImage.addEventListener('click', function () {
        chatImage.click();
    });

    chatImage.addEventListener('change', function () {
        setStatus('');

        const file = selectedFile();

        if (!file) {
            updatePreview();
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif'
        ];

        if (!allowedTypes.includes(file.type)) {
            clearSelectedImage();
            setStatus('Pilih gambar JPG, PNG, WEBP, atau GIF.', true);
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            clearSelectedImage();
            setStatus('Ukuran gambar maksimal 5 MB.', true);
            return;
        }

        updatePreview();
    });

    removeImage.addEventListener('click', function () {
        clearSelectedImage();
        setStatus('');
    });

    chatInput.addEventListener('input', updateSendButton);

    chatInput.addEventListener('keydown', function (event) {
        if (
            event.key === 'Enter'
            && !event.shiftKey
            && !event.isComposing
        ) {
            event.preventDefault();

            if (!chatSend.disabled) {
                chatForm.requestSubmit();
            }
        }
    });

    chatForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (sending) {
            return;
        }

        const pesan = chatInput.value.trim();
        const file = selectedFile();

        if (!pesan && !file) {
            return;
        }

        sending = true;
        updateSendButton();

        chatSend.textContent = '...';
        chatInput.disabled = true;
        attachImage.disabled = true;
        setStatus('Mengirim pesan...');

        const formData = new FormData();
        formData.append('id_pesanan', String(idPesanan));
        formData.append('pesan', pesan);

        if (file) {
            formData.append('gambar', file);
        }

        try {
            const response = await fetch(
                '../actions/chat_kirim.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Pesan gagal dikirim.'
                );
            }

            chatInput.value = '';
            clearSelectedImage();
            setStatus('Pesan terkirim.');

            await loadChat();

            chatBox.scrollTop = chatBox.scrollHeight;
            chatInput.focus();

        } catch (error) {
            setStatus(
                error.message || 'Terjadi kesalahan saat mengirim pesan.',
                true
            );
        } finally {
            sending = false;
            chatInput.disabled = false;
            attachImage.disabled = false;
            chatSend.textContent = 'Kirim';
            updateSendButton();
        }
    });

    updateSendButton();
    loadChat();

    /*
     * Hanya mengambil data pesan setiap 3 detik.
     * Tidak me-refresh halaman atau navbar.
     */
    const pollingTimer = setInterval(loadChat, 3000);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            loadChat();
        }
    });

    window.addEventListener('pagehide', function () {
        clearInterval(pollingTimer);

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
    });
})();
</script>
<?php endif; ?>

</body>
</html>
