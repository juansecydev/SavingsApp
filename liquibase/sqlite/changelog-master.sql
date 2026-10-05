--liquibase formatted sql

--changeset juan:001-create-database
CREATE TABLE roles (
  role_id INTEGER PRIMARY KEY AUTOINCREMENT CHECK (role_id BETWEEN 1 AND 255),
  role_name TEXT NOT NULL,
  role_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_role_name UNIQUE (role_name),
  CONSTRAINT chk_role_name_length CHECK (length(role_name) <= 15)
);

CREATE TABLE currencies (
  currency_id INTEGER PRIMARY KEY AUTOINCREMENT CHECK (currency_id BETWEEN 1 AND 255),
  currency_name TEXT NOT NULL,
  currency_code TEXT NOT NULL,
  currency_symbol TEXT NOT NULL,
  currency_minor_units INTEGER NOT NULL CHECK (typeof(currency_minor_units) = 'integer' AND currency_minor_units BETWEEN 0 AND 255),
  currency_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_currency_name UNIQUE (currency_name),
  CONSTRAINT uq_currency_code UNIQUE (currency_code),
  CONSTRAINT chk_currency_name_length CHECK (length(currency_name) <= 50),
  CONSTRAINT chk_currency_code_length CHECK (length(currency_code) <= 3),
  CONSTRAINT chk_currency_symbol_length CHECK (length(currency_symbol) <= 2)
);

CREATE TABLE transaction_operations (
  transaction_operation_id INTEGER PRIMARY KEY AUTOINCREMENT CHECK (transaction_operation_id BETWEEN 1 AND 255),
  transaction_operation_description TEXT NOT NULL,
  transaction_operation_symbol TEXT NOT NULL,
  transaction_operation_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_transaction_operation_description UNIQUE (transaction_operation_description),
  CONSTRAINT uq_transaction_operation_symbol UNIQUE (transaction_operation_symbol),
  CONSTRAINT chk_transaction_operation_description_length CHECK (length(transaction_operation_description) <= 20),
  CONSTRAINT chk_transaction_operation_symbol_length CHECK (length(transaction_operation_symbol) <= 3)
);

CREATE TABLE categories (
  category_id INTEGER PRIMARY KEY AUTOINCREMENT CHECK (category_id BETWEEN 1 AND 255),
  category_description TEXT NOT NULL,
  category_icon TEXT NULL,
  category_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_category_description UNIQUE (category_description),
  CONSTRAINT chk_category_description_length CHECK (length(category_description) <= 20),
  CONSTRAINT chk_category_icon_length CHECK (category_icon IS NULL OR length(category_icon) <= 30)
);

CREATE TABLE users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT CHECK (user_id BETWEEN 1 AND 4294967295),
  user_names TEXT NOT NULL,
  user_last_names TEXT NOT NULL,
  user_email TEXT NOT NULL,
  user_password TEXT NOT NULL,
  user_profile_picture TEXT NULL,
  user_role_id INTEGER NOT NULL CHECK (typeof(user_role_id) = 'integer' AND user_role_id BETWEEN 0 AND 255),
  user_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_updated_at TEXT NULL DEFAULT NULL,
  CONSTRAINT uq_user_email UNIQUE (user_email),
  CONSTRAINT uq_user_profile_picture UNIQUE (user_profile_picture),
  CONSTRAINT chk_user_email CHECK (user_email LIKE '%@%'),
  CONSTRAINT chk_user_names_length CHECK (length(user_names) <= 30),
  CONSTRAINT chk_user_last_names_length CHECK (length(user_last_names) <= 30),
  CONSTRAINT chk_user_email_length CHECK (length(user_email) <= 240),
  CONSTRAINT chk_user_password_length CHECK (length(user_password) <= 255),
  CONSTRAINT chk_user_profile_picture_length CHECK (user_profile_picture IS NULL OR length(user_profile_picture) <= 255),
  CONSTRAINT fk_users_role FOREIGN KEY (user_role_id) REFERENCES roles (role_id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE accounts (
  account_id INTEGER PRIMARY KEY AUTOINCREMENT CHECK (account_id BETWEEN 1 AND 4294967295),
  account_title TEXT NOT NULL,
  account_balance INTEGER NOT NULL CHECK (typeof(account_balance) = 'integer'),
  account_user_id INTEGER NOT NULL CHECK (typeof(account_user_id) = 'integer' AND account_user_id BETWEEN 0 AND 4294967295),
  account_currency_id INTEGER NOT NULL CHECK (typeof(account_currency_id) = 'integer' AND account_currency_id BETWEEN 0 AND 255),
  account_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_account_title_length CHECK (length(account_title) <= 30),
  CONSTRAINT fk_accounts_user FOREIGN KEY (account_user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_accounts_currency FOREIGN KEY (account_currency_id) REFERENCES currencies (currency_id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE account_transactions (
  account_transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
  account_transaction_account_id INTEGER NULL CHECK (account_transaction_account_id IS NULL OR (typeof(account_transaction_account_id) = 'integer' AND account_transaction_account_id BETWEEN 0 AND 4294967295)),
  account_transaction_title TEXT NOT NULL,
  account_transaction_reference TEXT NULL,
  account_transaction_category_id TEXT NULL,
  account_transaction_amount INTEGER NOT NULL CHECK (typeof(account_transaction_amount) = 'integer' AND account_transaction_amount >= 0),
  account_transaction_transaction_operation_id INTEGER NOT NULL CHECK (typeof(account_transaction_transaction_operation_id) = 'integer' AND account_transaction_transaction_operation_id BETWEEN 0 AND 255),
  account_transaction_created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_account_transaction_title_length CHECK (length(account_transaction_title) <= 30),
  CONSTRAINT chk_account_transaction_reference_length CHECK (account_transaction_reference IS NULL OR length(account_transaction_reference) <= 50),
  CONSTRAINT chk_account_transaction_category_json CHECK (account_transaction_category_id IS NULL OR json_valid(account_transaction_category_id)),
  CONSTRAINT fk_account_transactions_account FOREIGN KEY (account_transaction_account_id) REFERENCES accounts (account_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_account_transactions_operation FOREIGN KEY (account_transaction_transaction_operation_id) REFERENCES transaction_operations (transaction_operation_id) ON UPDATE CASCADE ON DELETE CASCADE
);

INSERT INTO roles (role_name) VALUES ('Administrator'), ('Normal');

INSERT INTO currencies (currency_name, currency_code, currency_symbol, currency_minor_units) VALUES
  ('US Dollar', 'USD', '$', 2),
  ('Colombian Peso', 'COP', '$', 2),
  ('Yen', 'JPY', '¥', 0);

INSERT INTO transaction_operations (transaction_operation_description, transaction_operation_symbol) VALUES
  ('Income', '+'),
  ('Egress', '-');

INSERT INTO categories (category_description, category_icon) VALUES
  ('Income', 'bi bi-graph-up-arrow'),
  ('Egress', 'bi bi-graph-down-arrow');

INSERT INTO users (
  user_names,
  user_last_names,
  user_email,
  user_password,
  user_profile_picture,
  user_role_id
) VALUES (
  'Marín',
  'Kitagawa',
  'marin.kitagawa@email.com',
  '$2y$11$OuS6XhPbsoTidBbdQtC/xeA3LOt4DIZip7pSKZfqtPtGC7SdssaIq',
  'users/pfp/b0ebfc71e37a35da5a834d47cc4ad9be.jpg',
  (SELECT role_id FROM roles WHERE role_name = 'Administrator')
);
