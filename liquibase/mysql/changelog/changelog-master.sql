--liquibase formatted sql

--changeset juan:001-create-database
CREATE TABLE `role` (
  `role_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(15) NOT NULL,
  `role_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `currency` (
  `currency_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `currency_name` VARCHAR(50) NOT NULL,
  `currency_code` VARCHAR(3) NOT NULL,
  `currency_symbol` VARCHAR(2) NOT NULL,
  `currency_decimals` TINYINT UNSIGNED NOT NULL,
  `currency_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`currency_id`),
  UNIQUE KEY `uq_currency_name` (`currency_name`),
  UNIQUE KEY `uq_currency_code` (`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `account_event_transaction` (
  `account_event_transaction_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_event_transaction_description` VARCHAR(50) NOT NULL,
  `account_event_transaction_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_event_transaction_id`),
  UNIQUE KEY `uq_account_event_transaction_description` (`account_event_transaction_description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_event_transaction` (
  `user_event_transaction_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_event_transaction_description` VARCHAR(50) NOT NULL,
  `user_event_transaction_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_event_transaction_id`),
  UNIQUE KEY `uq_user_event_transaction_description` (`user_event_transaction_description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `transaction_operation` (
  `transaction_operation_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_operation_description` VARCHAR(20) NOT NULL,
  `transaction_operation_symbol` VARCHAR(3) NOT NULL,
  `transaction_operation_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_operation_id`),
  UNIQUE KEY `uq_transaction_operation_description` (`transaction_operation_description`),
  UNIQUE KEY `uq_transaction_operation_symbol` (`transaction_operation_symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `category` (
  `category_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_description` VARCHAR(20) NOT NULL,
  `category_icon` VARCHAR(30) NULL,
  `category_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_description` (`category_description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_current_version_number` INT UNSIGNED NOT NULL,
  `user_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_version` (
  `user_version_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_version_number` INT UNSIGNED NOT NULL,
  `user_version_user_id` INT UNSIGNED NOT NULL,
  `user_version_names` VARCHAR(30) NOT NULL,
  `user_version_last_names` VARCHAR(30) NOT NULL,
  `user_version_email` VARCHAR(240) NOT NULL,
  `user_version_password` VARCHAR(255) NOT NULL,
  `user_version_profile_picture` VARCHAR(255) NULL,
  `user_version_role_id` TINYINT UNSIGNED NOT NULL,
  `user_version_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_version_id`),
  UNIQUE KEY `uq_user_version_email` (`user_version_email`),
  UNIQUE KEY `uq_user_version_profile_picture` (`user_version_profile_picture`),
  CONSTRAINT `chk_user_version_email` CHECK (`user_version_email` LIKE '%@%'),
  CONSTRAINT `fk_user_version_user` FOREIGN KEY (`user_version_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_user_version_role` FOREIGN KEY (`user_version_role_id`) REFERENCES `role` (`role_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_event` (
  `user_event_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_event_user_version_id` INT UNSIGNED NOT NULL,
  `user_event_old_user_version_id` INT UNSIGNED NOT NULL,
  `user_event_new_user_version_id` INT UNSIGNED NOT NULL,
  `user_event_user_event_transaction_id` TINYINT UNSIGNED NOT NULL,
  `user_event_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_event_id`),
  CONSTRAINT `fk_user_event_user_version` FOREIGN KEY (`user_event_user_version_id`) REFERENCES `user_version` (`user_version_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_user_event_old_user_version` FOREIGN KEY (`user_event_old_user_version_id`) REFERENCES `user_version` (`user_version_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_user_event_new_user_version` FOREIGN KEY (`user_event_new_user_version_id`) REFERENCES `user_version` (`user_version_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_user_event_transaction` FOREIGN KEY (`user_event_user_event_transaction_id`) REFERENCES `user_event_transaction` (`user_event_transaction_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `account` (
  `account_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_title` VARCHAR(30) NOT NULL,
  `account_balance` BIGINT NOT NULL,
  `account_user_id` INT UNSIGNED NOT NULL,
  `account_currency_id` TINYINT UNSIGNED NOT NULL,
  `account_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  CONSTRAINT `fk_account_user` FOREIGN KEY (`account_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_account_currency` FOREIGN KEY (`account_currency_id`) REFERENCES `currency` (`currency_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `account_transaction` (
  `account_transaction_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_transaction_account_id` INT UNSIGNED NULL,
  `account_transaction_title` VARCHAR(30) NOT NULL,
  `account_transaction_category_id` JSON NULL,
  `account_transaction_amount` BIGINT UNSIGNED NOT NULL,
  `account_transaction_transaction_operation_id` TINYINT UNSIGNED NOT NULL,
  `account_transaction_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_transaction_id`),
  CONSTRAINT `fk_account_transaction_account` FOREIGN KEY (`account_transaction_account_id`) REFERENCES `account` (`account_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_account_transaction_operation` FOREIGN KEY (`account_transaction_transaction_operation_id`) REFERENCES `transaction_operation` (`transaction_operation_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `account_event` (
  `account_event_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_event_account_id` INT UNSIGNED NOT NULL,
  `account_event_user_version_id` INT UNSIGNED NOT NULL,
  `account_event_account_transaction_id` TINYINT UNSIGNED NOT NULL,
  `account_event_description` VARCHAR(200) NULL,
  `account_event_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_event_id`),
  CONSTRAINT `fk_account_event_account` FOREIGN KEY (`account_event_account_id`) REFERENCES `account` (`account_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_account_event_user_version` FOREIGN KEY (`account_event_user_version_id`) REFERENCES `user_version` (`user_version_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_account_event_transaction` FOREIGN KEY (`account_event_account_transaction_id`) REFERENCES `account_event_transaction` (`account_event_transaction_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `role` (`role_name`) VALUES
  ('Administrator'),
  ('Normal');

INSERT INTO `users` (`user_current_version_number`) VALUES (1);
SET @user_id := LAST_INSERT_ID();

INSERT INTO `user_version` (
  `user_version_number`,
  `user_version_user_id`,
  `user_version_names`,
  `user_version_last_names`,
  `user_version_email`,
  `user_version_password`,
  `user_version_profile_picture`,
  `user_version_role_id`
) VALUES (
  1,
  @user_id,
  'Marin',
  'Kitagawa',
  'marin.kitagawa@email.com',
  '$2y$11$OuS6XhPbsoTidBbdQtC/xeA3LOt4DIZip7pSKZfqtPtGC7SdssaIq',
  'storage/users/pfp/179cde1570302a0549233d670c6d5334.jpg',
  (SELECT `role_id` FROM `role` WHERE `role_name` = 'Administrator')
);

SET @user_version_id := LAST_INSERT_ID();

INSERT INTO `currency` (`currency_name`, `currency_code`, `currency_symbol`, `currency_decimals`) VALUES
  ('US Dollar', 'USD', '$', 2),
  ('Colombian Peso', 'COP', '$', 2),
  ('Yen', 'JPY', '¥', 0);

INSERT INTO `transaction_operation` (`transaction_operation_description`, `transaction_operation_symbol`) VALUES
  ('Income', '+'),
  ('Egress', '-');

INSERT INTO `category` (`category_description`, `category_icon`) VALUES
  ('Income', 'bi bi-graph-up-arrow'),
  ('Egress', 'bi bi-graph-down-arrow');

INSERT INTO `account_event_transaction` (`account_event_transaction_description`) VALUES
  ('Create'),
  ('Update'),
  ('Enable'),
  ('Disable'),
  ('Delete');

INSERT INTO `user_event_transaction` (`user_event_transaction_description`) VALUES
  ('Create'),
  ('Update'),
  ('Enable'),
  ('Disable'),
  ('Delete');

INSERT INTO `user_event` (
  `user_event_user_version_id`,
  `user_event_old_user_version_id`,
  `user_event_new_user_version_id`,
  `user_event_user_event_transaction_id`
) VALUES (
  @user_version_id,
  @user_version_id,
  @user_version_id,
  (SELECT `user_event_transaction_id` FROM `user_event_transaction` WHERE `user_event_transaction_description` = 'Create')
);
