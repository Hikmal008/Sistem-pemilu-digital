<?php
// File: admin/export_pdf_pemilu.php
// Deskripsi: Export hasil pemilu tertentu ke PDF (versi print-friendly)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Cek apakah ada ID pemilu
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID pemilu tidak ditemukan!');
    redirect('hasil.php');
}

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

// Query untuk hasil pemilu
$query = "SELECT k.nomor_urut, k.nama_kandidat, k.visi, k.misi,
          COUNT(v.id_voting) as jumlah_suara
          FROM kandidat k
          LEFT JOIN voting v ON k.id_kandidat = v.id_kandidat AND v.id_election = ?
          WHERE k.id_election = ?
          GROUP BY k.id_kandidat
          ORDER BY jumlah_suara DESC, k.nomor_urut ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_election, $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Hitung total suara
$query_total = "SELECT COUNT(*) as total FROM voting WHERE id_election = ?";
$stmt_total = mysqli_prepare($conn, $query_total);
mysqli_stmt_bind_param($stmt_total, "i", $id_election);
mysqli_stmt_execute($stmt_total);
$total_suara = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total))['total'];

// Hitung total pemilih
$query_pemilih = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result_pemilih = mysqli_query($conn, $query_pemilih);
$total_pemilih = mysqli_fetch_assoc($result_pemilih)['total'];

$persentase_partisipasi = $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil - <?php echo $election['nama_pemilu']; ?></title>
    <link rel="stylesheet" href="../assets/css/cetak.css" media="all">

</head>
<body>
    <div class="no-print" style="display: flex; gap: 10px; margin-bottom: 20px;">
    <button onclick="window.print()" class="print-btn">
        🖨️ Cetak / Simpan PDF
    </button>
    <a href="hasil.php" class="print-btn" style="background: #6c757d; text-decoration: none; display: inline-block; text-align: center;">
        ← Kembali ke Hasil
    </a>
    </div>
    <div class="header">
        <h1>LAPORAN HASIL PEMILU</h1>
        <h2><?php echo $election['nama_pemilu']; ?></h2>
        <p><?php echo $election['deskripsi']; ?></p>
        <p>Tanggal Export: <?php echo date('d F Y, H:i:s'); ?></p>
    </div>

    <h3>INFORMASI UMUM</h3>
    <table class="info-table">
        <tr>
            <td>Tanggal Pemilu Mulai</td>
            <td><?php echo date('d F Y, H:i', strtotime($election['tanggal_mulai'])); ?></td>
        </tr>
        <tr>
            <td>Tanggal Pemilu Selesai</td>
            <td><?php echo date('d F Y, H:i', strtotime($election['tanggal_selesai'])); ?></td>
        </tr>
        <tr>
            <td>Total Kandidat</td>
            <td><?php echo mysqli_num_rows($result); ?> kandidat</td>
        </tr>
        <tr>
            <td>Total Pemilih Terdaftar</td>
            <td><?php echo $total_pemilih; ?> pemilih</td>
        </tr>
        <tr>
            <td>Total Suara Masuk</td>
            <td><?php echo $total_suara; ?> suara</td>
        </tr>
        <tr>
            <td>Persentase Partisipasi</td>
            <td><?php echo $persentase_partisipasi; ?>%</td>
        </tr>
        <tr>
            <td>Status Pemilu</td>
            <td><?php echo ucfirst($election['status']); ?></td>
        </tr>
    </table>

    <h3>HASIL PEROLEHAN SUARA</h3>
    <table>
        <thead>
            <tr>
                <th style="text-align: center;">Peringkat</th>
                <th style="text-align: center;">No. Urut</th>
                <th>Nama Kandidat</th>
                <th style="text-align: center;">Jumlah Suara</th>
                <th style="text-align: center;">Persentase</th>
                <th style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $peringkat = 1;
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)): 
                $persentase = $total_suara > 0 ? round(($row['jumlah_suara'] / $total_suara) * 100, 2) : 0;
            ?>
                <tr>
                    <td style="text-align: center;">
                        <?php if ($peringkat == 1 && $total_suara > 0): ?>
                            🥇
                        <?php elseif ($peringkat == 2): ?>
                            🥈
                        <?php elseif ($peringkat == 3): ?>
                            🥉
                        <?php else: ?>
                            <?php echo $peringkat; ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><strong><?php echo $row['nomor_urut']; ?></strong></td>
                    <td><?php echo $row['nama_kandidat']; ?></td>
                    <td style="text-align: center;"><strong><?php echo $row['jumlah_suara']; ?></strong></td>
                    <td style="text-align: center;"><strong><?php echo $persentase; ?>%</strong></td>
                    <td style="text-align: center;">
                        <?php if ($peringkat == 1 && $total_suara > 0): ?>
                            <span class="pemenang">👑 PEMENANG</span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php 
            $peringkat++;
            endwhile; 
            ?>
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Keterangan:</strong></p>
        <ul>
            <li>Laporan ini berisi hasil pemilu: <?php echo $election['nama_pemilu']; ?></li>
            <li>Data diurutkan berdasarkan perolehan suara tertinggi</li>
            <li>Persentase dihitung dari total suara yang masuk</li>
        </ul>
        
        <p style="margin-top: 30px;">
            <strong>Dicetak oleh:</strong> <?php echo $_SESSION['nama_lengkap']; ?> (Administrator)<br>
            <strong>Tanggal:</strong> <?php echo date('d F Y, H:i:s'); ?>
        </p>
    </div>
</body>
</html>
