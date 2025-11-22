# Security Improvements Guide

## 🔒 Critical Security Fixes

### 1. Move Credentials to Environment Variables

**Current Problem**: Credentials hardcoded in source code

**Solution**: Create `.env` file and use environment variables

```php
// Create .env file (DO NOT commit to git)
BOT_TOKEN=8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA
BOT_USERNAME=@Bypasa12_bot
ADMIN_IDS=1901426549
DHRU_API_URL=https://realmcloud.cfd/api_center/api_dhru.php
DHRU_USERNAME=iFastServer
DHRU_API_KEY=iFastServer_API_KEY_ADMIN_2025_SZ00
DHRU_SERVICE_ID=1
SERVICE_COST=30.00
GIFT_NOTIFY_CHAT_ID=-1001433615146
GIFT_REDEEM_COOLDOWN=1800

// Updated config.php
<?php
// Load environment variables
function load_env($file) {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

load_env(__DIR__ . '/.env');

return [
    'bot_token' => getenv('BOT_TOKEN'),
    'bot_username' => getenv('BOT_USERNAME'),
    'admin_ids' => array_map('intval', explode(',', getenv('ADMIN_IDS'))),
    'dhru_api_url' => getenv('DHRU_API_URL'),
    'dhru_username' => getenv('DHRU_USERNAME'),
    'dhru_api_key' => getenv('DHRU_API_KEY'),
    'dhru_service_id' => (int)getenv('DHRU_SERVICE_ID'),
    'service_cost' => (float)getenv('SERVICE_COST'),
    'gift_notify_chat_id' => (int)getenv('GIFT_NOTIFY_CHAT_ID'),
    'gift_redeem_cooldown' => (int)getenv('GIFT_REDEEM_COOLDOWN')
];
```

**Add to .gitignore**:
```
.env
bot_data/
bot_logs/
config.php
```

---

### 2. Enable SSL Certificate Verification

**Current Problem**: `CURLOPT_SSL_VERIFYPEER => false` allows MITM attacks

**Solution**: Enable SSL verification

```php
// In process_order() function
$ch = curl_init(DHRU_API_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($post),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,  // ENABLE THIS
    CURLOPT_SSL_VERIFYHOST => 2,     // ADD THIS
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);
```

---

### 3. Implement Webhook Validation

**Current Problem**: Anyone can send fake updates to your webhook

**Solution**: Validate Telegram webhook signature

```php
// Add at the beginning of main handler
function validate_telegram_request() {
    // Get secret token from webhook setup
    $secret_token = getenv('WEBHOOK_SECRET_TOKEN');
    
    // Get the secret token from header
    $received_token = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    
    if ($secret_token && $received_token !== $secret_token) {
        http_response_code(403);
        die('Forbidden');
    }
    
    return true;
}

// Set webhook with secret token using:
// https://api.telegram.org/bot<TOKEN>/setWebhook?url=<URL>&secret_token=<RANDOM_STRING>

validate_telegram_request();
```

---

### 4. Proper Error Handling

**Current Problem**: `error_reporting(0)` and `@` suppress all errors

**Solution**: Log errors properly without displaying them

```php
// At the beginning of the file
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't show errors to users
ini_set('log_errors', 1);      // Log errors
ini_set('error_log', __DIR__ . '/bot_logs/php_errors.log');

// Create custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $message = date('Y-m-d H:i:s') . " PHP Error [{$errno}]: {$errstr} in {$errfile}:{$errline}\n";
    file_put_contents(__DIR__ . '/bot_logs/php_errors.log', $message, FILE_APPEND);
    return true;
});

// Replace @ operators with proper try-catch
// BAD:
@file_put_contents($file, $data);

// GOOD:
try {
    file_put_contents($file, $data, LOCK_EX);
} catch (Exception $e) {
    log_error("Failed to write file: " . $e->getMessage());
}
```

---

### 5. Input Sanitization

**Current Problem**: Minimal input sanitization

**Solution**: Sanitize all user inputs

