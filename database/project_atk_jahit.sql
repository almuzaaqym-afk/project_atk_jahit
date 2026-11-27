-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Nov 2026 pada 07.46
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project_atk_jahit`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Owner', 'admin@gmail.com', '$2y$12$xcsEaxiaa1JktepCFadmperN3RoTdqJWpwxnFhcixDNwpFv6Edx9G', 'Owner', '2026-09-24 07:08:31'),
(10, 'admin2', 'admin2@gmail.com', '$2y$10$wQ0Ia3jOPhNFInc8optCrObzmRGpNZfXOxc/K459vZeqfSco79DJS', 'Asisten', '2026-11-10 23:58:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_log_aktivitas`
--

CREATE TABLE `admin_log_aktivitas` (
  `id_log` bigint(20) UNSIGNED NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `nama_admin` varchar(100) DEFAULT NULL,
  `modul` varchar(50) NOT NULL,
  `aktivitas` varchar(100) NOT NULL,
  `detail` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin_log_aktivitas`
--

INSERT INTO `admin_log_aktivitas` (`id_log`, `id_admin`, `nama_admin`, `modul`, `aktivitas`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(54, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Benang Jahit\" (ID: 23). Jumlah varian: 17.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:04:05'),
(55, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Jarum Jahit\" (ID: 24). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:06:39'),
(56, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Kapur Jahit\" (ID: 25). Jumlah varian: 4.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:10:52'),
(57, 1, 'Owner', 'produk', 'Edit Produk', 'Mengubah produk \"Benang Jahit\" menjadi \"Benang Jahit\" (ID: 23). Jumlah varian setelah diedit: 17.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:11:20'),
(58, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Gunting Benang\" (ID: 26). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:14:25'),
(59, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Gunting Bahan\" (ID: 27). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:15:46'),
(60, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Meteran Kain\" (ID: 28). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:18:03'),
(61, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Pendedel Benang\" (ID: 29). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:19:07'),
(62, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Jarum Pentul\" (ID: 30). Jumlah varian: 2.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:22:16'),
(63, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Rader Tracing Wheel / Alat Pola Jahit Gerigi & Bulat\" (ID: 31). Jumlah varian: 2.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:25:44'),
(64, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Pensil Kapur / Pensil Bahan\" (ID: 32). Jumlah varian: 6.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 19:32:20'),
(65, 1, 'Owner', 'produk', 'Edit Produk', 'Mengubah produk \"Gunting Benang\" menjadi \"Gunting Benang\" (ID: 26). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:11:40'),
(66, 1, 'Owner', 'produk', 'Edit Produk', 'Mengubah produk \"Kapur Jahit\" menjadi \"Kapur Jahit\" (ID: 25). Jumlah varian setelah diedit: 4.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:13:16'),
(67, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Pulpen JOYKO\" (ID: 33). Jumlah varian: 3.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:18:42'),
(68, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Pensil 2b JOYKO\" (ID: 34). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:21:17'),
(69, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Penghapus JOYKO\" (ID: 35). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:22:29'),
(70, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Penggaris 30cm BUTTERFLY\" (ID: 36). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:23:59'),
(71, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Buku Tulis Campus\" (ID: 37). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:26:02'),
(72, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"JOYKO Gunting Scissors Stainless Steel SC-828~848\" (ID: 38). Jumlah varian: 3.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:29:43'),
(73, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Stapler JOYKO\" (ID: 39). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:31:51'),
(74, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"JOYKO Staples Isi Stapler Refill Stapler\" (ID: 40). Jumlah varian: 2.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:35:29'),
(75, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Benang Jahit\" (ID: 23). Total stok varian aktif: 0 → 1000. Varian \"Hitam\" (ID: 4): stok 0 → 100; stok minimum 0 → 12; lokasi/rak - → -. | Varian \"Putih\" (ID: 5): stok 0 → 100; stok minimum 0 → 12; lokasi/rak - → -. | Varian \"Dongker / Navy\" (ID: 6): stok 0 → 100; stok minimum 0 → 12; lokasi/rak - → -. | Varian \"Merah Tua\" (ID: 7): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Merah Terang\" (ID: 8): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Coklat Tua\" (ID: 9): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Coklat Muda\" (ID: 10): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Kuning\" (ID: 11): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Hijau Tua\" (ID: 12): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Hijau Muda\" (ID: 13): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Biru Muda\" (ID: 14): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Cream\" (ID: 15): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Pink\" (ID: 16): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Ungu\" (ID: 17): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Orange\" (ID: 18): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Abu-abu Muda\" (ID: 19): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Abu-abu Tua\" (ID: 20): stok 0 → 50; stok minimum 0 → 6; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:45:47'),
(76, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Buku Tulis Campus\" (ID: 37). Stok: 0 → 12; stok minimum: 0 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:46:21'),
(77, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Gunting Bahan\" (ID: 27). Stok: 0 → 12; stok minimum: 0 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:46:37'),
(78, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Gunting Benang\" (ID: 26). Stok: 0 → 24; stok minimum: 0 → 6; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:46:46'),
(79, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Jarum Jahit\" (ID: 24). Stok: 0 → 24; stok minimum: 0 → 6; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:46:54'),
(80, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Jarum Pentul\" (ID: 30). Total stok varian aktif: 0 → 36. Varian \"Hitam\" (ID: 25): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Warna-warni\" (ID: 26): stok 0 → 24; stok minimum 0 → 5; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:47:15'),
(81, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"JOYKO Gunting Scissors Stainless Steel SC-828~848\" (ID: 38). Total stok varian aktif: 0 → 26. Varian \"SC-828\" (ID: 38): stok 0 → 10; stok minimum 0 → 3; lokasi/rak - → -. | Varian \"SC-838\" (ID: 39): stok 0 → 8; stok minimum 0 → 3; lokasi/rak - → -. | Varian \"SC-848\" (ID: 40): stok 0 → 8; stok minimum 0 → 3; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:47:45'),
(82, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"JOYKO Staples Isi Stapler Refill Stapler\" (ID: 40). Total stok varian aktif: 0 → 48. Varian \"No. 10-1M\" (ID: 41): stok 0 → 24; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"No. 10S\" (ID: 42): stok 0 → 24; stok minimum 0 → 5; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:48:03'),
(83, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Kapur Jahit\" (ID: 25). Total stok varian aktif: 0 → 48. Varian \"Merah\" (ID: 21): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Biru\" (ID: 22): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Kuning\" (ID: 23): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Putih\" (ID: 24): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:48:30'),
(84, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Meteran Kain\" (ID: 28). Stok: 0 → 24; stok minimum: 0 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:48:37'),
(85, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Pendedel Benang\" (ID: 29). Stok: 0 → 24; stok minimum: 0 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:48:45'),
(86, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Penggaris 30cm BUTTERFLY\" (ID: 36). Stok: 0 → 12; stok minimum: 0 → 3; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:48:52'),
(87, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Penghapus JOYKO\" (ID: 35). Stok: 0 → 24; stok minimum: 0 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:48:59'),
(88, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Pensil 2b JOYKO\" (ID: 34). Stok: 0 → 12; stok minimum: 0 → 3; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:49:06'),
(89, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Pensil Kapur / Pensil Bahan\" (ID: 32). Total stok varian aktif: 0 → 60. Varian \"Putih\" (ID: 29): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Hitam\" (ID: 30): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Biru\" (ID: 31): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Hijau\" (ID: 32): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Kuning\" (ID: 33): stok 0 → 12; stok minimum 0 → 0; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:49:45'),
(90, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Pensil Kapur / Pensil Bahan\" (ID: 32). Total stok varian aktif: 60 → 72. Varian \"Kuning\" (ID: 33): stok 12 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Merah\" (ID: 34): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:50:07'),
(91, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Pulpen JOYKO\" (ID: 33). Total stok varian aktif: 0 → 36. Varian \"Hitam\" (ID: 35): stok 0 → 24; stok minimum 0 → 6; lokasi/rak - → -. | Varian \"Merah\" (ID: 37): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:50:24'),
(92, 1, 'Owner', 'stok', 'Perbarui Stok Varian', 'Memperbarui stok varian produk \"Rader Tracing Wheel / Alat Pola Jahit Gerigi & Bulat\" (ID: 31). Total stok varian aktif: 0 → 24. Varian \"Gerigi\" (ID: 27): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -. | Varian \"Bulat\" (ID: 28): stok 0 → 12; stok minimum 0 → 5; lokasi/rak - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:50:37'),
(93, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Stapler JOYKO\" (ID: 39). Stok: 0 → 12; stok minimum: 0 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-10 20:50:45'),
(94, 1, 'Owner', 'pesanan', 'Perbarui Status Pesanan', 'Mengubah status pesanan \"PS-20261110205334-42\" (ID pesanan: 23) dari \"Diproses\" menjadi \"Siap Diambil\".', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:27:17'),
(95, 1, 'Owner', 'pesanan', 'Perbarui Status Pesanan', 'Mengubah status pesanan \"PS-20261110205334-42\" (ID pesanan: 23) dari \"Siap Diambil\" menjadi \"Selesai\".', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:27:21'),
(96, 1, 'Owner', 'pesanan', 'Perbarui Status Pesanan', 'Mengubah status pesanan \"PS-20261111062641-93\" (ID pesanan: 24) dari \"Diproses\" menjadi \"Siap Diambil\".', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:33:30'),
(97, 1, 'Owner', 'cs', 'Ambil Chat CS', 'Admin mengambil chat CS ID: 11, ID pelanggan: 7, status: Menunggu menjadi Dilayani.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:37:10'),
(98, 1, 'Owner', 'voucher', 'Tambah Voucher', 'Menambahkan voucher ID: 5, kode: DISKON10KJLZ, nama: Diskon 10%, jenis diskon: Persen, nilai diskon: 10, status: Aktif.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:41:21'),
(99, 1, 'Owner', 'produk', 'Tambah Produk', 'Menambahkan produk \"Tipe x cair\" (ID: 41). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:44:10'),
(100, 1, 'Owner', 'produk', 'Edit Produk', 'Mengubah produk \"Tipe x cair\" menjadi \"Tipe x cair\" (ID: 41). Produk tanpa varian.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:45:11'),
(101, 1, 'Owner', 'produk', 'Hapus Produk', 'Menghapus produk \"Tipe x cair\" (ID: 41).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:45:21'),
(102, 1, 'Owner', 'kategori', 'Tambah Kategori', 'Menambahkan kategori \"Bahan Menjahit\" (ID kategori: 9).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:46:06'),
(103, 1, 'Owner', 'kategori', 'Hapus Kategori', 'Menghapus kategori \"Bahan Menjahit\" (ID kategori: 9).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:46:12'),
(104, 1, 'Owner', 'stok', 'Perbarui Stok', 'Produk \"Buku Tulis Campus\" (ID: 37). Stok: 12 → 12; stok minimum: 5 → 5; lokasi/rak: - → -.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:47:12'),
(105, 1, 'Owner', 'lokasi', 'Tambah Lokasi/Rak', 'Menambahkan lokasi/rak \"RAK ATK\" dengan kode rak Rak 001 (ID lokasi: 6).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:48:10'),
(106, 1, 'Owner', 'lokasi', 'Tambah Lokasi/Rak', 'Menambahkan lokasi/rak \"RAK Alat jahit\" dengan kode rak RAK 002 (ID lokasi: 7).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:48:38'),
(107, 1, 'Owner', 'lokasi', 'Tambah Lokasi/Rak', 'Menambahkan lokasi/rak \"Rak ATK\" dengan kode rak Rak 003 (ID lokasi: 8).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:50:29'),
(108, 1, 'Owner', 'supplier', 'Tambah Supplier', 'Menambahkan supplier \"CV Sumber JAYA\" (Kode: SUP001, ID: 5, Status: Aktif).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:51:44'),
(109, 1, 'Owner', 'supplier', 'Tambah Supplier', 'Menambahkan supplier \"SETIA JAYA ABADI\" (Kode: SUP002, ID: 6, Status: Aktif).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:52:57'),
(110, 1, 'Owner', 'pembelian', 'Tambah Pembelian', 'Membuat pembelian \"PB-20261111005353-8308\" (ID: 7, supplier: CV Sumber JAYA, tanggal: 2026-11-11, jumlah baris produk: 1, total: Rp 540.000,00, status: Draft).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:53:53'),
(111, 1, 'Owner', 'pembelian', 'Selesaikan Pembelian', 'Menyelesaikan pembelian \"PB-20261111005353-8308\" (ID: 7, status: Draft menjadi Selesai). Stok produk/varian telah diperbarui.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:53:59'),
(112, 1, 'Owner', 'pembelian', 'Tambah Pembelian', 'Membuat pembelian \"PB-20261111005444-9832\" (ID: 8, supplier: SETIA JAYA ABADI, tanggal: 2026-11-11, jumlah baris produk: 1, total: Rp 250.000,00, status: Draft).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:54:44'),
(113, 1, 'Owner', 'pembelian', 'Selesaikan Pembelian', 'Menyelesaikan pembelian \"PB-20261111005444-9832\" (ID: 8, status: Draft menjadi Selesai). Stok produk/varian telah diperbarui.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 06:54:59'),
(114, 1, 'Owner', 'banner', 'Tambah Banner', 'Menambahkan banner ID: 7, judul: PROMO AMBYAR, nama gambar: banner_20261120072115_520bf6a1.png, urutan: 1, status: Aktif.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-20 13:21:15'),
(115, 1, 'Owner', 'banner', 'Edit Banner', 'Mengubah banner ID: 7. Judul: PROMO AMBYAR menjadi PROMO SPESIAL; status: Aktif menjadi Aktif; Gambar diganti dari banner_20261120072115_520bf6a1.png menjadi banner_20261120072305_f0c03022.png.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-20 13:23:05'),
(116, 1, 'Owner', 'voucher', 'Tambah Voucher', 'Menambahkan voucher ID: 6, kode: DISKONR5C, nama: Diskon 100%, jenis diskon: Persen, nilai diskon: 100, status: Aktif.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-20 13:26:08'),
(117, 1, 'Owner', 'banner', 'Tambah Banner', 'Menambahkan banner ID: 8, judul: PROMO AMBYAR, nama gambar: banner_20261120072802_5d34b383.png, urutan: 2, status: Aktif.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-20 13:28:02'),
(118, 1, 'Owner', 'banner', 'Edit Banner', 'Mengubah banner ID: 8. Judul: PROMO AMBYAR menjadi PROMO AMBYAR; status: Aktif menjadi Tidak Aktif; Gambar tetap banner_20261120072802_5d34b383.png.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-20 13:30:02'),
(119, 1, 'Owner', 'banner', 'Edit Banner', 'Mengubah banner ID: 8. Judul: PROMO AMBYAR menjadi PROMO AMBYAR; status: Tidak Aktif menjadi Aktif; Gambar tetap banner_20261120072802_5d34b383.png.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-20 13:30:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `banner`
--

CREATE TABLE `banner` (
  `id_banner` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) NOT NULL,
  `teks_tombol` varchar(50) DEFAULT NULL,
  `link_tombol` varchar(255) DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `banner`
--

INSERT INTO `banner` (`id_banner`, `judul`, `deskripsi`, `gambar`, `teks_tombol`, `link_tombol`, `urutan`, `status`, `created_at`, `updated_at`) VALUES
(7, 'PROMO SPESIAL', '', 'banner_20261120072305_f0c03022.png', 'Belanja Sekarang', '/project_atk_jahit/pelanggan/pages/katalog.php', 1, 'Aktif', '2026-11-20 06:21:15', '2026-11-20 06:23:05'),
(8, 'PROMO AMBYAR', '', 'banner_20261120072802_5d34b383.png', '', '/project_atk_jahit/pelanggan/pages/katalog.php', 2, 'Aktif', '2026-11-20 06:28:02', '2026-11-20 06:30:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_message`
--

CREATE TABLE `chat_message` (
  `id_message` int(11) NOT NULL,
  `id_chat` int(11) NOT NULL,
  `pengirim` enum('Pelanggan','Admin') NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chat_message`
--

INSERT INTO `chat_message` (`id_message`, `id_chat`, `pengirim`, `id_pengirim`, `pesan`, `gambar`, `created_at`) VALUES
(29, 9, 'Pelanggan', 7, 'Halo', NULL, '2026-11-10 13:55:22'),
(30, 9, 'Admin', 1, 'Hai kak, ada yang bisa kami bantu?', NULL, '2026-11-10 13:56:03'),
(31, 10, 'Pelanggan', 7, 'Halo', NULL, '2026-11-10 23:34:13'),
(32, 10, 'Admin', 1, 'Hai kak ada yang bisa kami bantu?', NULL, '2026-11-10 23:34:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_session`
--

CREATE TABLE `chat_session` (
  `id_chat` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `status_chat` enum('Aktif','Selesai') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chat_session`
--

INSERT INTO `chat_session` (`id_chat`, `id_pesanan`, `id_pelanggan`, `status_chat`, `created_at`, `ended_at`) VALUES
(9, 23, 7, 'Aktif', '2026-11-10 13:54:39', NULL),
(10, 24, 7, 'Aktif', '2026-11-10 23:34:07', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `cs_chat`
--

CREATE TABLE `cs_chat` (
  `id_chat` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `status_chat` enum('Menunggu','Dilayani','Selesai') NOT NULL DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cs_chat`
--

INSERT INTO `cs_chat` (`id_chat`, `id_pelanggan`, `status_chat`, `created_at`, `ended_at`) VALUES
(11, 7, 'Dilayani', '2026-11-10 23:36:55', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `cs_message`
--

CREATE TABLE `cs_message` (
  `id_message` int(11) NOT NULL,
  `id_chat` int(11) NOT NULL,
  `pengirim` enum('Pelanggan','Admin') NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cs_message`
--

INSERT INTO `cs_message` (`id_message`, `id_chat`, `pengirim`, `id_pengirim`, `pesan`, `gambar`, `created_at`) VALUES
(36, 11, 'Pelanggan', 7, 'halo kak', NULL, '2026-11-10 23:37:20'),
(37, 11, 'Admin', 1, 'Iya kak apakah ada yang bisa kami bantu?', NULL, '2026-11-10 23:37:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id_detail` int(11) NOT NULL,
  `id_pembelian` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_varian` int(11) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_beli` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pembelian`
--

INSERT INTO `detail_pembelian` (`id_detail`, `id_pembelian`, `id_produk`, `id_varian`, `jumlah`, `harga_beli`, `subtotal`) VALUES
(8, 7, 33, 35, 120, 4500.00, 540000.00),
(9, 8, 24, NULL, 50, 5000.00, 250000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_varian` int(11) DEFAULT NULL,
  `nama_varian` varchar(100) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `id_varian`, `nama_varian`, `jumlah`, `harga`, `subtotal`) VALUES
(30, 23, 30, 25, 'Hitam', 1, 5000.00, 5000.00),
(31, 24, 39, NULL, NULL, 1, 14000.00, 14000.00),
(32, 25, 25, 22, 'Biru', 2, 3000.00, 6000.00),
(33, 26, 39, NULL, NULL, 1, 14000.00, 14000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `favorit`
--

CREATE TABLE `favorit` (
  `id_favorit` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `tanggal_ditambahkan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `favorit`
--

INSERT INTO `favorit` (`id_favorit`, `id_pelanggan`, `id_produk`, `tanggal_ditambahkan`) VALUES
(23, 7, 30, '2026-11-10 13:52:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'ATK'),
(2, 'Alat Jahit');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id_keranjang` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_varian` int(11) DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `tanggal_ditambahkan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `lokasi_rak`
--

CREATE TABLE `lokasi_rak` (
  `id_lokasi` int(11) NOT NULL,
  `kode_rak` varchar(50) NOT NULL,
  `nama_lokasi` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lokasi_rak`
--

INSERT INTO `lokasi_rak` (`id_lokasi`, `kode_rak`, `nama_lokasi`, `keterangan`, `created_at`) VALUES
(6, 'Rak 001', 'RAK ATK', 'Rak baris 1', '2026-11-10 23:48:10'),
(7, 'RAK 002', 'RAK Alat jahit', 'Rak Baris 1', '2026-11-10 23:48:38'),
(8, 'Rak 003', 'Rak ATK', 'Rak baris 3', '2026-11-10 23:50:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_reset_token`
--

CREATE TABLE `password_reset_token` (
  `id_reset` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama`, `email`, `foto_profil`, `password`, `created_at`) VALUES
(7, 'pelanggan', 'pelanggan@gmail.com', NULL, '$2y$10$RnX9jMfzFoBbXw6VeucjfO9uCviOtDSOyfQ.QNrmdUsBu8/sSaWHu', '2026-11-10 11:51:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembelian`
--

CREATE TABLE `pembelian` (
  `id_pembelian` int(11) NOT NULL,
  `nomor_pembelian` varchar(30) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `tanggal_pembelian` date NOT NULL,
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Selesai','Dibatalkan') NOT NULL DEFAULT 'Draft',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembelian`
--

INSERT INTO `pembelian` (`id_pembelian`, `nomor_pembelian`, `id_supplier`, `tanggal_pembelian`, `total_harga`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(7, 'PB-20261111005353-8308', 5, '2026-11-11', 540000.00, 'Selesai', 'Pembelian Atk', '2026-11-10 23:53:53', '2026-11-10 23:53:59'),
(8, 'PB-20261111005444-9832', 6, '2026-11-11', 250000.00, 'Selesai', 'Pembelian Alat jahit', '2026-11-10 23:54:44', '2026-11-10 23:54:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_toko`
--

CREATE TABLE `pengaturan_toko` (
  `id_pengaturan` int(11) NOT NULL,
  `nama_toko` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `jam_operasional` varchar(150) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `google_maps` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `qris` varchar(255) DEFAULT NULL,
  `pickup` tinyint(1) NOT NULL DEFAULT 1,
  `notifikasi` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan_toko`
--

INSERT INTO `pengaturan_toko` (`id_pengaturan`, `nama_toko`, `logo`, `alamat`, `telepon`, `email`, `jam_operasional`, `whatsapp`, `google_maps`, `deskripsi`, `qris`, `pickup`, `notifikasi`, `updated_at`) VALUES
(1, 'Toko ATK & Alat Jahit', 'assets/images/settings/logo_toko_1791203978.png', 'Gg. Aliyas, RT.1/RW.9 No.58 ,\r\nKp. Tengah, Kec. Kramat Jati,\r\nKota Jakarta Timur, DKI Jakarta 13540', '085813522811', 'atkjahit@gmail.com', 'Senin - Minggu, 08.00 - 21.00 WIB', '6285813522811', 'https://maps.app.goo.gl/7yc149eBuWwRPp24A', 'Toko ATK & Alat Jahit menyediakan berbagai kebutuhan alat tulis kantor dan perlengkapan menjahit.', 'assets/images/settings/qris_1791203903.png', 1, 1, '2026-10-05 12:39:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `nomor_pesanan` varchar(30) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_voucher` int(11) DEFAULT NULL,
  `kode_voucher` varchar(50) DEFAULT NULL,
  `diskon_voucher` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tanggal_pesanan` datetime NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status_pesanan` enum('Menunggu Pembayaran','Menunggu Verifikasi','Diproses','Siap Diambil','Selesai','Dibatalkan') NOT NULL DEFAULT 'Menunggu Pembayaran',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `nomor_pesanan`, `id_pelanggan`, `id_voucher`, `kode_voucher`, `diskon_voucher`, `tanggal_pesanan`, `total_harga`, `status_pesanan`, `bukti_pembayaran`, `catatan`, `created_at`, `updated_at`) VALUES
(23, 'PS-20261110205334-42', 7, NULL, NULL, 0.00, '2026-11-10 20:53:34', 5000.00, 'Selesai', NULL, 'Pembayaran QRIS berhasil dikonfirmasi secara otomatis.', '2026-11-10 13:53:34', '2026-11-10 23:27:21'),
(24, 'PS-20261111062641-93', 7, NULL, NULL, 0.00, '2026-11-11 06:26:41', 14000.00, 'Siap Diambil', NULL, 'Pembayaran QRIS berhasil dikonfirmasi secara otomatis.', '2026-11-10 23:26:41', '2026-11-10 23:33:30'),
(25, 'PS-20261111062834-44', 7, NULL, NULL, 0.00, '2026-11-11 06:28:34', 6000.00, 'Diproses', NULL, 'Pembayaran QRIS berhasil dikonfirmasi secara otomatis.', '2026-11-10 23:28:34', '2026-11-10 23:28:34'),
(26, 'PS-20261120141518-65', 7, NULL, NULL, 0.00, '2026-11-20 14:15:18', 14000.00, 'Diproses', NULL, 'Pembayaran QRIS berhasil dikonfirmasi secara otomatis.', '2026-11-20 07:15:18', '2026-11-20 07:15:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 5,
  `gambar` varchar(255) DEFAULT NULL,
  `lokasi_rak` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `id_kategori`, `nama_produk`, `deskripsi`, `harga`, `stok`, `stok_minimum`, `gambar`, `lokasi_rak`, `created_at`) VALUES
(23, 2, 'Benang Jahit', 'BENANG JAHIT EXTRA 500 YARDS\r\n\r\nHarga Tertera adalah harga per 1 pcs \r\n\r\n\r\nMaterial : Polyester\r\nSatu Warna Tidak Boleh Campur\r\n\r\nREADY BANYAK WARNA DAN LENGKAP\r\n\r\n\r\nBenang jahit berkualitas yang halus dan elastis merk EXTRA yang sudah terkenal di kalangan penjahit rumahan, tailor, konveksi dan garment.\r\n\r\n\r\n\r\nBerat Bersih : 200 gram', 0.00, 1000, 0, 'produk_6af308353066c1.02118715.webp', NULL, '2026-11-10 12:04:05'),
(24, 2, 'Jarum Jahit', 'Jarum Jahit Tangan Set Wadah Bundar\r\nNetto: 50 gram\r\n- Warna Random sesuai stok\r\n- Harga per set', 5000.00, 74, 6, 'produk_6af308cfa38322.38907773.webp', NULL, '2026-11-10 12:06:39'),
(25, 2, 'Kapur Jahit', 'Kapur Penanda Bahan/Tailor Chalk\r\n\r\nBerfungsi untuk menandai bahan (untuk pola atau penanda)\r\n\r\nMerk : Leo\r\n\r\nHARGA SATUAN\r\n\r\nTersedia dalam 4 pilihan warna :\r\n- Putih (White)\r\n- Kuning (Yellow)\r\n- Biru (Blue) \r\n- Merah (Red)', 0.00, 46, 20, 'produk_6af309cc0e3481.01777723.jpg', NULL, '2026-11-10 12:10:52'),
(26, 2, 'Gunting Benang', 'SPESIFIKASI:\r\n\r\n➡ DIMENSI : 10,5x2,3cm\r\n\r\n➡ BAHAN : Stainless Steel\r\n\r\n➡ BERAT : 12g\r\n\r\n➡ DAFTAR AKSESORIS : 1X Gunting Benang Jahit\r\n\r\nWARNA DI KIRIM RANDOM SESUAI STOK YANG TERSEDIA', 4500.00, 24, 6, 'produk_6af30aa1ad03f3.53556490.webp', NULL, '2026-11-10 12:14:25'),
(27, 2, 'Gunting Bahan', 'Tajam, Tahan Karat, Tahan Lama\r\n\r\nMaterial bilah menggunakan baja tahan karat grade 2Cr13, yaitu stainless steel dengan kandungan kromium tinggi yang dikenal tahan terhadap karat dan korosi. Grade ini umum digunakan pada alat potong profesional karena mampu menjaga ketajaman lebih lama dibanding stainless steel standar.\r\n\r\nPotongan Lebih Rata dan Akurat\r\n\r\n\r\nSalah satu keunggulan utama gunting bahan ini adalah desain bilah dan handle yang sejajar datar dengan permukaan meja potong saat digunakan. Ketika bilah bawah meluncur rata di atas meja, kain tetap terbentang sempurna selama proses pemotongan. Hasilnya, pola potongan jauh lebih akurat dan garis lurus lebih konsisten.', 50000.00, 12, 5, 'produk_6af30af2da63e7.91805380.webp', NULL, '2026-11-10 12:15:46'),
(28, 2, 'Meteran Kain', 'Merk : Butterlfy\r\n\r\nMaterial: Plastik\r\n\r\nUkuran: 150cm / 60 inch\r\n\r\nLebar Meteran 1,3cm\r\n\r\nOriginal Butterfly\r\n\r\n\r\n\r\nWarna Random\r\n\r\nSangat berguna untuk pekerjaan tata busana, tailor ataupun industri pakaian, untuk mengukur bahan yang akan dipotong ,dijahit maupun mengukur badan.', 2500.00, 24, 5, 'produk_6af30b7b85bda5.00562714.webp', NULL, '2026-11-10 12:18:03'),
(29, 2, 'Pendedel Benang', 'PENDEDEL BENANG  WARNA WARNI\r\n\r\n\r\n\r\n● PENDEDEL BENANG KUALITAS BAGUS\r\n\r\n● MEMUDAHKAN MEMBUKA JAHITAN YANG SALAH DENGAN CEPAT\r\n\r\n● UJUNG TAJAM MEMUDAHKAN MENEMBUS SERAT KAIN\r\n\r\n● DESAIN ERGONOMIS DENGAN GAGANG NYAMAN DIGENGGAM\r\n\r\n● DILENGKAPI PENUTUP UNTUK KEAMANAN DAN HIGIENIS\r\n\r\n● RINGAN DAN MUDAH DIBAWA KEMANA SAJA\r\n\r\n\r\n\r\n● HARGA UNTUK 1 PCS\r\n\r\n● WARNA  RANDOM SESUAI STOK\r\n\r\n● UKURAN PENDEDEL 7CM,  DENGAN TUTUP 9CM', 5000.00, 24, 5, 'produk_6af30bbbd4a0d4.92941464.webp', NULL, '2026-11-10 12:19:07'),
(30, 2, 'Jarum Pentul', 'JARUM PENTUL ANTI KARAT \r\n\r\nSTAINLESS STILL, ANTI TUMPUL \r\n\r\n✔️MERK HAODIE DRESSMAKER PINS \r\n\r\n✔️HARGA UNTUK 1BOX MIKA \r\n\r\n✔️1 DUS ISI 20BOX MIKA\r\n\r\n✔️BERAT 22GRAM', 0.00, 35, 10, 'produk_6af30c78bdba19.17413751.webp', NULL, '2026-11-10 12:22:16'),
(31, 2, 'Rader Tracing Wheel / Alat Pola Jahit Gerigi & Bulat', 'ader Tracing Wheel / Alat Pola Jahit Gerigi & Botak \r\n\r\nAlat Yang Berfungsi Untuk Memberikan Tanda ( Trace ) Di Bahan Atau Pola Jahit Yang Akan Dijahit Atau Dipotong\r\n\r\nWARNA RANDOM SESUAI STOK', 0.00, 24, 0, 'produk_6af30d4853e020.10970535.webp', NULL, '2026-11-10 12:25:44'),
(32, 2, 'Pensil Kapur / Pensil Bahan', 'Pensil Bahan / Pensil Kain / Pensil Kaca / Pencil Glass / Pensil Kapur \r\n\r\nHARGA TERTERA UNTUK 1 BIJI PENSIL!', 0.00, 72, 0, 'produk_6af30ed48c9b12.33044098.webp', NULL, '2026-11-10 12:32:20'),
(33, 1, 'Pulpen JOYKO', 'OYKO Gel Pen Pulpen Pena GP-265NT Q3 Gel 0.5 mm\r\n\r\n\r\n\r\n>Product Size ( Ukuran Produk ) : Panjang 14.8 cm x Diameter 1 cm\r\n\r\n>Tip : 0.5 mm ( Needle Tip, Mata Pena Jarum )\r\n\r\n>Warna Tinta ( Ink Colors ) : Black ( Hitam ), Biru ( Blue ), Red ( Merah )\r\n\r\n>Smooth Writing\r\n\r\n>Soft Grip\r\n\r\n>Retractable\r\n\r\n>Harga 1 Pcs', 0.00, 156, 11, 'produk_6af319b2a44378.19866300.webp', NULL, '2026-11-10 13:18:42'),
(34, 1, 'Pensil 2b JOYKO', 'Pensil 2B Joyko P-88\r\n\r\n\r\n\r\nkualitas bagus \r\n\r\nIsi pensil tebal dan Kuat, Lembut untuk menulis.\r\n\r\nHarga perBUAH = 1 PCS / Ecer / Satuan', 4500.00, 12, 3, 'produk_6af31a4d99d8d2.24579787.webp', NULL, '2026-11-10 13:21:17'),
(35, 1, 'Penghapus JOYKO', 'JOYKO Eraser Penghapus ER-105\r\n\r\n>Ukuran Produk : Panjang 4 cm x Lebar 2 cm x Tinggi 1 cm\r\n\r\n>Warna : Hitam\r\n\r\n>Super Clean Erasing ( Menghapus dengan sangat bersih )\r\n\r\n>Dust Free ( Tidak meninggalkan kotoran )\r\n\r\n>HARGA 1 PC', 2500.00, 24, 5, 'produk_6af31a94f143f9.39531748.webp', NULL, '2026-11-10 13:22:28'),
(36, 1, 'Penggaris 30cm BUTTERFLY', 'Penggaris Plastik 30 Cm Transparan Merk Butterfly / PB EDUKASI ANAK – PBNB\r\n\r\n\r\n- Harga 1 Pcs\r\n\r\n- Ukuran : 30 cm\r\n\r\n- Bahan : Mika Transparan / Plastik', 3500.00, 12, 3, 'produk_6af31aefe0da41.91951849.webp', NULL, '2026-11-10 13:23:59'),
(37, 1, 'Buku Tulis Campus', '[ 1 Buku ] Buku Tulis Campus Greebel 50 Lembar ( 1PCS ) CAMPUS BOXY B5 50-1R\r\n\r\nSpesifikasi Produk:\r\n\r\n> Harga 1 PCS (satu buah buku).\r\n\r\n> Ukuran buku B5 | Panjang 25 cm | Lebar 17,5 cm\r\n\r\n> Isi 50 lembar\r\n\r\n> Kertas 65 GSM berkualitas dengan ketebalan ideal, membantu mencegah tinta tembus agar hasil tulisan lebih rapi\r\n\r\n> Isi kertas bergaris\r\n\r\n> Tersedia dalam cover warna merah motif kotak-kotak', 7500.00, 12, 5, 'produk_6af31b6ad529a3.70000933.webp', NULL, '2026-11-10 13:26:02'),
(38, 1, 'JOYKO Gunting Scissors Stainless Steel SC-828~848', 'JOYKO Gunting Scissors Stainless Steel SC-828~848\r\n\r\n>Product Size ( Ukuran Produk ) :\r\n\r\n-SC-828 : Panjang 12 cm x Tinggi 6.2 cm\r\n\r\n-SC-838 : Panjang 15.9 cm x Tinggi 5.7 cm\r\n\r\n-SC-848 : Panjang 20.9 cm x Tinggi 7.7 cm\r\n\r\n>Color ( Warna ) : Black ( Hitam )\r\n\r\n>Blade Material ( Bahan Pisau ) : Stainless Steel ( Baja Tahan Karat )\r\n\r\n>Strong and Sharp ( Kuat dan Tajam )\r\n\r\n>Hardened and Tempered\r\n\r\n>Untuk Sekolah, Kantor, dan Rumah\r\n\r\n>Harga 1 Pc', 0.00, 26, 0, 'produk_6af31c473926e5.75926611.webp', NULL, '2026-11-10 13:29:43'),
(39, 1, 'Stapler JOYKO', 'JOYKO Stapler Stepler Jepretan HD-10\r\n\r\n\r\n>Product Size ( Ukuran Produk ) : Panjang 9 cm x Lebar 4.2 cm\r\n\r\n*WARNA DIKIRIM RANDOM SESUAI DENGAN PERSEDIAAN YANG ADA\r\n\r\n>Isi Stepler : Staples No. 10\r\n\r\n>Harga 1 Pc', 14000.00, 10, 5, 'produk_6af31cc7385748.71414841.webp', NULL, '2026-11-10 13:31:51'),
(40, 1, 'JOYKO Staples Isi Stapler Refill Stapler', 'JOYKO Staples Isi Stapler Refill Stapler No. 10 1 Box 1000 Pcs\r\n\r\n\r\n>Product Size ( Ukuran Produk ) :\r\n\r\n-No.10-1M : Panjang 10 x Lebar 5 mm\r\n\r\n-No.10S : Panjang 10 x Lebar 4.5 mm\r\n\r\n>Sharp Point ( Ujung Tajam dan Kuat )\r\n\r\n>Super Strong ( Super Kuat )\r\n\r\n>Dapat digunakan untuk Stapler JOYKO HD-10, HD-10CL, HD-10D, HD-10M, HD-10MP, HD-10SN, ST-9 ( dan lainnya yang menggunakan isi no. 10 )\r\n\r\n>Harga 1 Box Isi 1000 Pcs', 0.00, 48, 0, 'produk_6af31da1c4f523.50434543.webp', NULL, '2026-11-10 13:35:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk_varian`
--

CREATE TABLE `produk_varian` (
  `id_varian` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `nama_varian` varchar(100) NOT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimum` int(11) NOT NULL DEFAULT 0,
  `lokasi_rak` varchar(100) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk_varian`
--

INSERT INTO `produk_varian` (`id_varian`, `id_produk`, `nama_varian`, `harga`, `stok`, `stok_minimum`, `lokasi_rak`, `status`, `created_at`, `updated_at`) VALUES
(4, 23, 'Hitam', 3000.00, 100, 12, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(5, 23, 'Putih', 3000.00, 100, 12, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(6, 23, 'Dongker / Navy', 3000.00, 100, 12, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(7, 23, 'Merah Tua', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(8, 23, 'Merah Terang', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(9, 23, 'Coklat Tua', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(10, 23, 'Coklat Muda', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(11, 23, 'Kuning', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(12, 23, 'Hijau Tua', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(13, 23, 'Hijau Muda', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(14, 23, 'Biru Muda', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(15, 23, 'Cream', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(16, 23, 'Pink', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(17, 23, 'Ungu', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(18, 23, 'Orange', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(19, 23, 'Abu-abu Muda', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(20, 23, 'Abu-abu Tua', 3000.00, 50, 6, NULL, 'Aktif', '2026-11-10 12:04:05', '2026-11-10 13:45:47'),
(21, 25, 'Merah', 3000.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:10:52', '2026-11-10 13:48:30'),
(22, 25, 'Biru', 3000.00, 10, 5, NULL, 'Aktif', '2026-11-10 12:10:52', '2026-11-10 23:28:34'),
(23, 25, 'Kuning', 3000.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:10:52', '2026-11-10 13:48:30'),
(24, 25, 'Putih', 3000.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:10:52', '2026-11-10 13:48:30'),
(25, 30, 'Hitam', 5000.00, 11, 5, NULL, 'Aktif', '2026-11-10 12:22:16', '2026-11-10 13:53:34'),
(26, 30, 'Warna-warni', 5000.00, 24, 5, NULL, 'Aktif', '2026-11-10 12:22:16', '2026-11-10 13:47:15'),
(27, 31, 'Gerigi', 7500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:25:44', '2026-11-10 13:50:37'),
(28, 31, 'Bulat', 7500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:25:44', '2026-11-10 13:50:37'),
(29, 32, 'Putih', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:32:20', '2026-11-10 13:49:45'),
(30, 32, 'Hitam', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:32:20', '2026-11-10 13:49:45'),
(31, 32, 'Biru', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:32:20', '2026-11-10 13:49:45'),
(32, 32, 'Hijau', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:32:20', '2026-11-10 13:49:45'),
(33, 32, 'Kuning', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:32:20', '2026-11-10 13:50:07'),
(34, 32, 'Merah', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 12:32:20', '2026-11-10 13:50:07'),
(35, 33, 'Hitam', 4500.00, 144, 6, NULL, 'Aktif', '2026-11-10 13:18:42', '2026-11-10 23:53:59'),
(36, 33, 'Biru', 4500.00, 0, 0, NULL, 'Aktif', '2026-11-10 13:18:42', '2026-11-10 13:18:42'),
(37, 33, 'Merah', 4500.00, 12, 5, NULL, 'Aktif', '2026-11-10 13:18:42', '2026-11-10 13:50:24'),
(38, 38, 'SC-828', 7000.00, 10, 3, NULL, 'Aktif', '2026-11-10 13:29:43', '2026-11-10 13:47:45'),
(39, 38, 'SC-838', 11000.00, 8, 3, NULL, 'Aktif', '2026-11-10 13:29:43', '2026-11-10 13:47:45'),
(40, 38, 'SC-848', 16500.00, 8, 3, NULL, 'Aktif', '2026-11-10 13:29:43', '2026-11-10 13:47:45'),
(41, 40, 'No. 10-1M', 2500.00, 24, 5, NULL, 'Aktif', '2026-11-10 13:35:29', '2026-11-10 13:48:03'),
(42, 40, 'No. 10S', 3000.00, 24, 5, NULL, 'Aktif', '2026-11-10 13:35:29', '2026-11-10 13:48:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk_varian_gambar`
--

CREATE TABLE `produk_varian_gambar` (
  `id_gambar_varian` int(11) NOT NULL,
  `id_varian` int(11) NOT NULL,
  `nama_gambar` varchar(255) NOT NULL,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk_varian_gambar`
--

INSERT INTO `produk_varian_gambar` (`id_gambar_varian`, `id_varian`, `nama_gambar`, `urutan`, `created_at`) VALUES
(4, 4, 'varian_6af3083571ad61.42650856.webp', 1, '2026-11-10 12:04:05'),
(5, 5, 'varian_6af308357a11b3.05941837.webp', 1, '2026-11-10 12:04:05'),
(6, 6, 'varian_6af308358257e3.33775538.webp', 1, '2026-11-10 12:04:05'),
(7, 7, 'varian_6af3083583c737.05802691.webp', 1, '2026-11-10 12:04:05'),
(8, 8, 'varian_6af30835854926.96272489.webp', 1, '2026-11-10 12:04:05'),
(9, 9, 'varian_6af308358754e5.37361356.webp', 1, '2026-11-10 12:04:05'),
(10, 10, 'varian_6af3083588cc16.57420171.webp', 1, '2026-11-10 12:04:05'),
(11, 11, 'varian_6af308358a2699.04577759.webp', 1, '2026-11-10 12:04:05'),
(12, 12, 'varian_6af308358b92f0.58034335.webp', 1, '2026-11-10 12:04:05'),
(13, 13, 'varian_6af308358cc8e1.15554322.webp', 1, '2026-11-10 12:04:05'),
(14, 14, 'varian_6af308358e77d5.03163009.webp', 1, '2026-11-10 12:04:05'),
(15, 15, 'varian_6af308358fdd64.35252388.webp', 1, '2026-11-10 12:04:05'),
(16, 16, 'varian_6af30835912643.21321436.webp', 1, '2026-11-10 12:04:05'),
(17, 17, 'varian_6af30835926c16.32176431.webp', 1, '2026-11-10 12:04:05'),
(18, 18, 'varian_6af30835939426.17041615.webp', 1, '2026-11-10 12:04:05'),
(19, 19, 'varian_6af30835951e99.48932833.webp', 1, '2026-11-10 12:04:05'),
(20, 20, 'varian_6af308359699f8.43195067.webp', 1, '2026-11-10 12:04:05'),
(21, 21, 'varian_6af309cc107b04.32632065.webp', 1, '2026-11-10 12:10:52'),
(22, 22, 'varian_6af309cc124aa3.05532899.webp', 1, '2026-11-10 12:10:52'),
(23, 23, 'varian_6af309cc141a78.62813432.webp', 1, '2026-11-10 12:10:52'),
(24, 24, 'varian_6af309cc1568b2.33666102.webp', 1, '2026-11-10 12:10:52'),
(25, 25, 'varian_6af30c78c0db86.85127136.webp', 1, '2026-11-10 12:22:16'),
(26, 26, 'varian_6af30c78c28cd7.13203389.webp', 1, '2026-11-10 12:22:16'),
(27, 27, 'varian_6af30d48557096.25807363.webp', 1, '2026-11-10 12:25:44'),
(28, 28, 'varian_6af30d485726c2.99481347.webp', 1, '2026-11-10 12:25:44'),
(29, 29, 'varian_6af30ed48e29d0.08902789.webp', 1, '2026-11-10 12:32:20'),
(30, 30, 'varian_6af30ed4954104.86179172.webp', 1, '2026-11-10 12:32:20'),
(31, 31, 'varian_6af30ed4966b68.88001507.webp', 1, '2026-11-10 12:32:20'),
(32, 32, 'varian_6af30ed49877b8.46421181.webp', 1, '2026-11-10 12:32:20'),
(33, 33, 'varian_6af30ed49bd333.93577174.webp', 1, '2026-11-10 12:32:20'),
(34, 34, 'varian_6af30ed49d26c6.40577098.webp', 1, '2026-11-10 12:32:20'),
(35, 35, 'varian_6af319b2a61d69.76947357.webp', 1, '2026-11-10 13:18:42'),
(36, 36, 'varian_6af319b2a80509.88940166.webp', 1, '2026-11-10 13:18:42'),
(37, 37, 'varian_6af319b2a924c5.46805642.webp', 1, '2026-11-10 13:18:42'),
(38, 38, 'varian_6af31c473b4d99.43299313.webp', 1, '2026-11-10 13:29:43'),
(39, 39, 'varian_6af31c473c6594.20852273.webp', 1, '2026-11-10 13:29:43'),
(40, 40, 'varian_6af31c473e56d4.99918021.webp', 1, '2026-11-10 13:29:43'),
(41, 41, 'varian_6af31da1c69e22.99318631.webp', 1, '2026-11-10 13:35:29'),
(42, 42, 'varian_6af31da1c7db77.54119436.webp', 1, '2026-11-10 13:35:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `kode_supplier` varchar(30) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `nama_kontak` varchar(100) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `kode_supplier`, `nama_supplier`, `nama_kontak`, `telepon`, `email`, `alamat`, `keterangan`, `status`, `created_at`, `updated_at`) VALUES
(5, 'SUP001', 'CV Sumber JAYA', 'Susanti', '089238249233', 'Sumberjaya@gmail.com', 'Jalan Raya Kebagusan', 'TOKO ATK LENGKAP', 'Aktif', '2026-11-10 23:51:44', '2026-11-10 23:51:44'),
(6, 'SUP002', 'SETIA JAYA ABADI', 'Dena', '089238271423', 'setiajaya@gmail.com', 'jalan jatinegara', 'Peralatan JAHIT', 'Aktif', '2026-11-10 23:52:57', '2026-11-10 23:52:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ulasan`
--

CREATE TABLE `ulasan` (
  `id_ulasan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `komentar` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `voucher`
--

CREATE TABLE `voucher` (
  `id_voucher` int(11) NOT NULL,
  `kode_voucher` varchar(50) NOT NULL,
  `nama_voucher` varchar(150) NOT NULL,
  `jenis_diskon` enum('Persen','Nominal') NOT NULL DEFAULT 'Persen',
  `nilai_diskon` decimal(12,2) NOT NULL DEFAULT 0.00,
  `minimal_belanja` decimal(12,2) NOT NULL DEFAULT 0.00,
  `maksimal_diskon` decimal(12,2) DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_berakhir` datetime NOT NULL,
  `kuota` int(11) NOT NULL DEFAULT 0,
  `jumlah_digunakan` int(11) NOT NULL DEFAULT 0,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `voucher`
--

INSERT INTO `voucher` (`id_voucher`, `kode_voucher`, `nama_voucher`, `jenis_diskon`, `nilai_diskon`, `minimal_belanja`, `maksimal_diskon`, `tanggal_mulai`, `tanggal_berakhir`, `kuota`, `jumlah_digunakan`, `status`, `created_at`, `updated_at`) VALUES
(5, 'DISKON10KJLZ', 'Diskon 10%', 'Persen', 10.00, 5000.00, 12000.00, '2026-11-11 06:40:00', '2026-11-15 06:41:00', 10, 0, 'Aktif', '2026-11-10 23:41:21', '2026-11-10 23:41:21'),
(6, 'DISKONR5C', 'Diskon 100%', 'Persen', 100.00, 1000.00, 50000.00, '2026-11-20 13:25:00', '2026-11-30 13:25:00', 0, 0, 'Aktif', '2026-11-20 06:26:08', '2026-11-20 06:26:08');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `admin_log_aktivitas`
--
ALTER TABLE `admin_log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_log_waktu` (`created_at`),
  ADD KEY `idx_log_admin` (`id_admin`),
  ADD KEY `idx_log_modul` (`modul`),
  ADD KEY `idx_log_aktivitas` (`aktivitas`);

--
-- Indeks untuk tabel `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id_banner`);

--
-- Indeks untuk tabel `chat_message`
--
ALTER TABLE `chat_message`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `fk_message_chat` (`id_chat`);

--
-- Indeks untuk tabel `chat_session`
--
ALTER TABLE `chat_session`
  ADD PRIMARY KEY (`id_chat`),
  ADD UNIQUE KEY `unique_chat_pesanan` (`id_pesanan`),
  ADD KEY `fk_chat_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `cs_chat`
--
ALTER TABLE `cs_chat`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `fk_cs_chat_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `cs_message`
--
ALTER TABLE `cs_message`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `fk_cs_message_chat` (`id_chat`);

--
-- Indeks untuk tabel `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_pembelian` (`id_pembelian`),
  ADD KEY `fk_detail_produk` (`id_produk`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_pesanan` (`id_pesanan`),
  ADD KEY `fk_detail_pesanan_produk` (`id_produk`);

--
-- Indeks untuk tabel `favorit`
--
ALTER TABLE `favorit`
  ADD PRIMARY KEY (`id_favorit`),
  ADD UNIQUE KEY `unik_favorit` (`id_pelanggan`,`id_produk`),
  ADD KEY `fk_favorit_produk` (`id_produk`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id_keranjang`),
  ADD KEY `fk_keranjang_pelanggan` (`id_pelanggan`),
  ADD KEY `fk_keranjang_produk` (`id_produk`);

--
-- Indeks untuk tabel `lokasi_rak`
--
ALTER TABLE `lokasi_rak`
  ADD PRIMARY KEY (`id_lokasi`),
  ADD UNIQUE KEY `kode_rak` (`kode_rak`);

--
-- Indeks untuk tabel `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD PRIMARY KEY (`id_reset`),
  ADD UNIQUE KEY `unik_reset_token` (`token`),
  ADD KEY `idx_reset_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id_pembelian`),
  ADD UNIQUE KEY `nomor_pembelian` (`nomor_pembelian`),
  ADD KEY `fk_pembelian_supplier` (`id_supplier`);

--
-- Indeks untuk tabel `pengaturan_toko`
--
ALTER TABLE `pengaturan_toko`
  ADD PRIMARY KEY (`id_pengaturan`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `nomor_pesanan` (`nomor_pesanan`),
  ADD KEY `fk_pesanan_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `fk_produk_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `produk_varian`
--
ALTER TABLE `produk_varian`
  ADD PRIMARY KEY (`id_varian`),
  ADD UNIQUE KEY `unik_produk_varian` (`id_produk`,`nama_varian`);

--
-- Indeks untuk tabel `produk_varian_gambar`
--
ALTER TABLE `produk_varian_gambar`
  ADD PRIMARY KEY (`id_gambar_varian`),
  ADD KEY `idx_varian_gambar` (`id_varian`);

--
-- Indeks untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`),
  ADD UNIQUE KEY `kode_supplier` (`kode_supplier`);

--
-- Indeks untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id_ulasan`),
  ADD UNIQUE KEY `unique_ulasan_produk_pelanggan_pesanan` (`id_produk`,`id_pelanggan`,`id_pesanan`),
  ADD KEY `fk_ulasan_pelanggan` (`id_pelanggan`),
  ADD KEY `fk_ulasan_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`id_voucher`),
  ADD UNIQUE KEY `kode_voucher` (`kode_voucher`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `admin_log_aktivitas`
--
ALTER TABLE `admin_log_aktivitas`
  MODIFY `id_log` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT untuk tabel `banner`
--
ALTER TABLE `banner`
  MODIFY `id_banner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `chat_message`
--
ALTER TABLE `chat_message`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `chat_session`
--
ALTER TABLE `chat_session`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `cs_chat`
--
ALTER TABLE `cs_chat`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `cs_message`
--
ALTER TABLE `cs_message`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT untuk tabel `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `favorit`
--
ALTER TABLE `favorit`
  MODIFY `id_favorit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id_keranjang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `lokasi_rak`
--
ALTER TABLE `lokasi_rak`
  MODIFY `id_lokasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `password_reset_token`
--
ALTER TABLE `password_reset_token`
  MODIFY `id_reset` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id_pembelian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `pengaturan_toko`
--
ALTER TABLE `pengaturan_toko`
  MODIFY `id_pengaturan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `produk_varian`
--
ALTER TABLE `produk_varian`
  MODIFY `id_varian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `produk_varian_gambar`
--
ALTER TABLE `produk_varian_gambar`
  MODIFY `id_gambar_varian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id_ulasan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `voucher`
--
ALTER TABLE `voucher`
  MODIFY `id_voucher` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `chat_message`
--
ALTER TABLE `chat_message`
  ADD CONSTRAINT `fk_message_chat` FOREIGN KEY (`id_chat`) REFERENCES `chat_session` (`id_chat`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `chat_session`
--
ALTER TABLE `chat_session`
  ADD CONSTRAINT `fk_chat_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cs_chat`
--
ALTER TABLE `cs_chat`
  ADD CONSTRAINT `fk_cs_chat_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cs_message`
--
ALTER TABLE `cs_message`
  ADD CONSTRAINT `fk_cs_message_chat` FOREIGN KEY (`id_chat`) REFERENCES `cs_chat` (`id_chat`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `fk_detail_pembelian` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id_pembelian`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_pesanan_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `favorit`
--
ALTER TABLE `favorit`
  ADD CONSTRAINT `fk_favorit_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favorit_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `fk_keranjang_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_keranjang_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD CONSTRAINT `fk_reset_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produk_varian`
--
ALTER TABLE `produk_varian`
  ADD CONSTRAINT `fk_produk_varian_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produk_varian_gambar`
--
ALTER TABLE `produk_varian_gambar`
  ADD CONSTRAINT `fk_gambar_produk_varian` FOREIGN KEY (`id_varian`) REFERENCES `produk_varian` (`id_varian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `fk_ulasan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ulasan_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ulasan_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
