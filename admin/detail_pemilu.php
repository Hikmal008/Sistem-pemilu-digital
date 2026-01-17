<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

/* ================= VALIDASI ID ================= */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$id_election = clean_input($_GET['id']);

/* ================= DATA PEMILU ================= */
$query = "
    SELECT e.*, u.nama_lengkap AS creator
    FROM elections e
    JOIN users u ON e.created_by = u.id_user
    WHERE e.id_election = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    set_flash_message('danger', 'Pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$pemilu = mysqli_fetch_assoc($result);
$status_info = get_election_status($id_election);

/* ================= DATA KANDIDAT ================= */
$query_kandidat = "
    SELECT k.*, COUNT(v.id_voting) AS jumlah_suara
    FROM kandidat k
    LEFT JOIN voting v ON k.id_kandidat = v.id_kandidat
    WHERE k.id_election = ?
    GROUP BY k.id_kandidat
    ORDER BY jumlah_suara DESC, k.nomor_urut ASC
";

$stmt_kandidat = mysqli_prepare($conn, $query_kandidat);
mysqli_stmt_bind_param($stmt_kandidat, "i", $id_election);
mysqli_stmt_execute($stmt_kandidat);
$result_kandidat = mysqli_stmt_get_result($stmt_kandidat);

/* ================= TOTAL SUARA ================= */
$query_total = "SELECT COUNT(*) AS total FROM voting WHERE id_election = ?";
$stmt_total = mysqli_prepare($conn, $query_total);
mysqli_stmt_bind_param($stmt_total, "i", $id_election);
mysqli_stmt_execute($stmt_total);
$total_suara = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'];

/* ================= FLASH ================= */
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
            <a href="pemilu.php" class="sidebar-nav-item active">
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
            <div style="flex: 1;">
                <h1 class="main-title"><?php echo $pemilu['nama_pemilu']; ?></h1>
                <p style="color: var(--gray-600); margin-top: 8px;">
                    <?php echo $pemilu['deskripsi']; ?>
                </p>
            </div>
            <div class="action-buttons">
                <a href="pemilu.php" class="btn btn-secondary">
                    ← Kembali
                </a>
                <?php
                if ($status_info['status_real'] != 'selesai'): ?> <a href="edit_pemilu.php?id=<?php echo $id_election; ?>" class="btn btn-primary"> ✏️ Edit </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistik -->
        <div class="dashboard-grid">
            <div class="stats-card blue">
                <div class="stats-icon blue">
                    <span>👥</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo mysqli_num_rows($result_kandidat); ?></h3>
                    <p>Total Kandidat</p>
                </div>
            </div>

            <div class="stats-card orange">
                <div class="stats-icon orange">
                    <span>🗳️</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_suara; ?></h3>
                    <p>Total Suara</p>
                </div>
            </div>

            <div class="stats-card <?php echo ($status_info['status_real'] == 'berlangsung') ? 'green' : 'red'; ?>">
                <div class="stats-icon <?php echo ($status_info['status_real'] == 'berlangsung') ? 'green' : 'red'; ?>">
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

            <div class="stats-card green">
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
            <h3 style="color: var(--kpu-red); margin-bottom: 20px;">📋 Informasi Detail</h3>

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
            <h3 style="margin-top: 30px; color: var(--kpu-red);">⏰ Timeline Pemilu</h3>
            <div class="timeline">
                <div class="timeline-item <?php echo ($status_info['status_real'] == 'draft' || $status_info['status_real'] == 'belum_dimulai') ? 'future' : 'active'; ?>">
                    <strong style="color: var(--gray-900);">Pembuatan Pemilu</strong>
                    <p style="color: var(--gray-600); margin-top: 4px;"><?php echo date('d F Y, H:i', strtotime($pemilu['created_at'])); ?></p>
                </div>

                <div class="timeline-item <?php echo ($status_info['status_real'] == 'belum_dimulai') ? 'future' : (($status_info['status_real'] == 'berlangsung' || $status_info['status_real'] == 'selesai') ? 'active' : ''); ?>">
                    <strong style="color: var(--gray-900);">Pemilu Dimulai</strong>
                    <p style="color: var(--gray-600); margin-top: 4px;"><?php echo date('d F Y, H:i', strtotime($pemilu['tanggal_mulai'])); ?></p>
                </div>

                <div class="timeline-item <?php echo ($status_info['status_real'] == 'selesai') ? 'active' : 'future'; ?>">
                    <strong style="color: var(--gray-900);">Pemilu Berakhir</strong>
                    <p style="color: var(--gray-600); margin-top: 4px;"><?php echo date('d F Y, H:i', strtotime($pemilu['tanggal_selesai'])); ?></p>
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
                            📥 Excel
                        </a>
                        <a href="export_pdf_pemilu.php?id=<?php echo $id_election; ?>" class="btn-action btn-view" target="_blank">
                            📄 PDF
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (mysqli_num_rows($result_kandidat) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: center;">Peringkat</th>
                                <th style="text-align: center;">No. Urut</th>
                                <th>Foto</th>
                                <th>Nama Kandidat</th>
                                <th style="text-align: center;">Jumlah Suara</th>
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
                                            <span style="font-size: 2em;">🥇</span>
                                        <?php elseif ($peringkat == 2): ?>
                                            <span style="font-size: 2em;">🥈</span>
                                        <?php elseif ($peringkat == 3): ?>
                                            <span style="font-size: 2em;">🥉</span>
                                        <?php else: ?>
                                            <strong style="font-size: 1.2em;"><?php echo $peringkat; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%); color: white; width: 45px; height: 45px; border-radius: 50%; font-size: 1.3em; font-weight: 700;">
                                            <?php echo $row['nomor_urut']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <img src="../assets/img/kandidat/<?php echo $row['foto']; ?>"
                                            alt="<?php echo $row['nama_kandidat']; ?>"
                                            style="width: 60px; height: 60px; border-radius: var(--radius); object-fit: cover;">
                                    </td>
                                    <td><strong><?php echo $row['nama_kandidat']; ?></strong></td>
                                    <td style="text-align: center;">
                                        <strong style="font-size: 1.3em; color: var(--kpu-red);"><?php echo $row['jumlah_suara']; ?></strong>
                                        <div style="font-size: 0.85em; color: var(--gray-600);">suara</div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="flex: 1; background-color: var(--gray-200); height: 28px; border-radius: var(--radius-full); overflow: hidden;">
                                                <div style="width: <?php echo $persentase; ?>%; background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%); height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9em;">
                                                    <?php if ($persentase > 10): ?>
                                                        <?php echo $persentase; ?>%
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span style="min-width: 55px; text-align: right;">
                                                <strong style="font-size: 1.1em;"><?php echo $persentase; ?>%</strong>
                                            </span>
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
                    <a href="tambah_kandidat.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ Tambah Kandidat
                    </a>
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