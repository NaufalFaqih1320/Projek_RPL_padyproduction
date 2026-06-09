<?php

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");

$id         = (int) $_POST['id'];
$booking_id = (int) $_POST['booking_id'];
$status     = mysqli_real_escape_string($conn, $_POST['status_cek']);

if (in_array($status, ['Belum','Siap','Kurang'])) {
    mysqli_query($conn,
        "UPDATE kebutuhan_dekorasi SET status_cek='$status' WHERE id='$id' AND booking_id='$booking_id'"
    );
}

header("Location: detail.php?id=$booking_id");
exit();
