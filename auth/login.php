<?php
// File: auth/login.php
// Deskripsi: Halaman login untuk user dan admin - REDESIGNED
session_start();
require_once '../config/database.php';
// Jika sudah login, redirect ke dashboard
if (is_logged_in()) {
    if (is_admin()) {
        redirect('../admin/index.php');
    } else {
        redirect('../user/index.php');
    }
}
// Ambil flash message jika ada
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body class="auth-page">
    <div class="auth-container login-container">
        <div class="auth-header">
            <div class="auth-logo">🗳️</div>
            <h2>Selamat Datang</h2>
            <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username atau Email</label>
                <input type="text"
                    id="username"
                    name="username"
                    required
                    placeholder="Masukkan username atau email"
                    autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Masukkan password">
            </div>

            <button type="submit" class="btn-submit">
                🔐 Masuk
            </button>
        </form>

        <div class="auth-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>

        <div class="back-link">
            <a href="../index.php">← Kembali ke beranda</a>
        </div>
    </div>
</body>

</html>