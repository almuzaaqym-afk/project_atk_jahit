
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$id_admin = (int) $_SESSION['id_admin'];
$id_pesanan = (int) ($_GET['id'] ?? 0);

if ($id_pesanan <= 0) {
    header("Location: index.php?status=gagal");
    exit;
}

function adminChatEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
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
     LEFT JOIN pelanggan pl
        ON p.id_pelanggan = pl.id_pelanggan
     WHERE p.id_pesanan = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    header("Location: index.php?status=gagal");
    exit;
}

$id_pelanggan = (int) $pesanan['id_pelanggan'];
$status_pesanan = (string) $pesanan['status_pesanan'];

$chat_pesanan_aktif = in_array(
    $status_pesanan,
    ['Diproses', 'Siap Diambil'],
    true
);

/* CARI SESI CHAT */
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
    die("Gagal memuat sesi chat.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_pesanan,
    $id_pelanggan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* BUAT CHAT JIKA BELUM ADA */
if (!$chat && $chat_pesanan_aktif) {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO chat_session
            (id_pesanan, id_pelanggan, status_chat)
         VALUES (?, ?, 'Aktif')"
    );

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $id_pesanan,
            $id_pelanggan
        );

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

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $id_pesanan,
                $id_pelanggan
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $chat = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);
        }
    }
}

$id_chat = (int) ($chat['id_chat'] ?? 0);

