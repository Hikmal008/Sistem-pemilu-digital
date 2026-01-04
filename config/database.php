<?php
// File: config/database.php
// Deskripsi: File koneksi database dan fungsi helper

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_pemilu');

// Membuat koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset ke utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// ============================================
// FUNGSI UMUM
// ============================================

// Fungsi untuk membersihkan input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fungsi untuk format tanggal Indonesia
function format_tanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// ============================================
// FUNGSI FLASH MESSAGE
// ============================================

function set_flash_message($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $message = $_SESSION['flash_message'];
        
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        
        return [
            'type' => $type,
            'message' => $message
        ];
    }
    return null;
}

// ============================================
// FUNGSI AUTENTIKASI
// ============================================

// Fungsi untuk cek apakah user sudah login
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi untuk cek role admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fungsi untuk cek role user
function is_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// ============================================
// FUNGSI PEMILU (MULTIPLE ELECTIONS)
// ============================================

// Fungsi untuk mendapatkan pemilu aktif
function get_active_election() {
    global $conn;
    $now = date('Y-m-d H:i:s');
    
    $query = "SELECT * FROM elections 
              WHERE status = 'aktif' 
              AND tanggal_mulai <= ? 
              AND tanggal_selesai >= ?
              ORDER BY tanggal_mulai DESC
              LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $now, $now);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Fungsi untuk cek status pemilu berdasarkan ID
function get_election_status($id_election) {
    global $conn;
    $now = date('Y-m-d H:i:s');
    
    $query = "SELECT *, 
              CASE 
                  WHEN status = 'draft' THEN 'draft'
                  WHEN status = 'selesai' THEN 'selesai'
                  WHEN tanggal_mulai > ? THEN 'belum_dimulai'
                  WHEN tanggal_selesai < ? THEN 'selesai'
                  ELSE 'berlangsung'
              END as status_real
              FROM elections WHERE id_election = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $now, $now, $id_election);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Fungsi untuk auto-update status pemilu
function auto_update_election_status() {
    global $conn;
    $now = date('Y-m-d H:i:s');

    // Draft TIDAK BOLEH diubah otomatis
    // Aktif → Selesai jika waktu habis
    $query = "
        UPDATE elections 
        SET status = 'selesai'
        WHERE status = 'aktif'
        AND tanggal_selesai < ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $now);
    mysqli_stmt_execute($stmt);
}


// ============================================
// FUNGSI VOTING
// ============================================

// Fungsi untuk cek apakah user sudah voting (legacy - untuk backward compatibility)
function has_voted($user_id) {
    // Cek apakah sudah voting di pemilu aktif
    $election = get_active_election();
    if ($election) {
        return has_voted_in_election($user_id, $election['id_election']);
    }
    
    // Jika tidak ada pemilu aktif, cek apakah pernah voting
    global $conn;
    $query = "SELECT id_voting FROM voting WHERE id_user = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Fungsi untuk cek apakah user sudah voting di pemilu tertentu
function has_voted_in_election($user_id, $id_election) {
    global $conn;
    $query = "SELECT id_voting FROM voting WHERE id_user = ? AND id_election = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $id_election);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

// Fungsi untuk cek apakah pemilu sedang berlangsung
function is_voting_open() {
    $election = get_active_election();
    return $election !== null;
}

// Fungsi untuk cek status pemilu (legacy - untuk backward compatibility)
function get_status_pemilu() {
    $election = get_active_election();
    return $election ? 'buka' : 'tutup';
}

function checkElectionTie($id_election) {
    global $conn;

    $sql = "
        SELECT k.id_kandidat, COUNT(v.id_voting) AS total
        FROM kandidat k
        LEFT JOIN voting v ON v.id_kandidat = k.id_kandidat
        WHERE k.id_election = ?
        GROUP BY k.id_kandidat
        ORDER BY total DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_election);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $data = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if (count($data) === 0) return null;

    $max = $data[0]['total'];
    $seri = array_filter($data, fn($d) => $d['total'] == $max);

    return count($seri) > 1 ? $seri : null;
}

function createElectionReplay($id_election, $kandidat_seri) {
    global $conn;

    // Ambil data pemilu lama
    $old = mysqli_fetch_assoc(mysqli_query(
        $conn, "SELECT * FROM elections WHERE id_election = $id_election"
    ));

    $nama_baru = "Pemilu Ulang - " . $old['nama_pemilu'];

    mysqli_query($conn, "
        INSERT INTO elections 
        (nama_pemilu, deskripsi, status, parent_election_id, created_by)
        VALUES 
        ('$nama_baru', 'Pemilu ulang akibat hasil seri', 'draft', $id_election, {$old['created_by']})
    ");

    $new_id = mysqli_insert_id($conn);

    // Salin kandidat seri
    foreach ($kandidat_seri as $k) {
        mysqli_query($conn, "
            INSERT INTO kandidat (id_election, nomor_urut, nama_kandidat, visi, misi, foto)
            SELECT $new_id, nomor_urut, nama_kandidat, visi, misi, foto
            FROM kandidat
            WHERE id_kandidat = {$k['id_kandidat']}
        ");
    }

    return $new_id;
}

?>
