<?php

session_start();

if (isset($_SESSION['id'])) {
    $routes = [
        'admin'  => '../admin/dashboard.php',
        'owner'  => '../owner/dashboard.php',
        'crew'   => '../crew/dashboard.php',
        'client' => '../user/dashboard.php',
    ];
    header("Location: " . ($routes[$_SESSION['role']] ?? 'login.php'));
    exit();
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - PADY Production</title>
<style>
body { font-family: Arial, sans-serif; background: #1D1E20; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
.login-box { background: #fff; padding: 40px; border-radius: 12px; width: 360px; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
h2 { text-align: center; margin-bottom: 24px; color: #1D1E20; }
label { display: block; margin-bottom: 4px; font-weight: bold; font-size: 14px; }
input[type=text], input[type=password] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
button { width: 100%; padding: 12px; background: #FF6A34; color: #fff; border: none; border-radius: 100px; font-size: 16px; cursor: pointer; font-weight: bold; }
button:hover { background: #e05520; }
.error { background: #fdd; color: #900; padding: 10px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: center; }
.brand { text-align: center; margin-bottom: 8px; font-size: 22px; font-weight: bold; color: #FF6A34; }
</style>
</head>
<body>
<div class="login-box">
    <div class="brand">PADY Production</div>
    <h2>Masuk ke Sistem</h2>
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form action="process_login.php" method="POST">
        <label>Username</label>
        <input type="text" name="username" required autofocus placeholder="Masukkan username">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Masukkan password">
        <button type="submit">Masuk</button>
    </form>
</div>
</body>
</html>
