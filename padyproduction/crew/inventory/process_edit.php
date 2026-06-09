<?php

require_once("../../config/database.php");

$id = $_POST['id'];
$category_id = $_POST['category_id'];
$item_name = $_POST['item_name'];
$quantity = $_POST['quantity'];
$unit = $_POST['unit'];
$condition_status = $_POST['condition_status'];

mysqli_query(
    $conn,
    "UPDATE inventory
    SET
        category_id='$category_id',
        item_name='$item_name',
        quantity='$quantity',
        unit='$unit',
        condition_status='$condition_status'
    WHERE id='$id'"
);

header("Location: index.php");
exit();