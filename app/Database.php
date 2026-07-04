<?php

class Database
{
    /** @var \PDO|null */
    private static $pdo = null;

    public static function initialize(string $path): void
    {
        if (self::$pdo !== null) {
            return;
        }

        if (!file_exists($path)) {
            touch($path);
        }

        self::$pdo = new PDO('sqlite:' . $path);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec('PRAGMA foreign_keys = ON;');
    }

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('Database is not initialized.');
        }

        return self::$pdo;
    }

    private static function columnExists(string $table, string $column): bool
    {
        $stmt = self::get()->prepare("PRAGMA table_info({$table})");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $row) {
            if (isset($row['name']) && $row['name'] === $column) {
                return true;
            }
        }

        return false;
    }

    private static function addColumn(string $table, string $column, string $definition): void
    {
        if (!self::columnExists($table, $column)) {
            try {
                self::get()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            } catch (PDOException $e) {
                if (stripos($e->getMessage(), 'non-constant default') !== false && preg_match('/CURRENT_TIMESTAMP/i', $definition)) {
                    $fallbackDefinition = preg_replace('/\s+DEFAULT\s+CURRENT_TIMESTAMP/i', '', $definition);
                    self::get()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$fallbackDefinition}");
                } else {
                    throw $e;
                }
            }
        }
    }

    public static function migrate(): void
    {
        $db = self::get();

        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rubika_id TEXT UNIQUE,
            phone TEXT,
            name TEXT,
            lastname TEXT,
            national_id TEXT,
            birth_date TEXT,
            status TEXT DEFAULT 'pending',
            trust_score INTEGER DEFAULT 0,
            current_step TEXT DEFAULT 'new',
            pending_data TEXT DEFAULT '{}',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS kyc (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            selfie TEXT,
            id_front TEXT,
            id_back TEXT,
            status TEXT DEFAULT 'pending',
            submitted_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS ads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            type TEXT,
            amount REAL,
            description TEXT,
            loan_code TEXT UNIQUE,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            amount REAL,
            receipt TEXT,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS escrow (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            deal_id INTEGER,
            amount REAL,
            escrow_code TEXT,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS escrows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            buyer_id INTEGER,
            seller_id INTEGER,
            amount REAL,
            fee REAL DEFAULT 0,
            status TEXT DEFAULT 'pending',
            description TEXT,
            metadata TEXT DEFAULT '{}',
            order_id TEXT,
            zarinpal_authority TEXT,
            zarinpal_ref_id TEXT,
            payment_verified_at TEXT,
            released_at TEXT,
            disputed_at TEXT,
            dispute_reason TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(buyer_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS escrow_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            escrow_id INTEGER,
            event TEXT,
            actor_id INTEGER,
            data TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(escrow_id) REFERENCES escrows(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            target_id INTEGER,
            reason TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS login_otps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            phone TEXT,
            code TEXT,
            expires_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS user_verifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            full_name TEXT,
            address TEXT,
            postal_code TEXT,
            phone TEXT,
            national_id TEXT,
            national_card_back_image TEXT,
            status TEXT DEFAULT 'pending',
            admin_notes TEXT,
            submitted_at TEXT DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TEXT,
            reviewed_by INTEGER,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // جداول احراز هویت JWT
        $db->exec("CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT,
            email TEXT,
            role TEXT DEFAULT 'admin',
            status TEXT DEFAULT 'active',
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS token_blacklist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            jti TEXT UNIQUE NOT NULL,
            token_type TEXT DEFAULT 'refresh',
            reason TEXT,
            blacklisted_at TEXT DEFAULT CURRENT_TIMESTAMP,
            expires_at TEXT NOT NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            admin_id INTEGER,
            action TEXT NOT NULL,
            description TEXT,
            ip_address TEXT,
            user_agent TEXT,
            metadata TEXT DEFAULT '{}',
            severity TEXT DEFAULT 'info',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE SET NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            ip_address TEXT,
            success INTEGER DEFAULT 0,
            attempted_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        // اضافه کردن ستون‌های JWT به جدول users
        self::addColumn('user_verifications', 'national_id', 'TEXT');
        self::addColumn('users', 'phone_verified', 'INTEGER DEFAULT 0');
        self::addColumn('users', 'is_verified', 'INTEGER DEFAULT 0');
        self::addColumn('users', 'email', 'TEXT');
        self::addColumn('users', 'password_hash', 'TEXT');
        self::addColumn('users', 'is_admin', 'INTEGER DEFAULT 0');
        self::addColumn('users', 'last_login', 'TEXT');
        self::addColumn('users', 'last_activity', 'TEXT DEFAULT CURRENT_TIMESTAMP');
    }
}
