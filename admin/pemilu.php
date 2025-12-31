<?php
// File: admin/pemilu.php
// Deskripsi: Halaman manajemen pemilu

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Auto update status pemilu
auto_update_election_status();

// Ambil flash message
$flash = get_flash_message();

// Query semua pemilu
$query = "SELECT e.*, u.nama_lengkap as creator,
          (SELECT COUNT(*) FROM kandidat WHERE id_election = e.id_election) as jumlah_kandidat,
          (SELECT COUNT(*) FROM voting WHERE id_election = e.id_election) as jumlah_suara
          FROM elections e
          JOIN users u ON e.created_by = u.id_user
          ORDER BY e.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pemilu - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .election-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .election-title {
            font-size: 1.5em;
            color: #333;
            margin: 0 0 5px 0;
        }
        
        .election-dates {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            color: #666;
        }
        
        .election-dates div {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .election-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-box h4 {
            font-size: 1.8em;
            margin: 0;
            color: #667eea;
        }
        
        .stat-box p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .badge-draft {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-aktif {
            background-color: #28a745;
            color: white;
        }
        
        .badge-selesai {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-belum {
            background-color: #ffc107;
            color: #333;
        }
        
        .countdown {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 15px;
        }
        
        .countdown h4 {
            margin: 0 0 10px 0;
        }
        
        .countdown-timer {
            font-size: 1.5em;
            font-weight: bold;
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
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">📋 Manajemen Pemilu</h2>
                <a href="tambah_pemilu.php" class="btn btn-add">
                    ➕ Buat Pemilu Baru
                </a>
            </div>
        </div>

        <!-- Daftar Pemilu -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): 
                $status_info = get_election_status($row['id_election']);
                $status_real = $status_info['status_real'];
                
                // Hitung countdown
                $now = time();
                $start = strtotime($row['tanggal_mulai']);
                $end = strtotime($row['tanggal_selesai']);
            ?>
                <div class="election-card">
                    <div class="election-header">
                        <div>
                            <h3 class="election-title"><?php echo $row['nama_pemilu']; ?></h3>
                            <p style="color: #666; margin: 5px 0;">
                                <?php echo $row['deskripsi']; ?>
                            </p>
                            
                            <?php if ($status_real == 'draft'): ?>
                                <span class="badge badge-draft">📝 Draft</span>
                            <?php elseif ($status_real == 'belum_dimulai'): ?>
                                <span class="badge badge-belum">⏳ Belum Dimulai</span>
                            <?php elseif ($status_real == 'berlangsung'): ?>
                                <span class="badge badge-aktif">🟢 Berlangsung</span>
                            <?php else: ?>
                                <span class="badge badge-selesai">🔴 Selesai</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="detail_pemilu.php?id=<?php echo $row['id_election']; ?>" 
                               class="btn-action btn-view">
                                👁️ Detail
                            </a>
                            <?php if ($status_real != 'selesai'): ?>
                                <a href="edit_pemilu.php?id=<?php echo $row['id_election']; ?>" 
                                   class="btn-action btn-edit">
                                    ✏️ Edit
                                </a>
                            <?php endif; ?>
                            <?php if ($status_real == 'draft'): ?>
                                <a href="hapus_pemilu.php?id=<?php echo $row['id_election']; ?>" 
                                   class="btn-action btn-delete"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus pemilu ini?')">
                                    🗑️ Hapus
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="election-dates">
                        <div>
                            <span>📅 Mulai:</span>
                            <strong><?php echo date('d/m/Y H:i', strtotime($row['tanggal_mulai'])); ?></strong>
                        </div>
                        <div>
                            <span>📅 Selesai:</span>
                            <strong><?php echo date('d/m/Y H:i', strtotime($row['tanggal_selesai'])); ?></strong>
                        </div>
                        <div>
                            <span>👤 Dibuat oleh:</span>
                            <strong><?php echo $row['creator']; ?></strong>
                        </div>
                    </div>
                    
                    <div class="election-stats">
                        <div class="stat-box">
                            <h4><?php echo $row['jumlah_kandidat']; ?></h4>
                            <p>Kandidat</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php echo $row['jumlah_suara']; ?></h4>
                            <p>Suara Masuk</p>
                        </div>
                        <div class="stat-box">
                            <h4>
                                <?php 
                                $durasi = ($end - $start) / (60 * 60 * 24);
                                echo round($durasi) . ' hari'; 
                                ?>
                            </h4>
                            <p>Durasi</p>
                        </div>
                    </div>
                    
                    <!-- Countdown Timer -->
                    <?php if ($status_real == 'belum_dimulai'): ?>
                        <div class="countdown">
                            <h4>⏰ Pemilu dimulai dalam:</h4>
                            <div class="countdown-timer" 
                                 data-target="<?php echo $start * 1000; ?>"
                                 id="countdown-<?php echo $row['id_election']; ?>">
                                Menghitung...
                            </div>
                        </div>
                    <?php elseif ($status_real == 'berlangsung'): ?>
                        <div class="countdown">
                            <h4>⏰ Pemilu berakhir dalam:</h4>
                            <div class="countdown-timer" 
                                 data-target="<?php echo $end * 1000; ?>"
                                 id="countdown-<?php echo $row['id_election']; ?>">
                                Menghitung...
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="content-card">
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>Belum Ada Pemilu</h3>
                    <p>Silakan buat pemilu baru untuk memulai</p>
                    <a href="tambah_pemilu.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ Buat Pemilu Baru
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Countdown Timer Function
        function updateCountdown() {
            const timers = document.querySelectorAll('.countdown-timer');
            
            timers.forEach(timer => {
                const target = parseInt(timer.getAttribute('data-target'));
                const now = new Date().getTime();
                const distance = target - now;
                
                if (distance < 0) {
                    timer.innerHTML = "Waktu telah berakhir";
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                timer.innerHTML = `${days} hari ${hours} jam ${minutes} menit ${seconds} detik`;
            });
        }
        
        // Update setiap detik
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Jalankan sekali saat load
    </script>
</body>
</html>