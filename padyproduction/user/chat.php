<?php

/**
 * user/chat.php
 * Halaman chat untuk client — chat langsung ke owner
 */

session_start();
require_once("../config/database.php");
require_once("../config/helpers.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (!in_array($_SESSION['role'], ['client', 'user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['id'];

// Ambil owner aktif (ambil yang pertama, atau bisa pakai dropdown jika multi-owner)
$owner = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT id, name FROM users WHERE role='owner' ORDER BY id LIMIT 1")
);
if (!$owner) {
    die("<p>Belum ada owner yang terdaftar. Hubungi admin.</p>");
}
$owner_id = (int) $owner['id'];

// Tandai pesan dari owner sebagai sudah dibaca
mysqli_query($conn, "
    UPDATE chats SET is_read=1
    WHERE sender_id='$owner_id' AND receiver_id='$user_id' AND is_read=0
");

// Ambil riwayat pesan
$res = mysqli_query($conn, "
    SELECT c.*, u.name AS sender_name
    FROM chats c
    JOIN users u ON c.sender_id = u.id
    WHERE (c.sender_id='$user_id' AND c.receiver_id='$owner_id')
       OR (c.sender_id='$owner_id' AND c.receiver_id='$user_id')
    ORDER BY c.created_at ASC
    LIMIT 100
");
$messages = [];
while ($row = mysqli_fetch_assoc($res)) $messages[] = $row;

$unread_notif = getUnreadNotificationCount($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chat - PADY Production</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; flex-direction: column; height: 100vh; }
.chat-header { padding: 14px 20px; background: #1D1E20; color: #fff; display: flex; align-items: center; gap: 12px; }
.chat-header a { color: #ccc; font-size: 13px; text-decoration: none; }
.chat-header h2 { font-size: 16px; flex: 1; }
.chat-header .status { font-size: 12px; color: #aaa; }
.chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
.msg { max-width: 65%; padding: 10px 14px; border-radius: 12px; font-size: 14px; line-height: 1.5; }
.msg.me   { background: #FF6A34; color: #fff; align-self: flex-end; border-bottom-right-radius: 2px; }
.msg.them { background: #fff; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,.1); }
.msg .time { font-size: 10px; opacity: .7; margin-top: 4px; text-align: right; }
.msg .sender-tag { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #FF6A34; }
.chatbot-note { background: #fff3cd; padding: 8px 14px; font-size: 12px; color: #856404; border-radius: 8px; align-self: center; }
.chat-input { padding: 14px 20px; background: #fff; border-top: 1px solid #ddd; display: flex; gap: 10px; }
.chat-input input { flex: 1; padding: 10px 14px; border: 1px solid #ddd; border-radius: 24px; font-size: 14px; outline: none; }
.chat-input button { background: #FF6A34; color: #fff; border: none; border-radius: 24px; padding: 10px 20px; cursor: pointer; font-weight: bold; }
.faq-chips { padding: 8px 20px; background: #f8f9fa; display: flex; gap: 8px; flex-wrap: wrap; border-top: 1px solid #eee; }
.faq-chip { background: #fff; border: 1px solid #FF6A34; color: #FF6A34; padding: 4px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; }
.faq-chip:hover { background: #FF6A34; color: #fff; }
</style>
</head>
<body>

<div class="chat-header">
    <a href="dashboard.php">← Dashboard</a>
    <h2>💬 Chat dengan PADY Production</h2>
    <span class="status">Online</span>
    <?php if ($unread_notif > 0): ?>
    <a href="notification.php" style="background:#FF6A34;color:#fff;border-radius:50%;padding:2px 7px;font-size:12px;"><?= $unread_notif; ?></a>
    <?php endif; ?>
</div>

<div class="chat-messages" id="chatMessages">
    <?php if (empty($messages)): ?>
    <div class="chatbot-note">👋 Halo! Ada yang bisa kami bantu? Tanyakan seputar layanan, harga, jadwal, atau cara booking.</div>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
    <div class="msg <?= ($m['sender_id'] == $user_id) ? 'me' : 'them'; ?>">
        <?php if ($m['sender_id'] != $user_id): ?>
        <div class="sender-tag">PADY Production</div>
        <?php endif; ?>
        <?= nl2br(htmlspecialchars($m['message'])); ?>
        <div class="time"><?= date('d/m H:i', strtotime($m['created_at'])); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pertanyaan cepat (FAQ chips) -->
<div class="faq-chips">
    <span style="font-size:12px;color:#666;align-self:center;">Tanya cepat:</span>
    <button class="faq-chip" onclick="setMsg('Berapa harga sewa dekorasi?')">💰 Harga</button>
    <button class="faq-chip" onclick="setMsg('Bagaimana cara booking?')">📋 Cara Booking</button>
    <button class="faq-chip" onclick="setMsg('Apa saja layanan PADY Production?')">🎪 Layanan</button>
    <button class="faq-chip" onclick="setMsg('Bagaimana sistem pembayaran?')">💳 Pembayaran</button>
    <button class="faq-chip" onclick="setMsg('Apakah jadwal saya tersedia?')">📅 Cek Jadwal</button>
</div>

<form class="chat-input" action="../owner/process_chat.php" method="POST">
    <input type="hidden" name="receiver_id" value="<?= $owner_id; ?>">
    <input type="text" name="message" id="msgInput" placeholder="Ketik pesan Anda..." required autocomplete="off">
    <button type="submit">Kirim ➤</button>
</form>

<script>
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;
function setMsg(text) {
    document.getElementById('msgInput').value = text;
    document.getElementById('msgInput').focus();
}
// Auto-refresh tiap 8 detik
setTimeout(() => location.reload(), 8000);
</script>
</body>
</html>