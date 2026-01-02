<?php
// File: user/voting.php
// Deskripsi: Halaman voting pemilih

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

// Auto update status pemilu
auto_update_election_status();

// Ambil pemilu aktif
$active_election = get_active_election();

// Jika tidak ada pemilu aktif
if (!$active_election) {
    set_flash_message('danger', 'Tidak ada pemilu yang sedang berlangsung!');
    redirect('index.php');
}

// Cek apakah user sudah voting
$sudah_voting = has_voted_in_election(
    $_SESSION['user_id'],
    $active_election['id_election']
);

// Jika sudah voting, redirect ke hasil
if ($sudah_voting) {
    set_flash_message('info', 'Anda sudah memberikan suara di pemilu ini!');
    redirect('hasil.php?id=' . $active_election['id_election']);
}

// Ambil flash message
$flash = get_flash_message();

// Ambil keyword pencarian kandidat
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Query kandidat
if (!empty($search)) {
    $query = "
        SELECT *
        FROM kandidat
        WHERE id_election = ?
          AND (nama_kandidat LIKE ? OR nomor_urut LIKE ?)
        ORDER BY nomor_urut ASC
    ";
    $stmt = mysqli_prepare($conn, $query);
    $search_param = "%$search%";
    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $active_election['id_election'],
        $search_param,
        $search_param
    );
} else {
    $query = "
        SELECT *
        FROM kandidat
        WHERE id_election = ?
        ORDER BY nomor_urut ASC
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $active_election['id_election']
    );
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Hitung waktu tersisa
$now = time();
$end = strtotime($active_election['tanggal_selesai']);
$time_remaining = $end - $now;
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting - <?php echo $active_election['nama_pemilu']; ?></title>
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
            <a href="index.php">Beranda</a>
            <a href="voting.php" class="active">Voting</a>
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

    <!-- Countdown Banner -->
    <?php if ($time_remaining > 0): ?>
        <div class="countdown-banner">
            <h4>⏰ Pemilu berakhir dalam:</h4>
            <div class="countdown-timer" id="countdown">
                Menghitung...
            </div>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="content-card">
        <div class="content-header">
            <div style="flex: 1;">
                <h2 class="content-title"><?php echo $active_election['nama_pemilu']; ?></h2>
                <p style="color: var(--gray-700); margin: 8px 0 0 0; font-size: 1.05em;">
                    <?php echo $active_election['deskripsi']; ?>
                </p>
            </div>
            <span class="status-badge open">Pemilu Berlangsung</span>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="🔍 Cari kandidat..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       style="width: 100%; max-width: 500px;">
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>
        </div>
    </div>

    <!-- Kandidat Grid -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="kandidat-grid">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="kandidat-card">
                    <div style="position: relative;">
                        <img src="../assets/img/kandidat/<?php echo $row['foto']; ?>" 
                             alt="<?php echo $row['nama_kandidat']; ?>"
                             class="kandidat-image">
                        <div class="kandidat-nomor"><?php echo $row['nomor_urut']; ?></div>
                    </div>
                    
                    <div class="kandidat-body">
                        <h3 class="kandidat-name"><?php echo $row['nama_kandidat']; ?></h3>
                        
                        <div class="kandidat-info">
                            <h4>📌 Visi:</h4>
                            <p><?php echo $row['visi']; ?></p>
                        </div>
                        
                        <div class="kandidat-info">
                            <h4>🎯 Misi:</h4>
                            <p><?php echo $row['misi']; ?></p>
                        </div>
                        
                        <div class="kandidat-actions">
                            <button onclick="confirmVote(<?php echo $row['id_kandidat']; ?>, '<?php echo addslashes($row['nama_kandidat']); ?>', <?php echo $row['nomor_urut']; ?>, <?php echo $active_election['id_election']; ?>)" 
                                    class="btn-pilih">
                                ✅ Pilih Kandidat Ini
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="content-card">
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>Belum Ada Kandidat</h3>
                <p>Belum ada kandidat yang terdaftar untuk pemilu ini</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi -->
<div id="modalKonfirmasi" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Konfirmasi Pilihan</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div style="text-align: center; padding: 20px;">
            <div style="font-size: 4em; margin-bottom: 20px;">🗳️</div>
            <h3 style="margin-bottom: 16px; color: var(--gray-900);">Apakah Anda yakin memilih:</h3>
            <div style="background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%); color: white; padding: 24px; border-radius: var(--radius-lg); margin: 20px 0;">
                <h2 style="margin: 0; font-size: 2em;" id="modalNomor"></h2>
                <h3 style="margin: 12px 0 0 0;" id="modalNama"></h3>
            </div>
            <p style="color: var(--danger); font-weight: 700; margin: 20px 0; font-size: 1.05em;">
                ⚠️ PERHATIAN: Anda hanya dapat memilih SATU KALI di pemilu ini!
            </p>
            <form action="../process/proses_voting.php" method="POST">
                <input type="hidden" name="id_kandidat" id="inputIdKandidat">
<input type="hidden" name="id_election" id="inputIdElection">
<div class="action-buttons" style="margin-top: 24px;">
<button type="submit" class="btn btn-primary" style="flex: 1;">
✅ Ya, Saya Yakin
</button>
<button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">
❌ Batal
</button>
</div>
</form>
</div>
</div>
</div>
<script>
    function confirmVote(idKandidat, namaKandidat, nomorUrut, idElection) {
        document.getElementById('inputIdKandidat').value = idKandidat;
        document.getElementById('inputIdElection').value = idElection;
        document.getElementById('modalNomor').textContent = 'Nomor Urut: ' + nomorUrut;
        document.getElementById('modalNama').textContent = namaKandidat;
        document.getElementById('modalKonfirmasi').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('modalKonfirmasi').classList.remove('show');
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('modalKonfirmasi');
        if (event.target == modal) {
            closeModal();
        }
    }
    
    // Countdown Timer
    function updateCountdown() {
        const target = <?php echo $end * 1000; ?>;
        const now = new Date().getTime();
        const distance = target - now;
        
        if (distance < 0) {
            document.getElementById('countdown').innerHTML = "Waktu telah berakhir";
            setTimeout(function() {
                location.reload();
            }, 2000);
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        document.getElementById('countdown').innerHTML = 
            days + " hari " + hours + " jam " + minutes + " menit " + seconds + " detik";
    }
    
    setInterval(updateCountdown, 1000);
    updateCountdown();
</script>
</body>
</html>