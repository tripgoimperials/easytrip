-- Booking class (needs 01_users.sql and 05_trips.sql first)
USE easytrip;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_ref VARCHAR(20) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  trip_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled','expired') DEFAULT 'pending',
  expires_at DATETIME DEFAULT NULL,          -- seat hold deadline while paying
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (trip_id) REFERENCES trips(id)
) ENGINE=InnoDB;
