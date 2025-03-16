CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `maintenance_date` date NOT NULL,
  `status` enum('scheduled','completed','overdue') NOT NULL DEFAULT 'scheduled',
  `description` varchar(255) DEFAULT NULL,
  `notes` text,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `maintenance_date` (`maintenance_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
