CREATE DATABASE IF NOT EXISTS tripgo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
USE tipgo


CREATE TABLE cities(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE key uq_cities_name(name)
)

CREATE table users(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT
    full_name NOT NULL
    emailphone NOT NULL
    password_hash NOT NULL
    role ENUM('passenger','admin' ) NOT NULL DEFAULT 'passenger'
    created_at DATETIME not null DEFAULT CURRENT_TIMESTAMP
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE = InnoDB;

CREATE TABLE routes(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT
    origin_city_id INT UNSIGNED NOT NULL
    destination_city_id INT UNSIGNED NOT NULL
    PRIMARY KEY (id),
    UNIQUE KEY uq_routes_pair (origin_city_id,destination_city_id)
    CONSTRAINT fk_routes_origin
         FOREIGN KEY (origin_city_id) REFERENCES cities (id)
         ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_routes_destination
         FOREIGN KEY (origin_city_id) REFERENCES cities (id)
         ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_routes_different CHECK (origin_city_id <> destination_city_id)
         
) = ENGINE = InnoDB;

CREATE TABLE buses(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    plate_number VARCHAR(50) NOT NULL,
    bus_type VARCHAR(50) NOT NULL,
    total_rows TINYINT UNSIGNED NOT NULL,
    seats_per_row TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE uq_buses_plate(plate_number),
    CONSTRAINT chk_buses_row CHECK (total_rows > 0),
    CONSTRAINT chk_buses_per_row CHECK (seats_per_row > 0),

) ENGINE = InnoDB;


CREATE TABLE seats(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bus_id INT UNSIGNED NOT NULL,
    seat_number VARCHAR(50) NOT NULL,
    CONSTRAINT fk_seats_bus
         FOREIGN KEY (bus_id)  REFERENCES buses (id)
         ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;


CREATE TABLE trips(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    route_id INT UNSIGNED NOT NULL,
    bus_id INT UNSIGNED NOT NULL,
    departure_time DATETIME NOT NULL,
    fare INT UNSIGNED NOT NULL,
    status ENUM('scheduled','departed','cancelled') NOT NULL DEFAULT 'scheduled'
    PRIMARY KEY (id),
    UNIQUE KEY uq_trips_bus_time (bus_id,departure_time)
    KEY idx_trips_search (route_id,departure_time)
    CONSTRAINT fk_trips_route
         FOREIGN KEY (route_id) REFERENCES route (id)
         ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trips_bus
         FOREIGN KEY (bus_id) REFERENCES buses (id)
         ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_trips_fare CHECK (fare > 0)
         
) = ENGINE = InnoDB;

CREATE TABLE trips(
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    route_id INT UNSIGNED NOT NULL,
    bus_id INT UNSIGNED NOT NULL,
    departure_time DATETIME NOT NULL,
    fare INT UNSIGNED NOT NULL,
    status ENUM('scheduled','departed','cancelled') NOT NULL DEFAULT 'scheduled'
    PRIMARY KEY (id),
    UNIQUE KEY uq_trips_bus_time (bus_id,departure_time)
    KEY idx_trips_search (route_id,departure_time)
    CONSTRAINT fk_trips_route
         FOREIGN KEY (route_id) REFERENCES route (id)
         ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trips_bus
         FOREIGN KEY (bus_id) REFERENCES buses (id)
         ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_trips_fare CHECK (fare > 0)
         
) = ENGINE = InnoDB;


