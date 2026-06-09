<?php

/**
 * owner/process_chat.php & user/process_chat.php (bisa dipakai keduanya)
 * Kirim pesan chat — dengan logika chatbot untuk pesan dari client ke owner
 */

session_start();
require_once("../config/database.php");
require_once("../config/auth.php");
require_once("../config/helpers.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: chat.php");
    exit();
}

$sender_id   = (int) $_SESSION['id'];
$receiver_id = (int) ($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

if ($receiver_id <= 0 || empty($message)) {
    header("Location: chat.php?with=$receiver_id");
    exit();
}

// Pastikan receiver ada
$receiver = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT id, role FROM users WHERE id='$receiver_id' LIMIT 1")
);
if (!$receiver) {
    header("Location: chat.php");
    exit();
}

$message_esc = mysqli_real_escape_string($conn, $message);

// Simpan pesan user/client ke owner
mysqli_query($conn,
    "INSERT INTO chats (sender_id, receiver_id, message)
     VALUES ('$sender_id', '$receiver_id', '$message_esc')"
);

// Jika pengirim adalah client dan penerima adalah owner,
// coba jawab otomatis dengan chatbot
if ($_SESSION['role'] === 'client') {
    // Cari owner (receiver_id sudah owner)
    $auto_reply = chatbotReply($conn, $message);

    if ($auto_reply !== null) {
        // Chatbot bisa menjawab — kirim balasan otomatis dari owner
        $reply_esc = mysqli_real_escape_string($conn, $auto_reply);
        mysqli_query($conn,
            "INSERT INTO chats (sender_id, receiver_id, message)
             VALUES ('$receiver_id', '$sender_id', '$reply_esc')"
        );
    } else {
        // Chatbot tidak bisa jawab — beri notifikasi ke owner agar follow-up
        insertNotification($conn, $receiver_id,
            "Pesan Baru dari {$_SESSION['name']}",
            "Ada pertanyaan baru yang perlu ditangani: \"" . mb_substr($message, 0, 80) . "...\""
        );
    }
}

// Jika pengirim owner, beri notifikasi ke receiver
if ($_SESSION['role'] === 'owner') {
    insertNotification($conn, $receiver_id,
        "Pesan dari Owner PADY Production",
        mb_substr($message, 0, 100)
    );
}

// Redirect kembali ke chat
$redirect = (in_array($_SESSION['role'], ['owner','admin']))
    ? "chat.php?with=$receiver_id"
    : "../user/chat.php?with=$receiver_id";

header("Location: $redirect");
exit();