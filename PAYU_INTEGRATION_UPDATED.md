# PayU Integration - Updated According to Official Documentation

## ✅ Updates Made

### 1. Hash Generation (Fixed)
**Updated to match official PayU documentation:**

- **Formula:** `sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)`
- **Change:** Now uses **6 pipes** (`||||||`) after `udf5|` before `SALT`
- **Location:** `app/Services/PayuService.php::generateHash()`

### 2. Response Hash Verification (Fixed)
**Updated to match official PayU documentation:**

- **Formula:** `sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)`
- **Change:** Now uses **6 pipes** (`||||||`) after `status|` before `udf5`
- **Location:** `app/Services/PayuService.php::verifyHash()`

### 3. Verify Payment API (Updated)
**Updated to match official PayU documentation:**

- **Test Endpoint:** `https://test.payu.in/merchant/postservice.php?form=2`
- **Production Endpoint:** `https://info.payu.in/merchant/postservice.php?form=2`
- **Hash Formula:** `sha512(key|command|var1|salt)`
- **Command:** `verify_payment`
- **Response Format:** JSON with structure:
  ```json
  {
    "status": 1,
    "msg": "1 out of 1 Transactions Fetched Successfully",
    "transaction_details": {
      "txnid": {
        "status": "success",
        "mihpayid": "...",
        "bank_ref_num": "...",
        "amt": "1000.00",
        ...
      }
    }
  }
  ```
- **Location:** `app/Services/PayuService.php::checkTransactionStatus()`

### 4. Payment URLs (Verified)
**Already correct in configuration:**

- **Test URL:** `https://test.payu.in/_payment` ✅
- **Production URL:** `https://secure.payu.in/_payment` ✅
- **Location:** `config/services.php`

### 5. Transaction Status Check (Enhanced)
**Updated to properly parse PayU Verify Payment API response:**

- Now correctly extracts transaction status from `transaction_details` object
- Handles both success and failure responses
- Maps PayU response fields to our database format
- **Location:** `app/Http/Controllers/IxApplicationController.php::paymentSuccess()`

---

## 📋 Mandatory Parameters

According to PayU documentation, these are **mandatory**:

1. `key` - Merchant key
2. `txnid` - Transaction ID
3. `amount` - Payment amount
4. `productinfo` - Product description
5. `firstname` - Customer first name
6. `email` - Customer email
7. `phone` - Customer phone number
8. `surl` - Success URL
9. `furl` - Failure URL
10. `hash` - Calculated hash

**All of these are already being sent in your code.** ✅

---

## 🔍 Hash Calculation Details

### Request Hash (Payment Initiation)
```
sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
```

**Important:**
- Use empty strings for missing `udf*` fields
- Exactly **6 pipes** (`||||||`) after `udf5|` before `SALT`
- All fields must be trimmed strings
- Hash result must be lowercase

### Response Hash (Callback Verification)
```
sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
```

**Important:**
- Exactly **6 pipes** (`||||||`) after `status|` before `udf5`
- Field order is **reversed** from request hash
- Hash comparison is case-sensitive

### Verify Payment API Hash
```
sha512(key|command|var1|salt)
```

**Where:**
- `command` = `verify_payment`
- `var1` = Transaction ID (`txnid`)

---

## 🧪 Testing

### Test Cards (PayU Test Environment)

**Successful Payment:**
- Card Number: `5123456789012346`
- Expiry: Any future date (e.g., `12/2030`)
- CVV: `123`
- Name: Any name
- OTP: `123456`

**Failed Payment:**
- Card Number: `5123456789012340` (Payment failed by user)

**UPI Test:**
- UPI ID: `anything@payu` or `9999999999@payu`

### Test Flow

1. **Initiate Payment:**
   - Check logs for `PayU Hash Debug` - verify hash string format
   - Check logs for `PayU Payment Data Prepared` - verify all parameters

2. **Complete Payment:**
   - Use test card details above
   - Complete payment on PayU page

3. **Verify Response:**
   - Check callback logs: `PayU Success Callback Method Called`
   - Check hash verification: `PayU Hash Verification`
   - Check transaction update: `PayU Payment Success - Transaction Updated`

4. **Verify Payment API (If Callback Fails):**
   - System automatically queries PayU API if callback has no parameters
   - Check logs: `PayU Status Check Response`
   - Transaction status updated from API response

---

## 📝 Response Handling

### Success Response (surl)
PayU POSTs to your success URL with:
```
mihpayid=403993715531077182
mode=CC
status=success
unmappedstatus=captured
key=JPM7Fg
txnid=TXN12345
amount=1000.00
productinfo=Pro Plan
firstname=Aditi
email=aditi@example.com
phone=9999999999
udf1=...
udf5=...
hash=<response_hash>
```

### Failure Response (furl)
Similar structure but with `status=failure` and error details.

### Webhook (S2S)
Most reliable method - PayU POSTs directly to your server:
- URL: `/payu/webhook`
- Always includes full transaction data
- Independent of browser redirects

---

## 🔧 Configuration

### Environment Variables (.env)
```env
PAYU_MERCHANT_ID=8847461
PAYU_MERCHANT_KEY=iaH0zp
PAYU_SALT=YSEB0ghJuWV69ZttwxW7fv1F9XXHEosC
PAYU_MODE=test
PAYU_TEST_URL=https://test.payu.in/_payment
PAYU_LIVE_URL=https://secure.payu.in/_payment
PAYU_SERVICE_PROVIDER=payu_paisa
```

### Switching to Production
1. Change `PAYU_MODE=live` in `.env`
2. Update merchant credentials (key, salt) for production
3. Test with small amount first
4. Monitor logs for any issues

---

## ✅ Verification Checklist

- [x] Hash generation uses 6 pipes (`||||||`) after `udf5|`
- [x] Response hash verification uses 6 pipes (`||||||`) after `status|`
- [x] Verify Payment API uses correct endpoint (`postservice.php?form=2`)
- [x] Verify Payment API response parsing handles JSON format
- [x] All mandatory parameters are included
- [x] URLs are absolute and accessible
- [x] Callback routes are outside authentication middleware
- [x] Webhook handler is implemented
- [x] Transaction status check handles empty callbacks

---

## 📚 References

- **PayU Hosted Checkout API Documentation**
- **PayU Hash Generation Guide**
- **PayU Verify Payment API Documentation**

---

**Last Updated:** Based on official PayU Hosted Checkout API documentation

