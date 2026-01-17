<?php
// File: admin/pemilih.php
// Deskripsi: Manajemen data pemilih

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Ambil flash message
$flash = get_flash_message();

// Ambil keyword pencarian
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Query data pemilih
if (!empty($search)) {
    $query = "
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM voting v WHERE v.id_user = u.id_user) AS sudah_voting
        FROM users u
        WHERE u.role = 'user'
          AND (
              u.nama_lengkap LIKE '%$search%' OR
              u.username LIKE '%$search%' OR
              u.email LIKE '%$search%' OR
              u.nik LIKE '%$search%'
          )
        ORDER BY u.created_at DESC
    ";
} else {
    $query = "
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM voting v WHERE v.id_user = u.id_user) AS sudah_voting
        FROM users u
        WHERE u.role = 'user'
        ORDER BY u.created_at DESC
    ";
}

$result = mysqli_query($conn, $query);

// Hitung total pemilih
$query_total = "SELECT COUNT(*) AS total FROM users WHERE role = 'user'";
$result_total = mysqli_query($conn, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_pemilih = $row_total['total'];

// Hitung jumlah pemilih yang sudah voting
$query_sudah_voting = "SELECT COUNT(DISTINCT id_user) AS total FROM voting";
$result_sudah_voting = mysqli_query($conn, $query_sudah_voting);
$row_sudah_voting = mysqli_fetch_assoc($result_sudah_voting);
$sudah_voting = $row_sudah_voting['total'];

// Hitung yang belum voting
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
            <a href="pemilih.php" class="sidebar-nav-item active">
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
            <a href="logout.php" class="sidebar-logout">Logout</a>
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
            <h1 class="main-title">Manajemen Pemilih</h1>
        </div>

        <!-- Statistik Pemilih -->
        <div class="dashboard-grid">
            <div class="stats-card blue">
                <div class="stats-icon blue">
                    <span>👥</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_pemilih; ?></h3>
                    <p>Total Pemilih</p>
                </div>
            </div>

            <div class="stats-card green">
                <div class="stats-icon green">
                    <span>✅</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $sudah_voting; ?></h3>
                    <p>Sudah Voting</p>
                </div>
            </div>

            <div class="stats-card orange">
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
                    <button type="submit" class="btn btn-primary">Cari</button>
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
                                    <td style="text-align: center;"><strong><?php echo $no++; ?></strong></td>
                                    <td><?php echo $row['username']; ?></td>
                                    <td><strong><?php echo $row['nama_lengkap']; ?></strong></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['nik']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_lahir'])); ?></td>
                                    <td>
                                        <?php if ($row['sudah_voting'] > 0): ?>
                                            <span class="badge badge-success">✅ Sudah Voting</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⏳ Belum Voting</span>
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