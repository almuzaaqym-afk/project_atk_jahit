
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../includes/hak_akses.php";

cekLoginAdmin();
cekAkses('cs');

$id_chat = (int) ($_GET['id'] ?? 0);

if ($id_chat <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        c.id_chat,
        c.id_pelanggan,
        c.status_chat,
        c.created_at,
        c.ended_at,
        p.nama,
        p.email
     FROM cs_chat c
     INNER JOIN pelanggan p ON p.id_pelanggan = c.id_pelanggan
     WHERE c.id_chat = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    exit("Gagal memuat percakapan.");
}

mysqli_stmt_bind_param($stmt, 'i', $id_chat);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$chat = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$chat) {
    header("Location: index.php");
    exit;
}

$nama_pelanggan = trim((string) ($chat['nama'] ?? 'Pelanggan'));

if ($nama_pelanggan === '') {
    $nama_pelanggan = 'Pelanggan';
}

$inisial = strtoupper(substr($nama_pelanggan, 0, 1));
$status_chat = (string) $chat['status_chat'];

function eChat($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$statusMap = [
    'Dilayani' => [
        'text' => 'Sedang Dilayani',
        'class' => 'status-serving'
    ],
    'Menunggu' => [
        'text' => 'Menunggu',
        'class' => 'status-waiting'
    ],
    'Selesai' => [
        'text' => 'Selesai',
        'class' => 'status-done'
    ]
];

$statusInfo = $statusMap[$status_chat] ?? $statusMap['Selesai'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#102f4b">
<title>Detail Percakapan | Toko ATK &amp; Alat Jahit</title>

<link rel="stylesheet" href="../assets/css/admin.css">

<style>
.cs-page{width:100%;max-width:1500px;margin:0 auto;min-width:0}
.cs-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:22px}
.cs-heading{display:flex;align-items:center;gap:12px;min-width:0}
.cs-back{width:41px;height:41px;min-width:41px;display:flex;align-items:center;justify-content:center;background:#edf2f7;color:#102f4b;border-radius:10px;text-decoration:none;font-size:20px}
.cs-heading h1{margin:0;color:#102f4b;font-size:23px}
.cs-heading p{margin:5px 0 0;color:#7890a4;font-size:12px}
.cs-header-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}
.cs-status{display:inline-flex;align-items:center;justify-content:center;padding:9px 13px;border-radius:22px;font-size:11px;font-weight:700}
.status-serving{background:#e3f5e9;color:#27804a}
.status-waiting{background:#fff4d6;color:#996900}
.status-done{background:#edf0f4;color:#627d98}
.cs-end-button{display:inline-flex;align-items:center;justify-content:center;min-height:39px;padding:9px 13px;border:1px solid #e6c6c6;border-radius:8px;background:#fff7f7;color:#b83232;font-size:11px;font-weight:700;cursor:pointer}
.cs-end-button:hover{background:#fde8e8}
.cs-end-button:disabled{opacity:.55;cursor:not-allowed}
.cs-layout{display:grid;grid-template-columns:minmax(230px,300px) minmax(0,1fr);align-items:start;gap:20px}
.cs-customer-card,.cs-chat-card{min-width:0;background:#fff;border:1px solid #e0e7ed;border-radius:14px;overflow:hidden;box-shadow:0 4px 15px rgba(16,47,75,.04)}
.cs-customer-card{padding:24px}
.cs-avatar{width:72px;height:72px;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;background:#102f4b;color:#fff;border-radius:50%;font-size:24px;font-weight:800}
.cs-name{text-align:center;color:#102f4b;font-size:16px;font-weight:800;overflow-wrap:anywhere}
.cs-email{text-align:center;color:#7890a4;font-size:12px;margin-top:6px;overflow-wrap:anywhere}
.cs-divider{height:1px;background:#e5ebf0;margin:22px 0}
.cs-info-row{margin-bottom:16px}
.cs-info-row:last-child{margin-bottom:0}
.cs-info-label{color:#8ba0b3;font-size:10px;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.cs-info-value{color:#102f4b;font-size:12px;font-weight:700;overflow-wrap:anywhere}
.cs-chat-top{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid #e5ebf0}
.cs-chat-title{color:#102f4b;font-size:15px;font-weight:800}
.cs-chat-id{color:#8ba0b3;font-size:11px}
.cs-messages{height:520px;min-height:280px;max-height:65vh;padding:18px;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;background:#f5f8fb}
.cs-loading,.cs-empty{text-align:center;color:#829ab1;font-size:13px;line-height:1.7;padding:60px 15px;white-space:pre-line;overflow-wrap:anywhere}
.cs-message{display:flex;min-width:0;margin-bottom:14px}
.cs-message.customer{justify-content:flex-start}
.cs-message.admin{justify-content:flex-end}
.cs-bubble{width:fit-content;max-width:78%;min-width:0;padding:11px 14px;border-radius:13px;font-size:13px;line-height:1.6;overflow-wrap:anywhere;word-break:break-word}
.cs-message.customer .cs-bubble{background:#fff;color:#334f66;border:1px solid #dfe6ec;border-bottom-left-radius:4px}
.cs-message.admin .cs-bubble{background:#102f4b;color:#fff;border-bottom-right-radius:4px}
.cs-sender{display:block;font-size:10px;font-weight:700;margin-bottom:4px;opacity:.8}
.cs-text{white-space:pre-wrap;overflow-wrap:anywhere}
.cs-time{display:block;margin-top:6px;font-size:9px;opacity:.7;text-align:right}
.cs-image-link{display:block;max-width:310px;margin-top:7px}
.cs-image{display:block;width:100%;max-height:300px;object-fit:contain;border-radius:8px;background:#eef2f6}
.cs-form{display:block;width:100%;padding:12px;border-top:1px solid #e5ebf0;background:#fff;min-width:0}
.cs-preview{display:none;align-items:center;gap:10px;padding:0 2px 12px}
.cs-preview.visible{display:flex}
.cs-preview img{width:62px;height:62px;object-fit:cover;border:1px solid #e1e7ec;border-radius:8px;background:#f4f7fa}
.cs-preview-info{flex:1;min-width:0}
.cs-preview-name{color:#334f66;font-size:11px;overflow-wrap:anywhere}
.cs-preview-size{color:#829ab1;font-size:10px;margin-top:4px}
.cs-remove-image{width:31px;height:31px;border:0;border-radius:7px;background:#fde8e8;color:#b83232;font-size:18px;cursor:pointer}
.cs-compose{display:flex;align-items:flex-end;gap:8px;width:100%;min-width:0}
.cs-input{display:block;flex:1 1 auto;width:0;min-width:0;min-height:46px;max-height:140px;padding:11px 13px;border:1px solid #d6e0e8;border-radius:9px;outline:none;color:#334f66;background:#fff;font-family:inherit;font-size:13px;line-height:1.5;resize:vertical;overflow-y:auto}
.cs-input:focus{border-color:#102f4b;box-shadow:0 0 0 2px rgba(16,47,75,.08)}
.cs-input:disabled{background:#f4f7fa}
.cs-attach{flex:0 0 42px;width:42px;height:46px;display:flex;align-items:center;justify-content:center;border:1px solid #d6e0e8;border-radius:9px;background:#fff;color:#334f66;font-size:21px;cursor:pointer}
.cs-attach:hover{background:#f4f7fa}
.cs-attach:disabled{opacity:.5;cursor:not-allowed}
.cs-send{flex:0 0 auto;min-width:68px;height:46px;padding:0 15px;border:0;border-radius:9px;background:#102f4b;color:#fff;font-size:12px;font-weight:700;cursor:pointer}
.cs-send:hover{background:#1c496b}
.cs-send:disabled{background:#9aabba;cursor:not-allowed}
.cs-hint{padding:7px 3px 0;color:#8a9bab;font-size:10px;line-height:1.5}
.cs-message-status{display:block;min-height:0;color:#627d98;font-size:11px;line-height:1.6;overflow-wrap:anywhere}
.cs-message-status:not(:empty){padding:8px 3px 0}
.cs-ended{padding:18px;border-top:1px solid #e5ebf0;background:#f8fafc;text-align:center;color:#7890a4;font-size:12px;line-height:1.7}
.cs-ended strong{color:#334f66}
.cs-back-bottom{display:inline-flex;align-items:center;justify-content:center;max-width:100%;margin-top:18px;padding:10px 15px;min-height:40px;background:#eef3f7;border:1px solid #dce5ec;color:#334f66;text-decoration:none;border-radius:8px;font-size:12px;font-weight:700}
@media(max-width:1000px){.cs-layout{grid-template-columns:minmax(210px,250px) minmax(0,1fr);gap:15px}.cs-customer-card{padding:19px}.cs-messages{height:460px}}
@media(max-width:760px){.cs-layout{grid-template-columns:1fr}.cs-header{align-items:flex-start}.cs-heading h1{font-size:20px}.cs-customer-card{width:100%}.cs-messages{height:420px;max-height:58vh;min-height:270px}}
@media(max-width:480px){.cs-header{align-items:flex-start}.cs-header-actions{justify-content:flex-start;width:100%;padding-left:52px}.cs-status{font-size:10px;padding:8px 10px}.cs-end-button{font-size:10px;padding:8px 10px}.cs-chat-top{padding:15px}.cs-messages{height:390px;padding:11px}.cs-bubble{max-width:89%;padding:9px 11px;font-size:12px}.cs-form{padding:9px}.cs-compose{gap:5px}.cs-input{min-height:43px;padding:9px;font-size:12px}.cs-attach{height:43px;width:36px;flex-basis:36px}.cs-send{height:43px;min-width:55px;padding:0 10px}.cs-image-link{max-width:235px}.cs-image{max-height:230px}.cs-back-bottom{display:flex;width:100%}}
</style>
</head>
<body>

<div class="admin-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="admin-main">
        <?php require_once __DIR__ . "/../includes/navbar.php"; ?>

        <section class="admin-content">
            <div class="cs-page">

                <div class="cs-header">
                    <div class="cs-heading">
                        <a href="index.php" class="cs-back" title="Kembali">←</a>
                        <div>
                            <h1>Detail Percakapan</h1>
                            <p>Percakapan Customer Service dengan pelanggan</p>
                        </div>
                    </div>

                    <div class="cs-header-actions">
                        <span
                            id="csStatusBadge"
                            class="cs-status <?= eChat($statusInfo['class']); ?>"
                        ><?= eChat($statusInfo['text']); ?></span>

                        <button
                            type="button"
                            id="btnSelesai"
                            class="cs-end-button"
                            style="display:<?= $status_chat === 'Dilayani' ? 'inline-flex' : 'none'; ?>"
                        >✓ Akhiri Percakapan</button>
                    </div>
                </div>

                <div class="cs-layout">

                    <aside class="cs-customer-card">
                        <div class="cs-avatar"><?= eChat($inisial); ?></div>
                        <div class="cs-name"><?= eChat($nama_pelanggan); ?></div>

                        <?php if (!empty($chat['email'])): ?>
                            <div class="cs-email"><?= eChat($chat['email']); ?></div>
                        <?php endif; ?>

                        <div class="cs-divider"></div>

                        <div class="cs-info-row">
                            <div class="cs-info-label">ID Chat</div>
                            <div class="cs-info-value">#<?= (int) $id_chat; ?></div>
                        </div>

                        <div class="cs-info-row">
                            <div class="cs-info-label">ID Pelanggan</div>
                            <div class="cs-info-value">#<?= (int) $chat['id_pelanggan']; ?></div>
                        </div>

                        <div class="cs-info-row">
                            <div class="cs-info-label">Mulai Chat</div>
                            <div class="cs-info-value">
                                <?= !empty($chat['created_at'])
                                    ? eChat(date('d/m/Y H:i', strtotime($chat['created_at'])))
                                    : '-'; ?>
                            </div>
                        </div>

                        <div class="cs-info-row">
                            <div class="cs-info-label">Status Percakapan</div>
                            <div class="cs-info-value" id="csStatusInfo">
                                <?= eChat($statusInfo['text']); ?>
                            </div>
                        </div>

                        <div class="cs-info-row">
                            <div class="cs-info-label">Selesai Pada</div>
                            <div class="cs-info-value" id="csEndedAt">
                                <?= !empty($chat['ended_at'])
                                    ? eChat(date('d/m/Y H:i', strtotime($chat['ended_at'])))
                                    : '-'; ?>
                            </div>
                        </div>
                    </aside>

                    <section class="cs-chat-card">
                        <div class="cs-chat-top">
                            <span class="cs-chat-title">Percakapan</span>
                            <span class="cs-chat-id">Chat #<?= (int) $id_chat; ?></span>
                        </div>

                        <div class="cs-messages" id="csMessages" aria-live="polite">
                            <div class="cs-loading">Memuat percakapan...</div>
                        </div>

                        <form
                            class="cs-form"
                            id="csForm"
                            enctype="multipart/form-data"
                            style="display:<?= $status_chat === 'Dilayani' ? 'block' : 'none'; ?>"
                        >
                            <div class="cs-preview" id="csPreview">
                                <img id="csPreviewImage" src="" alt="Pratinjau gambar">

                                <div class="cs-preview-info">
                                    <div class="cs-preview-name" id="csPreviewName"></div>
                                    <div class="cs-preview-size" id="csPreviewSize"></div>
                                </div>

                                <button
                                    type="button"
                                    class="cs-remove-image"
                                    id="csRemoveImage"
                                    aria-label="Hapus gambar"
                                >×</button>
                            </div>

                            <div class="cs-compose">
                                <input
                                    type="file"
                                    name="gambar"
                                    id="csImage"
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    hidden
                                >

                                <button
                                    type="button"
                                    class="cs-attach"
                                    id="csAttach"
                                    title="Kirim gambar"
                                    aria-label="Pilih gambar"
                                >＋</button>

                                <textarea
                                    class="cs-input"
                                    id="csInput"
                                    name="pesan"
                                    maxlength="10000"
                                    rows="1"
                                    placeholder="Tulis balasan kepada pelanggan..."
                                    aria-label="Tulis pesan"
                                ></textarea>

                                <button
                                    type="submit"
                                    class="cs-send"
                                    id="csSend"
                                    disabled
                                >Kirim</button>
                            </div>

                            <div class="cs-hint">
                                Enter untuk mengirim · Shift+Enter untuk baris baru · Gambar maksimal 5 MB
                            </div>

                            <div class="cs-message-status" id="csMessageStatus" role="status"></div>
                        </form>

                        <div
                            class="cs-ended"
                            id="csEnded"
                            style="display:<?= $status_chat === 'Dilayani' ? 'none' : 'block'; ?>"
                        >
                            <strong id="csEndedTitle">
                                <?= $status_chat === 'Menunggu'
                                    ? 'Chat sedang menunggu untuk dilayani.'
                                    : 'Percakapan sudah selesai.'; ?>
                            </strong>
                            <br>
                            Status akan diperbarui otomatis.
                        </div>
                    </section>

                </div>

                <a href="index.php" class="cs-back-bottom">← Kembali ke Customer Service</a>

            </div>
        </section>

        <?php require_once __DIR__ . "/../includes/footer.php"; ?>
    </main>
</div>

<script src="../assets/js/admin.js"></script>

<script>
(function () {
    const idChat = <?= (int) $id_chat; ?>;

    const messagesBox = document.getElementById('csMessages');
    const statusBadge = document.getElementById('csStatusBadge');
    const statusInfo = document.getElementById('csStatusInfo');
    const endedAt = document.getElementById('csEndedAt');
    const endedBox = document.getElementById('csEnded');
    const endedTitle = document.getElementById('csEndedTitle');

    const endButton = document.getElementById('btnSelesai');
    const form = document.getElementById('csForm');
    const input = document.getElementById('csInput');
    const sendButton = document.getElementById('csSend');
    const imageInput = document.getElementById('csImage');
    const attachButton = document.getElementById('csAttach');
    const preview = document.getElementById('csPreview');
    const previewImage = document.getElementById('csPreviewImage');
    const previewName = document.getElementById('csPreviewName');
    const previewSize = document.getElementById('csPreviewSize');
    const removeImageButton = document.getElementById('csRemoveImage');
    const messageStatus = document.getElementById('csMessageStatus');

    let currentStatus = <?= json_encode($status_chat); ?>;
    let loading = false;
    let sending = false;
    let ending = false;
    let firstLoad = true;
    let lastMessageKey = '';
    let previewUrl = '';
    let timer = null;

    function element(tag, className, text) {
        const node = document.createElement(tag);

        if (className) node.className = className;
        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }

        return node;
    }

    function setMessageStatus(text, isError = false) {
        messageStatus.textContent = text || '';
        messageStatus.style.color = isError ? '#b83232' : '#627d98';
    }

    function updateSendButton() {
        const file = imageInput.files && imageInput.files[0];

        sendButton.disabled = sending
            || currentStatus !== 'Dilayani'
            || (!input.value.trim() && !file);
    }

    function updateStatus(status, endTime = null) {
        currentStatus = status;

        const details = {
            Dilayani: {
                label: 'Sedang Dilayani',
                className: 'status-serving'
            },
            Menunggu: {
                label: 'Menunggu',
                className: 'status-waiting'
            },
            Selesai: {
                label: 'Selesai',
                className: 'status-done'
            }
        };

        const item = details[status] || details.Selesai;

        statusBadge.textContent = item.label;
        statusBadge.className = 'cs-status ' + item.className;
        statusInfo.textContent = item.label;

        const active = status === 'Dilayani';

        form.style.display = active ? 'block' : 'none';
        endButton.style.display = active ? 'inline-flex' : 'none';
        endedBox.style.display = active ? 'none' : 'block';

        if (status === 'Menunggu') {
            endedTitle.textContent = 'Chat sedang menunggu untuk dilayani.';
        } else if (status === 'Selesai') {
            endedTitle.textContent = 'Percakapan sudah selesai.';
        } else {
            endedTitle.textContent = '';
        }

        if (endTime) {
            endedAt.textContent = endTime;
        } else if (status === 'Selesai' && endedAt.textContent === '-') {
            endedAt.textContent = 'Baru saja';
        }

        updateSendButton();
    }

    function clearImage() {
        imageInput.value = '';

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = '';
        }

        preview.classList.remove('visible');
        previewImage.removeAttribute('src');
        previewName.textContent = '';
        previewSize.textContent = '';

        updateSendButton();
    }

    function renderMessages(messages) {
        const nearBottom =
            messagesBox.scrollHeight
            - messagesBox.scrollTop
            - messagesBox.clientHeight < 120;

        const shouldScroll = firstLoad || nearBottom;
        const fragment = document.createDocumentFragment();

        messages.forEach(function (message) {
            const adminMessage = message.pengirim === 'Admin';

            const row = element(
                'div',
                'cs-message ' + (adminMessage ? 'admin' : 'customer')
            );

            const bubble = element('div', 'cs-bubble');

            bubble.appendChild(
                element(
                    'span',
                    'cs-sender',
                    adminMessage ? 'Kamu / Admin' : 'Pelanggan'
                )
            );

            if (message.pesan) {
                bubble.appendChild(
                    element('div', 'cs-text', message.pesan)
                );
            }

            if (message.gambar_url) {
                const link = element('a', 'cs-image-link');
                link.href = message.gambar_url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';

                const image = element('img', 'cs-image');
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
                element('span', 'cs-time', message.waktu || '')
            );

            row.appendChild(bubble);
            fragment.appendChild(row);
        });

        messagesBox.replaceChildren(fragment);

        if (shouldScroll) {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }

        firstLoad = false;
    }

    async function loadMessages() {
        if (loading || document.visibilityState === 'hidden') {
            return;
        }

        loading = true;

        try {
            const response = await fetch(
                'ambil.php?id_chat=' + encodeURIComponent(idChat),
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
                    messagesBox.replaceChildren(
                        element(
                            'div',
                            'cs-empty',
                            data.message || 'Percakapan tidak dapat dimuat.'
                        )
                    );
                }
                return;
            }

            const chat = data.chat || {};
            const remoteStatus = chat.status_chat || currentStatus;

            if (remoteStatus !== currentStatus) {
                updateStatus(remoteStatus, chat.ended_at || null);
            }

            if (remoteStatus === 'Selesai') {
                stopPolling();
            }

            const messages = data.messages || [];

            const key = JSON.stringify(
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

            if (key === lastMessageKey) {
                firstLoad = false;
                return;
            }

            if (messages.length === 0) {
                messagesBox.replaceChildren(
                    element(
                        'div',
                        'cs-empty',
                        'Belum ada pesan dalam percakapan ini.'
                    )
                );

                lastMessageKey = key;
                firstLoad = false;
                return;
            }

            renderMessages(messages);
            lastMessageKey = key;

        } catch (error) {
            if (firstLoad) {
                messagesBox.replaceChildren(
                    element(
                        'div',
                        'cs-empty',
                        'Gagal memuat percakapan. Periksa koneksi lalu coba lagi.'
                    )
                );
            }
        } finally {
            loading = false;
        }
    }

    async function endConversation() {
        if (ending || currentStatus !== 'Dilayani') {
            return;
        }

        if (!confirm('Akhiri percakapan dengan pelanggan ini?')) {
            return;
        }

        ending = true;
        endButton.disabled = true;
        endButton.textContent = 'Memproses...';

        try {
            const formData = new FormData();
            formData.append('id_chat', String(idChat));

            const response = await fetch(
                'selesai.php',
                {
                    method: 'POST',
                    body: formData,
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Percakapan gagal diakhiri.'
                );
            }

            updateStatus('Selesai');
            setMessageStatus('');

            /*
             * Ambil pesan terakhir dan status baru,
             * tanpa me-refresh halaman admin.
             */
            await loadMessages();
            stopPolling();

        } catch (error) {
            alert(error.message || 'Tidak dapat mengakhiri percakapan.');
        } finally {
            ending = false;
            endButton.disabled = false;
            endButton.textContent = '✓ Akhiri Percakapan';
        }
    }

    function selectedFile() {
        return imageInput.files && imageInput.files.length
            ? imageInput.files[0]
            : null;
    }

    attachButton.addEventListener('click', function () {
        if (currentStatus === 'Dilayani') {
            imageInput.click();
        }
    });

    imageInput.addEventListener('change', function () {
        const file = selectedFile();
        setMessageStatus('');

        if (!file) {
            updateSendButton();
            return;
        }

        const validTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif'
        ];

        if (!validTypes.includes(file.type)) {
            clearImage();
            setMessageStatus(
                'Gunakan gambar JPG, PNG, WEBP, atau GIF.',
                true
            );
            return;
        }

        if (file.size <= 0 || file.size > 5 * 1024 * 1024) {
            clearImage();
            setMessageStatus('Ukuran gambar maksimal 5 MB.', true);
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        previewImage.src = previewUrl;
        previewName.textContent = file.name;
        previewSize.textContent =
            (file.size / 1024 / 1024).toFixed(2) + ' MB';

        preview.classList.add('visible');
        updateSendButton();
    });

    removeImageButton.addEventListener('click', clearImage);

    input.addEventListener('input', updateSendButton);

    input.addEventListener('keydown', function (event) {
        if (
            event.key === 'Enter'
            && !event.shiftKey
            && !event.isComposing
        ) {
            event.preventDefault();

            if (!sendButton.disabled) {
                form.requestSubmit();
            }
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (sending || currentStatus !== 'Dilayani') {
            return;
        }

        const text = input.value.trim();
        const file = selectedFile();

        if (!text && !file) {
            return;
        }

        sending = true;
        updateSendButton();

        sendButton.textContent = '...';
        input.disabled = true;
        attachButton.disabled = true;

        setMessageStatus('Mengirim pesan...');

        const formData = new FormData();
        formData.append('id_chat', String(idChat));
        formData.append('pesan', text);

        if (file) {
            formData.append('gambar', file);
        }

        try {
            const response = await fetch(
                'kirim.php',
                {
                    method: 'POST',
                    body: formData,
                    cache: 'no-store',
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

            input.value = '';
            clearImage();
            setMessageStatus('Pesan terkirim.');

            await loadMessages();
            messagesBox.scrollTop = messagesBox.scrollHeight;
            input.focus();

        } catch (error) {
            setMessageStatus(
                error.message || 'Terjadi kesalahan saat mengirim.',
                true
            );
        } finally {
            sending = false;
            input.disabled = false;
            attachButton.disabled = false;
            sendButton.textContent = 'Kirim';
            updateSendButton();
        }
    });

    endButton.addEventListener('click', endConversation);

    function startPolling() {
        if (timer) {
            clearInterval(timer);
        }

        loadMessages();
        timer = setInterval(loadMessages, 3000);
    }

    function stopPolling() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    updateStatus(currentStatus);
    updateSendButton();
    startPolling();

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            loadMessages();
        }
    });

    window.addEventListener('pagehide', function () {
        stopPolling();

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
    });
})();
</script>

</body>
</html>
