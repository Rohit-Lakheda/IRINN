# Test PayU Callback URLs

## Test These URLs Directly

After moving the routes outside authentication, test these URLs:

### Success URL:
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success
```

### Failure URL:
```
https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure
```

### Webhook URL:
```
https://interlinxpartnering.com/nixi/public/payu/webhook
```

## How to Test

### Method 1: Browser Test
1. Open browser
2. Go to the URL directly
3. You should see a response (even if it's an error about missing transaction)
4. Check logs at `/admin/view-logs` - you should see "=== PayU Success/Failure Callback Method Called ==="

### Method 2: cURL Test
```bash
# Test Success URL
curl -v "https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-success?txnid=TEST123&status=success"

# Test Failure URL
curl -v "https://interlinxpartnering.com/nixi/public/user/applications/ix/payment-failure?txnid=TEST123&status=failure"
```

### Method 3: Postman/API Client
- Method: GET or POST
- URL: One of the URLs above
- Add query parameters: `txnid`, `status`, `hash`, etc.

## Expected Response

If URLs are working, you should see in logs:
- `=== PayU Success Callback Method Called ===` or
- `=== PayU Failure Callback Method Called ===`

Even if parameters are missing, the method should be called and logged.

## If URLs Don't Work

1. Check if route exists: `php artisan route:list | grep payment`
2. Check server logs for 404 errors
3. Verify `.htaccess` is configured correctly
4. Check if subdirectory routing is working

