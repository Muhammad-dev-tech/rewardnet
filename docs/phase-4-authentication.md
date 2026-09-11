# PHASE 4 — AUTHENTICATION & AUTHORIZATION

## Objective
Implement secure registration, login, logout, and role-based access control for RewardNet.

## What we are building
The app must be able to register user accounts, verify credentials, establish authenticated sessions, and prevent ordinary users from reaching admin-only pages.

## Why it matters
Authentication proves identity. Authorization decides what the authenticated user may do. In RewardNet, this is critical because ad reward issuance, admin management, and user dashboards must be protected from abuse.

## Core concepts

### Authentication
Authentication answers: Who is this user?

Examples:
- user provides email and password
- PHP checks the stored password hash
- server creates a session if valid

### Authorization
Authorization answers: What is this user allowed to do?

Examples:
- admins can access admin pages
- regular users are redirected away from admin actions
- login is required before entering the dashboard

### Password hashing
Use PHP's `password_hash()` and `password_verify()` so passwords are not stored in plain text.

## Files involved
- app/core/AuthService.php
- public/login.php
- public/register.php
- public/dashboard.php
- public/logout.php
- public/admin/ (future admin area)

## Tasks
Task 1 — Implement registration validation
Task 2 — Hash passwords securely
Task 3 — Implement login flow with session creation
Task 4 — Implement logout and session cleanup
Task 5 — Protect dashboard routes
Task 6 — Add admin-only checks

## Implementation summary
The authentication layer is now built around a dedicated service with secure session creation and role checks.

## Testing
- successful user registration
- duplicate email rejection
- invalid credentials rejection
- successful login and redirect
- logout clears the session
- non-admin redirects away from admin routes

## What you should understand
A reward platform is only trustworthy when the server decides who is allowed to do what. Client-side checks alone are not enough.

## Phase completion checklist
- [x] Registration flow exists
- [x] Password hashing added
- [x] Login flow exists
- [x] Logout flow exists
- [x] Session-based protection added
- [x] Admin access control designed
- [x] Ready for user dashboard and reward flow
