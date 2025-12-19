-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 06:23 AM
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
-- Database: `crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `goal` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `status` enum('Planned','Running','Completed','Canceled') DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `name`, `goal`, `start_date`, `end_date`, `budget`, `status`, `created_at`) VALUES
(2, 'lead generative drive', 'demo campagin goal', '2025-11-18', '2025-12-18', 15000.00, 'Running', '2025-11-18 06:23:00');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `owner_id` int(11) UNSIGNED NOT NULL COMMENT 'Links to the users table',
  `total_deals` int(11) DEFAULT 0,
  `latest_activity` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `industry`, `phone`, `owner_id`, `total_deals`, `latest_activity`, `created_at`) VALUES
(1, 'Samuthra Tech Solutions', 'Software Technology', '8610096458', 1, 0, '2025-12-10', '2025-11-17 13:46:02'),
(3, 'Kalpaka Organics', 'Fertilizer', '9043484737', 4, 0, '2025-12-10', '2025-12-10 11:55:23'),
(4, 'Mithra', 'Software Technology', '8610096458', 1, 0, NULL, '2025-12-11 10:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Contact Full Name',
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL COMMENT 'Job Title',
  `company_name` varchar(255) DEFAULT NULL COMMENT 'Associated Company/Customer',
  `status` varchar(50) NOT NULL DEFAULT 'Prospect' COMMENT 'Contact status (e.g., Lead, Customer, Cold)',
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `company_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `title`, `company_name`, `status`, `created_by`, `created_at`, `company_id`) VALUES
(1, 'Micheal Mithra M', 'mmichealmithra@gmail.com', '8610096458', 'Developer', 'sts', 'Lead', 1, '2025-11-14 13:12:11', NULL),
(2, 'Jeeva', 'jeeva@gmail.com', '9043484736', 'Developer', 'sts', 'Customer', 1, '2025-11-14 13:15:22', NULL),
(4, 'Jenifer', 'jeni@gmail.com', '8610096458', 'sample', NULL, 'Prospect', 0, '2025-11-17 15:13:12', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Primary Contact Name',
  `company` varchar(255) NOT NULL,
  `tier` varchar(50) NOT NULL COMMENT 'Subscription Tier (Basic, Pro, Enterprise)',
  `status` varchar(50) NOT NULL COMMENT 'Customer Status (Active, Churn Risk, Onboarding)',
  `renewal_date` date DEFAULT NULL,
  `arr_value` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Annual Recurring Revenue',
  `created_by` int(11) UNSIGNED NOT NULL COMMENT 'Foreign key to users table',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `company`, `tier`, `status`, `renewal_date`, `arr_value`, `created_by`, `created_at`) VALUES
