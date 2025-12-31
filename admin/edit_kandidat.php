<?php
// File: admin/edit_kandidat.php
// Deskripsi: Form edit data kandidat

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Cek apakah ada ID kandidat
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID kandidat tidak ditemukan!');
    redirect('kandidat.php');
}

$id_kandidat = clean_input($_GET['id']);

// Ambil data kandidat
$query = "SELECT * FROM kandidat WHERE id_kandidat = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_kandidat);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    set_flash_message('danger', 'Kandidat tidak ditemukan!');
    redirect('kandidat.php');
}

$kandidat = mysqli_fetch_assoc($result);

// TAMBAHAN: Ambil info pemilu
$query_pemilu = "SELECT nama_pemilu FROM elections WHERE id_election = ?";
$stmt_pemilu = mysqli_prepare($conn, $query_pemilu);
mysqli_stmt_bind_param($stmt_pemilu, "i", $kandidat['id_election']);
mysqli_stmt_execute($stmt_pemilu);
$pemilu_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pemilu));

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil dan bersihkan input
    $nomor_urut = clean_input($_POST['nomor_urut']);
    $nama_kandidat = clean_input($_POST['nama_kandidat']);
    $visi = clean_input($_POST['visi']);
    $misi = clean_input($_POST['misi']);
    
    // Array untuk menampung error
    $errors = array();
    
    // Validasi input kosong
    if (empty($nomor_urut) || empty($nama_kandidat) || empty($visi) || empty($misi)) {
        $errors[] = "Semua field harus diisi!";
    }
    
    // Validasi nomor urut adalah angka
    if (!is_numeric($nomor_urut) || $nomor_urut < 1) {
        $errors[] = "Nomor urut harus berupa angka positif!";
    }
    
    // Cek apakah nomor urut sudah ada (kecuali nomor urut kandidat yang sedang diedit)
    $check_nomor = mysqli_query($conn, "SELECT nomor_urut FROM kandidat 
                                        WHERE nomor_urut = '$nomor_urut' 
                                        AND id_kandidat != '$id_kandidat'");
    if (mysqli_num_rows($check_nomor) > 0) {
        $errors[] = "Nomor urut sudah digunakan!";
    }
    
    // Variabel untuk menyimpan nama foto
    $foto_name = $kandidat['foto']; // Default foto lama
    
    // Validasi upload foto (jika ada foto baru)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] != 4) {
        $foto = $_FILES['foto'];
        $foto_tmp = $foto['tmp_name'];
        $foto_size = $foto['size'];
        $foto_error = $foto['error'];
        
        // Ambil ekstensi file
        $foto_ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        
        // Ekstensi yang diizinkan
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
        
        // Validasi ekstensi
        if (!in_array($foto_ext, $allowed_ext)) {
            $errors[] = "Format foto harus JPG, JPEG, PNG, atau GIF!";
        }
        
        // Validasi ukuran file (max 2MB)
        if ($foto_size > 2097152) {
            $errors[] = "Ukuran foto maksimal 2MB!";
        }
        
        // Validasi error upload
        if ($foto_error !== 0) {
            $errors[] = "Terjadi error saat upload foto!";
        }
        
        // Jika validasi foto berhasil
        if (count($errors) == 0) {
            // Generate nama file unik
            $new_foto_name = uniqid('kandidat_', true) . '.' . $foto_ext;
            $foto_destination = '../assets/img/kandidat/' . $new_foto_name;
            
            // Upload foto baru
            if (move_uploaded_file($foto_tmp, $foto_destination)) {
                // Hapus foto lama jika bukan default.jpg
                if ($kandidat['foto'] != 'default.jpg' && file_exists('../assets/img/kandidat/' . $kandidat['foto'])) {
                    unlink('../assets/img/kandidat/' . $kandidat['foto']);
                }
                $foto_name = $new_foto_name;
            } else {
                $errors[] = "Gagal mengupload foto!";
            }
        }
    }
    
    // Jika tidak ada error, update data
    if (count($errors) == 0) {
        
        $query = "UPDATE kandidat SET 
                  nomor_urut = ?, 
                  nama_kandidat = ?, 
                  visi = ?, 
                  misi = ?, 
                  foto = ? 
                  WHERE id_kandidat = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssi", $nomor_urut, $nama_kandidat, $visi, $misi, $foto_name, $id_kandidat);
        
        if (mysqli_stmt_execute($stmt)) {
            set_flash_message('success', 'Data kandidat berhasil diupdate!');
            redirect('kandidat.php');
        } else {
            $errors[] = "Gagal mengupdate data kandidat!";
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
    <title>Edit Kandidat - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .preview-foto {
            margin-top: 10px;
            text-align: center;
        }
        .preview-foto img {
            max-width: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="admin-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            🗳️ Sistem Pemilu - Admin
        </div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="kandidat.php" class="active">Kandidat</a>
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
                <h2 class="content-title">Edit Kandidat</h2>
                <a href="kandidat.php" class="btn btn-secondary">
                    ← Kembali
                </a>
            </div>
            <div class="alert alert-info">
                📋 <strong>Pemilu:</strong> <?php echo $pemilu_info['nama_pemilu']; ?>
                <div class="password-info">Kandidat ini terdaftar untuk pemilu di atas</div>
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

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nomor_urut">Nomor Urut <span>*</span></label>
                    <input type="number" id="nomor_urut" name="nomor_urut" required 
                           placeholder="Masukkan nomor urut" min="1"
                           value="<?php echo $kandidat['nomor_urut']; ?>">
                </div>

                <div class="form-group">
                    <label for="nama_kandidat">Nama Kandidat <span>*</span></label>
                    <input type="text" id="nama_kandidat" name="nama_kandidat" required 
                           placeholder="Masukkan nama lengkap kandidat"
                           value="<?php echo $kandidat['nama_kandidat']; ?>">
                </div>

                <div class="form-group">
                    <label for="visi">Visi <span>*</span></label>
                    <textarea id="visi" name="visi" required 
                              placeholder="Masukkan visi kandidat"><?php echo $kandidat['visi']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="misi">Misi <span>*</span></label>
                    <textarea id="misi" name="misi" required 
                              placeholder="Masukkan misi kandidat"><?php echo $kandidat['misi']; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="foto">Foto Kandidat</label>
                    <input type="file" id="foto" name="foto" accept="image/*">
                    <div class="password-info">Kosongkan jika tidak ingin mengubah foto. Format: JPG, JPEG, PNG, GIF (Max: 2MB)</div>
                    
                    <div class="preview-foto">
                        <p><strong>Foto Saat Ini:</strong></p>
                        <img src="../assets/img/kandidat/<?php echo $kandidat['foto']; ?>" 
                             alt="<?php echo $kandidat['nama_kandidat']; ?>">
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        💾 Update Kandidat
                    </button>
                    <a href="kandidat.php" class="btn btn-secondary">
                        ❌ Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>