<?php
// File: admin/edit_pemilu.php
// Deskripsi: Form untuk mengedit pemilu

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Cek apakah ada ID pemilu
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$id_election = clean_input($_GET['id']);

// Ambil data pemilu
$query = "SELECT * FROM elections WHERE id_election = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    set_flash_message('danger', 'Pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$pemilu = mysqli_fetch_assoc($result);

// Cek status pemilu
$status_info = get_election_status($id_election);
if ($status_info['status_real'] == 'selesai') {
    set_flash_message('danger', 'Pemilu yang sudah selesai tidak dapat diedit!');
    redirect('pemilu.php');
}

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
    
    // Jika pemilu sudah berlangsung, tidak bisa ubah tanggal mulai
    if ($status_info['status_real'] == 'berlangsung') {
        if ($tanggal_mulai != $pemilu['tanggal_mulai']) {
            $errors[] = "Tidak dapat mengubah tanggal mulai pada pemilu yang sedang berlangsung!";
        }
    }
    
    // Jika tidak ada error, update data
    if (count($errors) == 0) {
        
        $query_update = "UPDATE elections SET 
                         nama_pemilu = ?, 
                         deskripsi = ?, 
                         tanggal_mulai = ?, 
                         tanggal_selesai = ?, 
                         status = ? 
                         WHERE id_election = ?";
        
        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssssi", $nama_pemilu, $deskripsi, $tanggal_mulai, $tanggal_selesai, $status, $id_election);
        
        if (mysqli_stmt_execute($stmt_update)) {
            set_flash_message('success', 'Data pemilu berhasil diupdate!');
            redirect('pemilu.php');
        } else {
            $errors[] = "Gagal mengupdate data pemilu!";
        }
        
        mysqli_stmt_close($stmt_update);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pemilu - Sistem Pemilu</title>
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
                <h2 class="content-title">✏️ Edit Pemilu</h2>
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
                           value="<?php echo $pemilu['nama_pemilu']; ?>">
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4"><?php echo $pemilu['deskripsi']; ?></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal & Waktu Mulai <span>*</span></label>
                        <input type="datetime-local" id="tanggal_mulai" name="tanggal_mulai" required
                               value="<?php echo date('Y-m-d\TH:i', strtotime($pemilu['tanggal_mulai'])); ?>"
                               <?php echo ($status_info['status_real'] == 'berlangsung') ? 'readonly style="background-color: #f0f0f0;"' : ''; ?>>
                        <?php if ($status_info['status_real'] == 'berlangsung'): ?>
                            <div class="password-info">Tidak dapat mengubah tanggal mulai pada pemilu yang sedang berlangsung</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal & Waktu Selesai <span>*</span></label>
                        <input type="datetime-local" id="tanggal_selesai" name="tanggal_selesai" required
                               value="<?php echo date('Y-m-d\TH:i', strtotime($pemilu['tanggal_selesai'])); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status Pemilu <span>*</span></label>
                    <select id="status" name="status" required>
                        <option value="draft" <?php echo ($pemilu['status'] == 'draft') ? 'selected' : ''; ?>>
                            Draft (Belum Dipublikasikan)
                        </option>
                        <option value="aktif" <?php echo ($pemilu['status'] == 'aktif') ? 'selected' : ''; ?>>
                            Aktif (Dipublikasikan)
                        </option>
                    </select>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        💾 Update Pemilu
                    </button>
                    <a href="pemilu.php" class="btn btn-secondary">
                        ❌ Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>