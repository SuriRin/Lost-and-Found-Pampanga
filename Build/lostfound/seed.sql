-- =====================================
-- SQL CODE TO INSERT SAMPLE DATA 
-- =====================================

INSERT INTO Category (category_name) VALUES
('Wallets & Bags'),('Phones & Gadgets'),('Keys'),('IDs & Cards'),
('Clothing'),('Umbrellas'),('School Supplies'),('Jewelry');

INSERT INTO Location (place_name, municipality) VALUES
('SM City Clark','Mabalacat'),('Marquee Mall','Angeles City'),
('Nepo Mall','Angeles City'),('Holy Angel University','Angeles City'),
('SM City Pampanga','San Fernando'),('Robinsons Starmills','San Fernando'),
('Dau Bus Terminal','Mabalacat'),('Guagua Public Market','Guagua'),
('Lubao Town Plaza','Lubao'),('Apalit Church Plaza','Apalit');

INSERT INTO Claimant (full_name, email, contact_no) VALUES
('Juan Dela Cruz','juan.delacruz@example.com','09171234501'),
('Maria Santos','maria.santos@example.com','09171234502'),
('Jose Ramos','jose.ramos@example.com','09171234503'),
('Ana Reyes','ana.reyes@example.com','09171234504'),
('Pedro Garcia','pedro.garcia@example.com','09171234505'),
('Liza Mendoza','liza.mendoza@example.com','09171234506'),
('Carlo Bautista','carlo.bautista@example.com','09171234507'),
('Rina Aquino','rina.aquino@example.com','09171234508'),
('Mark Villanueva','mark.villanueva@example.com','09171234509'),
('Joy Navarro','joy.navarro@example.com','09171234510');

INSERT INTO Item (item_name, description, report_type, date_reported, status, category_id, location_id) VALUES
('Black leather wallet','Contains student ID, no cash','Lost','2026-09-05','Returned',1,1),
('Samsung phone, blue case','Cracked screen protector','Found','2026-09-14','Returned',2,2),
('House keys with red keychain','3 keys on a ring','Found','2026-09-28','Returned',3,4),
('Company ID card','Name partly faded','Found','2026-10-03','Open',4,5),
('Gray hoodie','Size M, HAU print','Lost','2026-10-11','Open',5,4),
('Black umbrella','Foldable, broken handle','Found','2026-10-19','Open',6,7),
('Scientific calculator','Casio fx-991','Lost','2026-10-27','Open',7,4),
('Silver necklace','Small cross pendant','Lost','2026-11-02','Open',8,3),
('Brown backpack','Laptop sleeve inside','Lost','2026-11-06','Open',1,6),
('iPhone with black case','Lock screen shows a dog','Found','2026-11-10','Open',2,5),
('Car key fob','Toyota fob','Found','2026-11-14','Open',3,8),
('Blue tote bag','Contains notebooks','Found','2026-11-17','Open',1,9);

INSERT INTO Claim (item_id, claimant_id, claim_date, claim_status, proof_description) VALUES
(1,1,'2026-09-07','Approved','Described wallet contents correctly'),
(2,2,'2026-09-15','Approved','Matched phone passcode'),
(3,3,'2026-09-30','Approved','Matched keychain and key count'),
(4,4,'2026-10-05','Pending','Says ID is hers'),
(4,5,'2026-10-08','Pending','Also claims the ID'),
(5,6,'2026-10-12','Rejected','Could not describe the print'),
(5,7,'2026-10-15','Pending','Knows the size'),
(6,8,'2026-10-21','Pending','Describes broken handle'),
(7,9,'2026-10-29','Rejected','Wrong model'),
(8,10,'2026-11-04','Pending','Describes pendant');