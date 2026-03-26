# PayU URL Configuration Guide

## Important: PayU URL Configuration Method

**PayU does NOT require URLs to be configured in the dashboard.** Instead, URLs are sent in each payment request via the `surl` and `furl` parameters, which your code is already doing correctly.

However, some PayU accounts may have optional URL settings in the dashboard. If you don't see these options, **that's fine** - your code is already sending the URLs correctly.

## Your Code is Already Sending URLs

Your payment code in `IxApplicationController.php` is already sending:
- `surl` (Success URL) in the payment request
- `furl` (Failure URL) in the payment request

These are sent to PayU with each transaction, so dashboard configuration is **not required**.

## Optional: Check PayU Dashboard (If Available)

If you want to check if there are any URL settings (optional):

### Step 1: Login to PayU Dashboard

1. Go to: https://payu.in/business (your dashboard)
   - Or Test Dashboard: https://testdashboard.payu.in/
   - Or Production Dashboard: https://dashboard.payu.in/

2. Login with your merchant credentials

### Step 2: Look for URL Settings (May Not Exist)

Some PayU dashboards have these options (but many don't):
- **Settings** → **Payment Settings** → **Return URLs**
- **Integration** → **Callback URLs**
- **Merchant** → **Settings** → **Default URLs**

**If you don't see these options, that's normal and fine!**

### Step 3: Your URLs (For Reference)

These are the URLs your code sends with each payment:

**Success URL:**
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success
```

**Failure URL:**
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure
```

**Webhook URL (S2S):**
```
https://interlinxpartnering.com/nixi/public/payu/webhook
```

---

## Alternative: If URLs are Set Per Transaction

Some PayU configurations allow you to set URLs in the payment request itself (which you're already doing in code). However, it's still recommended to set them in the dashboard as a fallback.

---

## Verification Steps

### 1. Test URL Accessibility

Test if your URLs are accessible:

**Success URL:**
```bash
curl -I https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success
```

**Failure URL:**
```bash
curl -I https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure
```

**Webhook URL:**
```bash
curl -I https://interlinxpartnering.com/nixi/public/payu/webhook
```

All should return `200 OK` or `302 Redirect` (if redirecting to login).

### 2. Check PayU Dashboard

After saving, verify the URLs are saved correctly:
- Go back to Settings
- Confirm the URLs are displayed correctly
- Check for any error messages

### 3. Test Payment Flow

1. Make a test payment
2. Check logs at `/admin/view-logs`
3. Look for:
   - `=== PayU Success Callback Method Called ===`
   - `=== PayU Failure Callback Method Called ===`
   - `PayU S2S Webhook Received`

---

## Common Issues

### Issue 1: Can't Find URL Settings

**Solution:**
- Look for **"Merchant Settings"** or **"Account Settings"**
- Contact PayU support if you can't find it
- Some accounts may need to contact PayU to enable URL configuration

### Issue 2: URLs Not Saving

**Solution:**
- Check if URLs are valid (accessible)
- Ensure you have proper permissions
- Try using HTTP instead of HTTPS (if in test mode only)
- Contact PayU support

### Issue 3: URLs Not Working After Saving

**Solution:**
- Wait 5-10 minutes for changes to propagate
- Clear browser cache
- Test with a new payment
- Check if URLs are accessible from outside

---

## PayU Dashboard Screenshots Guide

### Where to Find Settings:

1. **Main Dashboard** → **Settings** (top right or left sidebar)
2. **Settings** → **Payment Settings** or **Integration Settings**
3. Look for sections like:
   - Return URLs
   - Callback URLs
   - Payment URLs
   - Merchant Configuration

### What the Fields Look Like:

```
┌─────────────────────────────────────────┐
│ Success URL (surl)                      │
│ ┌─────────────────────────────────────┐ │
│ │ https://yourdomain.com/success      │ │
│ └─────────────────────────────────────┘ │
│                                          │
│ Failure URL (furl)                      │
│ ┌─────────────────────────────────────┐ │
│ │ https://yourdomain.com/failure      │ │
│ └─────────────────────────────────────┘ │
│                                          │
│ Webhook URL (optional)                  │
│ ┌─────────────────────────────────────┐ │
│ │ https://yourdomain.com/webhook      │ │
│ └─────────────────────────────────────┘ │
│                                          │
│         [Save] [Cancel]                  │
└─────────────────────────────────────────┘
```

---

## Important Notes

1. **URLs in Code vs Dashboard:**
   - Your code already sends `surl` and `furl` in the payment request
   - Dashboard URLs are used as fallback/default
   - It's best to set both

2. **Test vs Production:**
   - Test dashboard: https://testdashboard.payu.in/
   - Production dashboard: https://dashboard.payu.in/
   - Configure URLs in both if you have access

3. **URL Requirements:**
   - Must be HTTPS in production
   - Must be accessible from internet
   - Should return 200 OK (or redirect to login)
   - No authentication required (PayU server calls it)

4. **Webhook URL:**
   - Most reliable way to confirm payment
   - PayU POSTs transaction data directly
   - Should be configured if available

---

## Contact PayU Support

If you can't find the URL settings:

1. **Email Support:**
   - Test: support@payu.in
   - Include your Merchant ID: `8847461`

2. **Phone Support:**
   - Check PayU website for support numbers

3. **Live Chat:**
   - Available in PayU dashboard

4. **Request:**
   - "I need to configure Success URL, Failure URL, and Webhook URL for my merchant account"
   - Provide the URLs you want to use

---

## Quick Reference: Your URLs

**Success URL:**
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success
```

**Failure URL:**
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure
```

**Webhook URL (S2S):**
```
https://interlinxpartnering.com/nixi/public/payu/webhook
```

---

## After Configuration

1. ✅ Save URLs in dashboard
2. ✅ Wait 5-10 minutes
3. ✅ Test with a small payment
4. ✅ Check logs at `/admin/view-logs`
5. ✅ Verify transaction in PayU dashboard

---

**Last Updated:** Based on PayU dashboard interface (may vary by version)

