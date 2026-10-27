-- Seats reserved per booking (needs 04_seats.sql and 06_bookings.sql first)
-- UNIQUE (trip_id, seat_id) stops two people booking the same seat on a trip.
USE easytrip;

CREATE TABLE booking_seats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  trip_id INT NOT NULL,
  seat_id INT NOT NULL,
  passenger_name VARCHAR(100) DEFAULT NULL,
  UNIQUE KEY uq_trip_seat (trip_id, seat_id),
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (trip_id) REFERENCES trips(id),
  FOREIGN KEY (seat_id) REFERENCES seats(id)
) ENGINE=InnoDB;