```php
function sanitize_text($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

function sanitize_numeric($value, $default = 0) {
    return is_numeric($value) ? floatval($value) : $default;
}

function sanitize_chat_id($chat_id) {
    $id = filter_var($chat_id, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        throw new InvalidArgumentException("Invalid chat ID");
    }
    return $id;
}

// Use in commands:
function cmd_addcredits($chat_id, $target_id, $amount) {
    // ... admin check ...
    
    try {
        $target_id = sanitize_chat_id($target_id);
        $amount = sanitize_numeric($amount);
        
        if ($amount <= 0) {
            send_message($chat_id, "❌ Amount must be greater than 0");
            return;
        }
        
        // ... rest of code ...
    } catch (Exception $e) {
        send_message($chat_id, "❌ Invalid parameters");
        log_error("addcredits error: " . $e->getMessage());
    }
}
```

---

### 6. Implement Rate Limiting at Web Server Level

**Current Problem**: Rate limiting only in PHP (can be bypassed)

**Solution**: Add Nginx rate limiting

```nginx
# /etc/nginx/conf.d/rate-limit.conf
limit_req_zone $binary_remote_addr zone=bot_webhook:10m rate=10r/s;

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    # SSL configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location /webhook.php {
        limit_req zone=bot_webhook burst=20 nodelay;
        
        # Only allow Telegram IPs
        allow 149.154.160.0/20;
        allow 91.108.4.0/22;
        deny all;
        
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

### 7. File Permission Hardening

**Current Problem**: Default permissions may be too open

**Solution**: Restrict file permissions

```bash
# Set proper ownership
chown -R www-data:www-data /path/to/bot

# Restrict directory permissions
find /path/to/bot -type d -exec chmod 750 {} \;

# Restrict file permissions
find /path/to/bot -type f -exec chmod 640 {} \;

