<?php
// File: user/profil.php
// Deskripsi: Halaman profil user/pemilih

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

// Ambil data user dari database
$id_user = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id_user = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $email = clean_input($_POST['email']);
    $alamat = clean_input($_POST['alamat']);
    
    $errors = array();
    
    // Validasi input
    if (empty($nama_lengkap) || empty($email) || empty($alamat)) {
        $errors[] = "Semua field harus diisi!";
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    // Cek apakah email sudah digunakan user lain
    $check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email' AND id_user != '$id_user'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email sudah digunakan!";
    }
    
    // Jika tidak ada error, update data
    if (count($errors) == 0) {
        $query_update = "UPDATE users SET nama_lengkap = ?, email = ?, alamat = ? WHERE id_user = ?";
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssi", $nama_lengkap, $email, $alamat, $id_user);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['email'] = $email;
            set_flash_message('success', 'Profil berhasil diperbarui!');
            redirect('profil.php');
        } else {
            $errors[] = "Gagal memperbarui profil!";
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
    <title>Profil - Sistem Pemilu</title>
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
                <h2 class="content-title">👤 Profil Saya</h2>
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
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo $user['username']; ?>" disabled 
                           style="background-color: #f0f0f0; cursor: not-allowed;">
                    <div class="password-info">Username tidak dapat diubah</div>
                </div>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap <span>*</span></label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required 
                           value="<?php echo $user['nama_lengkap']; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo $user['email']; ?>">
                </div>

                <div class="form-group">
                    <label for="nik">NIK</label>
                    <input type="text" id="nik" value="<?php echo $user['nik']; ?>" disabled 
                           style="background-color: #f0f0f0; cursor: not-allowed;">
                    <div class="password-info">NIK tidak dapat diubah</div>
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" id="tanggal_lahir" value="<?php echo $user['tanggal_lahir']; ?>" disabled 
                           style="background-color: #f0f0f0; cursor: not-allowed;">
                    <div class="password-info">Tanggal lahir tidak dapat diubah</div>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat <span>*</span></label>
                    <textarea id="alamat" name="alamat" required><?php echo $user['alamat']; ?></textarea>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Perubahan
                    </button>
                    <a href="ubah_password.php" class="btn btn-secondary">
                        🔐 Ubah Password
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>