# PayU Payment Integration - Testing Checklist

## ✅ Implementation Status

### 1. Pre-Payment Validation
- ✅ **API Credentials**: Configured in `config/services.php`
  - Test Merchant ID: `8847461`
  - Key: `iaH0zp`
  - Salt: `YSEB0ghJuWV69ZttwxW7fv1F9XXHEosC`
- ✅ **Hash Calculation**: Fixed and verified
  - Formula: `sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|||||SALT)`
  - Hash string is logged in `storage/logs/laravel.log` (look for "PayU Hash Debug")
- ✅ **Mandatory Parameters**: Validated in `PayuService::preparePaymentData()`
- ✅ **Error Handling**: Comprehensive logging and error messages

### 2. Payment Callbacks (surl/furl)
- ✅ **Success URL**: `/user/applications/ix/payment-success`
- ✅ **Failure URL**: `/user/applications/ix/payment-failure`
- ✅ **Hash Verification**: Implemented in both callbacks
- ✅ **Transaction Update**: Updates payment status and application

### 3. Server-to-Server (S2S) Webhook ⭐ **NEW**
- ✅ **Webhook Route**: `/payu/webhook` (POST only, no auth required)
- ✅ **Webhook Handler**: `IxApplicationController::handleWebhook()`
- ✅ **Hash Verification**: Verifies PayU webhook authenticity
- ✅ **Transaction Update**: Updates payment status based on webhook (source of truth)
- ✅ **Application Status**: Automatically updates application when payment succeeds

---

## 📋 Testing Steps

### Step 2.1: Pre-Payment Validation

#### Verify API Credentials
```bash
# Check your .env file has:
PAYU_MERCHANT_ID=8847461
PAYU_MERCHANT_KEY=iaH0zp
PAYU_SALT=YSEB0ghJuWV69ZttwxW7fv1F9XXHEosC
PAYU_MODE=test
PAYU_TEST_URL=https://test.payu.in/_payment
```

#### Validate Hash Calculation
1. Initiate a test payment
2. Check `storage/logs/laravel.log` for:
   - `"PayU Hash Debug"` - Shows the hash string being generated
   - Verify the hash string format matches PayU's expected format
3. If you see "Checksum failed" error:
   - Check the hash string in logs
   - Verify salt and key are correct
   - Ensure no empty/null values in mandatory parameters

---

### Step 2.2: Simulate a Successful Transaction

#### Initiate Payment
1. Go to IX Application creation page
2. Fill in application details
3. Click "Pay Now" or "Initiate Payment"
4. You should be redirected to PayU payment page

#### Verify Payment Page
- ✅ Transaction amount displayed correctly
- ✅ Product details ("NIXI IX Application Fee") shown
- ✅ Payment methods visible (Credit/Debit Card, UPI, Net Banking)

#### Test Card Transaction
1. Select **Credit Card** payment method
2. Use test card:
   - **Card Number**: `5123456789012346`
   - **Expiry Date**: Any future date (e.g., `12/2030`)
   - **CVV**: `123`
   - **Name**: Test Name
3. Click **Pay Now**
4. On 3D Secure page, enter OTP: `123456`
5. Click **Submit**

**Expected Result:**
- Redirected to success page (`/user/applications/ix/payment-success`)
- Payment transaction status = `success`
- Application status = `submitted`
- Check logs for:
  - `"PayU Success Callback Received"`
  - `"PayU S2S Webhook Received"` (most reliable)
  - `"PayU Payment Success - Transaction Updated"`

#### Test UPI Transaction
1. Select **UPI** payment method
2. Enter test UPI ID: `anything@payu` or `9999999999@payu`
3. Click **Verify** then **Pay Now**

**Expected Result:**
- Same as card transaction above

---

### Step 2.3: Simulate a Failed Transaction

#### Test Failing Card Transaction
1. Initiate a new payment
2. Select **Credit Card** payment method
3. Use failing test card:
   - **Card Number**: `5123456789012340` (Payment failed by user)
4. Complete payment flow

**Expected Result:**
- Redirected to failure page (`/user/applications/ix/payment-failure`)
- Payment transaction status = `failed`
- Application status remains unchanged
- Check logs for:
  - `"PayU Failure Callback Received"`
  - `"PayU S2S Webhook Received"` with status = `failure`

---

### Step 2.4: Post-Transaction Verification

#### Check Return URLs (surl/furl)

**Success URL Verification:**
1. After successful payment, verify:
   - User is redirected to success page
   - Success message displayed
   - Payment details shown correctly
   - Application status updated to `submitted`

**Failure URL Verification:**
1. After failed payment, verify:
   - User is redirected to failure page
   - Clear error message displayed
   - Option to retry payment available

