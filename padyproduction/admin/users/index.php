<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$filter_role = sanitize($conn, $_GET['role'] ?? '');
$search      = sanitize($conn, $_GET['search'] ?? '');

$where = "WHERE 1=1";
if ($filter_role) $where .= " AND role='$filter_role'";
if ($search)      $where .= " AND (name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";

$users = mysqli_query($conn, "SELECT * FROM users $where ORDER BY role, name");

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Kelola Pengguna</title></head>
<body>

<h1>Kelola Pengguna</h1>
<a href="../dashboard.php">← Dashboard</a>

<?= $flash; ?>

<a href="add.php">+ Tambah Pengguna</a>
<hr>

<form method="GET">
    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama / username / email...">
    <select name="role">
        <option value="">-- Semua Role --</option>
        <?php foreach (['admin','owner','crew','client'] as $r): ?>
        <option value="<?= $r; ?>" <?= ($filter_role===$r)?'selected':''; ?>><?= ucfirst($r); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
    <a href="index.php">Reset</a>
</form>
<hr>

<table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;">
<thead><tr><th>ID</th><th>Nama</th><th>Username</th><th>Email</th><th>Telepon</th><th>Role</th><th>Aksi</th></tr></thead>
<tbody>
<?php while ($u = mysqli_fetch_assoc($users)): ?>
<tr>
    <td><?= $u['id']; ?></td>
    <td><?= htmlspecialchars($u['name']); ?></td>
    <td><?= htmlspecialchars($u['username']); ?></td>
    <td><?= htmlspecialchars($u['email']); ?></td>
    <td><?= htmlspecialchars($u['no_telepon']); ?></td>
    <td><?= $u['role']; ?></td>
    <td>
        <a href="edit.php?id=<?= $u['id']; ?>">Edit</a> |
        <?php if ($u['id'] != $_SESSION['id']): ?>
        <a href="delete.php?id=<?= $u['id']; ?>"
           onclick="return confirm('Hapus pengguna <?= htmlspecialchars($u['name']); ?>?')">Hapus</a>
        <?php else: ?>
        <em>(Akun Anda)</em>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
