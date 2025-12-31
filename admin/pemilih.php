<?php
// File: admin/pemilih.php
// Deskripsi: Halaman manajemen data pemilih

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Ambil flash message
$flash = get_flash_message();

// Pencarian pemilih
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Query pemilih dengan pencarian
if (!empty($search)) {
    $query = "SELECT u.*, 
            (SELECT COUNT(*) FROM voting v WHERE v.id_user = u.id_user) as sudah_voting
            FROM users u
            WHERE u.role = 'user'
            AND (u.nama_lengkap LIKE '%$search%' 
            OR u.username LIKE '%$search%'
            OR u.email LIKE '%$search%'
            OR u.nik LIKE '%$search%')
            ORDER BY u.created_at DESC";
} else {
    $query = "SELECT u.*, 
            (SELECT COUNT(*) FROM voting v WHERE v.id_user = u.id_user) as sudah_voting
            FROM users u
            WHERE u.role = 'user'
            ORDER BY u.created_at DESC";
}

$result = mysqli_query($conn, $query);

// Hitung statistik
$query_total = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result_total = mysqli_query($conn, $query_total);
$total_pemilih = mysqli_fetch_assoc($result_total)['total'];

$query_sudah_voting = "SELECT COUNT(DISTINCT id_user) as total FROM voting";
$result_sudah_voting = mysqli_query($conn, $query_sudah_voting);
$sudah_voting = mysqli_fetch_assoc($result_sudah_voting)['total'];

$belum_voting = $total_pemilih - $sudah_voting;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Pemilih - Sistem Pemilu</title>
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
            <a href="pemilu.php">Pemilu</a>
            <a href="kandidat.php">Kandidat</a>
            <a href="pemilih.php" class="active">Pemilih</a>
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
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Statistik Pemilih -->
    <div class="dashboard-grid">
        <div class="stats-card">
            <div class="stats-icon blue">
                <span>👥</span>
            </div>
            <div class="stats-info">
                <h3><?php echo $total_pemilih; ?></h3>
                <p>Total Pemilih</p>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon green">
                <span>✅</span>
            </div>
            <div class="stats-info">
                <h3><?php echo $sudah_voting; ?></h3>
                <p>Sudah Voting</p>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon orange">
                <span>⏳</span>
            </div>
            <div class="stats-info">
                <h3><?php echo $belum_voting; ?></h3>
                <p>Belum Voting</p>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="content-card">
        <div class="content-header">
            <h2 class="content-title">Daftar Pemilih</h2>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="🔍 Cari pemilih..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

        <!-- Table -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>NIK</th>
                            <th>Tanggal Lahir</th>
                            <th>Status Voting</th>
                            <th>Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['nik']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal_lahir'])); ?></td>
                                <td>
                                    <?php if ($row['sudah_voting'] > 0): ?>
                                        <span style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 5px; font-size: 0.9em;">
                                            ✅ Sudah Voting
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #ffc107; color: #333; padding: 5px 10px; border-radius: 5px; font-size: 0.9em;">
                                            ⏳ Belum Voting
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <h3>Belum Ada Pemilih</h3>
                <p>Belum ada pemilih yang terdaftar dalam sistem</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>