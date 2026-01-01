<?php

// File: user/index.php
// Deskripsi: Halaman utama user/pemilih
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
    $sudah_voting = has_voted_in_election(
        $_SESSION['user_id'],
        $active_election['id_election']
    );
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

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_kandidat = $row['total'];
}

// Hitung total suara di pemilu aktif
$total_suara = 0;
if ($active_election) {
    $query_total_suara = "SELECT COUNT(*) as total FROM voting WHERE id_election = ?";
    $stmt = mysqli_prepare($conn, $query_total_suara);
    mysqli_stmt_bind_param($stmt, "i", $active_election['id_election']);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_suara = $row['total'];
}

// Ambil arsip pemilu yang sudah selesai
$query_arsip = "
    SELECT id_election, nama_pemilu, tanggal_selesai
    FROM elections
    WHERE status = 'selesai'
    ORDER BY tanggal_selesai DESC
    LIMIT 5
";

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
    </nav><!-- Container -->
<div class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>    <!-- Info Box - KONTRAS DIPERBAIKI -->
    <div class="info-box">
        <h2>Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h2>
        <p>Gunakan hak suara Anda dengan bijak untuk masa depan yang lebih baik</p>
    </div>    <!-- Status Pemilu Aktif -->
    <?php if ($active_election): ?>
        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">🗳️ <?php echo $active_election['nama_pemilu']; ?></h2>
                <span class="status-badge open">Pemilu Berlangsung</span>
            </div>            <p style="color: var(--gray-700); margin-bottom: 20px; font-size: 1.05em; line-height: 1.6;">
                <?php echo $active_election['deskripsi']; ?>
            </p>            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div style="background: var(--gray-50); padding: 16px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                    <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">📅 Dimulai:</strong>
                    <span style="color: var(--gray-800);">
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_mulai'])); ?>
                    </span>
                </div>
                <div style="background: var(--gray-50); padding: 16px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                    <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">📅 Berakhir:</strong>
                    <span style="color: var(--gray-800);">
                        <?php echo date('d F Y, H:i', strtotime($active_election['tanggal_selesai'])); ?>
                    </span>
                </div>
            </div>            <?php if ($sudah_voting): ?>
                <div class="alert alert-info">
                    ✅ <strong>Anda sudah memberikan suara di pemilu ini!</strong><br>
                    Terima kasih atas partisipasi Anda dalam demokrasi.
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="hasil.php" class="btn btn-primary" style="padding: 14px 32px;">
                        📊 Lihat Hasil Pemilu
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    📢 <strong>Pemilu sedang berlangsung!</strong><br>
                    Silakan klik tombol di bawah untuk memberikan suara Anda.
                </div>
                <div style="text-align: center; margin-top: 24px;">
                    <a href="voting.php" class="btn btn-primary" style="padding: 16px 48px; font-size: 1.2em;">
                        🗳️ Mulai Voting Sekarang
                    </a>
                </div>
            <?php endif; ?>
        </div>        <!-- Statistik Pemilu Aktif -->
        <div class="dashboard-grid">
            <div class="stats-card">
                <div class="stats-icon blue">
                    <span>👥</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_kandidat; ?></h3>
                    <p>Total Kandidat</p>
                </div>
            </div>            <div class="stats-card">
                <div class="stats-icon orange">
                    <span>🗳️</span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $total_suara; ?></h3>
                    <p>Suara Masuk</p>
                </div>
            </div>            <div class="stats-card">
                <div class="stats-icon <?php echo $sudah_voting ? 'green' : 'purple'; ?>">
                    <span><?php echo $sudah_voting ? '✅' : '⏳'; ?></span>
                </div>
                <div class="stats-info">
                    <h3><?php echo $sudah_voting ? 'Sudah' : 'Belum'; ?></h3>
                    <p>Status Voting Anda</p>
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
                <a href="arsip.php" class="btn btn-secondary" style="margin-top: 20px;">
                    📚 Lihat Arsip Pemilu
                </a>
            </div>
        </div>
    <?php endif; ?>    <!-- Arsip Pemilu -->
    <?php if (mysqli_num_rows($result_arsip) > 0): ?>
        <div class="content-card">
            <div class="content-header">
                <h3 class="content-title">📚 Arsip Pemilu Sebelumnya</h3>
                <a href="arsip.php" style="color: var(--kpu-red); text-decoration: none; font-weight: 600;">
                    Lihat Semua →
                </a>
            </div>            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php while ($arsip = mysqli_fetch_assoc($result_arsip)): ?>
                    <a href="hasil.php?id=<?php echo $arsip['id_election']; ?>" 
                       style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: var(--gray-50); border-radius: var(--radius); text-decoration: none; color: var(--gray-900); transition: all 0.3s; border: 2px solid transparent;"
                       onmouseover="this.style.borderColor='var(--kpu-red)'; this.style.transform='translateX(5px)';"
                       onmouseout="this.style.borderColor='transparent'; this.style.transform='translateX(0)';">
                        <div>
                            <strong style="color: var(--kpu-red); font-size: 1.05em;">
                                <?php echo $arsip['nama_pemilu']; ?>
                            </strong>
                        </div>
                        <div style="font-size: 0.9em; color: var(--gray-600);">
                            <?php echo date('d F Y', strtotime($arsip['tanggal_selesai'])); ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>    <!-- Panduan Voting -->
    <div class="content-card">
        <div class="content-header">
            <h2 class="content-title">📋 Panduan Voting</h2>
        </div>        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                <div style="font-size: 2em; margin-bottom: 10px;">1️⃣</div>
                <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">Login ke Akun</strong>
                <p style="color: var(--gray-700); margin: 0; line-height: 1.6;">Pastikan Anda sudah login dengan akun yang terdaftar</p>
            </div>            <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                <div style="font-size: 2em; margin-bottom: 10px;">2️⃣</div>
                <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">Lihat Kandidat</strong>
                <p style="color: var(--gray-700); margin: 0; line-height: 1.6;">Baca visi dan misi setiap kandidat dengan teliti</p>
            </div>            <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                <div style="font-size: 2em; margin-bottom: 10px;">3️⃣</div>
                <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">Pilih Kandidat</strong>
                <p style="color: var(--gray-700); margin: 0; line-height: 1.6;">Klik tombol "Pilih" pada kandidat pilihan Anda</p>
            </div>            <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius); border-left: 4px solid var(--kpu-red);">
                <div style="font-size: 2em; margin-bottom: 10px;">4️⃣</div>
                <strong style="color: var(--kpu-red); display: block; margin-bottom: 8px;">Konfirmasi</strong>
                <p style="color: var(--gray-700); margin: 0; line-height: 1.6;">Konfirmasi pilihan Anda dan suara akan tersimpan</p>
            </div>
        </div>        <div class="alert alert-warning" style="margin-top: 20px;">
            <strong> PERHATIAN:</strong> Setiap akun hanya dapat memilih <strong>SATU KALI per pemilu</strong>. Pilih dengan bijak!
        </div>
    </div>
</div>
</body>
</html>
