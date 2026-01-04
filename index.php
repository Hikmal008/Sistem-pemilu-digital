<?php
// File: index.php
// Deskripsi: Landing page sistem pemilu - REDESIGNED

session_start();
require_once 'config/database.php'; // Jika sudah login, redirect ke dashboard
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/index.php');
    }
}
function count_data($conn, $query)
{
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)$row['total'];
    }
    return 0;
}

$total_pemilu  = count_data($conn, "SELECT COUNT(*) AS total FROM elections");
$total_voters  = count_data($conn, "SELECT COUNT(*) AS total FROM users WHERE role='user'");
$total_votes   = count_data($conn, "SELECT COUNT(*) AS total FROM voting");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pemilu Elektronik - KPU Indonesia</title>
    <meta name="description" content="Platform pemungutan suara elektronik yang aman, transparan, dan mudah digunakan">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>

<body class="landing-page"><!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">🗳️</div>
            <h1 class="hero-title">Sistem Pemilu Elektronik</h1>
            <p class="hero-subtitle">Platform Pemungutan Suara Digital Indonesia</p>
            <p class="hero-description">
                Sistem pemilu elektronik yang aman, transparan, dan mudah digunakan.
                Memberikan suara Anda dengan bijak untuk masa depan yang lebih baik.
            </p>
            <div class="btn-group">
                <a href="auth/login.php" class="btn-landing btn-login">
                    🔐 Masuk
                </a>
                <a href="auth/register.php" class="btn-landing btn-register">
                    📝 Daftar Sekarang
                </a>
            </div>
        </div>
    </section><!-- Features Section -->
    <section class="features-section">
        <div class="features-container">
            <div class="features-header">
                <h2>Mengapa Memilih Kami?</h2>
                <p>Platform terpercaya dengan teknologi modern dan keamanan terjamin</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Aman & Terpercaya</h3>
                    <p>Data terenkripsi dengan teknologi keamanan tingkat tinggi. Setiap suara terlindungi dan tidak dapat dimanipulasi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Cepat & Mudah</h3>
                    <p>Proses voting yang sederhana dan intuitif. Hanya butuh beberapa klik untuk memberikan suara Anda.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Transparan & Akurat</h3>
                    <p>Hasil real-time yang dapat diakses kapan saja. Perhitungan otomatis dan akurat tanpa kesalahan manual.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Akses Dimana Saja</h3>
                    <p>Voting dari rumah, kantor, atau dimana saja. Platform berbasis web yang responsif di semua perangkat.</p>
                </div>
            </div>
        </div>
    </section><!-- Stats Section -->
    <?php if ($total_pemilu > 0 || $total_voters > 0): ?>
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_pemilu, 0, ',', '.'); ?></div>
                    <div class="stat-label">Pemilu Terdaftar</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_voters, 0, ',', '.'); ?>+</div>
                    <div class="stat-label">Pemilih Terdaftar</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_votes, 0, ',', '.'); ?>+</div>
                    <div class="stat-label">Suara Tersalurkan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Keamanan Data</div>
                </div>
            </div>
        </section>
    <?php endif; ?><!-- How It Works Section -->
    <section class="how-it-works-section">
        <div class="how-it-works-container">
            <h2 class="section-title">Cara Menggunakan</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Daftar Akun</h3>
                    <p>Buat akun baru dengan data diri yang valid. Gunakan NIK dan informasi yang sesuai KTP.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Login & Verifikasi</h3>
                    <p>Masuk ke akun Anda dan sistem akan memverifikasi identitas Anda secara otomatis.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Pilih Kandidat</h3>
                    <p>Lihat profil, visi, dan misi kandidat. Pilih kandidat yang sesuai dengan aspirasi Anda.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Berikan Suara</h3>
                    <p>Konfirmasi pilihan Anda dan suara akan tersimpan dengan aman. Lihat hasil secara real-time.</p>
                </div>
            </div>
        </div>
    </section><!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Siap Memberikan Suara?</h2>
            <p>Bergabunglah dengan ribuan pemilih lainnya dan berpartisipasi dalam demokrasi digital</p>
            <div class="btn-group">
                <a href="auth/register.php" class="btn-landing btn-login">
                    Daftar Sekarang
                </a>
                <a href="auth/login.php" class="btn-landing btn-register">
                    Sudah Punya Akun? Login
                </a>
            </div>
        </div>
    </section><!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> Sistem Pemilu Elektronik. Terinspirasi oleh KPU Indonesia.
            </div>
        </div>
    </footer>
</body>

</html>