(1, 'Jenifer', 'Jeni', 'Basic', 'Onboarding', '2025-11-14', 200000.00, 1, '2025-11-14 13:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `deals`
--

CREATE TABLE `deals` (
  `id` int(11) UNSIGNED NOT NULL,
  `deal_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `stage` varchar(50) NOT NULL,
  `close_date` date DEFAULT NULL,
  `company_id` int(11) UNSIGNED DEFAULT NULL,
  `owner_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `opening_date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deals`
--

INSERT INTO `deals` (`id`, `deal_name`, `amount`, `stage`, `close_date`, `company_id`, `owner_id`, `created_at`, `updated_at`, `opening_date`) VALUES
(2, 'demo 1', 15000.00, 'Proposal Sent', '2025-11-30', 1, 1, '2025-11-18 04:36:48', '2025-11-18 04:38:19', '2025-12-10'),
(3, 'demo 2', 10000.00, 'Qualification', '2025-11-30', 1, 1, '2025-11-18 04:38:42', '2025-11-18 04:38:50', '2025-12-10'),
(5, 'demo 1', 15000.00, 'New', '2025-12-12', 3, 4, '2025-12-10 07:16:13', '2025-12-10 07:16:13', '2025-12-10');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` enum('New','Attempted','Contacted','Qualified','Unqualified') DEFAULT 'New',
  `source` varchar(100) DEFAULT NULL,
  `owner_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `campaign_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `company`, `email`, `phone`, `status`, `source`, `owner_id`, `created_at`, `updated_at`, `campaign_id`) VALUES
(5, 'Jenifer', 'Jeni', 'mmichealmithra@gmail.com', '1234567891', 'Attempted', 'Website', 1, '2025-11-18 06:19:54', '2025-11-18 06:28:39', 2);

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `date_time` datetime NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `contact_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `subject`, `date_time`, `type`, `status`, `contact_id`, `user_id`, `created_at`, `updated_at`) VALUES
(2, 'simple', '2025-11-20 10:37:00', 'Phone Call', 'Scheduled', 2, 1, '2025-11-18 05:07:23', '2025-11-18 05:07:23');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Planning','In Progress','On Hold','Completed','Cancelled') NOT NULL DEFAULT 'Planning',
  `progress` int(3) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `company_id` int(11) UNSIGNED DEFAULT NULL,
  `manager_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `status`, `progress`, `start_date`, `due_date`, `company_id`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Website', 'this demo for testing', 'In Progress', 81, '2025-11-01', NULL, 1, 1, '2025-11-18 06:53:28', '2025-11-18 07:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

CREATE TABLE `scheduled_reports` (
  `id` int(11) UNSIGNED NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_subject` varchar(255) NOT NULL,
  `report_body` text DEFAULT NULL,
  `recipients_json` text NOT NULL COMMENT 'JSON array of user IDs or roles (e.g., {"users": [1, 5, 9], "roles": ["admin", "manager"]})',
  `schedule_type` enum('Daily','Weekly','Monthly','Once') NOT NULL DEFAULT 'Daily',
  `schedule_time` time NOT NULL COMMENT 'Time of day to run (HH:MM:SS)',
  `schedule_date` date DEFAULT NULL COMMENT 'Used for Weekly/Monthly/Once schedules',
  `next_run_datetime` datetime NOT NULL COMMENT 'The next calculated date/time the cron job should run this report',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_datetime` datetime DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scheduled_reports`
--

INSERT INTO `scheduled_reports` (`id`, `report_name`, `report_subject`, `report_body`, `recipients_json`, `schedule_type`, `schedule_time`, `schedule_date`, `next_run_datetime`, `is_active`, `last_run_datetime`, `created_by`, `created_at`) VALUES
(1, 'Time for Demo', 'demo', 'Hi [Recipient Name],\r\n\r\nHere is your summary report:\r\n\r\n[NEW_LEADS_COUNT] new leads were created today.\r\n\r\n[DEALS_CLOSED_COUNT] deals were closed today.\r\n\r\nThank you.', '{\"users\":[2]}', 'Once', '11:45:00', '2025-11-19', '2025-11-19 11:45:00', 1, NULL, 4, '2025-11-19 11:41:00'),
(2, 'Time for Demo', 'demo', 'Hi [Recipient Name],\r\n\r\nHere is your summary report:\r\n\r\n[NEW_LEADS_COUNT] new leads were created today.\r\n\r\n[DEALS_CLOSED_COUNT] deals were closed today.\r\n\r\nThank you.', '{\"users\":[2,4]}', 'Once', '13:30:00', '2025-11-19', '2025-11-19 13:30:00', 1, NULL, 4, '2025-11-19 14:17:20'),
(3, 'Time for Demo', 'demo', 'Hi [Recipient Name],\r\n\r\nHere is your summary report:\r\n\r\n[NEW_LEADS_COUNT] new leads were created today.\r\n\r\n[DEALS_CLOSED_COUNT] deals were closed today.\r\n\r\nThank you.', '{\"users\":[2,4]}', 'Once', '13:45:00', '2025-11-19', '2025-11-19 13:45:00', 1, NULL, 4, '2025-11-19 15:41:39'),
(4, 'Time for Demo', 'demo', 'Hi [Recipient Name],\r\n\r\nHere is your summary report:\r\n\r\n[NEW_LEADS_COUNT] new leads were created today.\r\n\r\n[DEALS_CLOSED_COUNT] deals were closed today.\r\n\r\nThank you.', '{\"users\":[2]}', 'Daily', '21:00:00', '2025-11-19', '2025-11-19 21:00:00', 1, NULL, 4, '2025-11-19 16:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('company_name', 'STS CRM', '2025-11-18 09:35:17'),
('daily_summary_enabled', '0', '2025-11-19 06:01:49'),
('daily_summary_roles', '', '2025-11-19 06:01:49'),
('daily_summary_time', '09:00', '2025-11-19 06:01:49'),
('default_currency', 'INR', '2025-11-18 10:10:48'),
('password_min_length', '6', '2025-11-18 09:56:20'),
('session_timeout_minutes', '60', '2025-11-18 09:56:20'),
('timezone', 'Asia/Kolkata', '2025-11-18 10:10:48'),
('welcome_email_body', 'Dear Jeeva , Welcome to Samudhra Tech Solutions! Your journey starts here.', '2025-11-18 10:11:42'),
('welcome_email_subject', 'Welcome to the System!', '2025-11-18 10:11:42');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `contact_id` int(11) UNSIGNED DEFAULT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `due_date`, `priority`, `status`, `contact_id`, `user_id`, `created_at`, `updated_at`) VALUES
(2, 'test task 1', 'demo for check', '2025-11-19', 'Medium', 'In Progress', 2, 1, '2025-11-18 05:19:43', '2025-11-18 05:21:37'),
(3, 'test task 2', 'demo for update', '2025-11-19', 'Medium', 'Completed', 4, 2, '2025-11-18 05:24:20', '2025-11-18 07:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'FK to users table, the team lead',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `description`, `manager_id`, `created_at`) VALUES
(2, 'Developer', 'this is demo team', 1, '2025-11-18 13:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Stores the hashed password',
  `role` varchar(50) NOT NULL DEFAULT 'user' COMMENT 'e.g., admin, user, manager',
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `team` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `status`, `team`) VALUES
(1, 'Mithra', 'mithra@gmail.com', '$2y$10$2V/kU9KC84Eva6m9G2BFpOomDetExIHA4FdKqQz6S3dVdwV/oFX3G', 'admin', '2025-11-14 13:05:16', 'Active', NULL),
(2, 'Jeeva', 'jeevale2004@gmail.com', '$2y$10$srfgsBmmDOc1BPr5Ushufu.0OkZC7bnwwUEYOr0vcg0cEp4AlU3kW', 'Employee', '2025-11-17 13:49:06', 'Active', 'Product Development'),
(4, 'Micheal Mithra M', 'mmichealmithra@gmail.com', '$2y$10$k2Uqi7DNKZYBfxx9ycpRo./Mw9j1/0upYPh5sgq5x1BsBeSfMNo9K', 'user', '2025-11-19 11:02:43', 'Active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_contact_company` (`company_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deals`
--
ALTER TABLE `deals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `campaign_id` (`campaign_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `fk_contact_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deals`
--
ALTER TABLE `deals`
  ADD CONSTRAINT `deals_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deals_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meetings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD CONSTRAINT `scheduled_reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
