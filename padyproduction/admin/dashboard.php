<?php

require_once("../config/auth.php");
require_once("../config/database.php");
require_once("../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proses reminder yang jatuh tempo
processReminders($conn);

// Statistik ringkasan
$total_users     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))['n'];
$total_inventory = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM inventory"))['n'];
$total_bookings  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM bookings"))['n'];
$total_clients   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users WHERE role='client'"))['n'];

// Pengguna terbaru
$users_terbaru = mysqli_query($conn,
    "SELECT id, name, username, email, role, created_at
     FROM users ORDER BY created_at DESC LIMIT 5"
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin - PADY Production</title>
</head>
<body>

<h1>Dashboard Admin - PADY Production</h1>
<p>Login sebagai: <strong><?= htmlspecialchars($_SESSION['name']); ?></strong> (Admin)</p>

<nav>
    <a href="users/index.php">👤 Kelola Pengguna</a> |
    <a href="users/add.php">➕ Tambah Pengguna</a> |
    <a href="inventory/index.php">📦 Inventaris</a> |
    <a href="../auth/logout.php">🚪 Logout</a>
</nav>

<hr>

<?= $flash; ?>

<h2>Ringkasan Sistem</h2>
<table border="1" cellpadding="10" cellspacing="0">
<tr>
    <td><b>Total Pengguna</b><br><big><?= $total_users; ?></big></td>
    <td><b>Total Client</b><br><big><?= $total_clients; ?></big></td>
    <td><b>Total Inventaris</b><br><big><?= $total_inventory; ?></big></td>
    <td><b>Total Booking</b><br><big><?= $total_bookings; ?></big></td>
</tr>
</table>

<h2>Pengguna Terbaru</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">
<thead>
<tr>
    <th>ID</th><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Bergabung</th><th>Aksi</th>
</tr>
</thead>
<tbody>
<?php while ($u = mysqli_fetch_assoc($users_terbaru)): ?>
<tr>
    <td><?= $u['id']; ?></td>
    <td><?= htmlspecialchars($u['name']); ?></td>
    <td><?= htmlspecialchars($u['username']); ?></td>
    <td><?= htmlspecialchars($u['email']); ?></td>
    <td><?= $u['role']; ?></td>
    <td><?= date('d/m/Y', strtotime($u['created_at'])); ?></td>
    <td><a href="users/edit.php?id=<?= $u['id']; ?>">Edit</a></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
