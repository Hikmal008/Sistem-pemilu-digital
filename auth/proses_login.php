<?php
// File: auth/proses_login.php
// Deskripsi: Memproses autentikasi login

session_start();
require_once '../config/database.php';

// Cek apakah form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Ambil dan bersihkan input
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    // Validasi input kosong
    if (empty($username) || empty($password)) {
        set_flash_message('danger', 'Username dan password harus diisi!');
        redirect('login.php');
    }

    // Query untuk mencari user berdasarkan username atau email
    $query = "SELECT * FROM users 
              WHERE (username = ? OR email = ?) 
              AND status = 'aktif' 
              LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Cek apakah user ditemukan
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verifikasi password
        if (password_verify($password, $user['password'])) {

            // Set session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['foto'] = $user['foto'];

            // Catat waktu login
            $_SESSION['login_time'] = time();

            // Redirect berdasarkan role
            if ($user['role'] == 'admin') {
                set_flash_message('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
                redirect('../admin/index.php');
            } else {
                set_flash_message('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
                redirect('../user/index.php');
            }
        } else {
            // Password salah
            set_flash_message('danger', 'Password yang Anda masukkan salah!');
            redirect('login.php');
        }
    } else {
        // User tidak ditemukan atau tidak aktif
        set_flash_message('danger', 'Username/Email tidak ditemukan atau akun tidak aktif!');
        redirect('login.php');
    }

    mysqli_stmt_close($stmt);
} else {
    // Jika diakses langsung tanpa POST
    redirect('login.php');
}

mysqli_close($conn);
