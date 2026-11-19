-- =====================================
-- LOST AND FOUND HUB: SAMPLE DATA
-- Run AFTER schema.sql
--
-- Test logins (passwords are stored hashed; names, emails, phone numbers and
-- passwords are all made up for testing. Do not send real emails to them.)
--   kristinejoy.manalo@gmail.com       kj_manalo0412
--   jerome.pineda22@gmail.com          Jerome_22bball
--   angelicamae.tolentino@gmail.com    Angel#Tolentino03
--   cmdizon_98@gmail.com               Dizon.Carlo1998
--   bea.lacson21@gmail.com             bealacs_21!
--   rafmercado@gmail.com               Rafa#Mercado77
--   nicolepangilinan05@gmail.com       Nicole0508pang
--   jp.dungca@gmail.com                jpdungca_2001
--   maricelgonzales84@gmail.com        Maricel_84Gonz!
--   haroldjames.bautista@gmail.com     Harold#0321
--   teresita.quiambao@gmail.com        Tess#Quiambao72   (admin)
--   ronaldo.pangan@gmail.com           Ronald_Pangan88!   (admin)
-- =====================================

USE lostfound_pamp;

INSERT INTO `User` (full_name, email, password, contact_number, date_registered) VALUES
('Kristine Joy Manalo',     'kristinejoy.manalo@gmail.com',    '$2y$10$NdQSA2bwSTS/LZzIUZzw3OBzemaiTp/ww1/2MHNOBv7d2ZpYmDeF2', '09178342916', '2025-11-14 09:12:00'),
('Jerome Pineda',           'jerome.pineda22@gmail.com',       '$2y$10$M382CVrVNsmu0acfN5yoAue..hSmSs0KwQNpuLuiRS9qsHkCS0pMO', '09289174053', '2025-12-03 18:40:00'),
('Angelica Mae Tolentino',  'angelicamae.tolentino@gmail.com', '$2y$10$s2Z73AhNnYbhjmAYZ0mzo.k.7.rkp3o/DRTBPNm6/Xpb2l.grVJVW', '09353618240', '2026-01-19 11:25:00'),
('Carlo Miguel Dizon',      'cmdizon_98@gmail.com',            '$2y$10$AY0G.DjVIcSPf19sAZJUo.MonG7qiWYx7JAvMmrccrlox2uH8neru', '09451729386', '2026-02-27 20:05:00'),
('Bea Patricia Lacson',     'bea.lacson21@gmail.com',          '$2y$10$DHDvxFyb7TgZ.5lDDbSQuOe6VdlNfQcDAaMjYkZ2yukzQO0k0Vbhu', '09568203471', '2026-04-08 14:30:00'),
('Rafael Santiago Mercado', 'rafmercado@gmail.com',            '$2y$10$LY2uj8tqZ0Np6dET2OiJ/eXCDllOZnYZ1Bpp.UQDiXBB6V/Te4ama', '09665038129', '2026-05-16 08:55:00'),
('Nicole Anne Pangilinan',  'nicolepangilinan05@gmail.com',    '$2y$10$Yr6445rATqS8TTjPbsn3Ieelo.FU75qprnCYiPD.F5Tx9MuaLedQO', '09774915602', '2026-06-24 16:15:00'),
('John Paul Dungca',        'jp.dungca@gmail.com',             '$2y$10$gQlul4ScvSBJJoclKPFrTeeZHomZ1KbCMC04GbcW4EirTNb5Sgf/m', '09984427310', '2026-07-30 10:45:00'),
('Maricel Gonzales',        'maricelgonzales84@gmail.com',     '$2y$10$FQFx3tWNk8EmPJDt4.f3ZeHM4TBcAI2C4sOWPjFYviPOSFUozK3Tq', '09192650874', '2026-09-05 13:20:00'),
('Harold James Bautista',   'haroldjames.bautista@gmail.com',  '$2y$10$PUAv/kilOUZcnQ4aGvYb/u.eWu6qa.xbkUZYKw2K.J8uRjJslSUBu', '09056742198', '2026-10-21 19:10:00');

-- ---------- ADMIN (2) ----------
INSERT INTO Admin (full_name, email, password) VALUES
('Teresita Quiambao', 'teresita.quiambao@gmail.com', '$2y$10$CQnj2IBr1C0WURprUz.N1eCZPcJ0IsWtCa7b6p1xcvTfUAzL3URFO'),
('Ronaldo Pangan',    'ronaldo.pangan@gmail.com',    '$2y$10$6qRzwFxEl5DdvtKfsHu5/.200XI.vDMd4kQJwJwzboAjpLhOD0uzm');

-- ---------- CATEGORY (8) ----------
INSERT INTO Category (category_name, category_description) VALUES
('Wallets & Bags',        'Wallets, purses, backpacks, tote bags and similar carriers'),
('Phones & Gadgets',      'Mobile phones, tablets, earphones, chargers and other electronics'),
('Keys',                  'House keys, car keys and key fobs'),
('IDs & Documents',       'Identification cards, licenses, certificates and other papers'),
('Clothing',              'Jackets, hoodies, shirts, caps and other wearable items'),
('Umbrellas',             'Umbrellas and rain gear'),
('School Supplies',       'Calculators, notebooks, books and other school items'),
('Jewelry & Accessories', 'Necklaces, rings, watches and other accessories');

