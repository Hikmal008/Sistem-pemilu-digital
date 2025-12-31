<?php
// File: admin/kandidat.php
// UPDATE: Tambahkan filter pemilu

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Ambil flash message
$flash = get_flash_message();

// Pencarian kandidat
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$filter_pemilu = isset($_GET['pemilu']) ? clean_input($_GET['pemilu']) : '';

// Query kandidat dengan pencarian dan filter
$query = "SELECT k.*, e.nama_pemilu, e.status as status_pemilu
          FROM kandidat k
          JOIN elections e ON k.id_election = e.id_election
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (k.nama_kandidat LIKE '%$search%' OR k.nomor_urut LIKE '%$search%')";
}

if (!empty($filter_pemilu)) {
    $query .= " AND k.id_election = '$filter_pemilu'";
}

$query .= " ORDER BY e.created_at DESC, k.nomor_urut ASC";

$result = mysqli_query($conn, $query);

// Query daftar pemilu untuk filter
$query_pemilu = "SELECT id_election, nama_pemilu FROM elections ORDER BY created_at DESC";
$result_pemilu = mysqli_query($conn, $query_pemilu);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kandidat - Sistem Pemilu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
            <a href="kandidat.php" class="active">Kandidat</a>
            <a href="pemilih.php">Pemilih</a>
            <a href="hasil.php">Hasil</a>
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

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-header">
                <h2 class="content-title">Manajemen Kandidat</h2>
                <a href="tambah_kandidat.php" class="btn btn-add">
                    ➕ Tambah Kandidat
                </a>
            </div>

            <!-- Filter dan Search -->
            <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                <!-- Search Box -->
                <div style="flex: 2;">
                    <form action="" method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search" 
                               placeholder="🔍 Cari kandidat..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="flex: 1; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 25px;">
                        <input type="hidden" name="pemilu" value="<?php echo $filter_pemilu; ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </form>
                </div>
                
                <!-- Filter Pemilu -->
                <div style="flex: 1;">
                    <form action="" method="GET" id="filterForm">
                        <select name="pemilu" 
                                onchange="document.getElementById('filterForm').submit()"
                                style="width: 100%; padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 25px;">
                            <option value="">Semua Pemilu</option>
                            <?php 
                            mysqli_data_seek($result_pemilu, 0);
                            while ($pemilu = mysqli_fetch_assoc($result_pemilu)): 
                            ?>
                                <option value="<?php echo $pemilu['id_election']; ?>"
                                        <?php echo ($filter_pemilu == $pemilu['id_election']) ? 'selected' : ''; ?>>
                                    <?php echo $pemilu['nama_pemilu']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <input type="hidden" name="search" value="<?php echo $search; ?>">
                    </form>
                </div>
            </div>

            <!-- Table -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Pemilu</th>
                                <th>No. Urut</th>
                                <th>Foto</th>
                                <th>Nama Kandidat</th>
                                <th>Visi</th>
                                <th>Misi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $row['nama_pemilu']; ?></strong><br>
                                        <small style="color: #666;">
                                            <?php echo ucfirst($row['status_pemilu']); ?>
                                        </small>
                                    </td>
                                    <td><strong><?php echo $row['nomor_urut']; ?></strong></td>
                                    <td>
                                        <img src="../assets/img/kandidat/<?php echo $row['foto']; ?>" 
                                             alt="<?php echo $row['nama_kandidat']; ?>">
                                    </td>
                                    <td><?php echo $row['nama_kandidat']; ?></td>
                                    <td><?php echo substr($row['visi'], 0, 50) . '...'; ?></td>
                                    <td><?php echo substr($row['misi'], 0, 50) . '...'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_kandidat.php?id=<?php echo $row['id_kandidat']; ?>" 
                                               class="btn-action btn-edit">
                                                ✏️ Edit
                                            </a>
                                            <a href="hapus_kandidat.php?id=<?php echo $row['id_kandidat']; ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus kandidat ini?')">
                                                🗑️ Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>Belum Ada Kandidat</h3>
                    <p>Silakan tambah kandidat terlebih dahulu</p>
                    <a href="tambah_kandidat.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ Tambah Kandidat
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>