/* TUTUP CHAT JIKA PESANAN SELESAI ATAU BATAL */
if (
    $chat
    && !$chat_pesanan_aktif
    && $chat['status_chat'] !== 'Selesai'
) {
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

/* STATUS CHAT */
$chat_bisa_dipakai = $chat_pesanan_aktif
    && $id_chat > 0
    && ($chat['status_chat'] ?? '') === 'Aktif';

$status_chat_text = $chat_bisa_dipakai
    ? 'Chat Aktif'
    : 'Chat Selesai';

$status_chat_class = $chat_bisa_dipakai
    ? 'chat-active'
    : 'chat-ended';

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#102f4b">

<title>Live Chat Pesanan | Admin</title>

<link rel="stylesheet" href="../assets/css/admin.css">

<style>
.chat-page{
    width:100%;
    max-width:1000px;
    margin:0 auto;
    min-width:0
}
.chat-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:22px
}
.chat-header-left{
    flex:1;
    min-width:0
}
.chat-header-left h1{
    margin:0 0 7px;
    color:#102f4b;
    font-size:27px;
    line-height:1.35;
    overflow-wrap:anywhere
}
.chat-header-left p{
    margin:0;
    color:#7890a4;
    font-size:13px;
    line-height:1.6
}
.chat-header-right{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    flex-wrap:wrap;
    gap:8px;
    min-width:0
}
.chat-order-number{
    max-width:100%;
    padding:9px 12px;
    background:#f4f7fa;
    border:1px solid #dce5ec;
    border-radius:8px;
    color:#102f4b;
    font-size:12px;
    font-weight:700;
    overflow-wrap:anywhere
}
.chat-status{
    padding:8px 12px;
    border-radius:20px;
    font-size:11px;
    font-weight:700
}
.chat-active{
    background:#e3f5e9;
    color:#27804a
}
.chat-ended{
    background:#fde7e7;
    color:#b23b3b
}
.chat-card{
    min-width:0;
    overflow:hidden;
    background:#fff;
    border:1px solid #e0e7ed;
    border-radius:14px;
    box-shadow:0 4px 15px rgba(16,47,75,.04)
}
.chat-info{
    padding:16px 19px;
    background:#f4f7fa;
    border-bottom:1px solid #e0e7ed;
    color:#7890a4;
    font-size:12px;
    line-height:1.8;
    overflow-wrap:anywhere
}
.chat-info strong{
    color:#102f4b
}
.chat-box{
    height:520px;
    max-height:65vh;
    min-height:280px;
    padding:18px;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
    background:#f8fafc
}
.chat-loading,.chat-empty{
    padding:30px 15px;
    text-align:center;
    color:#9aafbf;
    font-size:13px;
    line-height:1.7;
    white-space:pre-line;
    overflow-wrap:anywhere
}
.chat-empty{
    padding-top:100px;
    padding-bottom:100px
}
.chat-message{
    display:flex;
    width:100%;
    min-width:0;
    margin-bottom:14px
}
.chat-message.customer{
    justify-content:flex-start
}
.chat-message.admin{
    justify-content:flex-end
}
.chat-bubble{
    width:fit-content;
    max-width:75%;
    min-width:0;
    padding:11px 14px;
    border-radius:13px;
    font-size:13px;
    line-height:1.6;
    overflow-wrap:anywhere;
    word-break:break-word
}
.chat-message.customer .chat-bubble{
    background:#fff;
    color:#334f66;
    border:1px solid #dfe7ed;
    border-bottom-left-radius:4px
}
.chat-message.admin .chat-bubble{
    background:#102f4b;
    color:#fff;
    border-bottom-right-radius:4px
}
.chat-sender{
    display:block;
    margin-bottom:4px;
    font-size:10px;
    font-weight:700;
    opacity:.75
}
.chat-text{
    white-space:pre-wrap;
    overflow-wrap:anywhere
}
.chat-time{
    display:block;
    margin-top:6px;
    font-size:9px;
    opacity:.65;
    text-align:right
}
.chat-image-link{
    display:block;
    max-width:320px;
    margin-top:7px
}
.chat-image{
    display:block;
    width:100%;
    max-height:300px;
    object-fit:contain;
    border-radius:8px;
    background:#eef2f6
}
.chat-form{
    padding:13px;
    border-top:1px solid #e0e7ed;
    background:#fff;
    min-width:0
}
.chat-compose{
    display:flex;
    align-items:flex-end;
    gap:8px;
    min-width:0
}
.chat-input{
    flex:1;
    width:100%;
    min-width:0;
    min-height:44px;
    max-height:140px;
    resize:vertical;
    padding:11px 12px;
    border:1px solid #d8e0e7;
    border-radius:9px;
    outline:none;
    font:inherit;
    font-size:13px;
    line-height:1.5;
    color:#334f66;
    background:#fff
}
.chat-input:focus{
    border-color:#102f4b;
    box-shadow:0 0 0 2px rgba(16,47,75,.08)
}
.chat-input:disabled{
    background:#f4f7fa
}
.chat-attach{
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 43px;
    width:43px;
    height:43px;
    border:1px solid #d8e0e7;
    border-radius:9px;
    color:#334f66;
    background:#fff;
    font-size:21px;
    cursor:pointer
}
.chat-attach:hover{
    background:#f4f7fa
}
.chat-attach:disabled{
    opacity:.5;
    cursor:not-allowed
}
.chat-send{
    flex:0 0 auto;
    min-width:72px;
    height:44px;
    padding:0 16px;
    border:0;
    border-radius:9px;
    background:#102f4b;
    color:#fff;
    font-size:12px;
    font-weight:700;
    cursor:pointer
}
.chat-send:hover{
    background:#1c496b
}
.chat-send:disabled{
    background:#9aafbf;
    cursor:not-allowed
}
.chat-hint{
    padding:7px 2px 0;
    color:#8a9bab;
    font-size:10px;
    line-height:1.5
}
.chat-preview{
    display:none;
    align-items:center;
    gap:10px;
    padding:0 2px 12px
}
.chat-preview.visible{
    display:flex
}
.chat-preview img{
    width:62px;
    height:62px;
    border:1px solid #e1e7ec;
    border-radius:8px;
    object-fit:cover;
    background:#f4f7fa
}
.chat-preview-info{
    flex:1;
    min-width:0
}
.chat-preview-name{
    color:#334f66;
    font-size:11px;
    overflow-wrap:anywhere
}
.chat-preview-size{
    margin-top:4px;
    color:#829ab1;
    font-size:10px
}
.chat-remove-image{
    width:31px;
    height:31px;
    border:0;
    border-radius:7px;
    background:#fde8e8;
    color:#b83232;
    font-size:18px;
    cursor:pointer
}
.chat-message-status{
    min-height:0;
    color:#627d98;
    font-size:11px
}
.chat-message-status:not(:empty){
    padding:8px 2px 0
}
.chat-ended-box{
    padding:17px;
    background:#f4f7fa;
    border-top:1px solid #e0e7ed;
    color:#7890a4;
    text-align:center;
    font-size:12px;
    line-height:1.7;
    overflow-wrap:anywhere
}
.chat-ended-box strong{
    color:#334f66
}
.btn-back-chat{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-top:18px;
    max-width:100%;
    min-height:40px;
    padding:10px 15px;
    background:#eef3f7;
    border:1px solid #dce5ec;
    color:#334f66;
    text-decoration:none;
    border-radius:8px;
    font-size:12px;
    font-weight:700;
    text-align:center;
    line-height:1.5
}
.btn-back-chat:hover{
    background:#e1e9ef
}
@media(max-width:700px){
    .chat-header{
        flex-direction:column;
        gap:13px
    }
    .chat-header-left h1{
        font-size:23px
    }
    .chat-header-right{
        justify-content:flex-start;
        width:100%
    }
    .chat-box{
        height:450px;
        max-height:58vh;
        min-height:270px;
        padding:14px
    }
    .chat-bubble{
        max-width:85%;
        padding:10px 12px
    }
    .chat-form{
        padding:10px
    }
}
@media(max-width:480px){
    .chat-header-left h1{
        font-size:21px
    }
    .chat-header-left p{
        font-size:12px
    }
    .chat-box{
        height:420px;
        min-height:250px;
        max-height:55vh;
        padding:11px
    }
    .chat-bubble{
        max-width:88%;
        padding:9px 11px;
        font-size:12px
    }
    .chat-form{
        padding:9px
    }
    .chat-compose{
        gap:6px
    }
    .chat-input{
        min-height:42px;
        padding:9px;
        font-size:12px
    }
    .chat-attach{
        width:37px;
        height:42px;
        flex-basis:37px
    }
    .chat-send{
        height:42px;
        min-width:55px;
        padding:0 10px;
        font-size:11px
    }
    .chat-image-link{
        max-width:240px
    }
    .chat-image{
        max-height:230px
    }
    .btn-back-chat{
        display:flex;
        width:100%
    }
}
</style>
</head>

