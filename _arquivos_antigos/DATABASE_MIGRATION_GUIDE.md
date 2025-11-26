# Database Migration Guide
## From JSON Files to PostgreSQL

## 📋 Why Migrate?

### Current Issues with JSON Files:
- ❌ No transaction support
- ❌ Race condition vulnerabilities
- ❌ Slow for large datasets
- ❌ No query optimization
- ❌ Difficult to maintain data integrity
- ❌ No indexing support
- ❌ Limited concurrent access

### Benefits of PostgreSQL:
- ✅ ACID compliance
- ✅ Transaction support
- ✅ Excellent performance
- ✅ Advanced querying
- ✅ Data integrity constraints
- ✅ Indexing and optimization
- ✅ High concurrency support

---

## 🗄️ Database Schema

### SQL Schema Definition

```sql
-- Create database
CREATE DATABASE telegram_bot;

-- Use the database
\c telegram_bot;

-- Users table
CREATE TABLE users (
    chat_id BIGINT PRIMARY KEY,
    credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_spent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_orders INTEGER NOT NULL DEFAULT 0,
    username VARCHAR(255),
    name VARCHAR(255),
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    last_gift_redeem_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster lookups
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_is_admin ON users(is_admin);
CREATE INDEX idx_users_last_seen ON users(last_seen_at);

-- Transactions table
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    chat_id BIGINT NOT NULL REFERENCES users(chat_id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    metadata JSONB,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_transactions_chat_id ON transactions(chat_id);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_created_at ON transactions(created_at DESC);
CREATE INDEX idx_transactions_metadata ON transactions USING GIN(metadata);

-- Orders table
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    chat_id BIGINT NOT NULL REFERENCES users(chat_id) ON DELETE CASCADE,
    serial_number VARCHAR(20) NOT NULL,
    dhru_order_id VARCHAR(100),
    status VARCHAR(20) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_orders_chat_id ON orders(chat_id);
CREATE INDEX idx_orders_serial ON orders(serial_number);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at DESC);

-- Gifts table
CREATE TABLE gifts (
    code VARCHAR(20) PRIMARY KEY,
    mode VARCHAR(20) NOT NULL,
    param VARCHAR(50) NOT NULL,
    uses_remaining INTEGER NOT NULL,
    uses_total INTEGER NOT NULL,
    is_batch BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT REFERENCES users(chat_id)
);

-- Create indexes
CREATE INDEX idx_gifts_mode ON gifts(mode);
CREATE INDEX idx_gifts_created_at ON gifts(created_at DESC);

-- Gift redemptions tracking
CREATE TABLE gift_redemptions (
    id SERIAL PRIMARY KEY,
    gift_code VARCHAR(20) NOT NULL REFERENCES gifts(code) ON DELETE CASCADE,
    chat_id BIGINT NOT NULL REFERENCES users(chat_id) ON DELETE CASCADE,
    value_received DECIMAL(10,2),
    days_received INTEGER,
    redeemed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(gift_code, chat_id)
);

-- Create indexes
CREATE INDEX idx_redemptions_gift_code ON gift_redemptions(gift_code);
CREATE INDEX idx_redemptions_chat_id ON gift_redemptions(chat_id);
CREATE INDEX idx_redemptions_redeemed_at ON gift_redemptions(redeemed_at DESC);

-- Rentals/Subscriptions table
CREATE TABLE subscriptions (
    chat_id BIGINT PRIMARY KEY REFERENCES users(chat_id) ON DELETE CASCADE,
    plan_days INTEGER NOT NULL,
    started_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN GENERATED ALWAYS AS (expires_at > CURRENT_TIMESTAMP) STORED,
    expired_notified BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_subscriptions_expires_at ON subscriptions(expires_at);
CREATE INDEX idx_subscriptions_is_active ON subscriptions(is_active);

-- Rate limiting table
CREATE TABLE rate_limits (
    id SERIAL PRIMARY KEY,
    chat_id BIGINT NOT NULL,
    command VARCHAR(50) NOT NULL,
    last_used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(chat_id, command)
);

-- Create indexes
CREATE INDEX idx_rate_limits_chat_cmd ON rate_limits(chat_id, command);
CREATE INDEX idx_rate_limits_last_used ON rate_limits(last_used_at);

-- Broadcast locks table
CREATE TABLE broadcast_locks (
    id SERIAL PRIMARY KEY,
    admin_id BIGINT NOT NULL,
    broadcast_type VARCHAR(50) NOT NULL,
    pid INTEGER NOT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

-- Create index
CREATE INDEX idx_broadcast_locks_expires ON broadcast_locks(expires_at);

-- Create function to automatically update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_subscriptions_updated_at BEFORE UPDATE ON subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create view for active subscriptions
CREATE VIEW active_subscriptions AS
SELECT 
    s.*,
    u.username,
    u.name,
    EXTRACT(DAY FROM (s.expires_at - CURRENT_TIMESTAMP)) as days_remaining
FROM subscriptions s
JOIN users u ON s.chat_id = u.chat_id
WHERE s.is_active = TRUE;

-- Create view for user statistics
CREATE VIEW user_statistics AS
SELECT 
    u.chat_id,
    u.username,
    u.name,
    u.credits,
    u.total_spent,
    u.total_orders,
    COUNT(DISTINCT o.id) as order_count,
    COUNT(DISTINCT CASE WHEN o.status = 'success' THEN o.id END) as successful_orders,
    COUNT(DISTINCT gr.id) as gifts_redeemed,
    CASE WHEN s.is_active THEN TRUE ELSE FALSE END as has_active_plan,
    s.expires_at as plan_expires_at
FROM users u
LEFT JOIN orders o ON u.chat_id = o.chat_id
LEFT JOIN gift_redemptions gr ON u.chat_id = gr.chat_id
LEFT JOIN subscriptions s ON u.chat_id = s.chat_id
GROUP BY u.chat_id, s.is_active, s.expires_at;
```

