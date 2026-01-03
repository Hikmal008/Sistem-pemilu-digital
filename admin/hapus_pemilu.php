<?php
// File: admin/hapus_pemilu.php
// Deskripsi: Menghapus data pemilu (Draft & Selesai)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

/* ================= CEK ID ================= */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'ID pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$id_election = clean_input($_GET['id']);

/* ================= AMBIL DATA PEMILU ================= */
$query = "SELECT * FROM elections WHERE id_election = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_election);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    set_flash_message('danger', 'Pemilu tidak ditemukan!');
    redirect('pemilu.php');
}

$pemilu = mysqli_fetch_assoc($result);

/* ================= CEK STATUS REAL ================= */
$status_info = get_election_status($id_election);
$status_real = $status_info['status_real'];

/*
    Aturan:
    - draft     -> boleh hapus
    - selesai   -> boleh hapus (beserta data kandidat & voting)
    - lainnya   -> DILARANG
*/
if (!in_array($status_real, ['draft', 'selesai'])) {
    set_flash_message(
        'danger',
        'Pemilu yang belum atau sedang berlangsung tidak dapat dihapus!'
    );
    redirect('pemilu.php');
}

/* ================= TRANSAKSI HAPUS ================= */
mysqli_begin_transaction($conn);

try {

    // Jika pemilu selesai, hapus voting & kandidat
    if ($status_real === 'selesai') {

        // Hapus voting
        $stmt_vote = mysqli_prepare(
            $conn,
            "DELETE FROM voting WHERE id_election = ?"
        );
        mysqli_stmt_bind_param($stmt_vote, "i", $id_election);
        mysqli_stmt_execute($stmt_vote);

        // Hapus kandidat
        $stmt_kandidat = mysqli_prepare(
            $conn,
            "DELETE FROM kandidat WHERE id_election = ?"
        );
        mysqli_stmt_bind_param($stmt_kandidat, "i", $id_election);
        mysqli_stmt_execute($stmt_kandidat);
    }

    // Hapus pemilu
    $stmt_pemilu = mysqli_prepare(
        $conn,
        "DELETE FROM elections WHERE id_election = ?"
    );
    mysqli_stmt_bind_param($stmt_pemilu, "i", $id_election);
    mysqli_stmt_execute($stmt_pemilu);

    mysqli_commit($conn);

    set_flash_message(
        'success',
        'Pemilu berhasil dihapus beserta seluruh data terkait!'
    );

} catch (Exception $e) {

    mysqli_rollback($conn);

    set_flash_message(
        'danger',
        'Terjadi kesalahan saat menghapus pemilu!'
    );
}

redirect('pemilu.php');