-- ---------- LOCATION (10) ----------
INSERT INTO Location (location_name, building, description) VALUES
('SM City Clark',                          'Main Mall',          'Mall in Mabalacat, Pampanga'),
('SM City Pampanga',                       'Main Mall',          'Mall in San Fernando, Pampanga'),
('Marquee Mall',                           'Main Mall',          'Mall in Angeles City, Pampanga'),
('Robinsons Starmills',                    'Main Mall',          'Mall in San Fernando, Pampanga'),
('Clark International Airport',            'Passenger Terminal', 'Airport in Clark Freeport Zone, Mabalacat, Pampanga'),
('Dau Bus Terminal',                       'Terminal Building',  'Bus terminal in Mabalacat, Pampanga'),
('Holy Angel University',                  'Main Campus',        'University in Angeles City, Pampanga'),
('Angeles University Foundation',          'Main Campus',        'University in Angeles City, Pampanga'),
('Nepo Mall Angeles',                      'Main Mall',          'Mall in Angeles City, Pampanga'),
('Metropolitan Cathedral of San Fernando', 'Church Grounds',     'Cathedral in San Fernando, Pampanga');

-- ---------- LOST_ITEM (6) ----------
-- columns: user_id, category_id, location_id, item_name, description, date_lost, image, status, date_posted
INSERT INTO Lost_Item (user_id, category_id, location_id, item_name, description, date_lost, image, status, date_posted) VALUES
(1,  1, 1, 'Black leather wallet',  'Contains student ID, no cash', '2026-02-10', NULL, 'Found',     '2026-02-10 18:00:00'),
(6,  5, 7, 'Gray hoodie',           'Size M, HAU print',            '2026-07-11', NULL, 'Found',     '2026-07-11 17:30:00'),
(9,  7, 8, 'Scientific calculator', 'Casio fx-991',                 '2026-09-27', NULL, 'Lost',      '2026-09-27 12:00:00'),
(10, 8, 9, 'Silver necklace',       'Small cross pendant',          '2026-11-02', NULL, 'Lost',      '2026-11-02 20:15:00'),
(2,  1, 4, 'Brown backpack',        'Laptop sleeve inside',         '2026-10-06', NULL, 'Lost',      '2026-10-06 19:00:00'),
(3,  6, 6, 'Red umbrella',          'Compact, red with white dots', '2026-05-20', NULL, 'Cancelled', '2026-05-20 08:30:00');

-- ---------- FOUND_ITEM (9) ----------
-- columns: user_id, category_id, location_id, item_name, description, date_found, image, status, date_posted
INSERT INTO Found_Item (user_id, category_id, location_id, item_name, description, date_found, image, status, date_posted) VALUES
(3, 1, 1,  'Black leather wallet',         'Has a student ID inside, no cash', '2026-02-11', NULL, 'Returned',  '2026-02-11 10:00:00'),
(5, 2, 3,  'Samsung phone, blue case',     'Cracked screen protector',         '2026-04-22', NULL, 'Returned',  '2026-04-22 15:00:00'),
(4, 3, 7,  'House keys with red keychain', '3 keys on a ring',                 '2026-03-18', NULL, 'Returned',  '2026-03-18 13:20:00'),
(7, 4, 2,  'Company ID card',              'Name partly faded',                '2026-08-03', NULL, 'Available', '2026-08-03 11:00:00'),
(8, 6, 6,  'Black umbrella',               'Foldable, broken handle',          '2026-09-12', NULL, 'Available', '2026-09-12 16:45:00'),
(2, 2, 2,  'iPhone with black case',       'Lock screen shows a dog',          '2026-11-10', NULL, 'Available', '2026-11-10 14:10:00'),
(4, 3, 5,  'Car key fob',                  'Toyota fob',                       '2026-11-14', NULL, 'Available', '2026-11-14 09:30:00'),
(6, 1, 10, 'Blue tote bag',                'Contains notebooks',               '2026-11-17', NULL, 'Available', '2026-11-17 17:00:00'),
(7, 5, 7,  'Gray hoodie',                  'Size M with a print on the front', '2026-07-12', NULL, 'Claimed',   '2026-07-12 12:30:00');

-- ---------- CLAIM (9) ----------
-- columns: found_item_id, user_id (claimant), admin_id (NULL = not yet reviewed),
--          claim_description, date_submitted, claim_status
INSERT INTO Claim (found_item_id, user_id, admin_id, claim_description, date_submitted, claim_status) VALUES
(1, 1,  1,    'Described the wallet contents correctly', '2026-02-12 09:00:00', 'Approved'),
(2, 2,  1,    'Matched the phone passcode',              '2026-04-23 10:30:00', 'Approved'),
(3, 3,  2,    'Matched the keychain and number of keys', '2026-03-20 14:00:00', 'Approved'),
(4, 4,  NULL, 'Says the ID is hers',                     '2026-08-05 09:15:00', 'Pending'),
(4, 5,  NULL, 'Also claims the ID',                      '2026-08-09 11:45:00', 'Pending'),
(9, 5,  1,    'Knows the size only',                     '2026-07-13 18:00:00', 'Rejected'),
(9, 6,  1,    'Described the HAU print and the size',    '2026-07-14 08:20:00', 'Approved'),
(5, 9,  NULL, 'Describes the broken handle',             '2026-09-14 16:00:00', 'Pending'),
(8, 10, NULL, 'Says the notebooks inside are hers',      '2026-11-18 10:10:00', 'Pending');