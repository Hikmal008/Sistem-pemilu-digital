<?php
// File: admin/tambah_kandidat.php
// Form tambah kandidat dengan sidebar

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

/* ================= AMBIL DATA PEMILU ================= */
$query_pemilu = "
    SELECT id_election, nama_pemilu, status 
    FROM elections
    WHERE status IN ('draft', 'aktif')
    ORDER BY created_at DESC
";
$result_pemilu = mysqli_query($conn, $query_pemilu);

/* ================= PROSES FORM ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_election   = clean_input($_POST['id_election']);
    $nomor_urut    = clean_input($_POST['nomor_urut']);
    $nama_kandidat = clean_input($_POST['nama_kandidat']);
    $visi          = clean_input($_POST['visi']);
    $misi          = clean_input($_POST['misi']);

    $errors = [];

    /* ---------- VALIDASI INPUT ---------- */
    if (
        empty($id_election) ||
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
        "SELECT id_kandidat FROM kandidat WHERE nomor_urut = ? AND id_election = ?"
    );
    mysqli_stmt_bind_param($stmt_check, "ii", $nomor_urut, $id_election);
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "Nomor urut sudah digunakan di pemilu ini!";
    }

    /* ---------- VALIDASI FOTO ---------- */
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === 4) {
        $errors[] = "Foto kandidat harus diupload!";
    } else {
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
    }

    /* ---------- SIMPAN DATA ---------- */
    if (empty($errors)) {

        $new_foto_name = uniqid('kandidat_', true) . '.' . $foto_ext;
        $foto_path = '../assets/img/kandidat/' . $new_foto_name;

        if (move_uploaded_file($foto['tmp_name'], $foto_path)) {

            $query = "
                INSERT INTO kandidat 
                (id_election, nomor_urut, nama_kandidat, visi, misi, foto)
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param(
                $stmt,
                "iissss",
                $id_election,
                $nomor_urut,
                $nama_kandidat,
                $visi,
                $misi,
                $new_foto_name
            );

            if (mysqli_stmt_execute($stmt)) {
                set_flash_message('success', 'Kandidat berhasil ditambahkan!');
                redirect('kandidat.php');
            } else {
                unlink($foto_path);
                $errors[] = "Gagal menyimpan data kandidat!";
            }

            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Gagal mengupload foto!";
        }
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
    <title>Tambah Kandidat - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="admin-page"><!-- Sidebar -->
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
    </aside><!-- Mobile Toggle -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button><!-- Main Content -->
    <main class="main-content"> <!-- Header -->
        <div class="main-header">
            <h1 class="main-title">Tambah Kandidat</h1>
            <a href="kandidat.php" class="btn btn-secondary">
                ← Kembali
            </a>
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
            <?php endif; ?> <?php if (mysqli_num_rows($result_pemilu) > 0): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <!-- Pilihan Pemilu -->
                    <div class="form-group">
                        <label for="id_election">Pilih Pemilu <span>*</span></label>
                        <select id="id_election" name="id_election" required>
                            <option value="">-- Pilih Pemilu --</option>
                            <?php
                                mysqli_data_seek($result_pemilu, 0);
                                while ($pemilu = mysqli_fetch_assoc($result_pemilu)):
                            ?>
                                <option value="<?php echo $pemilu['id_election']; ?>"
                                    <?php echo (isset($_POST['id_election']) && $_POST['id_election'] == $pemilu['id_election']) ? 'selected' : ''; ?>>
                                    <?php echo $pemilu['nama_pemilu']; ?>
                                    (<?php echo ucfirst($pemilu['status']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="password-info">Pilih pemilu untuk kandidat ini</div>
                    </div>
                    <div class="form-group">
                        <label for="nomor_urut">Nomor Urut <span>*</span></label>
                        <input type="number" id="nomor_urut" name="nomor_urut" required
                            placeholder="Masukkan nomor urut" min="1"
                            value="<?php echo isset($_POST['nomor_urut']) ? $_POST['nomor_urut'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="nama_kandidat">Nama Kandidat <span>*</span></label>
                        <input type="text" id="nama_kandidat" name="nama_kandidat" required
                            placeholder="Masukkan nama lengkap kandidat"
                            value="<?php echo isset($_POST['nama_kandidat']) ? $_POST['nama_kandidat'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="visi">Visi <span>*</span></label>
                        <textarea id="visi" name="visi" required
                            placeholder="Masukkan visi kandidat"><?php echo isset($_POST['visi']) ? $_POST['visi'] : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="misi">Misi <span>*</span></label>
                        <textarea id="misi" name="misi" required
                            placeholder="Masukkan misi kandidat"><?php echo isset($_POST['misi']) ? $_POST['misi'] : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="foto">Foto Kandidat <span>*</span></label>
                        <input type="file" id="foto" name="foto" required accept="image/*">
                        <div class="password-info">Format: JPG, JPEG, PNG, GIF (Max: 2MB)</div>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            💾 Simpan Kandidat
                        </button>
                        <a href="kandidat.php" class="btn btn-secondary">
                            ❌ Batal
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ Tidak ada pemilu yang tersedia. Silakan buat pemilu terlebih dahulu.
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="tambah_pemilu.php" class="btn btn-primary">
                        ➕ Buat Pemilu Baru
                    </a>
                </div>
            <?php endif; ?>
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