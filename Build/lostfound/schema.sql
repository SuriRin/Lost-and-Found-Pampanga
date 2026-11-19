-- =====================================
-- SQL CODE TO CREATE ITEM TABLES
-- =====================================

DROP TABLE IF EXISTS Claim;
DROP TABLE IF EXISTS Claimant;
DROP TABLE IF EXISTS Item;
DROP TABLE IF EXISTS Location;
DROP TABLE IF EXISTS Category;

CREATE TABLE Category (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE Location (
  location_id INT AUTO_INCREMENT PRIMARY KEY,
  place_name VARCHAR(100) NOT NULL,
  municipality VARCHAR(50) NOT NULL
);

CREATE TABLE Item (
  item_id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(100) NOT NULL,
  description TEXT,
  report_type ENUM('Lost','Found') NOT NULL,
  date_reported DATE NOT NULL,
  status ENUM('Open','Returned') NOT NULL DEFAULT 'Open',
  category_id INT NOT NULL,
  location_id INT NOT NULL,
  FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE RESTRICT,
  FOREIGN KEY (location_id) REFERENCES Location(location_id) ON DELETE RESTRICT
);

CREATE TABLE Claimant (
  claimant_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contact_no VARCHAR(20)
);

CREATE TABLE Claim (
  claim_id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  claimant_id INT NOT NULL,
  claim_date DATE NOT NULL,
  claim_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  proof_description TEXT,
  FOREIGN KEY (item_id) REFERENCES Item(item_id) ON DELETE RESTRICT,
  FOREIGN KEY (claimant_id) REFERENCES Claimant(claimant_id) ON DELETE RESTRICT
);