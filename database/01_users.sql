-- Passenger class (admins use the same table with role = 'admin')
USE easytrip;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) DEFAULT NULL,
  google_id VARCHAR(100) DEFAULT NULL UNIQUE,
  role ENUM('passenger','admin') NOT NULL DEFAULT 'passenger',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin login: admin@easytrip.com / password  (change after first login)
INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('EasyTrip Admin', 'admin@easytrip.com', '08000000000',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