---

## 🔧 PHP Database Connection

### 1. Install PostgreSQL Extension

```bash
# Install PostgreSQL PHP extension
sudo apt-get install php-pgsql php-pdo-pgsql

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### 2. Create Database Connection Class

```php
<?php
// db.php

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $dbname = getenv('DB_NAME') ?: 'telegram_bot';
        $user = getenv('DB_USER') ?: 'bot_user';
        $pass = getenv('DB_PASS') ?: '';
        
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        
        try {
            $this->conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ]);
        } catch (PDOException $e) {
            Logger::critical("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
```

---

## 📝 Updated Functions

### User Management

```php
<?php
// users.php

function get_user($chat_id) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM users WHERE chat_id = :chat_id
        ");
        $stmt->execute(['chat_id' => $chat_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Create new user
            $stmt = $db->prepare("
                INSERT INTO users (chat_id, is_admin)
                VALUES (:chat_id, :is_admin)
                RETURNING *
            ");
            $stmt->execute([
                'chat_id' => $chat_id,
                'is_admin' => in_array($chat_id, ADMIN_IDS)
            ]);
            $user = $stmt->fetch();
            
            Logger::info("New user registered", ['chat_id' => $chat_id]);
        }
        
        return $user;
        
    } catch (PDOException $e) {
        Logger::error("get_user failed", ['error' => $e->getMessage()]);
        return null;
    }
}

function update_user($chat_id, $data) {
    $db = Database::getInstance()->getConnection();
    
    $allowed_fields = ['username', 'name', 'last_seen_at', 'last_gift_redeem_at'];
    $updates = [];
    $params = ['chat_id' => $chat_id];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = :$key";
            $params[$key] = $value;
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    try {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE chat_id = :chat_id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
        
    } catch (PDOException $e) {
        Logger::error("update_user failed", ['error' => $e->getMessage()]);
        return false;
    }
}

function add_credits($chat_id, $amount, $admin_id = null) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Update user credits
        $stmt = $db->prepare("
            UPDATE users 
            SET credits = credits + :amount,
                updated_at = CURRENT_TIMESTAMP
            WHERE chat_id = :chat_id
            RETURNING credits
        ");
        $stmt->execute([
            'chat_id' => $chat_id,
            'amount' => $amount
        ]);
        $result = $stmt->fetch();
        $new_balance = $result['credits'];
        
        // Log transaction
        $stmt = $db->prepare("
            INSERT INTO transactions 
            (transaction_id, chat_id, type, amount, balance_after, metadata)
            VALUES (:tx_id, :chat_id, 'credit_add', :amount, :balance, :meta)
        ");
        $stmt->execute([
            'tx_id' => uniqid('tx_'),
            'chat_id' => $chat_id,
            'amount' => $amount,
            'balance' => $new_balance,
            'meta' => json_encode(['by_admin' => $admin_id])
        ]);
        
        $db->commit();
        
        Logger::info("Credits added", [
            'chat_id' => $chat_id,
            'amount' => $amount,
            'by_admin' => $admin_id
        ]);
        
        return $new_balance;
        
    } catch (PDOException $e) {
        $db->rollback();
        Logger::error("add_credits failed", ['error' => $e->getMessage()]);
        return false;
    }
}

function charge_credits($chat_id, $amount, $type, $meta = []) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Check if user has enough credits
        $stmt = $db->prepare("SELECT credits FROM users WHERE chat_id = :chat_id FOR UPDATE");
        $stmt->execute(['chat_id' => $chat_id]);
        $user = $stmt->fetch();
        
        if (!$user || $user['credits'] < $amount) {
            $db->rollback();
            return false;
        }
        
        // Update user
        $updates = ['credits = credits - :amount'];
        $params = ['chat_id' => $chat_id, 'amount' => $amount];
        
        if ($type === 'order_success') {
            $updates[] = 'total_spent = total_spent + :amount';
            $updates[] = 'total_orders = total_orders + 1';
        }
        
        $stmt = $db->prepare("
            UPDATE users 
            SET " . implode(', ', $updates) . ",
                updated_at = CURRENT_TIMESTAMP
            WHERE chat_id = :chat_id
            RETURNING credits
        ");
        $stmt->execute($params);
        $result = $stmt->fetch();
        $new_balance = $result['credits'];
        
        // Log transaction
        $stmt = $db->prepare("
            INSERT INTO transactions 
            (transaction_id, chat_id, type, amount, balance_after, metadata)
            VALUES (:tx_id, :chat_id, :type, :amount, :balance, :meta)
        ");
        $stmt->execute([
            'tx_id' => uniqid('tx_'),
            'chat_id' => $chat_id,
            'type' => $type,
            'amount' => -$amount,
            'balance' => $new_balance,
            'meta' => json_encode($meta)
        ]);
        
        $db->commit();
        
        Logger::info("Credits charged", [
            'chat_id' => $chat_id,
            'amount' => $amount,
            'type' => $type
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        $db->rollback();
        Logger::error("charge_credits failed", ['error' => $e->getMessage()]);
        return false;
    }
}
```

### Order Management

```php
<?php
// orders.php

function add_order($chat_id, $serial, $dhru_order_id, $status, $cost = SERVICE_COST) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO orders 
            (order_id, chat_id, serial_number, dhru_order_id, status, cost)
            VALUES (:order_id, :chat_id, :serial, :dhru_id, :status, :cost)
        ");
        
        return $stmt->execute([
            'order_id' => uniqid('ord_'),
            'chat_id' => $chat_id,
            'serial' => $serial,
            'dhru_id' => $dhru_order_id,
            'status' => $status,
            'cost' => $cost
        ]);
        
    } catch (PDOException $e) {
        Logger::error("add_order failed", ['error' => $e->getMessage()]);
        return false;
    }
}

function get_user_orders($chat_id, $limit = 10) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM orders 
            WHERE chat_id = :chat_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        Logger::error("get_user_orders failed", ['error' => $e->getMessage()]);
        return [];
    }
}

