<?php
// File: admin/pemilu.php
// Deskripsi: Manajemen pemilu (admin)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/election_helper.php';

// Cek apakah user adalah admin
check_admin();

// ===============================
// MANUAL ACTIVATE PEMILU
// ===============================
if (isset($_POST['activate_now'])) {

    $id_election = (int)$_POST['id_election'];
    $now = date('Y-m-d H:i:s');

    // Cek apakah masih ada pemilu berlangsung
    $cek = mysqli_query($conn, "
        SELECT id_election 
        FROM elections 
        WHERE status = 'aktif'
        LIMIT 1
    ");

    if (mysqli_num_rows($cek) > 0) {
        set_flash_message('danger', 'Masih ada pemilu yang sedang berlangsung.');
        header("Location: pemilu.php");
        exit;
    }
    $qDurasi = mysqli_query($conn, "
    SELECT tanggal_mulai, tanggal_selesai
    FROM elections
    WHERE id_election = $id_election
    AND status = 'draft'
    ");

    $data = mysqli_fetch_assoc($qDurasi);

    $durasi_hari = ceil(
        (strtotime($data['tanggal_selesai']) - strtotime($data['tanggal_mulai']))
            / (60 * 60 * 24)
    );

    // Aktifkan pemilu (override timer)
    mysqli_query($conn, "
    UPDATE elections
    SET status = 'aktif',
        tanggal_mulai = '$now',
        tanggal_selesai = DATE_ADD('$now', INTERVAL $durasi_hari DAY)
    WHERE id_election = $id_election
    AND status = 'draft'
    ");


    set_flash_message('success', 'Pemilu berhasil diaktifkan sekarang.');
    header("Location: pemilu.php");
    exit;
}

// ===============================
// AKHIRI PEMILU SEKARANG (MANUAL)
// ===============================
if (isset($_POST['finish_now'])) {

    $id_election = (int)$_POST['id_election'];
    $now = date('Y-m-d H:i:s');

    mysqli_query($conn, "
        UPDATE elections
        SET status = 'selesai',
            tanggal_selesai = '$now'
        WHERE id_election = $id_election
        AND status = 'aktif'
    ");
    promote_next_election_if_any();
    $seri = checkElectionTie($id_election);

    if ($seri) {
        createElectionReplay($id_election, $seri);
        set_flash_message(
            'warning',
            'Hasil pemilu SERI. Pemilu ulang telah dibuat (status Draft).'
        );
    }



    set_flash_message('success', 'Pemilu berhasil diakhiri.');
    header("Location: pemilu.php");
    exit;
}


// Auto update status pemilu
auto_update_election_status();

// Ambil flash message
$flash = get_flash_message();

// Query semua pemilu
$query = "
    SELECT 
        e.*, 
        u.nama_lengkap AS creator,
        (SELECT COUNT(*) FROM kandidat k WHERE k.id_election = e.id_election) AS jumlah_kandidat,
        (SELECT COUNT(*) FROM voting v WHERE v.id_election = e.id_election) AS jumlah_suara
    FROM elections e
    JOIN users u ON e.created_by = u.id_user
    ORDER BY e.created_at DESC
";

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
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--kpu-red);
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }

        .election-title {
            font-size: 1.6em;
            color: var(--kpu-red);
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .election-dates {
            display: flex;
            gap: 24px;
            margin: 16px 0;
            color: var(--gray-700);
        }

        .election-dates div {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .election-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
        }

        .stat-box {
            background: var(--gray-50);
            padding: 16px;
            border-radius: var(--radius);
            text-align: center;
            border: 2px solid var(--gray-200);
        }

        .stat-box h4 {
            font-size: 2em;
            margin: 0;
            color: var(--kpu-red);
            font-weight: 700;
        }

        .stat-box p {
            margin: 8px 0 0 0;
            color: var(--gray-600);
            font-size: 0.9em;
            font-weight: 600;
        }

        .countdown {
            background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%);
            color: white;
            padding: 16px;
            border-radius: var(--radius);
            text-align: center;
            margin-top: 16px;
        }

        .countdown h4 {
            margin: 0 0 10px 0;
            font-size: 1.1em;
        }

        .countdown-timer {
            font-size: 1.5em;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
    </style>
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
            <h1 class="main-title">Manajemen Pemilu</h1>
            <a href="tambah_pemilu.php" class="btn btn-primary">
                ➕ Buat Pemilu Baru
            </a>
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
                        <div style="flex: 1;">
                            <h3 class="election-title"><?php echo $row['nama_pemilu']; ?></h3>
                            <p style="color: var(--gray-600); margin: 5px 0;">
                                <?php echo $row['deskripsi']; ?>
                            </p>

                            <?php if ($status_real == 'draft'): ?>
                                <span class="badge badge-gray">📝 Draft</span>
                            <?php elseif ($status_real == 'belum_dimulai'): ?>
                                <span class="badge badge-warning">⏳ Belum Dimulai</span>
                            <?php elseif ($status_real == 'berlangsung'): ?>
                                <span class="badge badge-success">🟢 Berlangsung</span>
                            <?php else: ?>
                                <span class="badge badge-danger">🔴 Selesai</span>
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons">
                            <a href="detail_pemilu.php?id=<?php echo $row['id_election']; ?>"
                                class="btn-action btn-view">
                                👁️ Detail
                            </a>

                            <?php if ($status_real === 'draft'): ?>
                                <a href="edit_pemilu.php?id=<?php echo $row['id_election']; ?>"
                                    class="btn-action btn-edit">
                                    ✏️ Edit
                                </a>
                            <?php endif; ?>

                            <?php if ($status_real === 'draft'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_election" value="<?php echo $row['id_election']; ?>">
                                    <button type="submit"
                                        name="activate_now"
                                        class="btn-action btn-success"
                                        onclick="return confirm('Aktifkan pemilu sekarang?')">
                                        ▶️ Aktifkan Sekarang
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($status_real === 'draft' || $status_real === 'selesai'): ?>
                                <a href="hapus_pemilu.php?id=<?php echo $row['id_election']; ?>"
                                    class="btn-action btn-delete"
                                    onclick="return confirmHapusPemilu(
                                    '<?php echo addslashes($row['nama_pemilu']); ?>',
                                    '<?php echo $status_real; ?>'
                                    )">
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
                    <?php if ($status_real === 'berlangsung'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_election" value="<?php echo $row['id_election']; ?>">
                            <button type="submit"
                                name="finish_now"
                                class="btn-action btn-danger"
                                onclick="return confirm('Akhiri pemilu sekarang? Semua proses voting akan dihentikan.')">
                                ⏹️ Akhiri Sekarang
                            </button>
                        </form>
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

    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

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
        updateCountdown();

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
    <script>
        function confirmHapusPemilu(namaPemilu, status) {

            let pesan = "";

            if (status === "draft") {
                pesan =
                    "⚠️ KONFIRMASI HAPUS PEMILU\n\n" +
                    "Nama Pemilu: " + namaPemilu + "\n\n" +
                    "Pemilu ini masih DRAFT.\n" +
                    "Data akan dihapus secara permanen.\n\n" +
                    "Lanjutkan penghapusan?";
            } else if (status === "selesai") {
                pesan =
                    "⚠️ PERINGATAN PENTING!\n\n" +
                    "Nama Pemilu: " + namaPemilu + "\n\n" +
                    "Pemilu ini SUDAH SELESAI.\n" +
                    "SEMUA DATA berikut akan DIHAPUS:\n" +
                    "- Kandidat\n" +
                    "- Seluruh suara pemilih\n\n" +
                    "Tindakan ini TIDAK DAPAT DIBATALKAN!\n\n" +
                    "Yakin ingin melanjutkan?";
            } else {
                return false;
            }

            return confirm(pesan);
        }
    </script>

</body>

</html>