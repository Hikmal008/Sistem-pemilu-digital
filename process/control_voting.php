<?php
// File: process/control_voting.php
// Deskripsi: Mengontrol status pemilu (buka/tutup)

session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Cek apakah user adalah admin
check_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'buka') {
        $status = 'buka';
        $tanggal_mulai = date('Y-m-d H:i:s');
        
        $query = "UPDATE status_pemilu SET status = ?, tanggal_mulai = ? WHERE id = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $status, $tanggal_mulai);
        
        if (mysqli_stmt_execute($stmt)) {
            set_flash_message('success', 'Pemilu berhasil dibuka!');
        } else {
            set_flash_message('danger', 'Gagal membuka pemilu!');
        }
        
    } elseif ($action === 'tutup') {
        $status = 'tutup';
        $tanggal_selesai = date('Y-m-d H:i:s');
        
        $query = "UPDATE status_pemilu SET status = ?, tanggal_selesai = ? WHERE id = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $status, $tanggal_selesai);
        
        if (mysqli_stmt_execute($stmt)) {
            set_flash_message('success', 'Pemilu berhasil ditutup!');
        } else {
            set_flash_message('danger', 'Gagal menutup pemilu!');
        }
    }
    
    redirect('../admin/index.php');
}
?>