function is_duplicate_order($chat_id, $serial, $window_minutes = 5) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM orders 
            WHERE chat_id = :chat_id 
            AND UPPER(serial_number) = UPPER(:serial)
            AND created_at > CURRENT_TIMESTAMP - INTERVAL ':minutes minutes'
        ");
        $stmt->execute([
            'chat_id' => $chat_id,
            'serial' => $serial,
            'minutes' => $window_minutes
        ]);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        Logger::error("is_duplicate_order failed", ['error' => $e->getMessage()]);
        return false;
    }
}
```

### Gift Management

```php
<?php
// gifts.php

function create_gift($code, $mode, $param, $uses, $created_by, $is_batch = false) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO gifts 
            (code, mode, param, uses_remaining, uses_total, is_batch, created_by)
            VALUES (:code, :mode, :param, :uses, :uses, :is_batch, :created_by)
        ");
        
        return $stmt->execute([
            'code' => $code,
            'mode' => $mode,
            'param' => $param,
            'uses' => $uses,
            'is_batch' => $is_batch,
            'created_by' => $created_by
        ]);
        
    } catch (PDOException $e) {
        Logger::error("create_gift failed", ['error' => $e->getMessage()]);
        return false;
    }
}

function redeem_gift($chat_id, $code) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get gift with lock
        $stmt = $db->prepare("
            SELECT * FROM gifts 
            WHERE code = :code 
            FOR UPDATE
        ");
        $stmt->execute(['code' => $code]);
        $gift = $stmt->fetch();
        
        if (!$gift || $gift['uses_remaining'] <= 0) {
            $db->rollback();
            return ['success' => false, 'message' => 'Invalid or expired gift code'];
        }
        
        // Check if already redeemed by this user
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM gift_redemptions 
            WHERE gift_code = :code AND chat_id = :chat_id
        ");
        $stmt->execute(['code' => $code, 'chat_id' => $chat_id]);
        $check = $stmt->fetch();
        
        if ($check['count'] > 0) {
            $db->rollback();
            return ['success' => false, 'message' => 'Gift already redeemed'];
        }
        
        // Process redemption based on mode
        if ($gift['mode'] === 'credit') {
            $value = floatval($gift['param']);
            add_credits($chat_id, $value, null);
            
            // Record redemption
            $stmt = $db->prepare("
                INSERT INTO gift_redemptions 
                (gift_code, chat_id, value_received)
                VALUES (:code, :chat_id, :value)
            ");
            $stmt->execute([
                'code' => $code,
                'chat_id' => $chat_id,
                'value' => $value
            ]);
            
            $message = "Credits added: $" . number_format($value, 2);
            
        } else if ($gift['mode'] === 'auto') {
            // Handle subscription gift
            $param = $gift['param'];
            if (preg_match('/^(\d+)\s*d$/i', $param, $m)) {
                $days = intval($m[1]);
            } else {
                $days = intval($param);
            }
            
            // Add or extend subscription
            $stmt = $db->prepare("
                INSERT INTO subscriptions 
                (chat_id, plan_days, started_at, expires_at)
                VALUES (:chat_id, :days, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP + INTERVAL ':days days')
                ON CONFLICT (chat_id) DO UPDATE
                SET plan_days = subscriptions.plan_days + :days,
                    expires_at = CASE 
                        WHEN subscriptions.expires_at > CURRENT_TIMESTAMP 
                        THEN subscriptions.expires_at + INTERVAL ':days days'
                        ELSE CURRENT_TIMESTAMP + INTERVAL ':days days'
                    END
            ");
            $stmt->execute(['chat_id' => $chat_id, 'days' => $days]);
            
            // Record redemption
            $stmt = $db->prepare("
                INSERT INTO gift_redemptions 
                (gift_code, chat_id, days_received)
                VALUES (:code, :chat_id, :days)
            ");
            $stmt->execute([
                'code' => $code,
                'chat_id' => $chat_id,
                'days' => $days
            ]);
            
            $message = "Subscription extended by {$days} days";
        }
        
        // Decrease gift uses
        $stmt = $db->prepare("
            UPDATE gifts 
            SET uses_remaining = uses_remaining - 1 
            WHERE code = :code
        ");
        $stmt->execute(['code' => $code]);
        
        // Update user last gift redeem time
        update_user($chat_id, ['last_gift_redeem_at' => date('Y-m-d H:i:s')]);
        
        $db->commit();
        
        return ['success' => true, 'message' => $message];
        
    } catch (PDOException $e) {
        $db->rollback();
        Logger::error("redeem_gift failed", ['error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'System error'];
    }
}
```

---

## 🔄 Migration Script

```php
<?php
// migrate.php

require_once 'db.php';
require_once 'logger.php';

function migrate_json_to_postgresql() {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting migration...\n\n";
    
    try {
        $db->beginTransaction();
        
        // 1. Migrate Users
        echo "Migrating users...\n";
        $users = json_decode(file_get_contents('bot_data/users.json'), true);
        $user_count = 0;
        
        foreach ($users as $user) {
            $stmt = $db->prepare("
                INSERT INTO users 
                (chat_id, credits, registered_at, last_seen_at, total_spent, 
                 total_orders, username, name, is_admin, last_gift_redeem_at)
                VALUES 
                (:chat_id, :credits, :registered, :last_seen, :spent, 
                 :orders, :username, :name, :admin, :gift_redeem)
            ");
            $stmt->execute([
                'chat_id' => $user['chat_id'],
                'credits' => $user['credits'],
                'registered' => $user['registered'],
                'last_seen' => $user['last_seen'],
                'spent' => $user['total_spent'],
                'orders' => $user['total_orders'],
                'username' => $user['username'],
                'name' => $user['name'],
                'admin' => $user['is_admin'],
                'gift_redeem' => $user['last_gift_redeem']
            ]);
            $user_count++;
        }
        echo "✓ Migrated {$user_count} users\n\n";
        
        // 2. Migrate Transactions
        echo "Migrating transactions...\n";
        $txs = json_decode(file_get_contents('bot_data/transactions.json'), true);
        $tx_count = 0;
        
        foreach ($txs as $tx) {
            $stmt = $db->prepare("
                INSERT INTO transactions 
                (transaction_id, chat_id, type, amount, balance_after, metadata, created_at)
                VALUES 
                (:tx_id, :chat_id, :type, :amount, :balance, :meta, :created)
            ");
            $stmt->execute([
                'tx_id' => $tx['id'],
                'chat_id' => $tx['chat_id'],
                'type' => $tx['type'],
                'amount' => $tx['amount'],
                'balance' => $tx['meta']['balance'] ?? 0,
                'meta' => json_encode($tx['meta']),
                'created' => $tx['time']
            ]);
            $tx_count++;
        }
        echo "✓ Migrated {$tx_count} transactions\n\n";
        
        // 3. Migrate Orders
        echo "Migrating orders...\n";
        $orders = json_decode(file_get_contents('bot_data/orders.json'), true);
        $order_count = 0;
        
        foreach ($orders as $order) {
            $stmt = $db->prepare("
                INSERT INTO orders 
                (order_id, chat_id, serial_number, dhru_order_id, status, cost, created_at)
                VALUES 
                (:order_id, :chat_id, :serial, :dhru_id, :status, :cost, :created)
            ");
            $stmt->execute([
                'order_id' => $order['id'],
                'chat_id' => $order['chat_id'],
                'serial' => $order['serial'],
                'dhru_id' => $order['order_id'],
                'status' => $order['status'],
                'cost' => $order['cost'],
                'created' => $order['time']
            ]);
            $order_count++;
        }
        echo "✓ Migrated {$order_count} orders\n\n";
        
        // 4. Migrate Gifts
        echo "Migrating gifts...\n";
        $gifts = json_decode(file_get_contents('bot_data/gifts.json'), true);
        $gift_count = 0;
        
        foreach ($gifts as $code => $gift) {
            $stmt = $db->prepare("
                INSERT INTO gifts 
                (code, mode, param, uses_remaining, uses_total, is_batch, created_at)
                VALUES 
                (:code, :mode, :param, :uses, :uses, :batch, :created)
            ");
            $stmt->execute([
                'code' => $code,
                'mode' => $gift['mode'],
                'param' => $gift['param'],
                'uses' => $gift['uses'],
                'batch' => $gift['batch'] ?? false,
                'created' => $gift['created_at']
            ]);
            $gift_count++;
        }
        echo "✓ Migrated {$gift_count} gifts\n\n";
        
        // 5. Migrate Subscriptions
        echo "Migrating subscriptions...\n";
        $rentals = json_decode(file_get_contents('bot_data/rentals.json'), true);
        $rental_count = 0;
        
        foreach ($rentals as $chat_id => $rental) {
            $stmt = $db->prepare("
                INSERT INTO subscriptions 
                (chat_id, plan_days, started_at, expires_at, expired_notified)
                VALUES 
                (:chat_id, :days, :started, :expires, :notified)
            ");
            $stmt->execute([
                'chat_id' => $rental['chat_id'],
                'days' => $rental['days'],
                'started' => $rental['start'],
                'expires' => $rental['expires'],
                'notified' => $rental['expired_notified']
            ]);
            $rental_count++;
        }
        echo "✓ Migrated {$rental_count} subscriptions\n\n";
        
        $db->commit();
        
        echo "═══════════════════════════════════════\n";
        echo "✓ Migration completed successfully!\n";
        echo "═══════════════════════════════════════\n\n";
        echo "Summary:\n";
        echo "  - Users: {$user_count}\n";
        echo "  - Transactions: {$tx_count}\n";
        echo "  - Orders: {$order_count}\n";
        echo "  - Gifts: {$gift_count}\n";
        echo "  - Subscriptions: {$rental_count}\n";
        
    } catch (Exception $e) {
        $db->rollback();
        echo "✗ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Run migration
migrate_json_to_postgresql();
```

---

## 🚀 Deployment Steps

### 1. Install PostgreSQL

```bash
# Install PostgreSQL
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib

# Start PostgreSQL
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### 2. Create Database and User

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE telegram_bot;
CREATE USER bot_user WITH ENCRYPTED PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE telegram_bot TO bot_user;
\q
```

### 3. Run Schema Creation

```bash
# Run schema SQL
psql -U bot_user -d telegram_bot -f schema.sql
```

### 4. Backup Existing Data

```bash
# Create backup directory
mkdir -p backups/pre_migration

# Backup JSON files
cp bot_data/*.json backups/pre_migration/
```

### 5. Run Migration

```bash
# Run migration script
php migrate.php
```

### 6. Test Database Connection

```bash
php -r "require 'db.php'; \$db = Database::getInstance(); echo 'Connection successful!';"
```

### 7. Update Environment Variables

```bash
# Add to .env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=telegram_bot
DB_USER=bot_user
DB_PASS=your_secure_password
```

### 8. Deploy Updated Code

Replace all JSON-based functions with database functions.

---

## 📊 Performance Comparison

| Operation | JSON Files | PostgreSQL |
|-----------|------------|------------|
| User lookup | O(n) | O(1) with index |
| Credit update | ~10-50ms | ~1-5ms |
| Order search | O(n) | O(log n) with index |
| Concurrent writes | ❌ Locks file | ✅ ACID compliant |
| Transaction safety | ❌ None | ✅ Full support |
| Data integrity | ⚠️ Manual | ✅ Constraints |
| Scalability | < 1K users | > 100K users |

---

## 🔒 Backup Strategy with PostgreSQL

```bash
# Daily automated backup
0 3 * * * pg_dump -U bot_user telegram_bot | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz

# Backup retention (keep 30 days)
0 4 * * * find /backups -name "db_*.sql.gz" -mtime +30 -delete
```

---

**Migration Time Estimate**: 
- Setup: 1-2 hours
- Code refactoring: 4-6 hours
- Testing: 2-3 hours
- **Total: 7-11 hours**

**Recommended Approach**: 
Test migration on a copy of production data first!
