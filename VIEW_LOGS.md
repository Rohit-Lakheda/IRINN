# How to View Laravel Logs Live

## Method 1: Using Tail Command (Recommended)

### Windows (PowerShell)
```powershell
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

### Windows (Command Prompt)
```cmd
powershell -Command "Get-Content storage\logs\laravel.log -Wait -Tail 50"
```

### Linux/Mac
```bash
tail -f storage/logs/laravel.log
```

## Method 2: Filter PayU Related Logs Only

### Windows (PowerShell)
```powershell
Get-Content storage\logs\laravel.log -Wait | Select-String -Pattern "PayU"
```

### Linux/Mac
```bash
tail -f storage/logs/laravel.log | grep -i payu
```

## Method 3: View Last 100 Lines with PayU

### Windows (PowerShell)
```powershell
Get-Content storage\logs\laravel.log -Tail 100 | Select-String -Pattern "PayU"
```

### Linux/Mac
```bash
tail -n 100 storage/logs/laravel.log | grep -i payu
```

## Method 4: View All Recent Errors

### Windows (PowerShell)
```powershell
Get-Content storage\logs\laravel.log -Wait | Select-String -Pattern "ERROR|Exception|Failed"
```

### Linux/Mac
```bash
tail -f storage/logs/laravel.log | grep -iE "ERROR|Exception|Failed"
```

## Method 5: Clear Log and Watch Fresh

### Windows (PowerShell)
```powershell
Clear-Content storage\logs\laravel.log
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

### Linux/Mac
```bash
> storage/logs/laravel.log
tail -f storage/logs/laravel.log
```

## What to Look For

When testing payment, look for these log entries:

1. **"PayU Success Callback Received"** - Shows what PayU sent
2. **"PayU Hash Verification"** - Shows hash verification result
3. **"PayU Payment Success - Transaction Updated"** - Confirms transaction update
4. **"PayU S2S Webhook Received"** - Webhook from PayU server
5. **"ERROR"** or **"Exception"** - Any errors that occurred

## Quick Debug Commands

### View last payment attempt
```powershell
Get-Content storage\logs\laravel.log -Tail 200 | Select-String -Pattern "PayU|payment" -Context 5
```

### View all errors today
```powershell
Get-Content storage\logs\laravel.log | Select-String -Pattern "ERROR|Exception" | Select-Object -Last 20
```

