<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php"); exit();
}

$id   = (int) ($_GET['id'] ?? 0);
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$id' LIMIT 1"));
if (!$user) { header("Location: index.php"); exit(); }

$errors = $_SESSION['user_errors'] ?? [];
unset($_SESSION['user_errors']);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Edit Pengguna</title></head>
<body>
<h1>Edit Pengguna: <?= htmlspecialchars($user['name']); ?></h1>
<a href="index.php">← Kembali</a>

<?php if (!empty($errors)): ?>
<div style="background:#fdd;padding:10px;margin:10px 0;border:1px solid red;">
    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form action="process_edit.php" method="POST">
<input type="hidden" name="id" value="<?= $id; ?>">

<label>Nama Lengkap *</label><br>
<input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required><br><br>

<label>Username *</label><br>
<input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required><br><br>

<label>Email *</label><br>
<input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required><br><br>

<label>Password Baru (kosongkan jika tidak diubah)</label><br>
<input type="password" name="password"><br><br>

<label>No. Telepon</label><br>
<input type="text" name="no_telepon" value="<?= htmlspecialchars($user['no_telepon']); ?>"><br><br>

<label>Role *</label><br>
<select name="role">
    <?php foreach (['admin','owner','crew','client'] as $r): ?>
    <option value="<?= $r; ?>" <?= ($user['role']===$r)?'selected':''; ?>><?= ucfirst($r); ?></option>
    <?php endforeach; ?>
</select><br><br>

<button type="submit" style="padding:8px 20px;background:#FF6A34;color:#fff;border:none;cursor:pointer;">Update</button>
</form>
</body>
</html>
