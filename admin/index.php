<?php
// File: admin/index.php


session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Auto update status pemilu
auto_update_election_status();

// Ambil pemilu aktif
$active_election = get_active_election();

// Hitung statistik global
$query_total_kandidat = "SELECT COUNT(*) as total FROM kandidat";
$result_kandidat = mysqli_query($conn, $query_total_kandidat);
$total_kandidat = mysqli_fetch_assoc($result_kandidat)['total'];

$query_total_pemilih = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result_pemilih = mysqli_query($conn, $query_total_pemilih);
$total_pemilih = mysqli_fetch_assoc($result_pemilih)['total'];

$query_total_suara = "SELECT COUNT(*) as total FROM voting";
$result_suara = mysqli_query($conn, $query_total_suara);
$total_suara = mysqli_fetch_assoc($result_suara)['total'];

$query_total_pemilu = "SELECT COUNT(*) as total FROM elections";
$result_pemilu_count = mysqli_query($conn, $query_total_pemilu);
$total_pemilu = mysqli_fetch_assoc($result_pemilu_count)['total'];

$persentase_partisipasi = $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 2) : 0;

// Ambil flash message
$flash = get_flash_message();

// Ambil daftar pemilu terbaru
$query_pemilu_list = "SELECT e.*, 
                      (SELECT COUNT(*) FROM kandidat WHERE id_election = e.id_election) as jumlah_kandidat,
                      (SELECT COUNT(*) FROM voting WHERE id_election = e.id_election) as jumlah_suara
                      FROM elections e
                      ORDER BY e.created_at DESC
                      LIMIT 5";
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
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            🗳️ Sistem Pemilu - Admin
        </div>
        <div class="navbar-menu">
            <a href="index.php" class="active">Dashboard</a>
            <a href="pemilu.php">Pemilu</a>
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
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Total Pemilu -->
            <div class="stats-card">
                <div class="stats-icon blue">
                    <span>📋</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_pemilu; ?></h3>
                    <p>Total Pemilu</p>
                </div>
            </div>

            <!-- Total Kandidat -->
            <div class="stats-card">
                <div class="stats-icon green">
                    <span>👥</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_kandidat; ?></h3>
                    <p>Total Kandidat</p>
                </div>
            </div>

            <!-- Total Pemilih -->
            <div class="stats-card">
                <div class="stats-icon orange">
                    <span>🙋</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_pemilih; ?></h3>
                    <p>Total Pemilih</p>
                </div>
            </div>

            <!-- Total Suara Masuk -->
            <div class="stats-card">
                <div class="stats-icon purple">
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
                
                <h3 style="color: #667eea; margin-bottom: 10px;">
                    <?php echo $active_election['nama_pemilu']; ?>
                </h3>
                <p style="color: #666; margin-bottom: 15px;">
                    <?php echo $active_election['deskripsi']; ?>
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                        <strong style="color: #667eea;">📅 Dimulai:</strong><br>
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_mulai'])); ?>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                        <strong style="color: #667eea;">📅 Berakhir:</strong><br>
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
                
                <div class="dashboard-grid" style="margin-top: 20px;">
                    <div class="stats-card">
                        <div class="stats-icon blue">
                            <span>👥</span>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo $kandidat_aktif; ?></h3>
                            <p>Kandidat</p>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon orange">
                            <span>🗳️</span>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo $suara_aktif; ?></h3>
                            <p>Suara Masuk</p>
                        </div>
                    </div>
                    
                    <div class="stats-card">
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
                    <a href="tambah_pemilu.php" style="color: #667eea; font-weight: bold;">Buat pemilu baru →</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Daftar Pemilu Terbaru -->
        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">📋 Pemilu Terbaru</h2>
                <a href="pemilu.php" style="color: #667eea; text-decoration: none;">
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
                                            <span style="color: #6c757d;">📝 Draft</span>
                                        <?php elseif ($status_info['status_real'] == 'belum_dimulai'): ?>
                                            <span style="color: #ffc107;">⏳ Belum Dimulai</span>
                                        <?php elseif ($status_info['status_real'] == 'berlangsung'): ?>
                                            <span style="color: #28a745;">🟢 Berlangsung</span>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">🔴 Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $pemilu['jumlah_kandidat']; ?></td>
                                    <td><?php echo $pemilu['jumlah_suara']; ?></td>
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
    </div>
</body>
</html>