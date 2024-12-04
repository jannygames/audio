-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2024 at 10:42 PM
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
-- Database: `projektas`
--
CREATE DATABASE IF NOT EXISTS `projektas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `projektas`;

-- --------------------------------------------------------

--
-- Table structure for table `prekes`
--

DROP TABLE IF EXISTS `prekes`;
CREATE TABLE `prekes` (
  `id` int(11) NOT NULL,
  `gamintojas` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `modelis` varchar(30) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `paskirtis` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `tipas` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `kaina` float NOT NULL,
  `likutis` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `prekes`:
--

--
-- Dumping data for table `prekes`
--

INSERT INTO `prekes` (`id`, `gamintojas`, `modelis`, `paskirtis`, `tipas`, `kaina`, `likutis`) VALUES
(1, 'Sony', 'WH-1000XM4', 'Telefonams', 'Ausines', 299.99, 0),
(2, 'JBL', 'Flip 6', 'Kompiuteriams', 'Garsiakalbiai', 129.99, 14),
(3, 'Bose', 'QuietComfort 35', 'Telefonams', 'Ausines', 249.99, 16),
(4, 'Yamaha', 'RX-V4A', 'Namu kinui', 'Stiprintuvai', 449.99, 3),
(5, 'Audio-Technica', 'AT-LP120XUSB', 'Namu kinui', 'Vinilines ploksteles', 249.99, 5),
(6, 'Panasonic', 'RP-HTX90N', 'Auto', 'Ausines', 89.99, 24),
(7, 'Pioneer', 'DEH-S4220BT', 'Auto', 'CD', 109.99, 15),
(8, 'Klipsch', 'R-41M', 'Stereo', 'Garsiakalbiai', 149.99, 19),
(9, 'Samsung', 'HW-Q70T', 'Namu kinui', 'Garso procesoriai', 499.99, 7),
(10, 'Behringer', 'UCA222', 'Kompiuteriams', 'Garso procesoriai', 39.99, 39),
(11, 'AKG', 'K240 Studio', 'Irasai', 'Ausines', 69.99, 35),
(12, 'Shure', 'M97xE', 'Vinilines ploksteles', 'Stiprintuvai', 179.99, 10),
(13, 'Denon', 'DP-300F', 'Namu kinui', 'Vinilines ploksteles', 329.99, 12),
(14, 'Monster', 'MC 600', 'Kabeliai', 'Kabeliai', 29.99, 50),
(15, 'Sennheiser', 'HD 560S', 'Stereo', 'Ausines', 199.99, 25),
(16, 'Pyle', 'PLMR24', 'Auto', 'Garsiakalbiai', 24.99, 60),
(17, 'Onkyo', 'TX-8220', 'Stereo', 'Stiprintuvai', 299.99, 9),
(18, 'Philips', 'SHL3075', 'Kompiuteriams', 'Ausines', 39.99, 40),
(19, 'Bowers & Wilkins', 'ASW610', 'Namu kinui', 'Garsiakalbiai', 799.99, 6),
(20, 'Cambridge Audio', 'CXA81', 'Stereo', 'Stiprintuvai', 1299.99, 5),
(21, 'Sony', 'MDR-7506', 'Kompiuteriams', 'Ausines', 129.99, 20),
(22, 'JBL', 'Charge 5', 'Telefonams', 'Garsiakalbiai', 179.99, 15),
(23, 'Bose', 'SoundLink Revolve', 'Telefonams', 'Garsiakalbiai', 199.99, 25),
(24, 'Yamaha', 'HS5', 'Namu kinui', 'Garsiakalbiai', 199.99, 30),
(25, 'Audio-Technica', 'ATH-M50x', 'Irasai', 'Ausines', 149.99, 20),
(26, 'Panasonic', 'SC-HTB490', 'Namu kinui', 'Garso procesoriai', 249.99, 12),
(27, 'Pioneer', 'DJM-450', 'Irasai', 'Garso procesoriai', 699.99, 8),
(28, 'Klipsch', 'The One II', 'Kompiuteriams', 'Garsiakalbiai', 249.99, 10),
(29, 'Samsung', 'HW-S60T', 'Namu kinui', 'Garso procesoriai', 299.99, 10),
(30, 'Behringer', 'C-1U', 'Irasai', 'Garso procesoriai', 59.99, 50),
(31, 'AKG', 'N700NC', 'Telefonams', 'Ausines', 299.99, 18),
(32, 'Shure', 'MV7', 'Irasai', 'Garso procesoriai', 249.99, 15),
(33, 'Denon', 'AH-D5200', 'Namu kinui', 'Ausines', 699.99, 5),
(34, 'Monster', 'SuperStar Blaster', 'Kompiuteriams', 'Garsiakalbiai', 399.99, 20),
(35, 'Sennheiser', 'HD 25', 'Kompiuteriams', 'Ausines', 149.99, 35),
(36, 'Pyle', 'PT390AU', 'Auto', 'Stiprintuvai', 139.99, 22),
(37, 'Onkyo', 'A-9110', 'Stereo', 'Stiprintuvai', 299.99, 7),
(38, 'Philips', 'TAB5305', 'Telefonams', 'Garsiakalbiai', 99.99, 50),
(39, 'Bowers & Wilkins', 'PX7', 'Telefonams', 'Ausines', 399.99, 8),
(40, 'Cambridge Audio', 'AXA35', 'Stereo', 'Stiprintuvai', 349.99, 6),
(41, 'Sony', 'XB33', 'Telefonams', 'Garsiakalbiai', 119.99, 40),
(42, 'JBL', 'Boombox 2', 'Kompiuteriams', 'Garsiakalbiai', 499.99, 15),
(43, 'Bose', 'Noise Cancelling 700', 'Telefonams', 'Ausines', 379.99, 20),
(44, 'Yamaha', 'A-S301', 'Stereo', 'Stiprintuvai', 399.99, 10),
(45, 'Audio-Technica', 'AT2020', 'Irasai', 'Garso procesoriai', 99.99, 45),
(46, 'Panasonic', 'SC-UA3GW-K', 'Namu kinui', 'Garsiakalbiai', 249.99, 18),
(47, 'Pioneer', 'SE-MS5T', 'Kompiuteriams', 'Ausines', 79.99, 25),
(48, 'Klipsch', 'The Three', 'Stereo', 'Garsiakalbiai', 299.99, 15),
(49, 'Samsung', 'HW-Q950T', 'Namu kinui', 'Garso procesoriai', 1599.99, 5),
(50, 'Behringer', 'X32', 'Irasai', 'Garso procesoriai', 2499.99, 3),
(51, 'AKG', 'K92', 'Kompiuteriams', 'Ausines', 59.99, 60),
(52, 'Shure', 'SM58', 'Irasai', 'Garso procesoriai', 99.99, 35),
(53, 'Denon', 'HEOS 1', 'Namu kinui', 'Garsiakalbiai', 199.99, 22),
(54, 'Monster', 'ClarityHD', 'Telefonams', 'Garsiakalbiai', 149.99, 30),
(55, 'Sennheiser', 'Momentum 3', 'Telefonams', 'Ausines', 349.99, 18),
(56, 'Pyle', 'PDIC60', 'Auto', 'Garsiakalbiai', 29.99, 55),
(57, 'Onkyo', 'TX-RZ840', 'Namu kinui', 'Stiprintuvai', 799.99, 8),
(58, 'Philips', 'SHP9500', 'Stereo', 'Ausines', 79.99, 28),
(59, 'Bowers & Wilkins', 'Formation Duo', 'Stereo', 'Garsiakalbiai', 3999.99, 2);

-- --------------------------------------------------------

--
-- Table structure for table `uzsakymai`
--

DROP TABLE IF EXISTS `uzsakymai`;
CREATE TABLE `uzsakymai` (
  `id` int(5) NOT NULL,
  `user_id` int(5) NOT NULL,
  `data` datetime(6) NOT NULL,
  `uzsakyta_preke` int(5) NOT NULL,
  `prekiu_kiekis` int(5) NOT NULL,
  `suma` float NOT NULL,
  `busena` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `rezervacijos_galiojimo_data` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `uzsakymai`:
--   `uzsakyta_preke`
--       `prekes` -> `id`
--   `user_id`
--       `vartotojai` -> `id`
--

--
-- Dumping data for table `uzsakymai`
--

INSERT INTO `uzsakymai` (`id`, `user_id`, `data`, `uzsakyta_preke`, `prekiu_kiekis`, `suma`, `busena`, `rezervacijos_galiojimo_data`) VALUES
(2, 2, '2024-11-04 15:12:58.000000', 1, 1, 299.99, 'Processing', NULL),
(3, 2, '2024-11-04 15:12:58.000000', 2, 1, 129.99, 'Processing', NULL),
(4, 2, '2024-11-04 15:12:58.000000', 3, 1, 249.99, 'Processing', NULL),
(5, 2, '2024-11-04 15:25:22.000000', 2, 1, 129.99, 'Processing', NULL),
(6, 2, '2024-11-04 15:25:22.000000', 3, 1, 249.99, 'Processing', NULL),
(7, 2, '2024-11-04 15:29:54.000000', 1, 1, 299.99, 'priimtas', NULL),
(8, 2, '2024-11-04 15:29:54.000000', 2, 1, 129.99, 'priimtas', NULL),
(9, 2, '2024-11-04 15:29:54.000000', 3, 1, 249.99, 'atmestas', NULL),
(10, 2, '2024-11-04 15:29:54.000000', 4, 1, 449.99, 'atmestas', NULL),
(11, 2, '2024-11-04 15:37:25.000000', 2, 5, 649.95, 'priimtas', NULL),
(12, 2, '2024-11-04 15:37:25.000000', 5, 6, 1499.94, 'priimtas', NULL),
(13, 2, '2024-11-04 16:44:01.000000', 4, 1, 449.99, 'priimtas', NULL),
(14, 2, '2024-11-04 16:45:53.000000', 1, 1, 299.99, 'priimtas', NULL),
(15, 2, '2024-11-04 16:46:18.000000', 1, 4, 1199.96, 'atmestas', NULL),
(16, 2, '2024-11-04 16:46:18.000000', 2, 1, 129.99, 'priimtas', NULL),
(17, 2, '2024-11-04 16:46:18.000000', 3, 1, 249.99, 'atmestas', NULL),
(18, 2, '2024-11-04 16:46:18.000000', 4, 1, 449.99, 'atmestas', NULL),
(19, 2, '2024-11-04 16:46:18.000000', 9, 1, 499.99, 'atmestas', NULL),
(20, 2, '2024-11-04 16:46:52.000000', 1, 1, 299.99, 'atmestas', NULL),
(21, 2, '2024-11-04 18:07:54.000000', 1, 1, 299.99, 'priimtas', NULL),
(22, 2, '2024-11-04 18:07:54.000000', 2, 2, 259.98, 'priimtas', NULL),
(23, 2, '2024-11-04 18:07:54.000000', 4, 3, 1349.97, 'atmestas', NULL),
(24, 2, '2024-11-11 18:29:46.000000', 1, 1, 299.99, 'priimtas', NULL),
(25, 2, '2024-11-11 18:29:46.000000', 2, 1, 129.99, 'priimtas', NULL),
(26, 2, '2024-11-11 19:08:20.000000', 1, 1, 299.99, 'priimtas', NULL),
(27, 2, '2024-11-11 19:08:20.000000', 2, 1, 129.99, 'priimtas', NULL),
(28, 2, '2024-11-13 14:04:52.000000', 1, 4, 1199.96, 'priimtas', NULL),
(29, 2, '2024-11-13 14:04:52.000000', 6, 1, 89.99, 'priimtas', NULL),
(30, 2, '2024-12-03 21:37:58.000000', 7, 3, 329.97, 'rezervuotas', '2024-12-10 21:37:58'),
(31, 2, '2024-12-03 21:37:58.000000', 8, 1, 149.99, 'rezervuotas', '2024-12-10 21:37:58'),
(32, 2, '2024-12-03 21:37:58.000000', 10, 1, 39.99, 'rezervuotas', '2024-12-10 21:37:58'),
(33, 2, '2024-12-03 21:51:59.000000', 2, 1, 129.99, 'priimtas', NULL),
(34, 2, '2024-12-03 21:51:59.000000', 4, 1, 449.99, 'atmestas', NULL),
(35, 2, '2024-12-03 22:18:26.000000', 2, 1, 129.99, 'priimtas', NULL),
(36, 2, '2024-12-03 22:18:26.000000', 5, 1, 249.99, 'atmestas', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vartotojai`
--

