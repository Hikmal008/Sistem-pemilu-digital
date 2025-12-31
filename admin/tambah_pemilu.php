<?php
// File: admin/tambah_pemilu.php
// Deskripsi: Form untuk membuat pemilu baru

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nama_pemilu = clean_input($_POST['nama_pemilu']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $tanggal_mulai = clean_input($_POST['tanggal_mulai']);
    $tanggal_selesai = clean_input($_POST['tanggal_selesai']);
    $status = clean_input($_POST['status']);
    
    $errors = array();
    
    // Validasi input kosong
    if (empty($nama_pemilu) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
        $errors[] = "Nama pemilu, tanggal mulai, dan tanggal selesai harus diisi!";
    }
    
    // Validasi tanggal
    $start = strtotime($tanggal_mulai);
    $end = strtotime($tanggal_selesai);
    
    if ($end <= $start) {
        $errors[] = "Tanggal selesai harus lebih besar dari tanggal mulai!";
    }
    
    // Validasi durasi minimal 1 hari
    $durasi_hari = ($end - $start) / (60 * 60 * 24);
    if ($durasi_hari < 1) {
        $errors[] = "Durasi pemilu minimal 1 hari!";
    }
    
    // Jika tidak ada error, simpan data
    if (count($errors) == 0) {
        
        $created_by = $_SESSION['user_id'];
        
        $query = "INSERT INTO elections (nama_pemilu, deskripsi, tanggal_mulai, tanggal_selesai, status, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $nama_pemilu, $deskripsi, $tanggal_mulai, $tanggal_selesai, $status, $created_by);
        
        if (mysqli_stmt_execute($stmt)) {
            set_flash_message('success', 'Pemilu baru berhasil dibuat!');
            redirect('pemilu.php');
        } else {
            $errors[] = "Gagal menyimpan data pemilu!";
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pemilu - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            🗳️ Sistem Pemilu - Admin
        </div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="pemilu.php" class="active">Pemilu</a>
            <a href="kandidat.php">Kandidat</a>
            <a href="pemilih.php">Pemilih</a>
            <a href="hasil.php">Hasil</a>
            <a href="profil.php">Profil</a>
            <a href="logout.php" style="background-color: rgba(255,255,255,0.2);">Logout</a>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </nav>

    <!-- Container -->
    <div class="container">
        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">➕ Buat Pemilu Baru</h2>
                <a href="pemilu.php" class="btn btn-secondary">
                    ← Kembali
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
                    <label for="nama_pemilu">Nama Pemilu <span>*</span></label>
                    <input type="text" id="nama_pemilu" name="nama_pemilu" required 
                           placeholder="Contoh: Pemilu Umum 2025"
                           value="<?php echo isset($_POST['nama_pemilu']) ? $_POST['nama_pemilu'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4"
                              placeholder="Deskripsi singkat tentang pemilu ini"><?php echo isset($_POST['deskripsi']) ? $_POST['deskripsi'] : ''; ?></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal & Waktu Mulai <span>*</span></label>
                        <input type="datetime-local" id="tanggal_mulai" name="tanggal_mulai" required
                               value="<?php echo isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal & Waktu Selesai <span>*</span></label>
                        <input type="datetime-local" id="tanggal_selesai" name="tanggal_selesai" required
                               value="<?php echo isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status Pemilu <span>*</span></label>
                    <select id="status" name="status" required>
                        <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>
                            Draft (Belum Dipublikasikan)
                        </option>
                        <option value="aktif" <?php echo (isset($_POST['status']) && $_POST['status'] == 'aktif') ? 'selected' : ''; ?>>
                            Aktif (Dipublikasikan)
                        </option>
                    </select>
                    <div class="password-info">
                        <strong>Draft:</strong> Pemilu belum terlihat oleh pemilih<br>
                        <strong>Aktif:</strong> Pemilu akan otomatis berjalan sesuai jadwal
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Pemilu
                    </button>
                    <a href="pemilu.php" class="btn btn-secondary">
                        ❌ Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="content-card" style="margin-top: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h3 style="margin-top: 0;">ℹ️ Informasi Penting</h3>
            <ul style="line-height: 2;">
                <li>Pemilu dengan status <strong>Draft</strong> tidak akan terlihat oleh pemilih</li>
                <li>Pemilu dengan status <strong>Aktif</strong> akan otomatis berjalan sesuai jadwal yang ditentukan</li>
                <li>Sistem akan otomatis mengubah status pemilu menjadi <strong>Selesai</strong> ketika waktu berakhir</li>
                <li>Pastikan tanggal dan waktu sudah benar sebelum menyimpan</li>
                <li>Setelah pemilu dibuat, Anda dapat menambahkan kandidat untuk pemilu tersebut</li>
            </ul>
        </div>
    </div>
</body>
</html>