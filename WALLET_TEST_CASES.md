# PayU Merchant Wallet - Test Cases

This document provides comprehensive test cases for testing all wallet functionality step by step.

## Prerequisites

1. **User Account Setup**
   - User must be registered and logged in
   - User must have at least one application (IX or IRINN)
   - User should have PayU test credentials configured

2. **Environment Setup**
   - Application running in test mode (`PAYU_MODE=test`)
   - PayU test merchant credentials configured
   - Database migrations run (`php artisan migrate`)

---

## Test Case 1: Wallet Creation

### Objective
Test the wallet creation flow for a new user.

### Steps

1. **Login as User**
   - Navigate to login page
   - Login with valid credentials
   - Ensure user has at least one application

2. **Navigate to Wallet**
   - Go to `/user/wallet` or click "My Wallet" in navigation
   - Should redirect to wallet creation page if wallet doesn't exist

3. **Create Wallet**
   - Navigate to `/user/wallet/create`
   - Verify form shows:
     - Wallet Type dropdown (Closed-Loop selected by default)
     - Information message about wallet requirements
   - Click "Create Wallet" button

4. **Expected Results**
   - ✅ Wallet created successfully
   - ✅ Redirected to wallet dashboard (`/user/wallet`)
   - ✅ Wallet balance shows ₹0.00
   - ✅ Wallet status is "Active"
   - ✅ Creation transaction recorded in database
   - ✅ Success message displayed: "Wallet created successfully!"

5. **Database Verification**
   ```sql
   SELECT * FROM wallets WHERE user_id = [USER_ID];
   SELECT * FROM wallet_transactions WHERE wallet_id = [WALLET_ID] AND transaction_type = 'creation';
   ```

6. **Negative Test Cases**
   - ❌ Try creating wallet without any applications → Should show error
   - ❌ Try creating second wallet → Should redirect to existing wallet

---

## Test Case 2: Add Money to Wallet (Top-Up)

### Objective
Test adding money to wallet via PayU payment gateway.

### Steps

1. **Navigate to Add Money Page**
   - Login as user with active wallet
   - Go to `/user/wallet`
   - Click "Add Money" button

2. **Enter Amount**
   - Navigate to `/user/wallet/add-money`
   - Verify current balance is displayed
   - Enter amount (e.g., ₹1000)
   - Click "Proceed to Payment"

3. **PayU Payment Flow**
   - Should redirect to PayU payment gateway
   - Use PayU test credentials:
     - **Test Card**: 5123456789012346
     - **CVV**: 123
     - **Expiry**: Any future date (e.g., 12/25)
     - **Name**: Any name
   - Complete payment

4. **Payment Success Callback**
   - After successful payment, PayU redirects to success URL
   - Should redirect to wallet dashboard
   - Balance should be updated

5. **Expected Results**
   - ✅ Redirected to PayU payment gateway
   - ✅ Payment processed successfully
   - ✅ Redirected back to wallet dashboard
   - ✅ Wallet balance updated (₹0.00 → ₹1000.00)
   - ✅ Credit transaction recorded
   - ✅ Transaction shows in recent transactions
   - ✅ Success message: "Money added to wallet successfully!"

6. **Database Verification**
   ```sql
   SELECT balance FROM wallets WHERE id = [WALLET_ID];
   SELECT * FROM wallet_transactions 
   WHERE wallet_id = [WALLET_ID] 
   AND transaction_type = 'credit' 
   AND status = 'success'
   ORDER BY created_at DESC LIMIT 1;
   ```

7. **Transaction Details Check**
   - Verify `balance_before` = 0.00
   - Verify `balance_after` = 1000.00
   - Verify `amount` = 1000.00
   - Verify `payment_transaction_id` is linked

8. **Negative Test Cases**
   - ❌ Enter amount < ₹1 → Validation error
   - ❌ Enter amount > ₹1,00,000 → Validation error
   - ❌ Cancel payment → Should redirect to add-money page with error
   - ❌ Payment failure → Should show failure message

---

## Test Case 3: Wallet Dashboard - Balance Synchronization

### Objective
Test automatic balance synchronization when visiting wallet dashboard.

### Steps

1. **Initial State**
   - Ensure wallet has some balance (add money first)
   - Note current balance

