<?php
// File: admin/hasil.php
// Deskripsi: Daftar hasil pemilu (admin)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Auto update status pemilu
auto_update_election_status();

// Ambil flash message
$flash = get_flash_message();

// Jika ada parameter ID, redirect ke detail pemilu
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_election = clean_input($_GET['id']);
    redirect('detail_pemilu.php?id=' . $id_election);
}

// Query daftar pemilu
$query = "
    SELECT 
        e.*,
        (SELECT COUNT(*) FROM kandidat k WHERE k.id_election = e.id_election) AS jumlah_kandidat,
        (SELECT COUNT(*) FROM voting v WHERE v.id_election = e.id_election) AS jumlah_suara
    FROM elections e
    ORDER BY e.created_at DESC
";

$result = mysqli_query($conn, $query);

// Hitung total pemilih
$query_pemilih = "SELECT COUNT(*) AS total FROM users WHERE role = 'user'";
$result_pemilih = mysqli_query($conn, $query_pemilih);
$row_pemilih = mysqli_fetch_assoc($result_pemilih);
$total_pemilih = $row_pemilih['total'];
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemilu - Sistem Pemilu</title>
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
            <a href="hasil.php" class="sidebar-nav-item active">
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

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="main-header">
            <div>
                <h1 class="main-title">Hasil Pemilu</h1>
                <p style="color: var(--gray-600); margin-top: 8px;">
                    Pilih pemilu untuk melihat hasil lengkap dan statistik
                </p>
            </div>
        </div>

        <!-- Daftar Pemilu -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)):
                $status_info = get_election_status($row['id_election']);
                $persentase_partisipasi = $total_pemilih > 0 ? round(($row['jumlah_suara'] / $total_pemilih) * 100, 2) : 0;
            ?>
                <div class="pemilu-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 8px 0; color: var(--kpu-red); font-size: 1.6em; font-weight: 700;">
                                <?php echo $row['nama_pemilu']; ?>
                            </h3>
                            <p style="color: var(--gray-600); margin: 5px 0;">
                                <?php echo $row['deskripsi']; ?>
                            </p>

                            <?php if ($status_info['status_real'] == 'draft'): ?>
                                <span class="badge badge-gray">📝 Draft</span>
                            <?php elseif ($status_info['status_real'] == 'belum_dimulai'): ?>
                                <span class="badge badge-warning">⏳ Belum Dimulai</span>
                            <?php elseif ($status_info['status_real'] == 'berlangsung'): ?>
                                <span class="badge badge-success">🟢 Berlangsung</span>
                            <?php else: ?>
                                <span class="badge badge-danger">🔴 Selesai</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="color: var(--gray-700); margin: 12px 0;">
                        <div><strong>📅 Periode:</strong>
                            <?php echo date('d/m/Y', strtotime($row['tanggal_mulai'])); ?> -
                            <?php echo date('d/m/Y', strtotime($row['tanggal_selesai'])); ?>
                        </div>
                    </div>

                    <div class="pemilu-stats">
                        <div class="stat-box">
                            <h4><?php echo $row['jumlah_kandidat']; ?></h4>
                            <p>Total Kandidat</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php echo $row['jumlah_suara']; ?></h4>
                            <p>Suara Masuk</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php echo $persentase_partisipasi; ?>%</h4>
                            <p>Partisipasi</p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <a href="detail_pemilu.php?id=<?php echo $row['id_election']; ?>"
                            class="btn btn-primary" style="flex: 1; text-align: center;">
                            📊 Lihat Hasil Lengkap
                        </a>
                        <a href="export_hasil_pemilu.php?id=<?php echo $row['id_election']; ?>"
                            class="btn-action btn-add" style="text-align: center; padding: 12px;" target="_blank">
                            📥 Excel
                        </a>
                        <a href="export_pdf_pemilu.php?id=<?php echo $row['id_election']; ?>"
                            class="btn-action btn-view" style="text-align: center; padding: 12px;" target="_blank">
                            📄 PDF
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="content-card">
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <h3>Belum Ada Pemilu</h3>
                    <p>Belum ada pemilu yang terdaftar dalam sistem</p>
                    <a href="tambah_pemilu.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ Buat Pemilu Baru
                    </a>
                </div>
            </div>
        <?php endif; ?>

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