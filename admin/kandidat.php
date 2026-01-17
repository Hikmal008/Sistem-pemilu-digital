<?php
// File: admin/kandidat.php
// Deskripsi: Manajemen kandidat dikelompokkan per pemilu

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

// Ambil flash message
$flash = get_flash_message();

// Ambil parameter pencarian & filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$filter_pemilu = isset($_GET['pemilu']) ? clean_input($_GET['pemilu']) : '';

// Query dasar
$query = "
    SELECT k.*, e.nama_pemilu, e.status AS status_pemilu
    FROM kandidat k
    JOIN elections e ON k.id_election = e.id_election
    WHERE 1=1
";

// Filter pencarian
if (!empty($search)) {
    $query .= "
        AND (
            k.nama_kandidat LIKE '%$search%' 
            OR k.nomor_urut LIKE '%$search%'
        )
    ";
}

// Filter pemilu
if (!empty($filter_pemilu)) {
    $query .= " AND k.id_election = '$filter_pemilu'";
}

// Urutan
$query .= " ORDER BY e.created_at DESC, k.nomor_urut ASC";

// Eksekusi query kandidat
$result = mysqli_query($conn, $query);

// Query daftar pemilu untuk dropdown filter
$query_pemilu = "SELECT id_election, nama_pemilu FROM elections ORDER BY created_at DESC";
$result_pemilu = mysqli_query($conn, $query_pemilu);

// Kelompokkan kandidat per pemilu
$kandidat_per_pemilu = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $kandidat_per_pemilu[$row['nama_pemilu']][] = $row;
    }
}
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
            <a href="kandidat.php" class="sidebar-nav-item active">
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
            <h1 class="main-title">Manajemen Kandidat</h1>
            <a href="tambah_kandidat.php" class="btn btn-primary">
                ➕ Tambah Kandidat
            </a>
        </div>

        <!-- Filter dan Search -->
        <div class="content-card">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <!-- Search Box -->
                <div style="flex: 2; min-width: 300px;">
                    <form action="" method="GET" style="display: flex; gap: 10px;">
                        <input type="text" name="search"
                            placeholder="🔍 Cari kandidat..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="flex: 1; padding: 12px 20px; border: 2px solid var(--gray-300); border-radius: var(--radius-full);">
                        <input type="hidden" name="pemilu" value="<?php echo $filter_pemilu; ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </form>
                </div>

                <!-- Filter Pemilu -->
                <div style="flex: 1; min-width: 250px;">
                    <form action="" method="GET" id="filterForm">
                        <select name="pemilu"
                            onchange="document.getElementById('filterForm').submit()"
                            style="width: 100%; padding: 12px 20px; border: 2px solid var(--gray-300); border-radius: var(--radius-full);">
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
        </div>

        <!-- Kandidat per Pemilu -->
        <?php if (count($kandidat_per_pemilu) > 0): ?>
            <?php foreach ($kandidat_per_pemilu as $nama_pemilu => $kandidat_list): ?>
                <div class="pemilu-group">
                    <div class="pemilu-group-header">
                        <h3>
                            📋 <?php echo $nama_pemilu; ?>
                            <span class="kandidat-count"><?php echo count($kandidat_list); ?> Kandidat</span>
                        </h3>
                    </div>

                    <div class="pemilu-group-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No. Urut</th>
                                        <th>Foto</th>
                                        <th>Nama Kandidat</th>
                                        <th>Visi</th>
                                        <th>Misi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kandidat_list as $row): ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <strong style="font-size: 1.5em; color: var(--kpu-red);">
                                                    <?php echo $row['nomor_urut']; ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <img src="../assets/img/kandidat/<?php echo $row['foto']; ?>"
                                                    alt="<?php echo $row['nama_kandidat']; ?>"
                                                    style="width: 60px; height: 60px; border-radius: var(--radius); object-fit: cover;">
                                            </td>
                                            <td><strong><?php echo $row['nama_kandidat']; ?></strong></td>
                                            <td><?php echo substr($row['visi'], 0, 60) . '...'; ?></td>
                                            <td><?php echo substr($row['misi'], 0, 60) . '...'; ?></td>
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
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="content-card">
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3>Belum Ada Kandidat</h3>
                    <p>Silakan tambah kandidat terlebih dahulu</p>
                    <a href="tambah_kandidat.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ Tambah Kandidat
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

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
</body>

</html>