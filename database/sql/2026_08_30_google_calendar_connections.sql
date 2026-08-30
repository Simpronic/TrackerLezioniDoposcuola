-- Eseguire sul database InfinityFree prima di caricare il codice della feature.
-- Il valore di refresh_token verrà inserito dall'app e sarà cifrato con APP_KEY.
CREATE TABLE `google_calendar_connections` (
  `id` tinyint unsigned NOT NULL,
  `refresh_token` text NOT NULL,
  `connected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
