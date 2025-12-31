<?php
// File: admin/export_hasil_pemilu.php
// Deskripsi: Export hasil pemilu tertentu ke Excel

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
$query_election = "SELECT * FROM elections WHERE id_election = ?";
$stmt = mysqli_prepare($conn, $query_election);
mysqli_stmt_bind_param($stmt, "i", $id_election);
mysqli_stmt_execute($stmt);
$election = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$election) {
    set_flash_message('danger', 'Pemilu tidak ditemukan!');
    redirect('pemilu.php');
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

// Set header untuk download Excel
$filename = 'Hasil_' . str_replace(' ', '_', $election['nama_pemilu']) . '_' . date('Y-m-d_H-i-s') . '.xls';
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// Output Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hasil Pemilu - <?php echo $election['nama_pemilu']; ?></title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .info {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HASIL PEMILU</h1>
        <h2><?php echo $election['nama_pemilu']; ?></h2>
        <p><?php echo $election['deskripsi']; ?></p>
        <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>Tanggal Pemilu Mulai:</strong></td>
                <td><?php echo date('d F Y, H:i', strtotime($election['tanggal_mulai'])); ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal Pemilu Selesai:</strong></td>
                <td><?php echo date('d F Y, H:i', strtotime($election['tanggal_selesai'])); ?></td>
            </tr>
            <tr>
                <td><strong>Total Pemilih Terdaftar:</strong></td>
                <td><?php echo $total_pemilih; ?> pemilih</td>
            </tr>
            <tr>
                <td><strong>Total Suara Masuk:</strong></td>
                <td><?php echo $total_suara; ?> suara</td>
            </tr>
            <tr>
                <td><strong>Persentase Partisipasi:</strong></td>
                <td><?php echo $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 2) : 0; ?>%</td>
            </tr>
            <tr>
                <td><strong>Status Pemilu:</strong></td>
                <td><?php echo ucfirst($election['status']); ?></td>
            </tr>
        </table>
    </div>

    <h3>HASIL PEROLEHAN SUARA</h3>
    <table>
        <thead>
            <tr>
                <th>Peringkat</th>
                <th>Nomor Urut</th>
                <th>Nama Kandidat</th>
                <th>Visi</th>
                <th>Misi</th>
                <th>Jumlah Suara</th>
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
                    <td><?php echo $peringkat; ?></td>
                    <td><?php echo $row['nomor_urut']; ?></td>
                    <td><?php echo $row['nama_kandidat']; ?></td>
                    <td><?php echo $row['visi']; ?></td>
                    <td><?php echo $row['misi']; ?></td>
                    <td><?php echo $row['jumlah_suara']; ?></td>
                    <td><?php echo $persentase; ?>%</td>
                </tr>
            <?php 
            $peringkat++;
            endwhile; 
            ?>
        </tbody>
    </table>

    <br><br>
    <div>
        <p><strong>Keterangan:</strong></p>
        <ul>
            <li>Laporan ini berisi hasil pemilu: <?php echo $election['nama_pemilu']; ?></li>
            <li>Data diurutkan berdasarkan perolehan suara tertinggi</li>
            <li>Persentase dihitung dari total suara yang masuk</li>
        </ul>
    </div>

    <br><br>
    <div>
        <p>Dicetak oleh: <?php echo $_SESSION['nama_lengkap']; ?> (Administrator)</p>
        <p>Tanggal: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
exit();
?>