<body>
<div class="admin-layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="admin-main">

        <?php require_once __DIR__ . "/../includes/navbar.php"; ?>

        <section class="admin-content">
            <div class="chat-page">

                <div class="chat-header">
                    <div class="chat-header-left">
                        <h1>Live Chat Pesanan</h1>
                        <p>Komunikasi dengan pelanggan mengenai pesanan.</p>
                    </div>

                    <div class="chat-header-right">
                        <span class="chat-order-number">
                            <?= adminChatEscape($pesanan['nomor_pesanan']); ?>
                        </span>

                        <span class="chat-status <?= $status_chat_class; ?>">
                            <?= adminChatEscape($status_chat_text); ?>
                        </span>
                    </div>
                </div>

                <div class="chat-card">

                    <div class="chat-info">
                        Pelanggan:
                        <strong><?= adminChatEscape($pesanan['nama'] ?? '-'); ?></strong>

                        &nbsp; • &nbsp;

                        Pesanan:
                        <strong><?= adminChatEscape($pesanan['nomor_pesanan']); ?></strong>

                        &nbsp; • &nbsp;

                        Status:
                        <strong><?= adminChatEscape($status_pesanan); ?></strong>
                    </div>

                    <div class="chat-box" id="chatBox" aria-live="polite">
                        <div class="chat-loading">Memuat pesan...</div>
                    </div>

                    <?php if ($chat_bisa_dipakai): ?>

                    <form class="chat-form" id="chatForm" enctype="multipart/form-data" autocomplete="off">

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
                                name="gambar"
                                id="chatImage"
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
                                placeholder="Tulis balasan untuk pelanggan..."
                                maxlength="1000"
                                rows="1"
                                aria-label="Tulis balasan"
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
                        Chat ditutup karena pesanan sudah
                        <strong><?= adminChatEscape($status_pesanan); ?></strong>.
                    </div>

                    <?php endif; ?>

                </div>

                <a
                    href="detail.php?id=<?= $id_pesanan; ?>"
                    class="btn-back-chat"
                >← Kembali ke Detail Pesanan</a>

            </div>
        </section>

        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </main>
