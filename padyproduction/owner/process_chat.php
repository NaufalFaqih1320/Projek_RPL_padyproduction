<?php

/**
 * owner/process_chat.php
 * Memproses pengiriman pesan dari owner ATAU dari user/client.
 * Dipanggil oleh: owner/chat.php dan user/chat.php
 */

session_start();
require_once("../config/database.php");
require_once("../config/helpers.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../auth/login.php");
    exit();
}

$sender_id   = (int) $_SESSION['id'];
$receiver_id = (int) ($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

if ($receiver_id <= 0 || empty($message)) {
    // Kembalikan ke halaman sebelumnya
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../auth/login.php'));
    exit();
}

// Pastikan receiver ada di database
$cek = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM users WHERE id='$receiver_id' LIMIT 1"
));
if (!$cek) {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../auth/login.php'));
    exit();
}

$message_esc = mysqli_real_escape_string($conn, $message);

mysqli_query($conn,
    "INSERT INTO chats (sender_id, receiver_id, message)
     VALUES ('$sender_id', '$receiver_id', '$message_esc')"
);

// Cek apakah chatbot bisa jawab (hanya untuk pesan dari client ke owner)
$sender_role = $_SESSION['role'];
if (in_array($sender_role, ['client', 'user'])) {
    $bot_reply = chatbotReply($conn, $message);
    if ($bot_reply) {
        // Ambil id owner sebagai sender bot
        $owner = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM users WHERE role='owner' ORDER BY id LIMIT 1"
        ));
        if ($owner) {
            $bot_reply_esc = mysqli_real_escape_string($conn, $bot_reply);
            mysqli_query($conn,
                "INSERT INTO chats (sender_id, receiver_id, message)
                 VALUES ('{$owner['id']}', '$sender_id', '$bot_reply_esc')"
            );
        }
    } else {
        // Notifikasi ke owner bahwa ada pesan baru
        $owner_q = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM users WHERE role='owner' ORDER BY id LIMIT 1"
        ));
        if ($owner_q) {
            $sender_name = htmlspecialchars($_SESSION['name']);
            insertNotification($conn, (int)$owner_q['id'],
                "Pesan Baru dari $sender_name",
                substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '')
            );
        }
    }
}

// Redirect balik ke halaman chat pengirim
$back = ($_SERVER['HTTP_REFERER'] ?? '');
if (empty($back)) {
    if (in_array($sender_role, ['client', 'user'])) {
        $back = '../user/chat.php';
    } else {
        $back = 'chat.php';
    }
}
header("Location: $back");
exit();
