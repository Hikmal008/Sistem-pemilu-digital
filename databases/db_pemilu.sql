-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 04, 2026 at 08:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pemilu`
--

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `id_election` int(11) NOT NULL,
  `nama_pemilu` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `status` enum('draft','aktif','selesai') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `parent_election_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`id_election`, `nama_pemilu`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `status`, `created_by`, `created_at`, `updated_at`, `parent_election_id`) VALUES
(18, 'PEMILIHAN PRESIDEN TAHUN 2026', 'Pemilihan ini dibuat untuk memungut suara dari masyarakat untuk menentukan Kepala Negara Konoha Pada Tahun 2026', '2026-01-04 19:13:31', '2026-01-04 19:18:32', 'selesai', 1, '2026-01-04 18:05:33', '2026-01-04 18:18:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kandidat`
--

CREATE TABLE `kandidat` (
  `id_kandidat` int(11) NOT NULL,
  `id_election` int(11) NOT NULL,
  `nomor_urut` int(11) NOT NULL,
  `nama_kandidat` varchar(100) NOT NULL,
  `visi` text NOT NULL,
  `misi` text NOT NULL,
  `foto` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kandidat`
--

INSERT INTO `kandidat` (`id_kandidat`, `id_election`, `nomor_urut`, `nama_kandidat`, `visi`, `misi`, `foto`, `created_at`, `updated_at`) VALUES
(34, 18, 1, 'HIKMAL', 'MEWUJUDKAN NEGARA KONOHA YANG BEBAS AKAN KELAPA SAWIT', '1. MENDIRIKAN SEKOLAH &quot;MUNGKIN&quot;\\r\\n2. MEMBANGUN DINASTI\\r\\n3. MEMBUBARKAN YANG KATANYA WAKIL RAKYAT', 'kandidat_695aad34334cb4.88896627.jpg', '2026-01-04 18:11:00', '2026-01-04 18:11:00'),
(35, 18, 2, 'NGK TAU', 'NGK ADA VISI', 'NGK ADA MISI', 'kandidat_695aad6ff3d914.12369843.png', '2026-01-04 18:12:00', '2026-01-04 18:12:00');

-- --------------------------------------------------------

--
-- Table structure for table `status_pemilu`
--

CREATE TABLE `status_pemilu` (
  `id` int(11) NOT NULL DEFAULT 1,
  `status` enum('buka','tutup') NOT NULL DEFAULT 'tutup',
  `tanggal_mulai` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `alamat` text NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `email`, `password`, `nama_lengkap`, `nik`, `tanggal_lahir`, `alamat`, `role`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@pemilu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '1234567890123456', '1990-01-01', 'Jl. Admin No. 1', 'admin', NULL, 'aktif', '2025-12-25 14:48:58', '2025-12-25 14:48:58'),
(7, 'malii', 'mali@gmail.com', '$2y$10$xmpZCQyvtKn0CvzCMz6nSOLtfp08Toqajx6JKYfIVy0J8U.zxKAoq', 'malichan', '1122334455667788', '2004-01-01', 'Bandung', 'user', NULL, 'aktif', '2025-12-28 08:54:13', '2025-12-28 08:54:13'),
(8, 'dinii', 'dini@gmail.com', '$2y$10$B4YKWp15elx7sesJJXzvVe6UuPpwmm6nfmfATzN2tCMTvJ0ItW.d6', 'Wa Ode Dini Karlita', '2233445566778899', '2005-08-16', 'Jln. Sipanjonga', 'user', NULL, 'aktif', '2026-01-02 06:03:59', '2026-01-02 06:16:25'),
(9, 'ALJAN LHOJIE', 'aljanlhojie@gmail.coom', '$2y$10$1Q5PGK2WUZCfT8HMsVdVJ.5MPHoH5c.N8.vF0qVzsCBW59zgsZFey', 'ALJAN LHOJIE', '7472022505050002', '2005-05-25', 'Jln. Bhakti  Abri', 'user', NULL, 'aktif', '2026-01-04 11:12:16', '2026-01-04 11:12:16');

-- --------------------------------------------------------

--
-- Table structure for table `voting`
--

CREATE TABLE `voting` (
  `id_voting` int(11) NOT NULL,
  `id_election` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_kandidat` int(11) NOT NULL,
  `waktu_voting` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voting`
--

INSERT INTO `voting` (`id_voting`, `id_election`, `id_user`, `id_kandidat`, `waktu_voting`, `ip_address`) VALUES
(9, 18, 7, 34, '2026-01-04 18:14:29', '::1'),
(10, 18, 8, 34, '2026-01-04 18:14:54', '::1'),
(11, 18, 9, 34, '2026-01-04 18:15:21', '::1');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`id_election`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `kandidat`
--
ALTER TABLE `kandidat`
  ADD PRIMARY KEY (`id_kandidat`),
  ADD UNIQUE KEY `unique_nomor_urut_per_pemilu` (`id_election`,`nomor_urut`);

--
-- Indexes for table `status_pemilu`
--
ALTER TABLE `status_pemilu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- Indexes for table `voting`
--
ALTER TABLE `voting`
  ADD PRIMARY KEY (`id_voting`),
  ADD UNIQUE KEY `unique_vote_per_election` (`id_user`,`id_election`),
  ADD KEY `id_kandidat` (`id_kandidat`),
  ADD KEY `id_election` (`id_election`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `id_election` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `kandidat`
--
ALTER TABLE `kandidat`
  MODIFY `id_kandidat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `voting`
--
ALTER TABLE `voting`
  MODIFY `id_voting` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `elections`
--
ALTER TABLE `elections`
  ADD CONSTRAINT `elections_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `voting`
--
ALTER TABLE `voting`
  ADD CONSTRAINT `voting_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `voting_ibfk_2` FOREIGN KEY (`id_kandidat`) REFERENCES `kandidat` (`id_kandidat`) ON DELETE CASCADE,
  ADD CONSTRAINT `voting_ibfk_3` FOREIGN KEY (`id_election`) REFERENCES `elections` (`id_election`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
