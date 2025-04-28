<?php
class Database {
    private $host = "localhost";
    private $db_name = "u302876046_pestcozam";
    private $username = "u302876046_root"; 
    private $password = "Pestcozam@2025"; 
    private $conn;
    private static $instance = null;

    // Add singleton pattern to ensure only one connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn; // Return existing connection if available
        }

        try {
            // More detailed connection options for better stability
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            // Try to connect with database selection in one step
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                $options
            );
            
            error_log("Successfully connected to database: " . $this->db_name);
            return $this->conn;
            
        } catch(PDOException $exception) {
            // Log detailed error
            error_log("Database connection error: " . $exception->getMessage());
            error_log("Connection details: host=" . $this->host . ", dbname=" . $this->db_name . ", username=" . $this->username);
            
            // Try local default credentials as fallback
            try {
                $this->conn = new PDO(
                    "mysql:host=localhost;dbname=" . $this->db_name . ";charset=utf8mb4", 
                    "root", 
                    "",
                    $options
                );
                error_log("Connected to database with fallback credentials");
                return $this->conn;
            } catch(PDOException $e) {
                error_log("Fallback connection failed: " . $e->getMessage());
                
                // Try to create the database if it doesn't exist
                if ($this->createDatabase()) {
                    try {
                        $this->conn = new PDO(
                            "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                            $this->username, 
                            $this->password,
                            $options
                        );
                        error_log("Successfully connected to newly created database");
                        return $this->conn;
                    } catch(PDOException $e2) {
                        error_log("Failed to connect after creating database: " . $e2->getMessage());
                    }
                }
            }
        }

        return null;
    }
    
    // Create the database using the SQL file
    private function createDatabase() {
        try {
            // Connect without specifying a database
            $tempConn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create the database if it doesn't exist
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "` 
                            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            error_log("Created database: " . $this->db_name);
            
            // Select the database
            $tempConn->exec("USE `" . $this->db_name . "`");
            
            // Import the SQL file
            $sqlFile = __DIR__ . "/u302876046_pestcozam.sql";
            
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                
                // Split SQL by semicolons to execute each statement separately
                $statements = explode(';', $sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $tempConn->exec($statement);
                        } catch (PDOException $e) {
                            error_log("Error executing SQL statement: " . $e->getMessage());
                            // Continue with the next statement even if this one fails
                        }
                    }
                }
                
                error_log("Imported SQL file into database");
                return true;
            } else {
                error_log("SQL file not found: " . $sqlFile);
            }
            
        } catch (PDOException $e) {
            error_log("Error creating database: " . $e->getMessage());
        }
        
        return false;
    }
    
    // For local development/testing when remote DB fails
    public function useLocalConnection() {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO(
                "mysql:host=localhost;dbname=" . $this->db_name . ";charset=utf8mb4", 
                "root", 
                "",
                $options
            );
            error_log("Connected to local database");
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Local connection failed: " . $e->getMessage());
            return null;
        }
    }
}
?>