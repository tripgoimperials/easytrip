-- EasyTrip expired-seat cleanup / upgrade
-- Import this after 00-10 when upgrading an existing database.
-- It releases seats that belonged only to expired pending bookings so the
-- UNIQUE(trip_id, seat_id) safety constraint can continue preventing double booking.
USE easytrip;

DELETE bs
FROM booking_seats bs
JOIN bookings bk ON bk.id = bs.booking_id
WHERE bk.status = 'expired'
   OR (bk.status = 'pending' AND bk.expires_at IS NOT NULL AND bk.expires_at <= NOW());

UPDATE bookings
SET status = 'expired'
WHERE status = 'pending'
  AND expires_at IS NOT NULL
  AND expires_at <= NOW();
