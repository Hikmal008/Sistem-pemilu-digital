<?php
// File: admin/edit_kandidat.php
// Form edit kandidat dengan sidebar

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

/* ================= CEK ID ================= */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID kandidat tidak ditemukan!');
    redirect('kandidat.php');
}

$id_kandidat = clean_input($_GET['id']);

/* ================= AMBIL DATA KANDIDAT ================= */
$query = "SELECT * FROM kandidat WHERE id_kandidat = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_kandidat);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    set_flash_message('danger', 'Kandidat tidak ditemukan!');
    redirect('kandidat.php');
}

$kandidat = mysqli_fetch_assoc($result);

/* ================= AMBIL INFO PEMILU ================= */
$query_pemilu = "SELECT nama_pemilu FROM elections WHERE id_election = ?";
$stmt_pemilu = mysqli_prepare($conn, $query_pemilu);
mysqli_stmt_bind_param($stmt_pemilu, "i", $kandidat['id_election']);
mysqli_stmt_execute($stmt_pemilu);
$pemilu_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pemilu));

/* ================= PROSES FORM ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nomor_urut    = clean_input($_POST['nomor_urut']);
    $nama_kandidat = clean_input($_POST['nama_kandidat']);
    $visi          = clean_input($_POST['visi']);
    $misi          = clean_input($_POST['misi']);

    $errors = [];

    /* ---------- VALIDASI ---------- */
    if (
        empty($nomor_urut) ||
        empty($nama_kandidat) ||
        empty($visi) ||
        empty($misi)
    ) {
        $errors[] = "Semua field harus diisi!";
    }

    if (!is_numeric($nomor_urut) || $nomor_urut < 1) {
        $errors[] = "Nomor urut harus berupa angka positif!";
    }

    /* ---------- CEK NOMOR URUT ---------- */
    $stmt_check = mysqli_prepare(
        $conn,
        "SELECT id_kandidat FROM kandidat 
         WHERE nomor_urut = ? 
         AND id_election = ? 
         AND id_kandidat != ?"
    );
    mysqli_stmt_bind_param(
        $stmt_check,
        "iii",
        $nomor_urut,
        $kandidat['id_election'],
        $id_kandidat
    );
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "Nomor urut sudah digunakan!";
    }

    /* ---------- FOTO ---------- */
    $foto_name = $kandidat['foto']; // default foto lama

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== 4) {

        $foto = $_FILES['foto'];
        $foto_ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($foto_ext, $allowed_ext)) {
            $errors[] = "Format foto harus JPG, JPEG, PNG, atau GIF!";
        }

        if ($foto['size'] > 2097152) {
            $errors[] = "Ukuran foto maksimal 2MB!";
        }

        if ($foto['error'] !== 0) {
            $errors[] = "Terjadi error saat upload foto!";
        }

        if (empty($errors)) {
            $new_foto_name = uniqid('kandidat_', true) . '.' . $foto_ext;
            $foto_path = '../assets/img/kandidat/' . $new_foto_name;

            if (move_uploaded_file($foto['tmp_name'], $foto_path)) {

                if (
                    $kandidat['foto'] !== 'default.jpg' &&
                    file_exists('../assets/img/kandidat/' . $kandidat['foto'])
                ) {
                    unlink('../assets/img/kandidat/' . $kandidat['foto']);
                }

                $foto_name = $new_foto_name;
            } else {
                $errors[] = "Gagal mengupload foto!";
            }
        }
    }

    /* ---------- UPDATE DATA ---------- */
    if (empty($errors)) {

        $query_update = "
            UPDATE kandidat SET
                nomor_urut = ?,
                nama_kandidat = ?,
                visi = ?,
                misi = ?,
                foto = ?
            WHERE id_kandidat = ?
        ";

        $stmt_update = mysqli_prepare($conn, $query_update);
        mysqli_stmt_bind_param(
            $stmt_update,
            "issssi",
            $nomor_urut,
            $nama_kandidat,
            $visi,
            $misi,
            $foto_name,
            $id_kandidat
        );

        if (mysqli_stmt_execute($stmt_update)) {
            set_flash_message('success', 'Data kandidat berhasil diperbarui!');
            redirect('kandidat.php');
        } else {
            $errors[] = "Gagal mengupdate data kandidat!";
        }

        mysqli_stmt_close($stmt_update);
    }

    if (!empty($errors)) {
        set_flash_message('danger', implode('<br>', $errors));
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
            margin-top: 16px;
            text-align: center;
        }

        .preview-foto img {
            max-width: 250px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
    </style>
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
            <a href="kandidat.php" class="sidebar-nav-item active">
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
            <a href="profil.php" class="sidebar-nav-item">
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

        <!-- Header -->
        <div class="main-header">
            <h1 class="main-title">Edit Kandidat</h1>
            <a href="kandidat.php" class="btn btn-secondary">
                ← Kembali
            </a>
        </div>

        <div class="content-card">
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