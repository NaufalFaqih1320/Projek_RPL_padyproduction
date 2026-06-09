<?php

require_once("../../config/database.php");

$category_id = $_POST['category_id'];
$item_name = $_POST['item_name'];
$quantity = $_POST['quantity'];
$unit = $_POST['unit'];
$condition_status = $_POST['condition_status'];

mysqli_query(
    $conn,
    "INSERT INTO inventory (
        category_id,
        item_name,
        quantity,
        unit,
        condition_status
    )
    VALUES (
        '$category_id',
        '$item_name',
        '$quantity',
        '$unit',
        '$condition_status'
    )"
);

header("Location: index.php");
exit();