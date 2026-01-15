# Helper: Digital Wallet & Money Transfer API - Implementation Walkthrough

## 1. Database Schema
We have established a robust database schema using UUIDs, strict typing, and foreign key constraints.
- **Users**: Extended with `role` (Admin/User) and Soft Deletes.
- **Wallets**: Multi-currency support (TRY, USD, EUR) with `decimal(19,4)` precision for financial accuracy. Active/Blocked status tracking.
- **Transactions**: Double-entry bookkeeping style tracking (Source -> Target). 
  - Supports `Deposit`, `Withdraw`, `Transfer`, `Refund`.
  - Includes `fee`, `status`, and `metadata` handling.
- **Suspicious Activities**: Dedicated table for logging fraud detection events with risk scoring.

## 2. Architecture & Design Patterns
We implemented a modern, scalable Layered Architecture:
- **Repository Pattern**: Abstracted database access (`BaseRepository`, `TransactionRepository`, etc.) ensuring separation of concerns.
- **Service Layer**: Business logic encapsulation (`TransactionService`, `AuthService`, `WalletService`).
- **Pipeline Pattern**: Used in `TransactionService` to chain checks like `CheckInsufficientBalance` -> `CheckDailyLimit` -> `CalculateFee` -> `FraudCheck` before executing the transaction.
- **Strategy Pattern**: Used for Fee Calculation (`Low`, `Medium`, `High` amount strategies).
- **DTOs**: `TransactionDTO` used to carry data safely between Controller and Service layers.
- **Enums**: Extensive use of PHP 8.1+ Enums (`UserRole`, `TransactionStatus`, `WalletCurrency`) for type safety.

## 3. Key Features
### Authentication & Security
- **Sanctum**: Token-based authentication for API access.
- **RBAC**: `CheckAdmin` middleware prevents unauthorized access to admin endpoints.
- **Validation**: Strict FormRequests (`DepositRequest`, `TransferRequest`) ensure data integrity.

### Money Transfer Logic
- **ACID Transactions**: All financial operations are wrapped in `DB::transaction()` to prevent partial updates.
- **Optimistic Locking**: Handled via database constraints and transaction encapsulation.
- **Fee Calculation**: Dynamic fee rules based on transaction amount.
- **Fraud Detection**:
  - **Velocity Check**: Detects frequent transfers to different users.
  - **Night Limit**: Flags large transactions between 02:00 - 06:00.
  - **Account Age**: Flags high value transactions for new accounts.
  - **IP Mismatch**: Detects multiple accounts sharing the same IP.

## 4. API Endpoints (V1)
- **Auth**: `/register`, `/login`, `/me`, `/logout`
- **Wallets**: `/wallets` (List), `/wallets/{id}` (Detail)
- **Transactions**:
  - `POST /transactions/deposit`
  - `POST /transactions/withdraw`
  - `POST /transactions/transfer`
- **Admin**:
  - `/admin/users`
  - `/admin/transactions`
  - `/admin/suspicious-activities`
  - `POST /admin/suspicious-activities/{id}/resolve`

## 5. Testing & Verification
- **Seeders**: `DatabaseSeeder` creates an Admin user and 5 Demo users with funded wallets.
- **Docker**: Redis configured for caching and potential queue handling (used in event listeners).
- **Postman**: A complete [Postman Collection](Derslig_Digital_Wallet_API.postman_collection.json) is provided for testing all endpoints. Import it into Postman to get started.

## 6. Next Steps
- Implement Unit and Feature tests.
- Setup CI/CD pipeline.
- Implement strictly async processing for non-critical helpers (e.g. notifications).