2. **Visit Dashboard**
   - Navigate to `/user/wallet`
   - Page should load wallet dashboard

3. **Verify Balance Sync**
   - Check browser network tab for API calls
   - Balance should sync from PayU API
   - If balance differs, sync transaction should be recorded

4. **Expected Results**
   - ✅ Dashboard loads successfully
   - ✅ Balance displayed correctly
   - ✅ Recent transactions shown (last 10)
   - ✅ Wallet information displayed
   - ✅ Balance synced from PayU (if different)
   - ✅ Sync transaction recorded (if balance changed)

5. **Manual Sync Test**
   - Click "Refresh" button on dashboard
   - Should trigger manual balance sync
   - Balance should update if changed in PayU

6. **Database Verification**
   ```sql
   -- Check for sync transactions
   SELECT * FROM wallet_transactions 
   WHERE wallet_id = [WALLET_ID] 
   AND transaction_type = 'sync'
   ORDER BY created_at DESC;
   ```

---

## Test Case 4: Make Payment Using Wallet

### Objective
Test making application payment using wallet balance.

### Test Scenario A: Via Application Payment Flow

### Steps

1. **Prerequisites**
   - User has active wallet
   - Wallet balance ≥ application fee amount
   - User has a draft IX application

2. **Initiate Payment**
   - Go to application payment page
   - Select "Wallet Payment" option (if available in UI)
   - Or send POST request to `/user/applications/ix/initiate-payment` with:
     ```json
     {
       "payment_method": "wallet",
       ...other application data...
     }
     ```

3. **Payment Processing**
   - System checks wallet balance
   - If sufficient, debits wallet
   - Creates payment transaction
   - Updates application status

4. **Expected Results**
   - ✅ Payment processed successfully
   - ✅ Wallet balance debited
   - ✅ Debit transaction recorded
   - ✅ Application status updated to "submitted"
   - ✅ Payment transaction linked
   - ✅ Success message displayed

5. **Database Verification**
   ```sql
   -- Check wallet balance after payment
   SELECT balance FROM wallets WHERE id = [WALLET_ID];
   
   -- Check debit transaction
   SELECT * FROM wallet_transactions 
   WHERE wallet_id = [WALLET_ID] 
   AND transaction_type = 'debit'
   AND application_id = [APPLICATION_ID]
   ORDER BY created_at DESC LIMIT 1;
   
   -- Check payment transaction
   SELECT * FROM payment_transactions 
   WHERE id = [PAYMENT_TRANSACTION_ID];
   ```

### Test Scenario B: Via Wallet Payment Endpoint

### Steps

1. **Navigate to Application**
   - Go to application details page
   - Note application ID and amount

2. **Make Wallet Payment**
   - Send POST request to `/user/wallet/payment`:
     ```json
     {
       "application_id": [APPLICATION_ID],
       "amount": [AMOUNT]
     }
     ```

3. **Expected Results**
   - ✅ Same as Test Scenario A

4. **Negative Test Cases**
   - ❌ Insufficient balance → Error: "Insufficient wallet balance"
   - ❌ Wallet not active → Error: "Your wallet is not active"
   - ❌ Invalid application → Error: "Invalid application"
   - ❌ Application doesn't belong to user → Error

---

## Test Case 5: Transaction History

### Objective
Test viewing and syncing wallet transaction history.

### Steps

1. **Navigate to Transactions**
   - Go to `/user/wallet/transactions`
   - Or click "View All" from wallet dashboard

2. **Verify Transaction List**
   - Should show all wallet transactions
   - Paginated (20 per page)
   - Sorted by latest first

3. **Transaction Details**
   - Verify each transaction shows:
     - Date & Time
     - Transaction Type (Credit/Debit/Refund/Creation/Sync)
     - Transaction ID
     - Description
     - Amount (with +/- indicator)
     - Balance Before
     - Balance After
     - Status

4. **Transaction Sync**
   - Transactions should sync from PayU
   - Missing transactions should be added
   - Sync transactions marked with "Sync" badge

5. **Expected Results**
   - ✅ All transactions displayed
   - ✅ Transactions synced from PayU
   - ✅ Pagination works correctly
   - ✅ Transaction details accurate
   - ✅ Balance summary cards show correct totals

