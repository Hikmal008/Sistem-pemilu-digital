<?php
// File: admin/index.php
// UPDATE: Dashboard dengan sidebar dan tema merah
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
// Cek apakah user adalah admin
check_admin();
// Auto update status pemilu
auto_update_election_status();
// Ambil pemilu aktif
$active_election = get_active_election();
// =============================
// HITUNG STATISTIK GLOBAL
// =============================

$total_kandidat = 0;
$total_pemilih  = 0;
$total_suara    = 0;
$total_pemilu   = 0;

// Total kandidat
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM kandidat");
if ($q) {
    $row = mysqli_fetch_assoc($q);
    $total_kandidat = (int)$row['total'];
}

// Total pemilih
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
if ($q) {
    $row = mysqli_fetch_assoc($q);
    $total_pemilih = (int)$row['total'];
}

// Total suara
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM voting");
if ($q) {
    $row = mysqli_fetch_assoc($q);
    $total_suara = (int)$row['total'];
}

// Total pemilu
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM elections");
if ($q) {
    $row = mysqli_fetch_assoc($q);
    $total_pemilu = (int)$row['total'];
}

// Persentase partisipasi
$persentase_partisipasi = ($total_pemilih > 0)
    ? round(($total_suara / $total_pemilih) * 100, 2)
    : 0;


// Ambil flash message
$flash = get_flash_message();
// =============================
// AMBIL DAFTAR PEMILU TERBARU
$query_pemilu_list = "
    SELECT 
        e.*,
        COUNT(DISTINCT k.id_kandidat) AS jumlah_kandidat,
        COUNT(DISTINCT v.id_voting) AS jumlah_suara
    FROM elections e
    LEFT JOIN kandidat k ON k.id_election = e.id_election
    LEFT JOIN voting v ON v.id_election = e.id_election
    GROUP BY e.id_election
    ORDER BY e.created_at DESC
    LIMIT 5
";


$result_pemilu_list = mysqli_query($conn, $query_pemilu_list);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Pemilu</title>
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
            <a href="index.php" class="sidebar-nav-item active">
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
            <div>
                <h1 class="main-title">Dashboard</h1>
                <p style="color: var(--gray-600); margin-top: 8px;">
                    Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>!
                </p>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Total Pemilu -->
            <div class="stats-card red">
                <div class="stats-icon red">
                    <span>📋</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_pemilu; ?></h3>
                    <p>Total Pemilu</p>
                </div>
            </div>

            <!-- Total Kandidat -->
            <div class="stats-card green">
                <div class="stats-icon green">
                    <span>👥</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_kandidat; ?></h3>
                    <p>Total Kandidat</p>
                </div>
            </div>

            <!-- Total Pemilih -->
            <div class="stats-card orange">
                <div class="stats-icon orange">
                    <span>🙋</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_pemilih; ?></h3>
                    <p>Total Pemilih</p>
                </div>
            </div>

            <!-- Total Suara Masuk -->
            <div class="stats-card blue">
                <div class="stats-icon blue">
                    <span>🗳️</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_suara; ?></h3>
                    <p>Suara Masuk</p>
                </div>
            </div>
        </div>

        <!-- Status Pemilu Aktif -->
        <?php if ($active_election): ?>
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">🟢 Pemilu Aktif</h2>
                    <a href="detail_pemilu.php?id=<?php echo $active_election['id_election']; ?>" class="btn btn-primary">
                        Lihat Detail
                    </a>
                </div>

                <h3 style="color: var(--kpu-red); margin-bottom: 10px; font-size: 1.6em;">
                    <?php echo $active_election['nama_pemilu']; ?>
                </h3>
                <p style="color: var(--gray-600); margin-bottom: 20px;">
                    <?php echo $active_election['deskripsi']; ?>
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div style="background: var(--gray-50); padding: 16px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                        <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">📅 Dimulai:</strong>
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_mulai'])); ?>
                    </div>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                        <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">📅 Berakhir:</strong>
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_selesai'])); ?>
                    </div>
                </div>

                <?php
                // Hitung statistik pemilu aktif
                $query_kandidat_aktif = "SELECT COUNT(*) as total FROM kandidat WHERE id_election = ?";
                $stmt = mysqli_prepare($conn, $query_kandidat_aktif);
                mysqli_stmt_bind_param($stmt, "i", $active_election['id_election']);
                mysqli_stmt_execute($stmt);
                $kandidat_aktif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

                $query_suara_aktif = "SELECT COUNT(*) as total FROM voting WHERE id_election = ?";
                $stmt = mysqli_prepare($conn, $query_suara_aktif);
                mysqli_stmt_bind_param($stmt, "i", $active_election['id_election']);
                mysqli_stmt_execute($stmt);
                $suara_aktif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];

                $partisipasi_aktif = $total_pemilih > 0 ? round(($suara_aktif / $total_pemilih) * 100, 2) : 0;
                ?>

                <div class="dashboard-grid">
                    <div class="stats-card blue">
                        <div class="stats-icon blue">
                            <span>👥</span>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo $kandidat_aktif; ?></h3>
                            <p>Kandidat</p>
                        </div>
                    </div>

                    <div class="stats-card orange">
                        <div class="stats-icon orange">
                            <span>🗳️</span>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo $suara_aktif; ?></h3>
                            <p>Suara Masuk</p>
                        </div>
                    </div>

                    <div class="stats-card green">
                        <div class="stats-icon green">
                            <span>📊</span>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo $partisipasi_aktif; ?>%</h3>
                            <p>Partisipasi</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="content-card">
                <div class="alert alert-info">
                    ℹ️ Tidak ada pemilu yang sedang berlangsung.
                    <a href="tambah_pemilu.php" style="color: var(--kpu-red); font-weight: bold; margin-left: 8px;">Buat pemilu baru →</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Daftar Pemilu Terbaru -->
        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">📋 Pemilu Terbaru</h2>
                <a href="pemilu.php" style="color: var(--kpu-red); text-decoration: none; font-weight: 600;">
                    Lihat Semua →
                </a>
            </div>

            <?php if (mysqli_num_rows($result_pemilu_list) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Pemilu</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Kandidat</th>
                                <th>Suara</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pemilu = mysqli_fetch_assoc($result_pemilu_list)):
                                $status_info = get_election_status($pemilu['id_election']);
                            ?>
                                <tr>
                                    <td><strong><?php echo $pemilu['nama_pemilu']; ?></strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($pemilu['tanggal_mulai'])); ?></td>
                                    <td>
                                        <?php if ($status_info['status_real'] == 'draft'): ?>
                                            <span class="badge badge-gray">📝 Draft</span>
                                        <?php elseif ($status_info['status_real'] == 'belum_dimulai'): ?>
                                            <span class="badge badge-warning">⏳ Belum Dimulai</span>
                                        <?php elseif ($status_info['status_real'] == 'berlangsung'): ?>
                                            <span class="badge badge-success">🟢 Berlangsung</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">🔴 Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $pemilu['jumlah_kandidat']; ?></td>
                                    <td><strong><?php echo $pemilu['jumlah_suara']; ?></strong></td>
                                    <td>
                                        <a href="detail_pemilu.php?id=<?php echo $pemilu['id_election']; ?>"
                                            class="btn-action btn-view">
                                            👁️ Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>Belum Ada Pemilu</h3>
                    <p>Silakan buat pemilu baru untuk memulai</p>
                    <a href="tambah_pemilu.php" class="btn btn-primary" style="margin-top: 20px;">
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

        // Close sidebar when clicking outside on mobile
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