#### Verify Server-to-Server (S2S) Webhook ⭐ **CRITICAL**

**This is the most reliable way to confirm transaction status!**

1. **Check Server Logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "PayU S2S Webhook"
   ```

2. **Verify Webhook Received:**
   - Look for: `"PayU S2S Webhook Received"` in logs
   - Should contain full PayU response data
   - Should have IP address and user agent

3. **Verify Hash Validation:**
   - Look for: `"PayU Hash Verification"` in logs
   - `hash_match` should be `true`

4. **Verify Transaction Update:**
   - Look for: `"PayU S2S Webhook Processed Successfully"` in logs
   - Payment transaction should be updated with webhook data
   - `payu_response` JSON should contain `webhook_received_at` timestamp

5. **Webhook URL Configuration:**
   - **Webhook URL**: `https://interlinxpartnering.com/nixi/public/payu/webhook`
   - Configure this in PayU Dashboard:
     - Go to PayU Dashboard → Settings → Webhooks
     - Add webhook URL: `https://interlinxpartnering.com/nixi/public/payu/webhook`
     - Select events: `Transaction Success`, `Transaction Failure`

**Important Notes:**
- ⚠️ **Always use S2S webhook as source of truth**, not browser redirect
- ⚠️ **Update transaction status based on webhook**, not surl/furl
- ⚠️ **Webhook may arrive before or after browser redirect**
- ⚠️ **Webhook is more reliable** - doesn't depend on user's browser

#### Cross-Verify in PayU Dashboard

1. Log in to PayU Test Dashboard
2. Navigate to **Transactions** section
3. Verify:
   - ✅ Successful transactions show status = `success`
   - ✅ Failed transactions show status = `failure`
   - ✅ Transaction IDs match your database
   - ✅ Amounts match
   - ✅ Webhook delivery status (if available)

---

## 🔍 Debugging Tips

### Check Logs
```bash
# View all PayU related logs
tail -f storage/logs/laravel.log | grep -i payu

# View hash generation
tail -f storage/logs/laravel.log | grep "PayU Hash Debug"

# View webhook calls
tail -f storage/logs/laravel.log | grep "PayU S2S Webhook"
```

### Common Issues

1. **Hash Mismatch:**
   - Check hash string in logs
   - Verify salt and key are correct
   - Ensure 5 pipes (`|||||`) after `udf5|`

2. **Webhook Not Received:**
   - Verify webhook URL is accessible (not behind firewall)
   - Check PayU dashboard webhook configuration
   - Verify webhook URL uses HTTPS in production

3. **Transaction Not Found:**
   - Check `udf2` contains payment transaction ID
   - Verify transaction exists in database
   - Check logs for transaction lookup

---

## 📝 Configuration Checklist

### .env File
```env
APP_URL=https://interlinxpartnering.com/nixi/public
PAYU_MERCHANT_ID=8847461
PAYU_MERCHANT_KEY=iaH0zp
PAYU_SALT=YSEB0ghJuWV69ZttwxW7fv1F9XXHEosC
PAYU_MODE=test
PAYU_TEST_URL=https://test.payu.in/_payment
PAYU_SERVICE_PROVIDER=payu_paisa
```

### PayU Dashboard Configuration

**Success URL:**
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success
```

**Failure URL:**
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure
```

**Webhook URL:** ⭐ **NEW - IMPORTANT**
```
https://interlinxpartnering.com/nixi/public/payu/webhook
```

---

## ✅ Final Checklist Before Going Live

- [ ] All test transactions successful
- [ ] Hash calculation verified
- [ ] Success callback working
- [ ] Failure callback working
- [ ] **S2S Webhook receiving and processing correctly** ⭐
- [ ] Transaction status updates correctly
- [ ] Application status updates correctly
- [ ] Logs show all expected entries
- [ ] PayU Dashboard shows correct transaction status
- [ ] Webhook URL configured in PayU Dashboard
- [ ] Switch to production mode:
  - Update `PAYU_MODE=production` in .env
  - Update `PAYU_MERCHANT_ID` to production ID
  - Update `PAYU_MERCHANT_KEY` to production key
  - Update `PAYU_SALT` to production salt
  - Update URLs to production domain

---

## 🚀 Production Switch

When ready for production:

1. Update `.env`:
   ```env
   PAYU_MODE=production
   PAYU_MERCHANT_ID=13230879  # Your production merchant ID
   PAYU_MERCHANT_KEY=<production_key>
   PAYU_SALT=<production_salt>
   ```

2. Update PayU Dashboard:
   - Configure production webhook URL
   - Verify production success/failure URLs

3. Test with small amount first!

---

**Last Updated:** Based on PayU testing documentation and current implementation

