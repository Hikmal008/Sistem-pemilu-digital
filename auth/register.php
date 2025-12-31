<?php
// File: auth/register.php
// Deskripsi: Halaman registrasi pemilih baru

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
    <title>Registrasi - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-container register-container">
        <div class="auth-header">
            <h2>📝 Registrasi</h2>
            <p>Daftar sebagai pemilih baru</p>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>
        
        <form action="proses_register.php" method="POST">
            <div class="form-group">
                <label for="username">Username <span>*</span></label>
                <input type="text" id="username" name="username" required 
                       placeholder="Masukkan username" minlength="5">
            </div>
            
            <div class="form-group">
                <label for="email">Email <span>*</span></label>
                <input type="email" id="email" name="email" required 
                       placeholder="contoh@email.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password <span>*</span></label>
                <input type="password" id="password" name="password" required 
                       placeholder="Minimal 6 karakter" minlength="6">
                <div class="password-info">Minimal 6 karakter</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password <span>*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Ulangi password">
            </div>
            
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap <span>*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required 
                       placeholder="Nama lengkap sesuai KTP">
            </div>
            
            <div class="form-group">
                <label for="nik">NIK (Nomor Induk Kependudukan) <span>*</span></label>
                <input type="text" id="nik" name="nik" required 
                       placeholder="16 digit NIK" maxlength="16" pattern="[0-9]{16}">
            </div>
            
            <div class="form-group">
                <label for="tanggal_lahir">Tanggal Lahir <span>*</span></label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" required>
            </div>
            
            <div class="form-group">
                <label for="alamat">Alamat Lengkap <span>*</span></label>
                <textarea id="alamat" name="alamat" required 
                          placeholder="Masukkan alamat lengkap"></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Daftar Sekarang</button>
        </form>
        
        <div class="auth-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
        
        <div class="back-link">
            <a href="../index.php">← Kembali ke beranda</a>
        </div>
    </div>
</body>
</html>