<?php
// File: user/ubah_password.php
// Deskripsi: Halaman ubah password untuk user/pemilih

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

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
    <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body class="user-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            🗳️ Sistem Pemilu - Pemilih
        </div>
        <div class="navbar-menu">
            <a href="index.php">Beranda</a>
            <a href="voting.php">Voting</a>
            <a href="hasil.php">Hasil</a>
            <a href="arsip.php">Arsip</a>
            <a href="profil.php" class="active">Profil</a>
            <a href="logout.php" style="background-color: rgba(255,255,255,0.2);">Logout</a>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                <div class="user-role">Pemilih</div>
            </div>
        </div>
    </nav>

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