6. **Database Verification**
   ```sql
   -- Check all transactions
   SELECT COUNT(*) FROM wallet_transactions WHERE wallet_id = [WALLET_ID];
   
   -- Check synced transactions
   SELECT COUNT(*) FROM wallet_transactions 
   WHERE wallet_id = [WALLET_ID] 
   AND sync_source = true;
   ```

---

## Test Case 6: Balance API Endpoint

### Objective
Test the balance API endpoint for real-time balance checks.

### Steps

1. **API Call**
   - Send GET request to `/user/wallet/balance`
   - Include session authentication

2. **Expected Response**
   ```json
   {
     "success": true,
     "balance": 1000.00,
     "currency": "INR",
     "status": "active"
   }
   ```

3. **Verify Behavior**
   - ✅ Balance synced from PayU before returning
   - ✅ Returns current wallet balance
   - ✅ Includes wallet status

4. **Negative Test Cases**
   - ❌ Without authentication → 401 Unauthorized
   - ❌ No wallet → 404 Not Found

---

## Test Case 7: Wallet Payment Integration in Application Flow

### Objective
Test wallet payment option in IX application submission.

### Steps

1. **Create Application**
   - Fill IX application form
   - Complete all required fields
   - Submit application

2. **Payment Selection**
   - On payment page, verify wallet option available
   - Check wallet balance is displayed
   - Select "Pay with Wallet" option

3. **Payment Processing**
   - Submit payment with `payment_method: "wallet"`
   - System should:
     - Check wallet balance
     - Debit wallet if sufficient
     - Process payment

4. **Expected Results**
   - ✅ Payment processed via wallet
   - ✅ Application status updated
   - ✅ Wallet balance debited
   - ✅ Transaction recorded
   - ✅ Success message shown

5. **Compare with PayU Gateway**
   - Try same flow with `payment_method: "payu"`
   - Should redirect to PayU gateway
   - Both methods should work correctly

---

## Test Case 8: Error Scenarios

### Objective
Test error handling and edge cases.

### Test Cases

1. **Insufficient Balance**
   - Try to pay amount > wallet balance
   - Expected: Error message, payment not processed

2. **Wallet Not Active**
   - Suspend wallet in database
   - Try to make payment
   - Expected: Error: "Your wallet is not active"

3. **No Wallet**
   - User without wallet tries to pay
   - Expected: Redirect to create wallet page

4. **Payment Failure**
   - Simulate PayU payment failure
   - Expected: Error message, transaction marked as failed

5. **Network Issues**
   - Simulate PayU API timeout
   - Expected: Error logged, user-friendly message

6. **Concurrent Transactions**
   - Try multiple payments simultaneously
   - Expected: Proper locking, no double debit

---

## Test Case 9: Webhook Handling

### Objective
Test PayU wallet webhook notifications.

### Steps

1. **Simulate Webhook**
   - Send POST request to `/payu/wallet/webhook`:
     ```json
     {
       "transaction_id": "TXN123456",
       "wallet_id": "[WALLET_ID]",
       "status": "success",
       "amount": "100.00",
       "transaction_type": "credit",
       "balance_before": "0.00",
       "balance_after": "100.00",
       "hash": "[CALCULATED_HASH]"
     }
     ```

2. **Expected Results**
   - ✅ Webhook processed successfully
   - ✅ Transaction created/updated
   - ✅ Wallet balance synced
   - ✅ Returns success response

3. **Verify Database**
   ```sql
   SELECT * FROM wallet_transactions 
   WHERE transaction_id = 'TXN123456';
   ```

---

## Test Case 10: Transaction Recording Verification

### Objective
Verify all wallet operations record transactions correctly.

### Test Matrix

| Operation | Transaction Type | Balance Before | Balance After | Status |
|-----------|-----------------|----------------|---------------|--------|
| Wallet Creation | creation | 0.00 | 0.00 | success |
| Add Money | credit | X | X + Amount | success/failed |
| Make Payment | debit | X | X - Amount | success/failed |
| Balance Sync (if changed) | sync | Local | PayU | success |
| Refund | refund | X | X + Amount | success |

### Verification Steps

1. **After Each Operation**
   - Check `wallet_transactions` table
   - Verify transaction type is correct
   - Verify balance snapshots are accurate
   - Verify status is correct
   - Verify linked records (payment_transaction_id, application_id)

