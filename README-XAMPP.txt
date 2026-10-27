EASYTRIP - INTEGRATED XAMPP PROJECT (PERSONAL ACCOUNT + ADMIN MANAGEMENT)
======================================================================
NEW INSTALLATION
1. Copy/extract this folder to C:\xampp\htdocs\easytrip
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin
4. Import database SQL files 00_create_database.sql through 09_tickets.sql IN ORDER.
5. 10_admin_management_upgrade.sql is optional for a new installation (it only adds a useful index).
6. Open http://localhost/easytrip/

UPGRADING AN EXISTING EASYTRIP DATABASE
- Keep your existing database data.
- Replace the PHP/CSS/JS files with this version.
- Import database/10_admin_management_upgrade.sql once. If MySQL says the index already exists, it can be ignored.

PERSONAL ACCOUNT FLOW
- Registration writes full name, email, phone and a password hash to easytrip.users.
- Login authenticates against MySQL and stores the account ID in the PHP session.
- dashboard.php reloads the logged-in passenger's current details directly from MySQL.
- profile.php lets the passenger update name/email/phone in MySQL.
- Bookings, payments and tickets are tied to the logged-in user's database ID.
- Ticket and confirmation pages use the current database profile, not a hard-coded passenger name.

ADMIN ACCOUNT
Email: admin@easytrip.com
Password: password
Change this password for a real deployment.

ADMIN PASSENGER MANAGEMENT
Open Admin -> Passengers, or:
http://localhost/easytrip/admin-passengers.php
The admin can view, add, edit and delete passenger accounts. Deleting a passenger also removes that passenger's booking/payment/ticket history so foreign-key records are not left behind.

PHPMYADMIN
Open http://localhost/phpmyadmin and select easytrip.
Important tables:
- users: passenger/admin accounts
- bookings: bookings linked to user_id
- booking_seats: seats linked to bookings/trips
- payments: payment records
- tickets: generated ticket records

SECURITY / PROJECT NOTES
- Passwords are stored using PHP password_hash(), not as plain text.
- PDO prepared statements are used for database input.
- Admin account changes use CSRF tokens.
- Full card numbers and CVVs are never stored; card payment is a classroom/demo flow.
- Seat availability is revalidated in a transaction and protected by a unique trip/seat database constraint.

PAYMENT METHODS UPDATE
- payment.php now allows Card, USSD, and Bank Transfer.
- card.php handles demo card payment.
- ussd.php handles demo USSD payment.
- transfer.php handles demo bank transfer.
- All successful demo payments are stored in the payments table with the selected method.
- Full card number and CVV are never stored.

SEMESTER REQUIREMENTS
---------------------
See REQUIREMENTS-CHECKLIST.md, PROJECT-WRITEUP-OUTLINE.md and DEMO-GUIDE.txt.
The live seat page uses Fetch/AJAX with api/seats.php. Payment is simulated and now includes explicit successful and failed demonstrations.
