

CREATE DATABASE IF NOT EXISTS group8
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE group8;


DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS booking_seats;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS seats;
DROP TABLE IF EXISTS buses;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS cities;

CREATE TABLE cities (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cities_name (name)
) ENGINE=InnoDB;

-- 2. users: passengers and admins (role decides which)
CREATE TABLE users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    phone          CHAR(11)     NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('passenger','admin') NOT NULL DEFAULT 'passenger',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT chk_users_phone CHECK (phone REGEXP '^0[0-9]{10}$')
) ENGINE=InnoDB;

-- 3. routes: a pair of cities (Ibadan -> Lagos)
CREATE TABLE routes (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    origin_city_id       INT UNSIGNED NOT NULL,
    destination_city_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_routes_pair (origin_city_id, destination_city_id),
    -- No ON UPDATE CASCADE here: MySQL/MariaDB do not allow a CHECK on columns that
    -- have a cascading foreign key, and surrogate ids never change anyway.
    CONSTRAINT fk_routes_origin
        FOREIGN KEY (origin_city_id) REFERENCES cities (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_routes_destination
        FOREIGN KEY (destination_city_id) REFERENCES cities (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_routes_different CHECK (origin_city_id <> destination_city_id)
) ENGINE=InnoDB;

-- 4. buses: physical vehicles. Capacity is NOT stored: it is total_rows x seats_per_row
--    (column is total_rows, not rows, because ROWS is a reserved word in MySQL 8;
--     the API still calls it "rows")
CREATE TABLE buses (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    plate_number   VARCHAR(20)  NOT NULL,
    bus_type       VARCHAR(50)  NOT NULL,
    total_rows     TINYINT UNSIGNED NOT NULL,
    seats_per_row  TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_buses_plate (plate_number),
    CONSTRAINT chk_buses_rows CHECK (total_rows > 0),
    CONSTRAINT chk_buses_per_row CHECK (seats_per_row > 0)
) ENGINE=InnoDB;

-- 5. seats: the physical seats of each bus (A1, A2, ...)
CREATE TABLE seats (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bus_id       INT UNSIGNED NOT NULL,
    seat_number  VARCHAR(5)   NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_seats_bus_number (bus_id, seat_number),
    CONSTRAINT fk_seats_bus
        FOREIGN KEY (bus_id) REFERENCES buses (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. trips: a bus running a route at a date and time with a fare (whole Naira)
CREATE TABLE trips (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    route_id        INT UNSIGNED NOT NULL,
    bus_id          INT UNSIGNED NOT NULL,
    departure_time  DATETIME     NOT NULL,
    fare            INT UNSIGNED NOT NULL,
    status          ENUM('scheduled','departed','cancelled') NOT NULL DEFAULT 'scheduled',
    PRIMARY KEY (id),
    UNIQUE KEY uq_trips_bus_time (bus_id, departure_time),
    KEY idx_trips_search (route_id, departure_time),
    CONSTRAINT fk_trips_route
        FOREIGN KEY (route_id) REFERENCES routes (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trips_bus
        FOREIGN KEY (bus_id) REFERENCES buses (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_trips_fare CHECK (fare > 0)
) ENGINE=InnoDB;

-- 7. bookings: one passenger's claim on seats of ONE trip
--    fare_per_seat and total_amount are price snapshots (the trip fare may change later)
CREATE TABLE bookings (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference      VARCHAR(30)  NOT NULL,
    user_id        INT UNSIGNED NOT NULL,
    trip_id        INT UNSIGNED NOT NULL,
    status         ENUM('pending','confirmed','cancelled','expired') NOT NULL DEFAULT 'pending',
    fare_per_seat  INT UNSIGNED NOT NULL,
    total_amount   INT UNSIGNED NOT NULL,
    expires_at     DATETIME     NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bookings_reference (reference),
    UNIQUE KEY uq_bookings_id_trip (id, trip_id),      -- needed by the composite FK in booking_seats
    KEY idx_bookings_status_expiry (status, expires_at),
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_trip
        FOREIGN KEY (trip_id) REFERENCES trips (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_bookings_amounts CHECK (fare_per_seat > 0 AND total_amount > 0)
) ENGINE=InnoDB;

-- 8. booking_seats: resolves the many-to-many between bookings and seats.
--    trip_id is deliberately repeated here (a controlled denormalization) so that
--    UNIQUE (trip_id, seat_id) makes double booking impossible at database level.
--    The composite FK (booking_id, trip_id) guarantees trip_id always matches the booking's trip.
--    When a booking is cancelled or expires, DELETE its rows here to free the seats.
CREATE TABLE booking_seats (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id  INT UNSIGNED NOT NULL,
    trip_id     INT UNSIGNED NOT NULL,
    seat_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_seats_trip_seat (trip_id, seat_id),   -- the anti-double-booking rule
    UNIQUE KEY uq_booking_seats_booking_seat (booking_id, seat_id),
    CONSTRAINT fk_booking_seats_booking
        FOREIGN KEY (booking_id, trip_id) REFERENCES bookings (id, trip_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_booking_seats_seat
        FOREIGN KEY (seat_id) REFERENCES seats (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 9. payments: every payment attempt is recorded (a booking can have several)
CREATE TABLE payments (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id         INT UNSIGNED NOT NULL,
    amount             INT UNSIGNED NOT NULL,
    method             ENUM('card','bank_transfer','ussd') NOT NULL,
    status             ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    provider_reference VARCHAR(50)  NOT NULL,
    paid_at            DATETIME     NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_provider_ref (provider_reference),
    KEY idx_payments_booking (booking_id),
    CONSTRAINT fk_payments_booking
        FOREIGN KEY (booking_id) REFERENCES bookings (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_payments_amount CHECK (amount > 0)
) ENGINE=InnoDB;