2. **Transaction Chain**
   - Create wallet → 1 transaction
   - Add ₹1000 → 1 credit transaction
   - Pay ₹500 → 1 debit transaction
   - Total: 3 transactions
   - Final balance: ₹500

---

## Test Case 11: UI/UX Testing

### Objective
Test user interface and user experience.

### Checklist

1. **Wallet Dashboard**
   - ✅ Balance displayed prominently
   - ✅ Recent transactions visible
   - ✅ Quick actions available (Add Money, Refresh)
   - ✅ Wallet status indicator
   - ✅ Responsive design

2. **Add Money Page**
   - ✅ Current balance shown
   - ✅ Amount input with validation
   - ✅ Clear instructions
   - ✅ Error messages displayed

3. **Transaction History**
   - ✅ All transactions listed
   - ✅ Proper formatting (dates, amounts)
   - ✅ Color coding (green for credit, red for debit)
   - ✅ Pagination works
   - ✅ Filters/search (if implemented)

4. **Navigation**
   - ✅ Wallet accessible from main menu
   - ✅ Breadcrumbs work correctly
   - ✅ Back buttons functional

---

## Test Case 12: Performance Testing

### Objective
Test system performance under load.

### Tests

1. **Balance Sync Performance**
   - Measure time to sync balance
   - Should complete in < 2 seconds

2. **Transaction History Load**
   - Test with 100+ transactions
   - Should load in < 3 seconds
   - Pagination should work smoothly

3. **Concurrent Requests**
   - Multiple balance checks simultaneously
   - Should handle gracefully

---

## Test Data Setup

### SQL Scripts for Testing

```sql
-- Create test wallet
INSERT INTO wallets (user_id, wallet_id, wallet_type, status, balance, currency, created_at, updated_at)
VALUES ([USER_ID], 'TEST_WALLET_001', 'closed_loop', 'active', 0.00, 'INR', NOW(), NOW());

-- Add test transaction
INSERT INTO wallet_transactions (wallet_id, user_id, transaction_type, transaction_id, amount, balance_before, balance_after, status, created_at, updated_at)
VALUES ([WALLET_ID], [USER_ID], 'credit', 'TEST_TXN_001', 1000.00, 0.00, 1000.00, 'success', NOW(), NOW());
```

---

## Test Execution Checklist

- [ ] Test Case 1: Wallet Creation
- [ ] Test Case 2: Add Money to Wallet
- [ ] Test Case 3: Balance Synchronization
- [ ] Test Case 4: Make Payment Using Wallet
- [ ] Test Case 5: Transaction History
- [ ] Test Case 6: Balance API Endpoint
- [ ] Test Case 7: Application Payment Integration
- [ ] Test Case 8: Error Scenarios
- [ ] Test Case 9: Webhook Handling
- [ ] Test Case 10: Transaction Recording
- [ ] Test Case 11: UI/UX Testing
- [ ] Test Case 12: Performance Testing

---

## Notes

1. **PayU Test Credentials**
   - Use PayU test environment for all tests
   - Test cards available in PayU documentation
   - Test transactions don't process real money

2. **Database State**
   - Reset database between major test scenarios
   - Use transactions for test isolation

3. **Logging**
   - Check `storage/logs/laravel.log` for detailed logs
   - All wallet operations are logged

4. **API Endpoints**
   - Wallet endpoints require authentication
   - Use session-based authentication for web tests
   - Use API tokens for API tests

---

## Troubleshooting

### Common Issues

1. **Wallet not creating**
   - Check user has applications
   - Check PayU API credentials
   - Check logs for errors

2. **Balance not syncing**
   - Verify PayU API connectivity
   - Check wallet_id is set
   - Verify API endpoints in config

3. **Transactions not recording**
   - Check database constraints
   - Verify transaction recording logic
   - Check for exceptions in logs

4. **Payment failing**
   - Verify wallet balance
   - Check wallet status
   - Verify PayU integration

---

## Success Criteria

All test cases should pass with:
- ✅ No errors in application logs
- ✅ All transactions recorded correctly
- ✅ Balance calculations accurate
- ✅ User experience smooth
- ✅ Error handling appropriate
- ✅ Performance acceptable