</div>

<script src="../assets/js/admin.js"></script>

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
        chatSend.disabled = sending
            || (chatInput.value.trim() === '' && !selectedFile());
    }

    function clearSelectedImage() {
        chatImage.value = '';

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = '';
        }

        chatPreview.classList.remove('visible');
        chatPreviewImage.removeAttribute('src');
        chatPreviewName.textContent = '';
        chatPreviewSize.textContent = '';

        updateSendButton();
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
        const nearBottom =
            chatBox.scrollHeight
            - chatBox.scrollTop
            - chatBox.clientHeight < 120;

        const shouldScroll = firstLoad || nearBottom;
        const fragment = document.createDocumentFragment();

        messages.forEach(function (message) {
            const isAdmin = message.pengirim === 'Admin';

            const row = makeElement(
                'div',
                'chat-message ' + (isAdmin ? 'admin' : 'customer')
            );

            const bubble = makeElement('div', 'chat-bubble');

            bubble.appendChild(
                makeElement(
                    'span',
                    'chat-sender',
                    isAdmin ? 'Kamu / Admin' : 'Pelanggan'
                )
            );

            if (message.pesan) {
                bubble.appendChild(
                    makeElement('div', 'chat-text', message.pesan)
                );
            }

            if (message.gambar && message.gambar_url) {
                const link = makeElement('a', 'chat-image-link');
                link.href = message.gambar_url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';

                const image = makeElement('img', 'chat-image');
                image.src = message.gambar_url;
                image.alt = 'Gambar percakapan';
                image.loading = 'lazy';

                image.onerror = function () {
                    link.remove();
                };

                link.appendChild(image);
                bubble.appendChild(link);
            }

            bubble.appendChild(
                makeElement('span', 'chat-time', message.waktu || '')
            );

            row.appendChild(bubble);
            fragment.appendChild(row);
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
                'chat_ambil.php?id_pesanan=' + encodeURIComponent(idPesanan),
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
                        'Belum ada pesan dari pelanggan.'
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
            updateSendButton();
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
            setStatus(
                'Pilih gambar JPG, PNG, WEBP, atau GIF.',
                true
            );
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            clearSelectedImage();
            setStatus('Ukuran gambar maksimal 5 MB.', true);
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        chatPreviewImage.src = previewUrl;
        chatPreviewName.textContent = file.name;
        chatPreviewSize.textContent =
            (file.size / 1024 / 1024).toFixed(2) + ' MB';

        chatPreview.classList.add('visible');
        updateSendButton();
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
                'chat_kirim.php',
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
                    data.message || 'Balasan gagal dikirim.'
                );
            }

            chatInput.value = '';
            clearSelectedImage();
            setStatus('Balasan terkirim.');

            await loadChat();

            chatBox.scrollTop = chatBox.scrollHeight;
            chatInput.focus();

        } catch (error) {
            setStatus(
                error.message || 'Terjadi kesalahan saat mengirim.',
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
