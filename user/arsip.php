<?php
// File: user/arsip.php
// Deskripsi: Halaman arsip pemilu yang sudah selesai
// FIX: Commands out of sync error

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

// Auto update status pemilu
auto_update_election_status();

// Query semua pemilu - FIXED: Gunakan mysqli_query langsung (tidak pakai prepared statement)
$user_id = $_SESSION['user_id'];
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM kandidat WHERE id_election = e.id_election) as jumlah_kandidat,
          (SELECT COUNT(*) FROM voting WHERE id_election = e.id_election) as jumlah_suara,
          (SELECT COUNT(*) FROM voting WHERE id_election = e.id_election AND id_user = $user_id) as sudah_voting
          FROM elections e
          WHERE e.status != 'draft'
          ORDER BY e.created_at DESC";
$result = mysqli_query($conn, $query);

// Pisahkan pemilu aktif dan selesai
$pemilu_aktif = array();
$pemilu_selesai = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $status_info = get_election_status($row['id_election']);
        $row['status_real'] = $status_info['status_real'];
        
        if ($status_info['status_real'] == 'berlangsung' || $status_info['status_real'] == 'belum_dimulai') {
            $pemilu_aktif[] = $row;
        } else {
            $pemilu_selesai[] = $row;
        }
    }
}

// Ambil flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Pemilu - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <style>
        .pemilu-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .pemilu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .pemilu-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .pemilu-title {
            font-size: 1.5em;
            color: #333;
            margin: 0 0 5px 0;
        }
        
        .pemilu-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-item h4 {
            font-size: 1.8em;
            margin: 0;
            color: #667eea;
        }
        
        .stat-item p {
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
        
        .badge-aktif {
            background-color: #28a745;
            color: white;
        }
        
        .badge-selesai {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-belum {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-sudah-voting {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body class="user-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            🗳️ Sistem Pemilu - Pemilih
        </div>
        <div class="navbar-menu">
            <a href="index.php">Beranda</a>
            <a href="voting.php">Voting</a>
            <a href="hasil.php">Hasil</a>
            <a href="arsip.php" class="active">Arsip</a>
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

        <!-- Header -->
        <div class="content-card">
            <h2 class="content-title">📚 Arsip Pemilu</h2>
            <p style="color: #666; margin-top: 10px;">
                Lihat daftar pemilu yang sedang berlangsung dan yang sudah selesai
            </p>
        </div>

        <!-- Pemilu Aktif -->
        <?php if (count($pemilu_aktif) > 0): ?>
            <h3 style="margin: 30px 0 20px 0; color: #333;">🟢 Pemilu Aktif</h3>
            
            <?php foreach ($pemilu_aktif as $row): ?>
                <div class="pemilu-card">
                    <div class="pemilu-header">
                        <div>
                            <h3 class="pemilu-title"><?php echo $row['nama_pemilu']; ?></h3>
                            <p style="color: #666; margin: 5px 0;">
                                <?php echo $row['deskripsi']; ?>
                            </p>
                            
                            <?php if ($row['status_real'] == 'berlangsung'): ?>
                                <span class="badge badge-aktif">🟢 Berlangsung</span>
                            <?php else: ?>
                                <span class="badge badge-belum">⏳ Belum Dimulai</span>
                            <?php endif; ?>
                            
                            <?php if ($row['sudah_voting'] > 0): ?>
                                <span class="badge badge-sudah-voting">✅ Anda Sudah Voting</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="color: #666; margin: 10px 0;">
                        <div><strong>📅 Mulai:</strong> <?php echo date('d F Y, H:i', strtotime($row['tanggal_mulai'])); ?></div>
                        <div><strong>📅 Selesai:</strong> <?php echo date('d F Y, H:i', strtotime($row['tanggal_selesai'])); ?></div>
                    </div>
                    
                    <div class="pemilu-stats">
                        <div class="stat-item">
                            <h4><?php echo $row['jumlah_kandidat']; ?></h4>
                            <p>Kandidat</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo $row['jumlah_suara']; ?></h4>
                            <p>Suara Masuk</p>
                        </div>
                        <div class="stat-item">
                            <?php
                            $start = strtotime($row['tanggal_mulai']);
                            $end = strtotime($row['tanggal_selesai']);
                            $durasi = ($end - $start) / (60 * 60 * 24);
                            ?>
                            <h4><?php echo round($durasi); ?></h4>
                            <p>Hari</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <?php if ($row['status_real'] == 'berlangsung' && $row['sudah_voting'] == 0): ?>
                            <a href="voting.php" class="btn btn-primary" style="flex: 1;">
                                🗳️ Mulai Voting
                            </a>
                        <?php endif; ?>
                        <a href="hasil.php?id=<?php echo $row['id_election']; ?>" class="btn btn-secondary" style="flex: 1;">
                            📊 Lihat Hasil
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Pemilu Selesai -->
        <?php if (count($pemilu_selesai) > 0): ?>
            <h3 style="margin: 30px 0 20px 0; color: #333;">🔴 Pemilu Selesai</h3>
            
            <?php foreach ($pemilu_selesai as $row): ?>
                <div class="pemilu-card">
                    <div class="pemilu-header">
                        <div>
                            <h3 class="pemilu-title"><?php echo $row['nama_pemilu']; ?></h3>
                            <p style="color: #666; margin: 5px 0;">
                                <?php echo $row['deskripsi']; ?>
                            </p>
                            
                            <span class="badge badge-selesai">🔴 Selesai</span>
                            
                            <?php if ($row['sudah_voting'] > 0): ?>
                                <span class="badge badge-sudah-voting">✅ Anda Voting</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="color: #666; margin: 10px 0;">
                        <div><strong>📅 Selesai:</strong> <?php echo date('d F Y, H:i', strtotime($row['tanggal_selesai'])); ?></div>
                    </div>
                    
                    <div class="pemilu-stats">
                        <div class="stat-item">
                            <h4><?php echo $row['jumlah_kandidat']; ?></h4>
                            <p>Kandidat</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo $row['jumlah_suara']; ?></h4>
                            <p>Total Suara</p>
                        </div>
                        <div class="stat-item">
                            <h4><?php echo $row['sudah_voting'] > 0 ? 'Ya' : 'Tidak'; ?></h4>
                            <p>Partisipasi</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <a href="hasil.php?id=<?php echo $row['id_election']; ?>" class="btn btn-secondary" style="width: 100%;">
                            📊 Lihat Hasil
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Jika tidak ada pemilu -->
        <?php if (count($pemilu_aktif) == 0 && count($pemilu_selesai) == 0): ?>
            <div class="content-card">
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <h3>Belum Ada Pemilu</h3>
                    <p>Saat ini belum ada pemilu yang terdaftar dalam sistem</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>