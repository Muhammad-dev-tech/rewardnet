# PHASE 3 — BACKEND FOUNDATION

## Objective
Build the PHP backend foundation for RewardNet so the app can securely connect to MySQL, manage sessions, validate input, and support the business logic cleanly.

## What we are building
A backend foundation that handles configuration, database access, reusable services, and a consistent server-side execution model for RewardNet.

## Why it matters
The backend should be the authority for user actions, reward issuance, and data validation. If the business logic is embedded in UI code or insecurely mixed with SQL, the app becomes fragile and exploitable.

## Core concepts to understand

### PDO and prepared statements
PDO is the PHP interface for database access. It lets us connect to MySQL and safely send SQL with bound parameters instead of string concatenation.

This matters because a user should never be able to manipulate a query by typing special characters into a form field.

### password_hash and password_verify
PHP provides secure password hashing and verification functions.

- password_hash creates a salted hash
- password_verify compares a submitted password against the stored hash
- the system never stores plain text passwords

### sessions
Sessions allow the server to remember who a user is between requests.

For RewardNet:
- after successful login, the server stores the user ID or session token
- each protected page checks whether the session is valid
- the session is cleared on logout

### server-side validation
Browsers can be bypassed. The PHP backend must validate all input again before using it.

Examples:
- empty email
- invalid numeric reward amount
- duplicated account registration
- reward manipulation attempts

## Files in this phase
- app/config/config.php
- app/core/bootstrap.php
- app/core/Database.php
- app/services/ maybe if expanded
- public pages that call backend services

## Implementation summary
We already created the foundational project structure and a working MySQL connection layer.

## Database connection layer
The app uses a PDO-based `Database` class that loads configuration and establishes a single connection object.

This design keeps connection details in one place and makes all services use the same secure pattern.

## Session bootstrap
The app bootstrap initializes sessions and application constants early.

This gives us a predictable environment for login, authorization, and service access.

## Security principles used here
- do not trust browser-provided values
- use prepared statements
- validate IDs and numeric values
- reject invalid input before reaching business rules
- separate config from code

## Task list for Phase 3
Task 1 — Define backend responsibilities
Task 2 — Create configuration layer
Task 3 — Create database connection class
Task 4 — Create bootstrap session setup
Task 5 — Design service layer for business logic
Task 6 — Validate backend readiness

## Testing
- Verify PHP loads the configuration successfully
- Verify PDO can connect to MySQL
- Verify session initialization works
- Verify the app serves secure page actions without database failure

## What you should understand
This phase is about creating the safe operating environment for RewardNet. The frontend can be attractive, but without a trustworthy backend, the system is not reliable.

## Phase completion checklist
- [x] Project config exists
- [x] PHP bootstrap is in place
- [x] Database connection layer exists
- [x] Secure access patterns defined
- [x] Ready for authentication and authorization
