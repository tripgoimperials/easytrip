-- E-ticket (needs 06_bookings.sql first)
USE easytrip;

CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL UNIQUE,
  ticket_code VARCHAR(40) NOT NULL UNIQUE,   -- value encoded in the barcode
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;
