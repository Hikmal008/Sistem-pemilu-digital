<?php
// File: admin/detail_pemilu.php
// Deskripsi: Halaman detail pemilu dengan statistik lengkap

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Cek apakah ada ID pemilu
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$id_election = clean_input($_GET['id']);

// Ambil data pemilu
$query = "SELECT e.*, u.nama_lengkap as creator
          FROM elections e
          JOIN users u ON e.created_by = u.id_user
          WHERE e.id_election = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    set_flash_message('danger', 'Pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$pemilu = mysqli_fetch_assoc($result);
$status_info = get_election_status($id_election);

// Query kandidat
$query_kandidat = "SELECT k.*, COUNT(v.id_voting) as jumlah_suara
                   FROM kandidat k
                   LEFT JOIN voting v ON k.id_kandidat = v.id_kandidat
                   WHERE k.id_election = ?
                   GROUP BY k.id_kandidat
                   ORDER BY jumlah_suara DESC, k.nomor_urut ASC";
$stmt_kandidat = mysqli_prepare($conn, $query_kandidat);
mysqli_stmt_bind_param($stmt_kandidat, "i", $id_election);
mysqli_stmt_execute($stmt_kandidat);
$result_kandidat = mysqli_stmt_get_result($stmt_kandidat);

// Hitung total suara
$query_total = "SELECT COUNT(*) as total FROM voting WHERE id_election = ?";
$stmt_total = mysqli_prepare($conn, $query_total);
mysqli_stmt_bind_param($stmt_total, "i", $id_election);
mysqli_stmt_execute($stmt_total);
$total_suara = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'];

// Ambil flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemilu - <?php echo $pemilu['nama_pemilu']; ?></title>
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
                <div>
                    <h2 class="content-title"><?php echo $pemilu['nama_pemilu']; ?></h2>
                    <p style="color: #666; margin: 5px 0 0 0;">
                        <?php echo $pemilu['deskripsi']; ?>
                    </p>
                </div>
                <div class="action-buttons">
                    <a href="pemilu.php" class="btn btn-secondary">
                        ← Kembali
                    </a>
                    <?php if ($status_info['status_real'] != 'selesai'): ?>
                        <a href="edit_pemilu.php?id=<?php echo $id_election; ?>" class="btn btn-primary">
                            ✏️ Edit
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistik -->
        <div class="dashboard-grid">
            <div class="stats-card">
                <div class="stats-icon blue">
                    <span>👥</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo mysqli_num_rows($result_kandidat); ?></h3>
                    <p>Total Kandidat</p>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon orange">
                    <span>🗳️</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_suara; ?></h3>
                    <p>Total Suara</p>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon <?php echo ($status_info['status_real'] == 'berlangsung') ? 'green' : 'purple'; ?>">
                    <span>
                        <?php
                        if ($status_info['status_real'] == 'draft') echo '📝';
                        elseif ($status_info['status_real'] == 'belum_dimulai') echo '⏳';
                        elseif ($status_info['status_real'] == 'berlangsung') echo '🟢';
                        else echo '🔴';
                        ?>
                    </span>
                </div>
                <div class="stats-info">
                    <h3>
                        <?php
                        if ($status_info['status_real'] == 'draft') echo 'Draft';
                        elseif ($status_info['status_real'] == 'belum_dimulai') echo 'Belum Mulai';
                        elseif ($status_info['status_real'] == 'berlangsung') echo 'Berlangsung';
                        else echo 'Selesai';
                        ?>
                    </h3>
                    <p>Status</p>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon green">
                    <span>📅</span>
                </div>
                <div class="stats-info">
                    <h3>
                        <?php
                        $start = strtotime($pemilu['tanggal_mulai']);
                        $end = strtotime($pemilu['tanggal_selesai']);
                        $durasi = ($end - $start) / (60 * 60 * 24);
                        echo round($durasi);
                        ?>
                    </h3>
                    <p>Durasi (Hari)</p>
                </div>
            </div>
        </div>

        <!-- Detail Informasi -->
        <div class="content-card">
            <h3>📋 Informasi Detail</h3>

            <div class="detail-grid">
                <div class="detail-item">
                    <strong>Tanggal Mulai</strong>
                    <?php echo date('d F Y, H:i', strtotime($pemilu['tanggal_mulai'])); ?>
                </div>

                <div class="detail-item">
                    <strong>Tanggal Selesai</strong>
                    <?php echo date('d F Y, H:i', strtotime($pemilu['tanggal_selesai'])); ?>
                </div>

                <div class="detail-item">
                    <strong>Dibuat Oleh</strong>
                    <?php echo $pemilu['creator']; ?>
                </div>

                <div class="detail-item">
                    <strong>Tanggal Dibuat</strong>
                    <?php echo date('d F Y, H:i', strtotime($pemilu['created_at'])); ?>
                </div>
            </div>

            <!-- Timeline -->
            <h3 style="margin-top: 30px;">⏰ Timeline Pemilu</h3>
            <div class="timeline">
                <div class="timeline-item <?php echo ($status_info['status_real'] == 'draft' || $status_info['status_real'] == 'belum_dimulai') ? 'future' : 'active'; ?>">
                    <strong>Pembuatan Pemilu</strong>
                    <p><?php echo date('d F Y, H:i', strtotime($pemilu['created_at'])); ?></p>
                </div>

                <div class="timeline-item <?php echo ($status_info['status_real'] == 'belum_dimulai') ? 'future' : (($status_info['status_real'] == 'berlangsung' || $status_info['status_real'] == 'selesai') ? 'active' : ''); ?>"><strong>Pemilu Dimulai</strong>
                    <p><?php echo date('d F Y, H:i', strtotime($pemilu['tanggal_mulai'])); ?></p>
                </div>
                <div class="timeline-item <?php echo ($status_info['status_real'] == 'selesai') ? 'active' : 'future'; ?>">
                    <strong>Pemilu Berakhir</strong>
                    <p><?php echo date('d F Y, H:i', strtotime($pemilu['tanggal_selesai'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Hasil Pemilu -->
        <div class="content-card">
            <div class="content-header">
                <h3 class="content-title">📊 Hasil Pemilu</h3>
                <?php if ($total_suara > 0): ?>
                    <div class="action-buttons">
                        <a href="export_hasil_pemilu.php?id=<?php echo $id_election; ?>" class="btn-action btn-add" target="_blank">
                            📥 Export
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (mysqli_num_rows($result_kandidat) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Peringkat</th>
                                <th>No. Urut</th>
                                <th>Foto</th>
                                <th>Nama Kandidat</th>
                                <th>Jumlah Suara</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $peringkat = 1;
                            mysqli_data_seek($result_kandidat, 0);
                            while ($row = mysqli_fetch_assoc($result_kandidat)):
                                $persentase = $total_suara > 0 ? round(($row['jumlah_suara'] / $total_suara) * 100, 2) : 0;
                            ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <?php if ($peringkat == 1 && $total_suara > 0): ?>
                                            <span style="font-size: 1.5em;">🥇</span>
                                        <?php elseif ($peringkat == 2): ?>
                                            <span style="font-size: 1.5em;">🥈</span>
                                        <?php elseif ($peringkat == 3): ?>
                                            <span style="font-size: 1.5em;">🥉</span>
                                        <?php else: ?>
                                            <strong><?php echo $peringkat; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $row['nomor_urut']; ?></strong></td>
                                    <td>
                                        <img src="../assets/img/kandidat/<?php echo $row['foto']; ?>"
                                            alt="<?php echo $row['nama_kandidat']; ?>">
                                    </td>
                                    <td><?php echo $row['nama_kandidat']; ?></td>
                                    <td><strong><?php echo $row['jumlah_suara']; ?> suara</strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="flex: 1; background-color: #e0e0e0; height: 25px; border-radius: 12px; overflow: hidden;">
                                                <div style="width: <?php echo $persentase; ?>%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%;"></div>
                                            </div>
                                            <span style="min-width: 50px;"><strong><?php echo $persentase; ?>%</strong></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                                $peringkat++;
                            endwhile;
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3>Belum Ada Kandidat</h3>
                    <p>Silakan tambahkan kandidat untuk pemilu ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>