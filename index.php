<?php
// File: index.php
// Deskripsi: Landing page sistem pemilu

session_start();
require_once 'config/database.php';

// Jika sudah login, redirect ke dashboard
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pemilu - Beranda</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-page">
    <div class="landing-container">
        <h1>🗳️ Sistem Pemilu Elektronik</h1>
        <p>Platform pemungutan suara yang aman, transparan, dan mudah digunakan</p>
        
        <div class="btn-group">
            <a href="auth/login.php" class="btn-landing btn-login">Masuk</a>
            <a href="auth/register.php" class="btn-landing btn-register">Daftar</a>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <h3>🔒 Aman</h3>
                <p>Data terenkripsi dan terlindungi</p>
            </div>
            <div class="feature-card">
                <h3>⚡ Cepat</h3>
                <p>Proses voting yang mudah dan cepat</p>
            </div>
            <div class="feature-card">
                <h3>📊 Transparan</h3>
                <p>Hasil real-time dan akurat</p>
            </div>
        </div>
    </div>
</body>
</html>