# Make PHP files executable only for PHP-FPM
chmod 640 /path/to/bot/*.php

# Extra protection for sensitive data
chmod 600 /path/to/bot/.env
chmod 600 /path/to/bot/config.php
chmod 700 /path/to/bot/bot_data
chmod 700 /path/to/bot/bot_logs
```

---

### 8. Database Transaction Safety

**Current Problem**: No file locking, race conditions possible

**Solution**: Implement proper file locking

```php
function db_write_safe($file, $data) {
    $temp_file = $file . '.tmp';
    
    // Write to temporary file
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException("JSON encoding failed: " . json_last_error_msg());
    }
    
    $fp = fopen($temp_file, 'w');
    if (!$fp) {
        throw new RuntimeException("Cannot open temp file for writing");
    }
    
    // Exclusive lock
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new RuntimeException("Cannot acquire lock");
    }
    
    fwrite($fp, $json);
    fflush($fp);
    
    flock($fp, LOCK_UN);
    fclose($fp);
    
    // Atomic rename
    if (!rename($temp_file, $file)) {
        @unlink($temp_file);
        throw new RuntimeException("Cannot rename temp file");
    }
    
    return true;
}

function db_read_safe($file, $default = []) {
    if (!file_exists($file)) {
        return $default;
    }
    
    $fp = fopen($file, 'r');
    if (!$fp) {
        return $default;
    }
    
    // Shared lock for reading
    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        return $default;
    }
    
    $content = stream_get_contents($fp);
    
    flock($fp, LOCK_UN);
    fclose($fp);
    
    if ($content === false) {
        return $default;
    }
    
    $data = json_decode($content, true);
    return $data ?? $default;
}
```

---

### 9. Add CSRF Protection for Admin Actions

**Current Problem**: No CSRF protection

**Solution**: Add CSRF tokens

```php
function generate_csrf_token($chat_id) {
    $token = bin2hex(random_bytes(32));
    $csrf_file = DATA_DIR . '/csrf_' . $chat_id . '.token';
    file_put_contents($csrf_file, $token . '|' . (time() + 3600), LOCK_EX);
    return $token;
}

function verify_csrf_token($chat_id, $token) {
    $csrf_file = DATA_DIR . '/csrf_' . $chat_id . '.token';
    
    if (!file_exists($csrf_file)) {
        return false;
    }
    
    $data = explode('|', file_get_contents($csrf_file));
    $stored_token = $data[0] ?? '';
    $expiry = $data[1] ?? 0;
    
    if (time() > $expiry) {
        unlink($csrf_file);
        return false;
    }
    
    if (!hash_equals($stored_token, $token)) {
        return false;
    }
    
    unlink($csrf_file);
    return true;
}
```

---

### 10. Implement Logging with Levels

**Current Problem**: All logs treated equally

**Solution**: Add log levels

```php
class Logger {
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;
    
    private static $level = self::INFO;
    
    public static function setLevel($level) {
        self::$level = $level;
    }
    
    public static function log($level, $message, $context = []) {
        if ($level < self::$level) {
            return;
        }
        
        $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];
        $level_name = $levels[$level] ?? 'UNKNOWN';
        
        $line = sprintf(
            "[%s] %s: %s",
            date('Y-m-d H:i:s'),
            $level_name,
            $message
        );
        
        if (!empty($context)) {
            $line .= " | Context: " . json_encode($context);
        }
        
        $line .= PHP_EOL;
        
        // Write to appropriate log file
        $file = LOG_DEBUG;
        if ($level >= self::ERROR) {
            $file = LOG_ERRORS;
        }
        
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        
        // Also notify admin for critical errors
        if ($level === self::CRITICAL) {
            self::notifyAdmins($message);
        }
    }
    
    public static function debug($msg, $context = []) {
        self::log(self::DEBUG, $msg, $context);
    }
    
    public static function info($msg, $context = []) {
        self::log(self::INFO, $msg, $context);
    }
    
    public static function warning($msg, $context = []) {
        self::log(self::WARNING, $msg, $context);
    }
    
    public static function error($msg, $context = []) {
        self::log(self::ERROR, $msg, $context);
    }
    
    public static function critical($msg, $context = []) {
        self::log(self::CRITICAL, $msg, $context);
    }
    
    private static function notifyAdmins($message) {
        foreach (ADMIN_IDS as $admin_id) {
            send_message($admin_id, "🚨 CRITICAL ERROR\n\n" . $message);
        }
    }
}

// Usage:
Logger::setLevel(Logger::INFO);
Logger::info("User registered", ['chat_id' => $chat_id]);
Logger::error("API call failed", ['endpoint' => DHRU_API_URL]);
Logger::critical("Database corruption detected");
```

---

## 🔐 Additional Security Recommendations

### 1. Implement IP Whitelisting
Only allow Telegram's IP ranges to access the webhook.

### 2. Use HTTPS Only
Ensure all communication uses HTTPS.

### 3. Regular Security Audits
- Weekly log reviews
- Monthly code reviews
- Quarterly penetration testing

### 4. Backup Encryption
Encrypt backups before storing them.

```php
function encrypt_backup($file) {
    $key = getenv('BACKUP_ENCRYPTION_KEY');
    $data = file_get_contents($file);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    file_put_contents($file . '.enc', base64_encode($iv . $encrypted));
    unlink($file);
}
```

### 5. Implement 2FA for Admin Actions
Add additional verification for critical admin commands.

### 6. Set Up Intrusion Detection
Use fail2ban or similar tools to detect and block malicious IPs.

### 7. Regular Dependency Updates
Keep PHP and all libraries up to date.

### 8. Monitor for Suspicious Activity
- Multiple failed login attempts
- Unusual API usage patterns
- Rapid gift redemptions
- Abnormal credit additions

---

## 🎯 Implementation Priority

1. **Immediate** (Do Now):
   - Move credentials to environment variables
   - Enable SSL verification
   - Implement webhook validation
   - Fix error handling

2. **High Priority** (Within 1 Week):
   - Input sanitization
   - File permission hardening
   - Rate limiting at web server level
   - Proper file locking

3. **Medium Priority** (Within 1 Month):
   - CSRF protection
   - Logging improvements
   - Backup encryption
   - IP whitelisting

4. **Long Term** (Ongoing):
   - Regular security audits
   - Monitoring and alerting
   - Intrusion detection
   - Dependency updates

---

**Remember**: Security is not a one-time task, it's an ongoing process!
