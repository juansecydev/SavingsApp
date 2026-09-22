--liquibase formatted sql

--changeset juan:001-create-database
CREATE TABLE `roles` (
  `role_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(15) NOT NULL,
  `role_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `currencies` (
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

CREATE TABLE `transaction_operations` (
  `transaction_operation_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_operation_description` VARCHAR(20) NOT NULL,
  `transaction_operation_symbol` VARCHAR(3) NOT NULL,
  `transaction_operation_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_operation_id`),
  UNIQUE KEY `uq_transaction_operation_description` (`transaction_operation_description`),
  UNIQUE KEY `uq_transaction_operation_symbol` (`transaction_operation_symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `categories` (
  `category_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_description` VARCHAR(20) NOT NULL,
  `category_icon` VARCHAR(30) NULL,
  `category_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_description` (`category_description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_names` VARCHAR(30) NOT NULL,
  `user_last_names` VARCHAR(30) NOT NULL,
  `user_email` VARCHAR(240) NOT NULL,
  `user_password` VARCHAR(255) NOT NULL,
  `user_profile_picture` VARCHAR(255) NULL,
  `user_role_id` TINYINT UNSIGNED NOT NULL,
  `user_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_user_email` (`user_email`),
  UNIQUE KEY `uq_user_profile_picture` (`user_profile_picture`),
  CONSTRAINT `chk_user_email` CHECK (`user_email` LIKE '%@%'),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`user_role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `accounts` (
  `account_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_title` VARCHAR(30) NOT NULL,
  `account_balance` BIGINT NOT NULL,
  `account_user_id` INT UNSIGNED NOT NULL,
  `account_currency_id` TINYINT UNSIGNED NOT NULL,
  `account_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  CONSTRAINT `fk_accounts_user` FOREIGN KEY (`account_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_accounts_currency` FOREIGN KEY (`account_currency_id`) REFERENCES `currencies` (`currency_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `account_transactions` (
  `account_transaction_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_transaction_account_id` INT UNSIGNED NULL,
  `account_transaction_title` VARCHAR(30) NOT NULL,
  `account_transaction_category_id` JSON NULL,
  `account_transaction_amount` BIGINT UNSIGNED NOT NULL,
  `account_transaction_transaction_operation_id` TINYINT UNSIGNED NOT NULL,
  `account_transaction_created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_transaction_id`),
  CONSTRAINT `fk_account_transactions_account` FOREIGN KEY (`account_transaction_account_id`) REFERENCES `accounts` (`account_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_account_transactions_operation` FOREIGN KEY (`account_transaction_transaction_operation_id`) REFERENCES `transaction_operations` (`transaction_operation_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`role_name`) VALUES ('Administrator'), ('Normal');

INSERT INTO `currencies` (`currency_name`, `currency_code`, `currency_symbol`, `currency_decimals`) VALUES
  ('US Dollar', 'USD', '$', 2),
  ('Colombian Peso', 'COP', '$', 2),
  ('Yen', 'JPY', '¥', 0);

INSERT INTO `transaction_operations` (`transaction_operation_description`, `transaction_operation_symbol`) VALUES
  ('Income', '+'),
  ('Egress', '-');

INSERT INTO `categories` (`category_description`, `category_icon`) VALUES
  ('Income', 'bi bi-graph-up-arrow'),
  ('Egress', 'bi bi-graph-down-arrow');

INSERT INTO `users` (
  `user_names`,
  `user_last_names`,
  `user_email`,
  `user_password`,
  `user_profile_picture`,
  `user_role_id`
) VALUES (
  'Marín',
  'Kitagawa',
  'marin.kitagawa@email.com',
  '$2y$11$OuS6XhPbsoTidBbdQtC/xeA3LOt4DIZip7pSKZfqtPtGC7SdssaIq',
  'users/pfp/179cde1570302a0549233d670c6d5334.jpg',
  (SELECT `role_id` FROM `roles` WHERE `role_name` = 'Administrator')
);
