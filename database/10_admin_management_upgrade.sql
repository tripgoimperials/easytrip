-- EasyTrip account-management upgrade
-- Import this ONLY when upgrading an older EasyTrip database.
-- New installations do not need structural changes because the existing users table already stores account details.
USE easytrip;
-- Helpful index for admin passenger listings and login/account lookups.
CREATE INDEX idx_users_role_created ON users(role, created_at);
