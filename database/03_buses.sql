-- Bus class
USE easytrip;

CREATE TABLE buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bus_name VARCHAR(100) NOT NULL,
  plate_number VARCHAR(20) NOT NULL UNIQUE,
  bus_type ENUM('Standard','Executive','Luxury') DEFAULT 'Standard',
  total_seats INT NOT NULL DEFAULT 18,
  status ENUM('active','maintenance') DEFAULT 'active'
) ENGINE=InnoDB;

INSERT INTO buses (bus_name, plate_number, bus_type, total_seats) VALUES
('Sophia Express', 'IBD-101-AA', 'Executive', 18),
('Swift Transport', 'LAG-202-BB', 'Standard', 18),
('Skyline Tours', 'ABJ-303-CC', 'Luxury', 18),
('Sophia Express 2', 'BEN-404-DD', 'Standard', 18);
