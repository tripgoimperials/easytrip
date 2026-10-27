-- Payment class (needs 06_bookings.sql first)
-- Never store full card numbers or CVV: keep only the last 4 digits and the gateway reference.
USE easytrip;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  method ENUM('card','bank_transfer','ussd') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  gateway_reference VARCHAR(100) DEFAULT NULL,
  card_last4 CHAR(4) DEFAULT NULL,
  status ENUM('pending','success','failed') DEFAULT 'pending',
  paid_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
) ENGINE=InnoDB;
