-- Run once in phpMyAdmin after selecting the `leaf` database.
ALTER TABLE users ADD COLUMN region VARCHAR(30) NOT NULL DEFAULT 'Beirut' AFTER email;
