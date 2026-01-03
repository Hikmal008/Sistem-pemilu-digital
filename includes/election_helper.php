<?php
// File: includes/election_helper.php
// Helper untuk promosi pemilu berikutnya

function promote_next_election_if_any() {
    global $conn;

    // 1. Pastikan tidak ada pemilu aktif
    $cek = mysqli_query($conn, "
        SELECT id_election FROM elections
        WHERE status = 'aktif'
        LIMIT 1
    ");

    if (mysqli_num_rows($cek) > 0) {
        return;
    }

    // 2. Ambil pemilu draft terdekat yang sudah waktunya
    $q = mysqli_query($conn, "
        SELECT id_election
        FROM elections
        WHERE status = 'draft'
        AND tanggal_mulai <= NOW()
        ORDER BY tanggal_mulai ASC
        LIMIT 1
    ");

    if ($row = mysqli_fetch_assoc($q)) {
        $id = $row['id_election'];
        $now = date('Y-m-d H:i:s');

        mysqli_query($conn, "
            UPDATE elections
            SET status = 'aktif',
                tanggal_mulai = '$now'
            WHERE id_election = $id
        ");
    }
}
