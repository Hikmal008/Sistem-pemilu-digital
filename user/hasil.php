<?php
// File: user/hasil.php
// Deskripsi: Halaman hasil pemilu (user)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah pemilih
check_user();

// Auto update status pemilu
auto_update_election_status();

// Tentukan pemilu yang akan ditampilkan
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_election = clean_input($_GET['id']);

    $query_election = "SELECT * FROM elections WHERE id_election = ?";
    $stmt = mysqli_prepare($conn, $query_election);
    mysqli_stmt_bind_param($stmt, "i", $id_election);
    mysqli_stmt_execute($stmt);
    $result_election = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result_election) === 0) {
        set_flash_message('danger', 'Pemilu tidak ditemukan!');
        redirect('index.php');
    }

    $election = mysqli_fetch_assoc($result_election);
} else {
    $election = get_active_election();

    if (!$election) {
        set_flash_message(
            'info',
            'Tidak ada pemilu yang sedang berlangsung. Silakan pilih dari arsip.'
        );
        redirect('arsip.php');
    }

    $id_election = $election['id_election'];
}

// Cek apakah user sudah voting di pemilu ini
$sudah_voting = has_voted_in_election(
    $_SESSION['user_id'],
    $id_election
);

// Ambil flash message
$flash = get_flash_message();

// Query hasil pemilu
$query = "
    SELECT 
        k.id_kandidat,
        k.nomor_urut,
        k.nama_kandidat,
        k.foto,
        k.visi,
        k.misi,
        COUNT(v.id_voting) AS jumlah_suara
    FROM kandidat k
    LEFT JOIN voting v 
        ON k.id_kandidat = v.id_kandidat 
       AND v.id_election = ?
    WHERE k.id_election = ?
    GROUP BY k.id_kandidat
    ORDER BY jumlah_suara DESC, k.nomor_urut ASC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_election, $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Hitung total suara
$query_total = "SELECT COUNT(*) AS total FROM voting WHERE id_election = ?";
$stmt_total = mysqli_prepare($conn, $query_total);
mysqli_stmt_bind_param($stmt_total, "i", $id_election);
mysqli_stmt_execute($stmt_total);
$row_total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total));
$total_suara = $row_total['total'];

// Cari kandidat yang dipilih user (jika sudah voting)
$kandidat_dipilih = null;
if ($sudah_voting) {
    $query_pilihan = "
        SELECT 
            k.nama_kandidat,
            k.nomor_urut,
            v.waktu_voting
        FROM voting v
        JOIN kandidat k ON v.id_kandidat = k.id_kandidat
        WHERE v.id_user = ? AND v.id_election = ?
    ";

    $stmt_pilihan = mysqli_prepare($conn, $query_pilihan);
    mysqli_stmt_bind_param(
        $stmt_pilihan,
        "ii",
        $_SESSION['user_id'],
        $id_election
    );
    mysqli_stmt_execute($stmt_pilihan);
    $kandidat_dipilih = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt_pilihan)
    );
}

