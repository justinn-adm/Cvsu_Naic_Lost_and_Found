-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 03:16 AM
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
-- Database: `lost_and_found`
--

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `claim_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`id`, `user_id`, `item_id`, `message`, `proof_image`, `claim_date`, `status`, `is_read`) VALUES
(1, 40, 1, 'User submitted claim with proof.', 'proof_uploads/1760001264_green.png', '2025-10-09 09:14:24', 'approved', 1),
(2, 60, 2, 'User submitted claim with proof.', 'proof_uploads/1760001643_green.png', '2025-10-09 09:20:43', 'rejected', 1),
(3, 60, 3, 'User submitted claim with proof.', 'proof_uploads/1760086483_scarlet.png', '2025-10-10 08:54:43', 'approved', 1),
(4, 58, 4, 'User submitted claim with proof.', 'proof_uploads/1760224926_green.png', '2025-10-11 23:22:06', 'approved', 1),
(5, 59, 5, 'User submitted claim with proof.', 'proof_uploads/1760227572_green.png', '2025-10-12 00:06:12', 'approved', 1);

-- --------------------------------------------------------

--
-- Table structure for table `found_items`
--

CREATE TABLE `found_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `date_found` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `anonymous` tinyint(1) DEFAULT 0,
  `claimed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'unclaimed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `found_items`
--

INSERT INTO `found_items` (`id`, `user_id`, `item_id`, `item_name`, `description`, `location`, `date_found`, `image_path`, `anonymous`, `claimed`, `created_at`, `status`) VALUES
(3, 40, NULL, 'Watch', 'silver', 'amaya', '2025-10-10', 'uploads/1760085065_scarlet.png', 0, 1, '2025-10-10 08:31:05', 'unclaimed'),
(4, 40, NULL, 'rt', 'rt', 'rt', '2025-10-12', 'uploads/1760224909_green.png', 0, 1, '2025-10-11 23:21:49', 'unclaimed'),
(5, 60, NULL, 'burat', 'jitim', 'amaya', '2025-10-12', 'uploads/1760227539_green.png', 0, 1, '2025-10-12 00:05:39', 'unclaimed');

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `name` varchar(200) NOT NULL,
  `image_path` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `date_found` date NOT NULL DEFAULT current_timestamp(),
  `location` varchar(200) NOT NULL,
  `id` int(20) NOT NULL,
  `uploader_name` varchar(255) DEFAULT NULL,
  `anonymous` tinyint(1) DEFAULT 0,
  `claimed` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'missing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_items`
--

INSERT INTO `lost_items` (`name`, `image_path`, `description`, `date_found`, `location`, `id`, `uploader_name`, `anonymous`, `claimed`, `status`) VALUES
('aasasa', 'uploads/1749811528_a.png', 'as', '2025-06-09', 'as', 27, 'er', 0, 1, 'missing'),
('kkjk', 'uploads/1749811830_button.png', 'dh', '2025-06-08', 't', 28, 'er', 0, 1, 'missing'),
('hhhhhh', 'uploads/1749811885_Screenshot 2025-06-07 163416.png', 'asdas', '2025-06-03', 'asd', 29, NULL, 1, 1, 'missing'),
('susi', 'uploads/1749816564_Screenshot 2025-06-07 163416.png', 'sasasa', '2025-06-05', 'sas', 30, 'er', 0, 1, 'missing'),
('Unan', 'uploads/1749819129_495269889_3085149628318784_3496240566602755163_n.jpg', 'Brown', '2025-06-13', 'Amaya', 33, 'qw', 0, 1, 'missing'),
('hjgjhg', 'uploads/1749820539_494859936_1462328261431044_7381767369926303350_n.jpg', 'cghdghh', '2025-06-03', 'ghfhf', 34, NULL, 1, 1, 'missing'),
('adasd', 'uploads/1750006551_494859936_1462328261431044_7381767369926303350_n.jpg', 'asdadad', '2025-06-05', 'asdsad', 35, NULL, 1, 1, 'missing'),
('cx', 'uploads/1750009576_15623c05-88cd-4b9b-b841-2e86300b1865.jfif', 'cx', '2025-06-05', 'cx', 36, 'er', 0, 1, 'missing'),
('pipe', 'uploads/68eaf33497ac6.png', 'maasim', '2025-10-12', 'amaya', 39, 'caren', 0, 0, 'missing');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `image_path` varchar(300) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `profile_image` varchar(300) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `profile_img` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `image_path`, `role`, `profile_image`, `gender`, `profile_img`) VALUES
(24, 'asd', '$2y$10$g7iGJ9Q38j5MnnqrVyo52eRkfs.y99NqHD.gmiYvF3Knx7z3ycKNy', 'asddda@gmail.com', '', 'admin', '', '', ''),
(40, 'qw', '$2y$10$dKJMr4iZ9tqfPIl.sK1Nz.1PKmWWNmjEVm.GXgU00SufzBaCUq7ui', 'qw@gmail.com', '', 'user', '', 'female', 'girl.jpg'),
(58, 'qwe', '$2y$10$eR09jsbW/JVVCh6Njau/ye5rqhR8otThoZGRLjOZI1/HOHOAIhlj.', 'qwe@gmail.com', '', 'user', '', 'male', 'boy.jpg'),
(59, 'caren', '$2y$10$Wl8EPo67XhYpwsblQCBbIe49H0Rac27BNOx7QbgcP7Ba73wGtiIsy', 'caren@gmail.com', '', 'user', '', 'female', 'girl.jpg'),
(60, 'tin', '$2y$10$OI06pV5Ewer..6MEX8vEmO.0QTi8CxXas8aGjexURUntS3SdFy/z.', 'tin@gmail.com', '', 'user', '', 'male', 'boy.jpg'),
(61, 'zxc', '$2y$10$DpMD3Yw/diIe0fEclmxog..OhZo.XE/ForOsSx0CdmMj3rEsH3lHu', 'zxc@gmail.com', '', 'admin', '', '', ''),
(62, 'rainer', '$2y$10$GKkrxemvnl.u6DGQuieMOeQ0eqUwrBGbL1JeoZKLBZt493CWKh8Ai', 'rainer@gmail.com', '', 'user', '', 'male', 'boy.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `found_items`
--
ALTER TABLE `found_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `found_items`
--
ALTER TABLE `found_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `found_items`
--
ALTER TABLE `found_items`
  ADD CONSTRAINT `found_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `lost_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
