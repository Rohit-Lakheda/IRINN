# Debugging PayU Payment Errors

## Quick Start: View Logs Live

### Option 1: Use PowerShell Script (Easiest)
```powershell
.\view-logs.ps1
```

### Option 2: Manual PowerShell Command
```powershell
Get-Content storage\logs\laravel.log -Wait -Tail 50 | Select-String -Pattern "PayU"
```

### Option 3: View All Recent Errors
```powershell
Get-Content storage\logs\laravel.log -Tail 200 | Select-String -Pattern "ERROR|Exception|Failed"
```

---

## Common Error: "Pardon, Some Problem Occurred"

This error typically means PayU redirected successfully, but something failed on our server.

### Step 1: Check What PayU Sent

Look for this in logs:
```
[INFO] PayU Success Callback Received
```

This shows:
- All parameters PayU sent
- The URL they called
- Request method (GET or POST)

**What to check:**
- Is `txnid` present?
- Is `status` present?
- Is `hash` present?
- Are `udf1` and `udf2` present?

### Step 2: Check Hash Verification

Look for:
```
[INFO] PayU Hash Verification
```

**What to check:**
- `hash_match` should be `true`
- If `false`, the hash calculation is wrong
- Compare `calculated_hash` vs `received_hash`

### Step 3: Check Transaction Lookup

Look for:
```
[ERROR] Payment transaction not found
```

**Possible causes:**
- `udf2` (payment transaction ID) is missing or wrong
- Transaction ID doesn't match
- Transaction was deleted

### Step 4: Check View Rendering

Look for:
```
[ERROR] Error rendering payment confirmation view
```

**Possible causes:**
- Application relationship missing
- View file has errors
- Missing required data

---

## Debugging Checklist

### ✅ Before Testing Payment

1. **Clear old logs:**
   ```powershell
   Clear-Content storage\logs\laravel.log
   ```

2. **Start log viewer:**
   ```powershell
   .\view-logs.ps1
   ```

3. **Keep it running** while you test payment

### ✅ During Payment Test

1. **Initiate payment** from your application
2. **Watch the logs** - you should see:
   - `PayU Hash Debug` - hash generation
   - `PayU Payment Data Prepared` - payment data sent

3. **Complete payment** on PayU page
4. **Watch for redirect** - you should see:
   - `PayU Success Callback Received` - PayU redirected
   - `PayU Hash Verification` - hash check
   - `PayU Payment Success - Transaction Updated` - success
   - `Rendering payment confirmation view` - view render

### ✅ After Payment

1. **Check for errors** in logs (red text)
2. **Check transaction in database:**
   ```sql
   SELECT * FROM payment_transactions ORDER BY id DESC LIMIT 1;
   ```
3. **Check application status:**
   ```sql
   SELECT id, application_id, status FROM applications WHERE id = [application_id];
   ```

---

## Common Issues & Solutions

### Issue 1: Hash Verification Failed

**Symptoms:**
- Log shows `hash_match: false`
- Error: "Payment verification failed"

**Solution:**
1. Check salt and key in `.env`
2. Verify hash string format in logs
3. Ensure 5 pipes (`|||||`) after `udf5|`

### Issue 2: Transaction Not Found

**Symptoms:**
- Log shows "Payment transaction not found"
- Error: "Payment transaction not found"

**Solution:**
1. Check if `udf2` contains payment transaction ID
2. Verify transaction exists in database
3. Check if transaction_id matches

### Issue 3: Application Not Found

**Symptoms:**
- Log shows "has_application: false"
- View might show errors

**Solution:**
1. Check if `application_id` is set in payment_transaction
2. Verify application exists
3. View will handle null application gracefully now

### Issue 4: View Rendering Error

**Symptoms:**
- Error: "Error rendering payment confirmation view"
- Redirects to applications page

**Solution:**
1. Check full error trace in logs
2. Verify view file exists: `resources/views/user/applications/ix/payment-confirmation.blade.php`
3. Check if all required variables are passed

---

## Detailed Log Analysis

### What Each Log Entry Means

1. **`PayU Hash Debug`**
   - Shows hash string being generated
   - Verify format matches PayU's expected format

2. **`PayU Success Callback Received`**
   - PayU redirected to success URL
   - Shows all data PayU sent
   - **CRITICAL**: Check if all required fields present

3. **`PayU Hash Verification`**
   - Hash verification result
   - `hash_match: true` = good
   - `hash_match: false` = problem

4. **`PayU Payment Success - Transaction Updated`**
   - Transaction updated successfully
   - Payment status = success

5. **`Rendering payment confirmation view`**
   - About to show success page
   - Check `has_application` value

6. **`PayU S2S Webhook Received`**
   - Webhook from PayU server (most reliable)
   - Should arrive after browser redirect

---

## Quick Debug Commands

### View last payment attempt
```powershell
Get-Content storage\logs\laravel.log -Tail 500 | Select-String -Pattern "PayU|payment|Payment" -Context 3
```

### View only errors
```powershell
Get-Content storage\logs\laravel.log | Select-String -Pattern "ERROR|Exception" | Select-Object -Last 10
```

### View specific transaction
```powershell
Get-Content storage\logs\laravel.log | Select-String -Pattern "TXN17648673765126" -Context 5
```

### Export logs to file
```powershell
Get-Content storage\logs\laravel.log -Tail 1000 | Out-File payment-debug.log
```

---

## Still Having Issues?

1. **Check PayU Dashboard:**
   - Login to PayU test dashboard
   - Go to Transactions
   - Find your transaction
   - Check status and response

2. **Verify URLs:**
   - Success URL: `https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success`
   - Failure URL: `https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure`
   - Webhook URL: `https://interlinxpartnering.com/nixi/public/payu/webhook`

3. **Test Webhook Manually:**
   - Use Postman to POST to webhook URL
   - Send PayU response data
   - Check logs for processing

4. **Check Database:**
   - Verify payment_transactions table
   - Check if transaction was created
   - Verify application_id is set

---

## Next Steps After Fixing

Once payment works:
1. ✅ Test with different payment methods (Card, UPI)
2. ✅ Test failure scenarios
3. ✅ Verify webhook is received
4. ✅ Check PayU dashboard shows correct status
5. ✅ Test in production with small amount

