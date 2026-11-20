-- =====================================
-- LOST AND FOUND HUB: DATABASE SCHEMA
-- Based on the ERD (7 entities, 3NF)
-- =====================================

CREATE DATABASE IF NOT EXISTS lostfound_pamp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lostfound_pamp;

-- Drop in reverse dependency order (children first)
DROP TABLE IF EXISTS Claim;
DROP TABLE IF EXISTS Found_Item;
DROP TABLE IF EXISTS Lost_Item;
DROP TABLE IF EXISTS Location;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS Admin;
DROP TABLE IF EXISTS `User`;

-- ---------- USER ----------
CREATE TABLE `User` (
  user_id         INT AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100) NOT NULL,
  email           VARCHAR(100) NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,          -- store a password_hash() value, never plain text
  contact_number  VARCHAR(20),
  date_registered TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ---------- ADMIN ----------
CREATE TABLE Admin (
  admin_id   INT AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL                -- password_hash() value
);

-- ---------- CATEGORY ----------
CREATE TABLE Category (
  category_id          INT AUTO_INCREMENT PRIMARY KEY,
  category_name        VARCHAR(50) NOT NULL UNIQUE,
  category_description VARCHAR(255)
);

-- ---------- LOCATION ----------
CREATE TABLE Location (
  location_id   INT AUTO_INCREMENT PRIMARY KEY,
  location_name VARCHAR(100) NOT NULL,
  building      VARCHAR(100),
  description   VARCHAR(255)
);

-- ---------- LOST_ITEM ----------
CREATE TABLE Lost_Item (
  lost_item_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  category_id  INT NOT NULL,
  location_id  INT NOT NULL,
  item_name    VARCHAR(100) NOT NULL,
  description  TEXT,
  date_lost    DATE NOT NULL,
  image        VARCHAR(255),                      -- file path of the uploaded picture
  status       ENUM('Lost','Found','Cancelled') NOT NULL DEFAULT 'Lost',
  date_posted  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES `User`(user_id)       ON DELETE RESTRICT,
  FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE RESTRICT,
  FOREIGN KEY (location_id) REFERENCES Location(location_id) ON DELETE RESTRICT
);

-- ---------- FOUND_ITEM ----------
CREATE TABLE Found_Item (
  found_item_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  category_id   INT NOT NULL,
  location_id   INT NOT NULL,
  item_name     VARCHAR(100) NOT NULL,
  description   TEXT,
  date_found    DATE NOT NULL,
  image         VARCHAR(255),
  status        ENUM('Available','Claimed','Returned') NOT NULL DEFAULT 'Available',
  date_posted   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES `User`(user_id)       ON DELETE RESTRICT,
  FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE RESTRICT,
  FOREIGN KEY (location_id) REFERENCES Location(location_id) ON DELETE RESTRICT
);

-- ---------- CLAIM ----------
-- ---------- CLAIM ----------
CREATE TABLE Claim (
  claim_id          INT AUTO_INCREMENT PRIMARY KEY,
  found_item_id     INT NOT NULL,
  user_id           INT NOT NULL,                 -- the claimant
  admin_id          INT NULL,                     -- NULL until an admin reviews it (ERD: zero or one)
  claim_description TEXT NOT NULL,                -- proof of ownership
  date_submitted    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  claim_status      ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  UNIQUE KEY uq_claim_user_item (found_item_id, user_id), -- Only claim once rule
  FOREIGN KEY (found_item_id) REFERENCES Found_Item(found_item_id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id)       REFERENCES `User`(user_id)           ON DELETE RESTRICT,
  FOREIGN KEY (admin_id)      REFERENCES Admin(admin_id)           ON DELETE SET NULL
);