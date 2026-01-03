<?php
// File: admin/profil.php
// Deskripsi: Profil admin dengan sidebar

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Ambil data admin dari database
$id_user = $_SESSION['user_id'];

$query = "SELECT * FROM users WHERE id_user = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = clean_input($_POST['nama_lengkap']);
    $email        = clean_input($_POST['email']);
    $alamat       = clean_input($_POST['alamat']);

    $errors = [];

    // Validasi input
    if (empty($nama_lengkap) || empty($email) || empty($alamat)) {
        $errors[] = "Semua field harus diisi!";
    }

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }

    // Cek email sudah dipakai user lain
    $query_email = "SELECT id_user FROM users WHERE email = ? AND id_user != ?";
    $stmt_email = mysqli_prepare($conn, $query_email);
    mysqli_stmt_bind_param($stmt_email, "si", $email, $id_user);
    mysqli_stmt_execute($stmt_email);
    $result_email = mysqli_stmt_get_result($stmt_email);

    if (mysqli_num_rows($result_email) > 0) {
        $errors[] = "Email sudah digunakan!";
    }

    // Jika tidak ada error → update
    if (count($errors) === 0) {
        $query_update = "
            UPDATE users 
            SET nama_lengkap = ?, email = ?, alamat = ?
            WHERE id_user = ?
        ";

        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param(
            $stmt_update,
            "sssi",
            $nama_lengkap,
            $email,
            $alamat,
            $id_user
        );

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
    <title>Profil Admin - Sistem Pemilu</title>
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

    <!-- Main Content -->
    <main class="main-content">

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="main-header">
            <h1 class="main-title">Profil Saya</h1>
        </div>

        <div class="content-card">
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
                        style="background-color: var(--gray-100); cursor: not-allowed;">
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
                        style="background-color: var(--gray-100); cursor: not-allowed;">
                    <div class="password-info">NIK tidak dapat diubah</div>
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" id="tanggal_lahir" value="<?php echo $user['tanggal_lahir']; ?>" disabled
                        style="background-color: var(--gray-100); cursor: not-allowed;">
                    <div class="password-info">Tanggal lahir tidak dapat diubah</div>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat <span>*</span></label>
                    <textarea id="alamat" name="alamat" required><?php echo $user['alamat']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" value="Administrator" disabled
                        style="background-color: var(--gray-100); cursor: not-allowed;">
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

    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.sidebar-toggle');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>

</html>