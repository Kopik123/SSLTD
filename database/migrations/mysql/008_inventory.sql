-- Inventory: materials, tools, allocations, deliveries (MySQL/MariaDB)

CREATE TABLE IF NOT EXISTS `materials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `name` VARCHAR(255) NOT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'unit',
  `unit_cost_cents` BIGINT NOT NULL DEFAULT 0,
  `vendor` VARCHAR(255) NULL,
  `sku` VARCHAR(255) NULL,
  `notes` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_materials_status` (`status`),
  KEY `idx_materials_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `name` VARCHAR(255) NOT NULL,
  `serial` VARCHAR(255) NULL,
  `location` VARCHAR(255) NULL,
  `notes` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tools_status` (`status`),
  KEY `idx_tools_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_materials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'required',
  `qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `needed_by` VARCHAR(32) NULL,
  `notes` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_materials_project_id` (`project_id`),
  KEY `idx_project_materials_status` (`status`),
  CONSTRAINT `fk_project_materials_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_project_materials_material_id`
    FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_project_materials_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_tools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `tool_id` BIGINT UNSIGNED NOT NULL,
  `assigned_to_user_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'required',
  `notes` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_tools_project_id` (`project_id`),
  KEY `idx_project_tools_status` (`status`),
  CONSTRAINT `fk_project_tools_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_project_tools_tool_id`
    FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_project_tools_assigned_to_user_id`
    FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_project_tools_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `expected_at` VARCHAR(32) NULL,
  `delivered_at` VARCHAR(32) NULL,
  `notes` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_deliveries_project_id` (`project_id`),
  KEY `idx_deliveries_status` (`status`),
  KEY `idx_deliveries_expected_at` (`expected_at`),
  CONSTRAINT `fk_deliveries_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_deliveries_material_id`
    FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_deliveries_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

