-- A trip = one bus on one route at a set date, time and price
-- (needs 02_routes.sql and 03_buses.sql first)
USE easytrip;

CREATE TABLE trips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  route_id INT NOT NULL,
  bus_id INT NOT NULL,
  departure_time DATETIME NOT NULL,
  arrival_time DATETIME DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  status ENUM('scheduled','departed','completed','cancelled') DEFAULT 'scheduled',
  INDEX idx_search (route_id, departure_time),
  FOREIGN KEY (route_id) REFERENCES routes(id),
  FOREIGN KEY (bus_id) REFERENCES buses(id)
) ENGINE=InnoDB;

-- Sample trips for the next 3 days (dates are relative to the day you import)
INSERT INTO trips (route_id, bus_id, departure_time, arrival_time, price)
SELECT 1, 1, TIMESTAMP(CURDATE() + INTERVAL d DAY, '07:00:00'), TIMESTAMP(CURDATE() + INTERVAL d DAY, '09:30:00'), 6500
FROM (SELECT 1 d UNION SELECT 2 UNION SELECT 3) days
UNION ALL
SELECT 1, 2, TIMESTAMP(CURDATE() + INTERVAL d DAY, '10:00:00'), TIMESTAMP(CURDATE() + INTERVAL d DAY, '12:30:00'), 5000
FROM (SELECT 1 d UNION SELECT 2 UNION SELECT 3) days
UNION ALL
SELECT 2, 3, TIMESTAMP(CURDATE() + INTERVAL d DAY, '06:00:00'), TIMESTAMP(CURDATE() + INTERVAL d DAY, '15:00:00'), 28000
FROM (SELECT 1 d UNION SELECT 2 UNION SELECT 3) days
UNION ALL
SELECT 3, 4, TIMESTAMP(CURDATE() + INTERVAL d DAY, '08:00:00'), TIMESTAMP(CURDATE() + INTERVAL d DAY, '13:30:00'), 12000
FROM (SELECT 1 d UNION SELECT 2 UNION SELECT 3) days;
