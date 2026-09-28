-- Importare una sola volta nel database di produzione prima di caricare il codice.
CREATE TABLE `calendar_preferences` (
  `id` tinyint unsigned NOT NULL,
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
