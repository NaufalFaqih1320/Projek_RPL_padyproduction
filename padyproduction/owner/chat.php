<?php

/**
 * owner/chat.php
 * Halaman chat owner: lihat semua percakapan dengan client
 */

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

if ($_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

processReminders($conn); // jalankan reminder yang sudah waktunya

$owner_id = (int) $_SESSION['id'];

// Ambil daftar user yang pernah chat dengan owner ini
$contacts = mysqli_query($conn, "
    SELECT DISTINCT
        u.id, u.name, u.username, u.role,
        (SELECT message FROM chats
         WHERE (sender_id=u.id AND receiver_id='$owner_id')
            OR (sender_id='$owner_id' AND receiver_id=u.id)
         ORDER BY created_at DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM chats
         WHERE (sender_id=u.id AND receiver_id='$owner_id')
            OR (sender_id='$owner_id' AND receiver_id=u.id)
         ORDER BY created_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM chats
         WHERE sender_id=u.id AND receiver_id='$owner_id' AND is_read=0) AS unread_count
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT sender_id FROM chats WHERE receiver_id='$owner_id'
        UNION
        SELECT DISTINCT receiver_id FROM chats WHERE sender_id='$owner_id'
    )
    AND u.id != '$owner_id'
    ORDER BY last_time DESC
");

$selected_user_id = (int) ($_GET['with'] ?? 0);
$selected_user    = null;
$messages         = [];

if ($selected_user_id > 0) {
    $selected_user = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM users WHERE id='$selected_user_id' LIMIT 1")
    );

    if ($selected_user) {
        // Tandai pesan masuk sebagai sudah dibaca
        mysqli_query($conn, "
            UPDATE chats SET is_read=1
            WHERE sender_id='$selected_user_id' AND receiver_id='$owner_id' AND is_read=0
        ");

        // Ambil riwayat pesan
        $res = mysqli_query($conn, "
            SELECT c.*, u.name AS sender_name, u.role AS sender_role
            FROM chats c
            JOIN users u ON c.sender_id = u.id
            WHERE (c.sender_id='$owner_id' AND c.receiver_id='$selected_user_id')
               OR (c.sender_id='$selected_user_id' AND c.receiver_id='$owner_id')
            ORDER BY c.created_at ASC
            LIMIT 100
        ");
        while ($row = mysqli_fetch_assoc($res)) {
            $messages[] = $row;
        }
    }
}

$unread_notif = getUnreadNotificationCount($conn, $owner_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chat - PADY Production</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
.sidebar { width: 280px; background: #fff; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
.sidebar-header { padding: 16px; background: #1D1E20; color: #fff; }
.sidebar-header h2 { font-size: 16px; }
.contact-list { overflow-y: auto; flex: 1; }
.contact-item { display: block; padding: 14px 16px; border-bottom: 1px solid #f0f2f5; text-decoration: none; color: #333; transition: background .2s; }
.contact-item:hover, .contact-item.active { background: #f0f2f5; }
.contact-name { font-weight: bold; font-size: 14px; }
.contact-preview { font-size: 12px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.badge { background: #FF6A34; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 11px; float: right; }
.chat-area { flex: 1; display: flex; flex-direction: column; }
.chat-header { padding: 14px 20px; background: #fff; border-bottom: 1px solid #ddd; font-weight: bold; }
.chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
.msg { max-width: 65%; padding: 10px 14px; border-radius: 12px; font-size: 14px; line-height: 1.5; }
.msg.me { background: #FF6A34; color: #fff; align-self: flex-end; border-bottom-right-radius: 2px; }
.msg.them { background: #fff; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,.1); }
.msg .time { font-size: 10px; opacity: .7; margin-top: 4px; text-align: right; }
.msg .sender { font-size: 11px; font-weight: bold; margin-bottom: 2px; }
.chat-input { padding: 14px 20px; background: #fff; border-top: 1px solid #ddd; display: flex; gap: 10px; }
.chat-input input { flex: 1; padding: 10px 14px; border: 1px solid #ddd; border-radius: 24px; font-size: 14px; outline: none; }
.chat-input button { background: #FF6A34; color: #fff; border: none; border-radius: 24px; padding: 10px 20px; cursor: pointer; font-weight: bold; }
.no-chat { flex: 1; display: flex; align-items: center; justify-content: center; color: #999; font-size: 15px; }
.back-link { font-size: 13px; margin-bottom: 8px; display: inline-block; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" style="color:#fff;font-size:12px;text-decoration:none;">← Dashboard</a>
        <h2 style="margin-top:4px;">💬 Chat</h2>
    </div>
    <div class="contact-list">
        <?php if (mysqli_num_rows($contacts) === 0): ?>
        <div style="padding:20px;color:#999;font-size:13px;">Belum ada percakapan.</div>
        <?php endif; ?>
        <?php while ($c = mysqli_fetch_assoc($contacts)): ?>
        <a href="chat.php?with=<?= $c['id']; ?>"
           class="contact-item <?= ($selected_user_id == $c['id']) ? 'active' : ''; ?>">
            <?php if ($c['unread_count'] > 0): ?>
            <span class="badge"><?= $c['unread_count']; ?></span>
            <?php endif; ?>
            <div class="contact-name"><?= htmlspecialchars($c['name']); ?></div>
            <div class="contact-preview"><?= htmlspecialchars($c['last_message'] ?? ''); ?></div>
        </a>
        <?php endwhile; ?>
    </div>
</div>

<div class="chat-area">
    <?php if ($selected_user && $selected_user_id > 0): ?>
    <div class="chat-header">
        <?= htmlspecialchars($selected_user['name']); ?>
        <span style="font-size:12px;color:#999;font-weight:normal;"> (<?= $selected_user['role']; ?>)</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        <?php foreach ($messages as $m): ?>
        <div class="msg <?= ($m['sender_id'] == $owner_id) ? 'me' : 'them'; ?>">
            <?php if ($m['sender_id'] != $owner_id): ?>
            <div class="sender"><?= htmlspecialchars($m['sender_name']); ?></div>
            <?php endif; ?>
            <?= nl2br(htmlspecialchars($m['message'])); ?>
            <div class="time"><?= date('d/m H:i', strtotime($m['created_at'])); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <form class="chat-input" action="process_chat.php" method="POST">
        <input type="hidden" name="receiver_id" value="<?= $selected_user_id; ?>">
        <input type="text" name="message" placeholder="Ketik pesan..." required autocomplete="off">
        <button type="submit">Kirim</button>
    </form>
    <?php else: ?>
    <div class="no-chat">Pilih percakapan di sebelah kiri untuk mulai chat</div>
    <?php endif; ?>
</div>

<script>
// Auto-scroll ke bawah
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;
// Auto-refresh tiap 10 detik
setTimeout(() => location.reload(), 10000);
</script>
</body>
</html>