DROP TABLE IF EXISTS `vartotojai`;
CREATE TABLE `vartotojai` (
  `id` int(5) NOT NULL,
  `username` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `el_pastas` varchar(50) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `slaptazodis` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
  `role` varchar(20) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci DEFAULT NULL,
  `pinigai` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `vartotojai`:
--

--
-- Dumping data for table `vartotojai`
--

INSERT INTO `vartotojai` (`id`, `username`, `el_pastas`, `slaptazodis`, `role`, `pinigai`) VALUES
(1, 'admin', 'admin@example.com', 'admin', 'Administratorius', 1000),
(2, 'user1', 'user1@example.com', 'password1', 'Vartotojas', 6110.34),
(3, 'user2', 'user2@example.com', 'password2', 'Vartotojas', 750),
(5, 'user4', 'user4@example.com', 'password4', 'Vadybininkas', 1300),
(7, 'user6', 'user6@example.com', 'password6', 'Vartotojas', 600),
(9, 'user8', 'user8@example.com', 'password8', 'Administratorius', 1500),
(11, 'user10', 'user10@gmail.com', '12345', 'Vartotojas', 4000),
(12, 'vadyb', 'vadyb@gmail.com', 'vadyb', 'Vadybininkas', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `prekes`
--
ALTER TABLE `prekes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uzsakymai`
--
ALTER TABLE `uzsakymai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzsakymai_user_fk` (`user_id`),
  ADD KEY `uzsakymai_prekes_fk` (`uzsakyta_preke`);

--
-- Indexes for table `vartotojai`
--
ALTER TABLE `vartotojai`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `prekes`
--
ALTER TABLE `prekes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `uzsakymai`
--
ALTER TABLE `uzsakymai`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `vartotojai`
--
ALTER TABLE `vartotojai`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `uzsakymai`
--
ALTER TABLE `uzsakymai`
  ADD CONSTRAINT `uzsakymai_prekes_fk` FOREIGN KEY (`uzsakyta_preke`) REFERENCES `prekes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `uzsakymai_user_fk` FOREIGN KEY (`user_id`) REFERENCES `vartotojai` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
