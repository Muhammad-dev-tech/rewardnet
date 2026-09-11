# PHASE 2 — DATABASE DESIGN

## Objective
Design the MySQL schema for RewardNet and connect the database model to the business rules of reward-based access simulation.

## What we are building
The data model must store users, advertisements, view sessions, and reward transactions in a normalized, relational way.

## Why it matters
Database structure is the foundation for every reward calculation and security check. A good schema prevents invalid reward issuance, duplicate crediting, and inconsistent balances.

## Key concept: normalization
Normalization means storing each fact once in the correct table rather than duplicating data across many places.

For RewardNet:
- user profile data belongs in users
- ad details belong in advertisements
- a viewing session belongs in ad_views
- reward awards belong in reward_transactions

This reduces duplication and makes data validation easier.

## Entities and relationship overview

### users
Represents each RewardNet account.

Fields:
- id (PK)
- name
- email
- password_hash
- role
- data_balance
- status
- created_at
- updated_at

Why it exists:
- stores authentication data
- stores the user’s current virtual balance
- defines account permissions

### advertisements
Represents each simulated ad offer.

Fields:
- id (PK)
- title
- description
- media_url or simulator_reference
- duration_seconds
- reward_amount
- status
- created_at
- updated_at

Why it exists:
- defines what the user can watch
- stores ad duration and reward amount
- controls whether the ad is active

### ad_views
Tracks each advertisement viewing session.

Fields:
- id (PK)
- user_id (FK -> users.id)
- advertisement_id (FK -> advertisements.id)
- started_at
- completed_at
- completion_status
- reward_amount

Why it exists:
- records when a user watched an ad
- proves whether the session was completed
- acts as the evidence the reward engine should verify against

### reward_transactions
Stores the actual point/data grant.

Fields:
- id (PK)
- user_id (FK -> users.id)
- ad_view_id (FK -> ad_views.id)
- amount
- transaction_type
- description
- created_at

Why it exists:
- gives an auditable history of credit issuance
- keeps reward activity separate from user profile state
- allows admins to review who earned what and when

## Example ERD

```text
users
  ├── 1 : many ── ad_views
  ├── 1 : many ── reward_transactions

advertisements
  └── 1 : many ── ad_views

ad_views
  └── 1 : one ── reward_transactions
```

## Primary keys and foreign keys
- PK ensures each row is unique
- FK ensures referential integrity
- Example: ad_views.user_id must point to an existing user
- Example: reward_transactions.ad_view_id must point to a valid ad view

## Data integrity rules
- A user cannot have a reward without a valid ad view
- An inactive advertisement should not be rewarded
- Duplicate reward attempts should be prevented by checking the ad_view status and transaction history
- The database should reject impossible values

## RewardNet schema (recommended SQL)

```sql
CREATE DATABASE IF NOT EXISTS rewardnet;
USE rewardnet;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    data_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE advertisements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT,
    media_reference VARCHAR(255) DEFAULT NULL,
    duration_seconds INT NOT NULL,
    reward_amount DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE ad_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    advertisement_id INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    completion_status ENUM('incomplete', 'completed', 'rejected') NOT NULL DEFAULT 'incomplete',
    reward_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_ad_views_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ad_views_ad FOREIGN KEY (advertisement_id) REFERENCES advertisements(id)
        ON DELETE CASCADE
);

CREATE TABLE reward_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ad_view_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('credit', 'debit', 'adjustment') NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reward_transactions_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reward_transactions_ad_view FOREIGN KEY (ad_view_id) REFERENCES ad_views(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_ad_views_user ON ad_views(user_id, completion_status);
CREATE INDEX idx_ad_views_ad ON ad_views(advertisement_id);
CREATE INDEX idx_transactions_user ON reward_transactions(user_id, created_at);
```

## Why this schema matches the concept
This schema reflects the real business flow:
- a user starts a view session
- the ad is eventually completed
- the reward engine validates the completion
- a transaction is recorded
- the user balance is updated

## Task list for Phase 2
Task 1 — Explain normalization and relational design
Task 2 — Define entity relationships and ERD
Task 3 — Select core database tables
Task 4 — Implement schema in MySQL
Task 5 — Test connectivity and table creation

## Implementation
The schema is defined and created in the project database folder and can be executed to set up the MySQL environment for RewardNet.

## Testing
- Connect to MySQL successfully
- Confirm all tables exist
- Confirm foreign keys are valid
- Confirm indexes exist
- Validate inserts and reads with sample data (next phase)

## What you should understand
The database is not just for storage; it is the authority for the RewardNet business model. The reward engine must trust the database state rather than browser-side logic.

## Phase completion checklist
- [x] Database concept explained
- [x] Entities mapped
- [x] Relationships defined
- [x] Schema designed
- [x] Foreign keys and keys documented
- [x] Ready for Phase 3 backend foundation
