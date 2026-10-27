-- Seat class: the physical seats of each bus (needs 03_buses.sql first)
USE easytrip;

CREATE TABLE seats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bus_id INT NOT NULL,
  seat_number INT NOT NULL,
  UNIQUE KEY uq_bus_seat (bus_id, seat_number),
  FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seats 1..18 for every bus (72 rows)
INSERT INTO seats (bus_id, seat_number)
SELECT b.id, n.num
FROM buses b
JOIN (
  SELECT a.d + 10*c.d + 1 AS num
  FROM (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
  CROSS JOIN (SELECT 0 d UNION SELECT 1) c
) n ON n.num <= b.total_seats;
