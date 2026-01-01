<?php
// File: includes/auth_check.php
// Deskripsi: Middleware untuk cek autentikasi dan autorisasi

// Fungsi untuk cek apakah user sudah login
function check_login() {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Anda harus login terlebih dahulu!');
        redirect('../auth/login.php');
    }
}

// Fungsi untuk cek apakah user adalah admin
function check_admin() {
    check_login();
    if (!is_admin()) {
        set_flash_message('danger', 'Anda tidak memiliki akses ke halaman ini!');
        redirect('../user/index.php');
    }
}

// Fungsi untuk cek apakah user adalah pemilih biasa
function check_user() {
    check_login();
    if (!is_user()) {
        set_flash_message('danger', 'Anda tidak memiliki akses ke halaman ini!');
        redirect('../admin/index.php');
    }
}

?>