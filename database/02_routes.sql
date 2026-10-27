-- Route class
USE easytrip;

CREATE TABLE routes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  origin VARCHAR(80) NOT NULL,
  destination VARCHAR(80) NOT NULL,
  distance_km INT DEFAULT NULL,
  duration_minutes INT DEFAULT NULL,
  UNIQUE KEY uq_route (origin, destination)
) ENGINE=InnoDB;

INSERT INTO routes (origin, destination, distance_km, duration_minutes) VALUES
('Ibadan', 'Lagos', 128, 150),
('Ibadan', 'Abuja', 640, 540),
('Lagos', 'Benin', 320, 330);
