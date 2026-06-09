<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$errors = $_SESSION['user_errors'] ?? [];
$old    = $_SESSION['user_old']    ?? [];
unset($_SESSION['user_errors'], $_SESSION['user_old']);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Tambah Pengguna</title></head>
<body>
<h1>Tambah Pengguna</h1>
<a href="index.php">← Kembali</a>

<?php if (!empty($errors)): ?>
<div style="background:#fdd;padding:10px;margin:10px 0;border:1px solid red;">
    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form action="process_add.php" method="POST">
<label>Nama Lengkap *</label><br>
<input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? ''); ?>" required><br><br>

<label>Username *</label><br>
<input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? ''); ?>" required><br><br>

<label>Email *</label><br>
<input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? ''); ?>" required><br><br>

<label>Password *</label><br>
<input type="password" name="password" required><br><br>

<label>No. Telepon</label><br>
<input type="text" name="no_telepon" value="<?= htmlspecialchars($old['no_telepon'] ?? ''); ?>"><br><br>

<label>Role *</label><br>
<select name="role" required>
    <option value="">-- Pilih Role --</option>
    <?php foreach (['admin','owner','crew','client'] as $r): ?>
    <option value="<?= $r; ?>" <?= (($old['role']??'')===$r)?'selected':''; ?>><?= ucfirst($r); ?></option>
    <?php endforeach; ?>
</select><br><br>

<button type="submit" style="padding:8px 20px;background:#FF6A34;color:#fff;border:none;cursor:pointer;">Simpan</button>
</form>
</body>
</html>
