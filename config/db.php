<?php
if (class_exists('DB')) return;

class DB {
    private $host = DB_HOST;
    private $db   = DB_NAME;
    private $user = DB_USER;
    private $pass = DB_PASSWORD;
    private $charset = 'utf8mb4';

    private static $_instance = null;
    private $pdo;

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            $this->pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new DB();
        }
        return self::$_instance;
    }

    // Execute query
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // Fetch single row
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Get number of rows
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    // Last inserted ID
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    // Insert helper
    public function insert($table, $data) {
        $keys = implode(',', array_keys($data));
        $placeholders = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $this->query($sql, $data);
        return $this->lastInsertId();
    }

    // Update helper
    public function update($table, $data, $where, $whereParams = []) {
        $set = implode(',', array_map(fn($k) => "$k=:$k", array_keys($data)));
        $sql = "UPDATE $table SET $set WHERE $where";
        $this->query($sql, array_merge($data, $whereParams));
        return true;
    }

    // Delete helper
    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $whereParams);
        return true;
    }

    // Transactions
    public function beginTransaction() { return $this->pdo->beginTransaction(); }
    public function commit() { return $this->pdo->commit(); }
    public function rollback() { return $this->pdo->rollBack(); }

    // Close connection
    public function close() {
        $this->pdo = null;
        self::$_instance = null;
    }

    // Debug SQL with parameters
    public function debugSql(string $sql, array $params = []): string
    {
        $debugSql = $sql;

        // Handle named placeholders
        foreach ($params as $key => $value) {
            if (is_null($value)) $value = 'NULL';
            elseif (is_numeric($value)) $value = $value;
            else $value = "'" . addslashes($value) . "'";

            if (is_string($key) && strpos($key, ':') === 0) {
                $debugSql = str_replace($key, $value, $debugSql);
            }
        }

        // Handle positional placeholders
        if (!empty($params)) {
            $posParams = array_values($params);
            $debugSql = preg_replace_callback('/\?/', function() use (&$posParams) {
                $val = array_shift($posParams);
                if (is_null($val)) return 'NULL';
                elseif (is_numeric($val)) return $val;
                else return "'" . addslashes($val) . "'";
            }, $debugSql);
        }

        // Output via echo
        echo "<pre style='color:green; background:#f4f4f4; padding:5px; border:1px solid #ccc;'>$debugSql</pre>";

        // Output via JS alert
        echo "<script>alert(" . json_encode($debugSql) . ");</script>";

        // Output via JS console.log
        echo "<script>console.log(" . json_encode($debugSql) . ");</script>";

        return $debugSql;
    }

    public function inTransaction() { 
        return $this->pdo->inTransaction(); 
    }

}

?>
