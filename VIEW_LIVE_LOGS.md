# How to View Logs Live on Production Server

## Method 1: SSH Access (Most Common)

### If you have SSH access to your server:

#### Linux/Unix Server
```bash
# View logs in real-time (most common)
tail -f storage/logs/laravel.log

# View last 100 lines and follow
tail -n 100 -f storage/logs/laravel.log

# Filter for PayU/payment related logs only
tail -f storage/logs/laravel.log | grep -i "PayU\|payment\|Payment"

# View errors only
tail -f storage/logs/laravel.log | grep -iE "ERROR\|Exception\|Failed"

# View in multiple terminal windows (split screen)
tail -f storage/logs/laravel.log | grep --color=always "PayU\|ERROR\|Exception"
```

#### Windows Server (if using PowerShell/CMD)
```powershell
# View logs in real-time
Get-Content storage\logs\laravel.log -Wait -Tail 50

# Filter for PayU
Get-Content storage\logs\laravel.log -Wait | Select-String -Pattern "PayU|payment|ERROR"

# View errors only
Get-Content storage\logs\laravel.log -Wait | Select-String -Pattern "ERROR|Exception|Failed"
```

---

## Method 2: Using Laravel Tinker (If SSH Available)

```bash
# SSH into server, then:
cd /path/to/your/project
php artisan tinker

# Then in tinker, you can query logs:
# (Note: Tinker doesn't show live logs, but you can check recent entries)
```

---

## Method 3: cPanel / Hosting Control Panel

### If using cPanel:

1. **File Manager Method:**
   - Login to cPanel
   - Go to **File Manager**
   - Navigate to: `storage/logs/`
   - Open `laravel.log`
   - Click **Edit** (be careful not to modify)
   - Scroll to bottom to see latest entries
   - **Refresh** periodically to see new entries

2. **Terminal/SSH in cPanel:**
   - Login to cPanel
   - Go to **Terminal** or **SSH Access**
   - Run: `tail -f storage/logs/laravel.log`

### If using Plesk:
- Similar to cPanel - use File Manager or SSH Terminal

### If using other hosting:
- Check your hosting provider's documentation for SSH/Terminal access

---

## Method 4: Create a Log Viewer Route (Temporary - For Debugging Only)

**⚠️ WARNING: Only use this for debugging. Remove/secure it before production!**

Add this to your `routes/web.php` (temporarily):

```php
// TEMPORARY: Log viewer route - REMOVE AFTER DEBUGGING
Route::get('/admin/view-logs', function() {
    // Add authentication check here!
    // if (!auth()->check() || !auth()->user()->isAdmin()) {
    //     abort(403);
    // }
    
    $logFile = storage_path('logs/laravel.log');
    $lines = file($logFile);
    $recent = array_slice($lines, -200); // Last 200 lines
    
    return response()->json([
        'logs' => $recent,
        'count' => count($recent)
    ]);
})->middleware(['user.auth']); // Add your auth middleware
```

Then access: `https://interlinxpartnering.com/nixi/public/admin/view-logs`

**Remember to remove this route after debugging!**

---

## Method 5: Using Laravel Log Viewer Package (Recommended for Production)

### Install Laravel Log Viewer:

```bash
composer require rap2hpoutre/laravel-log-viewer
```

Then access logs at: `https://yourdomain.com/logs`

**Note:** This package provides a web interface to view logs. Make sure to secure it with authentication!

---

## Method 6: Real-time Monitoring with Browser DevTools

### Check Browser Console:
1. Open your website
2. Press `F12` to open Developer Tools
3. Go to **Console** tab
4. Look for JavaScript errors
5. Go to **Network** tab to see API calls and responses

### Check Network Requests:
1. In DevTools, go to **Network** tab
2. Filter by `XHR` or `Fetch`
3. Look for payment-related requests
4. Check response status codes and error messages

---

## Method 7: Server Logs (Apache/Nginx)

### Apache Error Log:
```bash
# Usually located at:
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/httpd/error_log
```

### Nginx Error Log:
```bash
# Usually located at:
tail -f /var/log/nginx/error.log
```

---

## Method 8: Database Logs (If Enabled)

If you're logging to database:

```sql
-- Check recent log entries
SELECT * FROM logs 
ORDER BY created_at DESC 
LIMIT 50;

-- Filter for PayU
SELECT * FROM logs 
WHERE message LIKE '%PayU%' OR message LIKE '%payment%'
ORDER BY created_at DESC 
LIMIT 50;
```

---

## Quick Commands Reference

### Most Useful Commands:

```bash
# 1. View live logs (most common)
tail -f storage/logs/laravel.log

# 2. View last 100 lines and follow
tail -n 100 -f storage/logs/laravel.log

# 3. View only PayU/payment logs
tail -f storage/logs/laravel.log | grep -i "PayU\|payment"

# 4. View only errors
tail -f storage/logs/laravel.log | grep -iE "ERROR\|Exception"

# 5. View last 500 lines (no follow)
tail -n 500 storage/logs/laravel.log

# 6. Search for specific transaction ID
grep "TXN17648673765126" storage/logs/laravel.log

# 7. Count errors today
grep -c "ERROR" storage/logs/laravel.log

# 8. View logs from last hour
grep "$(date -d '1 hour ago' '+%Y-%m-%d %H')" storage/logs/laravel.log
```

---

## For Your Specific Setup

Based on your URL `https://interlinxpartnering.com/nixi/public`, you likely have:

### Option A: cPanel Access
1. Login to cPanel
2. Go to **File Manager**
3. Navigate to: `public_html/nixi/storage/logs/` (or similar path)
4. Open `laravel.log`
5. Scroll to bottom for latest entries

### Option B: SSH Access
```bash
# SSH into your server
ssh username@interlinxpartnering.com

# Navigate to project
cd /path/to/nixi/public  # or wherever your project is

# View logs live
tail -f storage/logs/laravel.log
```

### Option C: FTP/SFTP
1. Connect via FTP/SFTP client (FileZilla, WinSCP, etc.)
2. Navigate to `storage/logs/`
3. Download `laravel.log`
4. Open in text editor
5. Scroll to bottom for latest entries

---

## Recommended Approach for Live Server

**Best Practice:**
1. Use SSH with `tail -f` for real-time monitoring
2. Install Laravel Log Viewer package for web-based viewing (with authentication)
3. Set up log rotation to prevent huge log files
4. Monitor errors and set up alerts

**Quick Start:**
```bash
# SSH into server
ssh your-username@interlinxpartnering.com

# Navigate to project directory
cd /path/to/your/project

# View logs live
tail -f storage/logs/laravel.log
```

---

## Troubleshooting

### If you can't access logs:

1. **Check file permissions:**
   ```bash
   ls -la storage/logs/laravel.log
   chmod 644 storage/logs/laravel.log  # If needed
   ```

2. **Check if logs directory exists:**
   ```bash
   ls -la storage/logs/
   ```

3. **Check Laravel configuration:**
   - Verify `config/logging.php` is configured correctly
   - Check `.env` has `LOG_CHANNEL=stack` or `LOG_CHANNEL=daily`

4. **Check disk space:**
   ```bash
   df -h
   ```

---

## Security Note

⚠️ **Important:** Never expose log files publicly. Always:
- Use authentication for web-based log viewers
- Restrict file permissions
- Remove temporary log viewer routes after debugging
- Use SSH with proper authentication

---

## Need Help?

If you can't access logs, contact your hosting provider and ask:
1. Do I have SSH access?
2. How do I access terminal/command line?
3. Where are my Laravel log files located?
4. Can I install Composer packages?

