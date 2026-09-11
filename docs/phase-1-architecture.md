# PHASE 1 — PROJECT ARCHITECTURE & PLANNING

## Objective
Define the full RewardNet system structure before implementation so that the backend, database, and UI all follow a consistent design.

## What we are building
RewardNet is a loyalty-style web app in which users earn virtual data balance by completing simulated advertisements. Administrators manage users, ads, and reward distribution.

## Why it matters
If the system boundaries are not defined early, the app develops into disconnected pages with inconsistent data and weak security. This phase establishes the domain model and technical architecture before coding the actual logic.

## System actors

### User
Can:
- register
- log in and log out
- view dashboard
- view virtual data balance
- browse available advertisements
- complete an advertisement simulation
- receive data rewards
- view transaction history
- update profile details

### Administrator
Can:
- log in
- access admin dashboard
- manage users
- manage advertisements
- configure reward values
- review ad completion data
- view system-wide statistics
- monitor transactions

## Functional requirements

### Authentication and account management
- User registration with validation
- Email uniqueness enforcement
- Secure password hashing using PHP password APIs
- Session-based authentication
- Logout cleanup
- Role-based authorization

### User dashboard
- Current data balance
- Ad completion count
- Total data earned
- Recent activity feed
- Available advertisements

### Advertisement system
- Create, edit, enable, disable ads
- Store reward value and ad duration
- Track ad availability status
- Record viewing sessions

### Reward engine
- Complete advertisement session
- Verify completion conditions server-side
- Award virtual data
- Record reward transaction
- Prevent duplicate reward issuance

### Administration
- User list and search
- Ad management
- Reward transaction review
- Platform-level stats

## Non-functional requirements
- Secure backend validation
- Prepared statements for SQL
- Password hashing, not plain text
- Session security
- Role protections for admin flows
- Responsive UI for mobile and desktop
- Clear error handling and validation messages

## Architecture overview

Frontend (HTML, CSS, JS)
  -> HTTP requests
  -> PHP application
  -> MySQL database

Key layers:
- presentation layer: pages and UI components
- application layer: controllers/services
- data layer: PDO/MySQL access
- security layer: auth/session validation
- business layer: reward calculation and verification

## Directory structure

```text
RewardNet/
├─ app/
│  ├─ config/
│  ├─ core/
│  ├─ services/
│  └─ auth/
├─ public/
│  ├─ assets/
│  ├─ index.php
│  ├─ login.php
│  ├─ register.php
│  ├─ dashboard.php
│  ├─ admin/
│  └─ api/
├─ database/
│  └─ schema.sql
├─ docs/
│  └─ phase-1-architecture.md
├─ README.md
└─ .gitignore
```

## Database entities

### users
- id
- name
- email
- password_hash
- role
- data_balance
- status
- created_at
- updated_at

### advertisements
- id
- title
- description
- duration_seconds
- reward_amount
- status
- created_at
- updated_at

### ad_views
- id
- user_id
- advertisement_id
- started_at
- completed_at
- completion_status
- reward_amount

### reward_transactions
- id
- user_id
- ad_view_id
- amount
- transaction_type
- description
- created_at

## Relationship model
- users has many ad_views
- advertisements has many ad_views
- users has many reward_transactions
- ad_views has one reward_transaction (or zero if invalid)

## Why normalization matters
Normalization reduces duplication and protects integrity. Example:
- user data lives in one table
- ads live in one table
- completions are recorded in a separate table
- transactions are not recomputed from UI state; they are persisted as authoritative records

## Core workflows

### User registration workflow
1. Submit name, email, password
2. Validate inputs
3. Check duplicate email
4. Hash password
5. Insert user record
6. Create session
7. Redirect to dashboard

### Advertisement completion workflow
1. User opens an ad
2. Server records a viewing session
3. User completes the simulated ad
4. Server verifies ad is active and valid
5. Server checks the user has not already received the same reward
6. Reward calculation is processed
7. data_balance is updated
8. transaction is inserted
9. dashboard reflects new balance

## API requirements
We will likely expose simple HTTP endpoints for:
- login
- register
- get dashboard data
- get available advertisements
- complete ad reward
- admin stats

These should return JSON for interactive front-end actions.

## Task list for Phase 1
Task 1 — Define actors and roles
Task 2 — Define functional requirements
Task 3 — Define non-functional requirements
Task 4 — Define system architecture and directory structure
Task 5 — Define database entities and relationships
Task 6 — Define core workflows and reward lifecycle
Task 7 — Confirm requirements and prepare for Phase 2

## Implementation summary
This phase is complete at the planning level. We now have a consistent architectural model for RewardNet and a clear foundation for database design and implementation in Phase 2.

## Testing
- Verify that roles are clearly separated
- Verify that all core workflows are documented and consistent
- Verify that all tables align with the business process
- Verify that security rules are understood before implementation

## What you should understand
RewardNet is not just a points calculator. It is a reward system with explicit approval logic: a user may only receive reward after a valid ad completion is server-verified.

## Phase completion checklist
- [x] Project objective defined
- [x] Actors defined
- [x] Functional requirements defined
- [x] Architecture documented
- [x] Directory structure drafted
- [x] Core entities identified
- [x] Reward lifecycle mapped
- [x] Ready for Phase 2 database design
