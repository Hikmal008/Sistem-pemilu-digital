<?php
// File: user/index.php
// UPDATE: Support multiple elections

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

// Auto update status pemilu
auto_update_election_status();

// Ambil pemilu aktif
$active_election = get_active_election();

// Cek apakah user sudah voting di pemilu aktif
$sudah_voting = false;
if ($active_election) {
    $sudah_voting = has_voted_in_election($_SESSION['user_id'], $active_election['id_election']);
}

// Ambil flash message
$flash = get_flash_message();

// Hitung total kandidat di pemilu aktif
$total_kandidat = 0;
if ($active_election) {
    $query_total_kandidat = "SELECT COUNT(*) as total FROM kandidat WHERE id_election = ?";
    $stmt = mysqli_prepare($conn, $query_total_kandidat);
    mysqli_stmt_bind_param($stmt, "i", $active_election['id_election']);
    mysqli_stmt_execute($stmt);
    $total_kandidat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
}

// Hitung total suara di pemilu aktif
$total_suara = 0;
if ($active_election) {
    $query_total_suara = "SELECT COUNT(*) as total FROM voting WHERE id_election = ?";
    $stmt = mysqli_prepare($conn, $query_total_suara);
    mysqli_stmt_bind_param($stmt, "i", $active_election['id_election']);
    mysqli_stmt_execute($stmt);
    $total_suara = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
}

// Ambil daftar pemilu yang sudah selesai untuk arsip
$query_arsip = "SELECT id_election, nama_pemilu, tanggal_selesai 
                FROM elections 
                WHERE status = 'selesai'
                ORDER BY tanggal_selesai DESC
                LIMIT 5";
$result_arsip = mysqli_query($conn, $query_arsip);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilih - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body class="user-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            🗳️ Sistem Pemilu - Pemilih
        </div>
        <div class="navbar-menu">
            <a href="index.php" class="active">Beranda</a>
            <a href="voting.php">Voting</a>
            <a href="hasil.php">Hasil</a>
            <a href="arsip.php">Arsip</a>
            <a href="profil.php">Profil</a>
            <a href="logout.php" style="background-color: rgba(255,255,255,0.2);">Logout</a>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                <div class="user-role">Pemilih</div>
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

        <!-- Info Box -->
        <div class="info-box">
            <h2>Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h2>
            <p>Gunakan hak suara Anda dengan bijak untuk masa depan yang lebih baik</p>
        </div>

        <!-- Status Pemilu Aktif -->
        <?php if ($active_election): ?>
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">🗳️ <?php echo $active_election['nama_pemilu']; ?></h2>
                </div>
                
                <p style="color: #666; margin-bottom: 15px;">
                    <?php echo $active_election['deskripsi']; ?>
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                        <strong style="color: #667eea;">📅 Dimulai:</strong><br>
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_mulai'])); ?>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                        <strong style="color: #667eea;">📅 Berakhir:</strong><br>
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_selesai'])); ?>
                    </div>
                </div>
                
                <?php if ($sudah_voting): ?>
                    <div class="alert alert-info">
                        ✅ <strong>Anda sudah memberikan suara di pemilu ini!</strong><br>
                        Terima kasih atas partisipasi Anda.
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="hasil.php" class="btn btn-primary">
                            📊 Lihat Hasil
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        📢 <strong>Pemilu sedang berlangsung!</strong><br>
                        Silakan klik tombol di bawah untuk memberikan suara Anda.
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="voting.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.2em;">
                            🗳️ Mulai Voting
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statistik -->
            <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <span>👥</span>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $total_kandidat; ?></h3>
                        <p>Total Kandidat</p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon orange">
                        <span>🗳️</span>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $total_suara; ?></h3>
                        <p>Total Suara Masuk</p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon <?php echo $sudah_voting ? 'green' : 'purple'; ?>">
                        <span><?php echo $sudah_voting ? '✅' : '⏳'; ?></span>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $sudah_voting ? 'Sudah' : 'Belum'; ?></h3>
                        <p>Status Voting</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Tidak Ada Pemilu Aktif -->
            <div class="content-card">
                <div class="empty-state">
                    <div class="empty-state-icon">🗳️</div>
                    <h3>Tidak Ada Pemilu Aktif</h3>
                    <p>Saat ini tidak ada pemilu yang sedang berlangsung.<br>
                    Silakan tunggu pengumuman pemilu berikutnya.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Arsip Pemilu -->
        <?php if (mysqli_num_rows($result_arsip) > 0): ?>
            <div class="content-card" style="margin-top: 30px;">
                <div class="content-header">
                    <h3 class="content-title">📚 Arsip Pemilu Sebelumnya</h3>
                    <a href="arsip.php" style="color: #667eea; text-decoration: none;">
                        Lihat Semua →
                    </a>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php while ($arsip = mysqli_fetch_assoc($result_arsip)): ?>
                        <a href="hasil.php?id=<?php echo $arsip['id_election']; ?>" 
                           style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 10px; text-decoration: none; color: #333; transition: all 0.3s;">
                            <div>
                                <strong><?php echo $arsip['nama_pemilu']; ?></strong>
                            </div>
                            <div style="font-size: 0.9em; color: #666;">
                                <?php echo date('d/m/Y', strtotime($arsip['tanggal_selesai'])); ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Panduan Voting -->
        <div class="content-card" style="margin-top: 30px;">
            <div class="content-header">
                <h2 class="content-title">📋 Panduan Voting</h2>
            </div>
            
            <div style="line-height: 2;">
                <p><strong>1.</strong> Pastikan Anda sudah login dengan akun Anda</p>
                <p><strong>2.</strong> Klik menu "Voting" untuk melihat daftar kandidat</p>
                <p><strong>3.</strong> Baca visi dan misi setiap kandidat dengan teliti</p>
                <p><strong>4.</strong> Klik tombol "Pilih" pada kandidat pilihan Anda</p>
                <p><strong>5.</strong> Konfirmasi pilihan Anda</p>
                <p><strong>6.</strong> Setiap akun hanya dapat memilih <strong>SATU KALI per pemilu</strong></p>
                <p><strong>7.</strong> Setelah voting, Anda dapat melihat hasil di menu "Hasil"</p>
            </div>
        </div>
    </div>
</body>
</html>