--
-- Database: `qsl_card`
--

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `qso_date` date NOT NULL,
  `time_on` time NOT NULL,
  `call` varchar(50) NOT NULL,
  `band` varchar(20) DEFAULT NULL,
  `freq` varchar(20) DEFAULT NULL,
  `mode` varchar(20) DEFAULT NULL,
  `rst_sent` varchar(10) DEFAULT NULL,
  `rst_rcvd` varchar(10) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `qth` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `station_callsign` varchar(50) DEFAULT NULL,
  `my_rig` varchar(100) DEFAULT NULL,
  `qsl_sent` char(1) DEFAULT 'N',
  `qsl_rcvd` char(1) DEFAULT 'N',
  `qsl_rdate` date DEFAULT NULL,
  `source` enum('manual','lotw') NOT NULL DEFAULT 'manual',
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qsl_templates`
--

CREATE TABLE `qsl_templates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `background_image` varchar(255) NOT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_public` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `callsign` varchar(20) DEFAULT NULL,
  `lotw_username` varchar(255) DEFAULT NULL,
  `lotw_password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lotw_last_sync` datetime DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postal_address` text DEFAULT NULL,
  `qsl_info` varchar(255) DEFAULT NULL,
  `qsl_manager` varchar(255) DEFAULT NULL,
  `grid` varchar(255) DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_qso` (`user_id`,`call`,`band`,`qso_date`,`time_on`);

--
-- Indexes for table `qsl_templates`
--
ALTER TABLE `qsl_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_index` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`name`);

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
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qsl_templates`
--
ALTER TABLE `qsl_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qsl_templates`
--
ALTER TABLE `qsl_templates`
  ADD CONSTRAINT `qsl_templates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;