<?php
// File: admin/hasil.php
// UPDATE: Tampilkan daftar pemilu, bukan gabungan hasil

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Auto update status
auto_update_election_status();

// Ambil flash message
$flash = get_flash_message();

// Jika ada parameter ID, tampilkan hasil pemilu tersebut
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_election = clean_input($_GET['id']);
    
    // Ambil data pemilu
    $query_election = "SELECT * FROM elections WHERE id_election = ?";
    $stmt = mysqli_prepare($conn, $query_election);
    mysqli_stmt_bind_param($stmt, "i", $id_election);
    mysqli_stmt_execute($stmt);
    $election = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if (!$election) {
        set_flash_message('danger', 'Pemilu tidak ditemukan!');
        redirect('hasil.php');
    }
    
    // Redirect ke detail pemilu
    redirect('detail_pemilu.php?id=' . $id_election);
}

// Query daftar pemilu
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM kandidat WHERE id_election = e.id_election) as jumlah_kandidat,
          (SELECT COUNT(*) FROM voting WHERE id_election = e.id_election) as jumlah_suara
          FROM elections e
          ORDER BY e.created_at DESC";
$result = mysqli_query($conn, $query);

// Hitung total pemilih
$query_pemilih = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result_pemilih = mysqli_query($conn, $query_pemilih);
$total_pemilih = mysqli_fetch_assoc($result_pemilih)['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemilu - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
        
        .pemilu-stats {
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
            <a href="pemilu.php">Pemilu</a>
            <a href="kandidat.php">Kandidat</a>
            <a href="pemilih.php">Pemilih</a>
            <a href="hasil.php" class="active">Hasil</a>
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
            <h2 class="content-title">📊 Hasil Pemilu</h2>
            <p style="color: #666; margin-top: 10px;">
                Pilih pemilu untuk melihat hasil lengkap dan statistik
            </p>
        </div>

        <!-- Daftar Pemilu -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): 
                $status_info = get_election_status($row['id_election']);
                $persentase_partisipasi = $total_pemilih > 0 ? round(($row['jumlah_suara'] / $total_pemilih) * 100, 2) : 0;
            ?>
                <div class="pemilu-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #333; font-size: 1.5em;">
                                <?php echo $row['nama_pemilu']; ?>
                            </h3>
                            <p style="color: #666; margin: 5px 0;">
                                <?php echo $row['deskripsi']; ?>
                            </p>
                            
                            <?php if ($status_info['status_real'] == 'draft'): ?>
                                <span class="badge" style="background-color: #6c757d; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em;">
                                    📝 Draft
                                </span>
                            <?php elseif ($status_info['status_real'] == 'belum_dimulai'): ?>
                                <span class="badge" style="background-color: #ffc107; color: #333; padding: 5px 12px; border-radius: 20px; font-size: 0.85em;">
                                    ⏳ Belum Dimulai
                                </span>
                            <?php elseif ($status_info['status_real'] == 'berlangsung'): ?>
                                <span class="badge" style="background-color: #28a745; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em;">
                                    🟢 Berlangsung
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em;">
                                    🔴 Selesai
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="color: #666; margin: 10px 0;">
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
                    <!-- FIXED: Tambah tombol Export PDF -->
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
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
    </div>
</body>
</html>