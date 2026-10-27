# EasyTrip - Online Transportation Booking and Payment System

## Introduction
EasyTrip is a web-based transportation booking system designed to allow passengers to search available intercity trips, select seats, make simulated payments and obtain electronic tickets. The system also provides passenger account management and an administrative view of passengers, bookings, buses and revenue information.

## Problem Statement
Manual transport booking can make it difficult for passengers to know bus availability before arriving at a terminal. It can also lead to seat allocation conflicts when booking records are not updated quickly. EasyTrip addresses these problems by storing booking information in a central MySQL database and refreshing seat availability asynchronously while still performing server-side validation before a booking is accepted.

## Objectives
The objectives are to provide passenger registration and login; route/date search; bus and seat availability; secure server-side seat allocation; fare calculation; simulated payment; booking confirmation; e-ticket generation; booking history; personal profile data; and an administrator interface for monitoring and managing passenger records.

## System Requirements
Software: XAMPP (Apache and MySQL), PHP with PDO MySQL, a modern web browser, and phpMyAdmin for database administration. Technologies: HTML, CSS, JavaScript, PHP and MySQL.

## Use-Case Diagram (content to draw)
Actors: Passenger and Administrator.
Passenger use cases: Register, Login, Search Trip, View Bus, Select Seat, Make Simulated Payment, View Confirmation, View Ticket, View Booking History, Update Profile, Logout.
Administrator use cases: Login, View Dashboard, View Passenger Accounts, Add Passenger, Edit Passenger, Delete Passenger, View Booking Statistics.

## System Architecture
The presentation layer consists of HTML/CSS pages rendered by PHP. JavaScript handles browser interaction and Fetch/AJAX. PHP contains the application and OOP domain logic. PDO connects PHP to MySQL. `api/seats.php` is the JSON API used by the seat-selection page.

Browser → PHP Pages / JavaScript → PHP OOP Classes → PDO → MySQL

Seat page → Fetch/AJAX → `api/seats.php` → `Seat`/`Booking` classes → MySQL

## Database / ER Diagram (entities to draw)
Entities: users, routes, buses, seats, trips, bookings, booking_seats, payments and tickets.
Important relationships: User 1:M Booking; Route 1:M Trip; Bus 1:M Trip; Bus 1:M Seat; Trip 1:M Booking; Booking M:M Seat through booking_seats; Booking 1:M Payment attempts; Booking 1:1 Ticket after confirmation.

## Database Tables
`users` stores passenger/admin account information. `routes` stores origins and destinations. `buses` stores bus information. `seats` stores bus seat numbers. `trips` connects a route and bus to departure/arrival time and price. `bookings` stores booking reference, user, trip, total and status. `booking_seats` stores selected seats and prevents duplicate trip-seat combinations. `payments` stores simulated payment attempts. `tickets` stores the generated ticket code for a confirmed booking.

## Important Interface Screenshots
Capture: Home/Search page, Search Results, Bus Details, Seat Selection showing live availability, Payment Method page, one payment simulation page, Booking Confirmation, E-Ticket, User Dashboard, Booking History, Profile, Admin Dashboard and Manage Passengers.

## PHP OOP Implementation
`Database` creates the shared PDO connection. `Passenger` handles account operations. `Route` retrieves routes. `Bus` searches trips and returns trip details. `Seat` checks seat availability. `Booking` creates and retrieves bookings and protects seats using transactions and database validation. `Payment` records successful or failed simulated payment attempts and generates a ticket after successful payment.

## API / AJAX Communication
The seat-selection page sends a Fetch request to `api/seats.php` approximately every four seconds. The API returns JSON containing taken seat IDs. JavaScript updates the seat buttons without refreshing the whole page. This improves the user experience, but it is not treated as the final security check. When the passenger submits a booking, PHP validates the seats again inside a MySQL transaction.

## Payment Workflow
After a booking is created, seats are held temporarily. The passenger selects Card, USSD or Bank Transfer. These are demonstrations only and no real financial transaction is made. A successful simulation records a successful payment, confirms the booking and generates a ticket. A failed simulation records a failed payment and leaves the booking pending so the passenger can try again before the hold expires. Full card numbers and CVV are not stored.

## Testing
Success tests: create account, login, search trip, select available seat, complete simulated payment, view confirmation/ticket, retrieve booking from history. Failure tests: duplicate registration, wrong password, invalid card input, explicit failed payment simulation, invalid API trip, and concurrent attempt to reserve an already-held seat.

## Conclusion
EasyTrip demonstrates a complete PHP/MySQL transportation booking workflow. It combines database-backed accounts and bookings, PHP OOP, a JSON API, asynchronous seat updates, simulated payments, e-ticket generation and server-side double-booking protection.
