<?php
// File: admin/ubah_password.php
// Deskripsi: Halaman ubah password untuk admin

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Proses ubah password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    $errors = array();
    
    // Validasi input kosong
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $errors[] = "Semua field harus diisi!";
    }
    
    // Validasi password baru minimal 6 karakter
    if (strlen($password_baru) < 6) {
        $errors[] = "Password baru minimal 6 karakter!";
    }
    
    // Validasi konfirmasi password
    if ($password_baru !== $konfirmasi_password) {
        $errors[] = "Password baru dan konfirmasi tidak sama!";
    }
    
    // Ambil password lama dari database
    $id_user = $_SESSION['user_id'];
    $query = "SELECT password FROM users WHERE id_user = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    // Verifikasi password lama
    if (!password_verify($password_lama, $user['password'])) {
        $errors[] = "Password lama tidak sesuai!";
    }
    
    // Jika tidak ada error, update password
    if (count($errors) == 0) {
        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
        
        $query_update = "UPDATE users SET password = ? WHERE id_user = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, "si", $hashed_password, $id_user);
        
        if (mysqli_stmt_execute($stmt_update)) {
            set_flash_message('success', 'Password berhasil diubah!');
            redirect('profil.php');
        } else {
            $errors[] = "Gagal mengubah password!";
        }
    }
}

// Ambil flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🗳️</div>
        <div>
            <div class="sidebar-title">Sistem Pemilu</div>
            <div class="sidebar-subtitle">Administrator</div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="sidebar-nav-item">
            <span class="sidebar-nav-icon">📊</span>
            Dashboard
        </a>
        <a href="pemilu.php" class="sidebar-nav-item">
            <span class="sidebar-nav-icon">📋</span>
            Pemilu
        </a>
        <a href="kandidat.php" class="sidebar-nav-item">
            <span class="sidebar-nav-icon">👥</span>
            Kandidat
        </a>
        <a href="pemilih.php" class="sidebar-nav-item">
            <span class="sidebar-nav-icon">🙋</span>
            Pemilih
        </a>
        <a href="hasil.php" class="sidebar-nav-item">
            <span class="sidebar-nav-icon">📈</span>
            Hasil
        </a>
        <a href="profil.php" class="sidebar-nav-item active">
            <span class="sidebar-nav-icon">⚙️</span>
            Profil
        </a>
    </nav>
    
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar">
                <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
            </div>
            <div>
                <div class="sidebar-user-name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="sidebar-logout">🚪 Logout</a>
    </div>
</aside>

<!-- Mobile Toggle -->
<button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Container -->
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">🔐 Ubah Password</h2>
                <a href="profil.php" class="btn btn-secondary">
                    ← Kembali ke Profil
                </a>
            </div>

            <?php if (isset($errors) && count($errors) > 0): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="password_lama">Password Lama <span>*</span></label>
                    <input type="password" id="password_lama" name="password_lama" required 
                           placeholder="Masukkan password lama">
                </div>

                <div class="form-group">
                    <label for="password_baru">Password Baru <span>*</span></label>
                    <input type="password" id="password_baru" name="password_baru" required 
                           placeholder="Masukkan password baru" minlength="6">
                    <div class="password-info">Minimal 6 karakter</div>
                </div>

                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password Baru <span>*</span></label>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" required 
                           placeholder="Ulangi password baru">
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        🔐 Ubah Password
                    </button>
                    <a href="profil.php" class="btn btn-secondary">
                        ❌ Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>