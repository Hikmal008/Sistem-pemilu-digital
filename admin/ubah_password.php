<?php
// File: admin/ubah_password.php
// Deskripsi: Halaman ubah password admin (dengan sidebar)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

/* ================= PROSES FORM ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password_lama        = $_POST['password_lama'];
    $password_baru        = $_POST['password_baru'];
    $konfirmasi_password  = $_POST['konfirmasi_password'];

    $errors = [];

    /* ---------- VALIDASI ---------- */
    if (
        empty($password_lama) ||
        empty($password_baru) ||
        empty($konfirmasi_password)
    ) {
        $errors[] = "Semua field harus diisi!";
    }

    if (strlen($password_baru) < 6) {
        $errors[] = "Password baru minimal 6 karakter!";
    }

    if ($password_baru !== $konfirmasi_password) {
        $errors[] = "Password baru dan konfirmasi tidak sama!";
    }

    /* ---------- CEK PASSWORD LAMA ---------- */
    if (empty($errors)) {

        $id_user = $_SESSION['user_id'];

        $stmt = mysqli_prepare(
            $conn,
            "SELECT password FROM users WHERE id_user = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user || !password_verify($password_lama, $user['password'])) {
            $errors[] = "Password lama tidak sesuai!";
        }
    }

    /* ---------- UPDATE PASSWORD ---------- */
    if (empty($errors)) {

        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmt_update = mysqli_prepare(
            $conn,
            "UPDATE users SET password = ? WHERE id_user = ?"
        );
        mysqli_stmt_bind_param(
            $stmt_update,
            "si",
            $hashed_password,
            $id_user
        );

        if (mysqli_stmt_execute($stmt_update)) {
            set_flash_message('success', 'Password berhasil diubah!');
            redirect('profil.php');
        } else {
            set_flash_message('danger', 'Gagal mengubah password!');
        }
    } else {
        set_flash_message('danger', implode('<br>', $errors));
    }
}

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
                    <div class="sidebar-user-name">
                        <?php echo $_SESSION['nama_lengkap']; ?>
                    </div>
                    <div class="sidebar-user-role">Administrator</div>
                </div>
            </div>
            <a href="logout.php" class="sidebar-logout">Logout</a>
        </div>
    </aside>

    <!-- Mobile Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Header -->
        <div class="main-header">
            <h1 class="main-title">Ubah Password</h1>
            <a href="profil.php" class="btn btn-secondary">
                ← Kembali
            </a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <div class="content-card">

            <form action="" method="POST">

                <div class="form-group">
                    <label for="password_lama">Password Lama <span>*</span></label>
                    <input type="password" id="password_lama" name="password_lama" required
                        placeholder="Masukkan password lama">
                </div>

                <div class="form-group">
                    <label for="password_baru">Password Baru <span>*</span></label>
                    <input type="password" id="password_baru" name="password_baru" required minlength="6"
                        placeholder="Masukkan password baru">
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
