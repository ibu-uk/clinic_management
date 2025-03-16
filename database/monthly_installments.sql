CREATE TABLE IF NOT EXISTS `monthly_installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_type` enum('equipment','clinic_record') NOT NULL,
  `record_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `status` enum('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  `payment_id` int(11) DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_type_id` (`record_type`,`record_id`),
  KEY `payment_id` (`payment_id`),
  KEY `status_due_date` (`status`,`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