// Ambil status pemilu
$election_status = get_election_status($id_election);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil - <?php echo $election['nama_pemilu']; ?></title>
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
            <a href="voting.php">Voting</a>
            <a href="hasil.php" class="active">Hasil</a>
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
    <?php endif; ?>    <!-- Info Pilihan User -->
    <?php if ($sudah_voting && $kandidat_dipilih): ?>
        <div class="content-card" style="background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%); color: white; border: none;">
            <h3 style="margin-bottom: 16px; font-size: 1.3em;">✅ Pilihan Anda:</h3>
            <h2 style="font-size: 2.2em; margin: 12px 0; font-weight: 700;">
                Nomor Urut <?php echo $kandidat_dipilih['nomor_urut']; ?> - <?php echo $kandidat_dipilih['nama_kandidat']; ?>
            </h2>
            <p style="opacity: 0.95; margin-top: 12px; font-size: 1.05em;">
                📅 Waktu Voting: <?php echo date('d F Y, H:i:s', strtotime($kandidat_dipilih['waktu_voting'])); ?>
            </p>
        </div>
    <?php endif; ?>    <!-- Header Pemilu -->
    <div class="content-card">
        <div class="content-header">
            <div style="flex: 1;">
                <h2 class="content-title">📊 <?php echo $election['nama_pemilu']; ?></h2>
                <p style="color: var(--gray-700); margin: 8px 0 0 0; font-size: 1.05em;">
                    <?php echo $election['deskripsi']; ?>
                </p>
            </div>
            <span class="status-badge <?php echo ($election_status['status_real'] == 'berlangsung') ? 'open' : 'closed'; ?>">
                <?php 
                if ($election_status['status_real'] == 'berlangsung') echo 'Sedang Berlangsung';
                elseif ($election_status['status_real'] == 'selesai') echo 'Selesai';
                elseif ($election_status['status_real'] == 'belum_dimulai') echo 'Belum Dimulai';
                else echo 'Draft';
                ?>
            </span>
        </div>        <div style="text-align: center; margin: 24px 0; padding: 24px; background: var(--gray-50); border-radius: var(--radius-lg); border: 3px solid var(--kpu-red);">
            <h3 style="color: var(--kpu-red); font-size: 3em; margin: 0; font-weight: 700;">
                <?php echo $total_suara; ?>
            </h3>
            <p style="color: var(--gray-700); margin: 8px 0 0 0; font-size: 1.1em; font-weight: 600;">
                Total Suara Masuk
            </p>
        </div>
    </div>    <!-- Hasil Pemilu -->
    <div class="content-card">
        <div class="content-header">
            <h3 class="content-title">🏆 Hasil Perolehan Suara</h3>
        </div>        <?php if (mysqli_num_rows($result) > 0): ?>
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
                        while ($row = mysqli_fetch_assoc($result)): 
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
                                        <strong style="font-size: 1.3em;"><?php echo $peringkat; ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-block; background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%); color: white; width: 50px; height: 50px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5em; font-weight: 700;">
                                        <?php echo $row['nomor_urut']; ?>
                                    </div>
                                </td>
                                <td>
                                    <img src="../assets/img/kandidat/<?php echo $row['foto']; ?>" 
                                         alt="<?php echo $row['nama_kandidat']; ?>"
                                         style="width: 70px; height: 70px; border-radius: var(--radius); object-fit: cover; box-shadow: var(--shadow);">
                                </td>
                                <td><strong style="font-size: 1.1em; color: var(--gray-900);"><?php echo $row['nama_kandidat']; ?></strong></td>
                                <td style="text-align: center;">
                                    <strong style="font-size: 1.3em; color: var(--kpu-red);">
                                        <?php echo $row['jumlah_suara']; ?>
                                    </strong>
                                    <div style="font-size: 0.85em; color: var(--gray-600);">suara</div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="flex: 1; background-color: var(--gray-200); height: 30px; border-radius: var(--radius-full); overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
                                            <div style="width: <?php echo $persentase; ?>%; background: linear-gradient(135deg, var(--kpu-red) 0%, var(--kpu-red-dark) 100%); height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9em; transition: width 0.5s ease;">
                                                <?php if ($persentase > 15): ?>
                                                    <?php echo $persentase; ?>%
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span style="min-width: 60px; text-align: right;">
                                            <strong style="font-size: 1.2em; color: var(--kpu-red);">
                                                <?php echo $persentase; ?>%
                                            </strong>
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
                <div class="empty-state-icon">📊</div>
                <h3>Belum Ada Data</h3>
                <p>Belum ada kandidat yang terdaftar</p>
            </div>
        <?php endif; ?>
    </div>    <!-- Tombol Kembali -->
    <?php if (isset($_GET['id'])): ?>
        <div style="text-align: center; margin-top: 24px;">
            <a href="arsip.php" class="btn btn-secondary" style="padding: 14px 32px;">
                ← Kembali ke Arsip
            </a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
