<?php
class Database {
    private $host = "localhost";
    private $db_name = "u302876046_pestcozam";
    private $username = "u302876046_root"; // Changed to local root username
    private $password = "Pestcozam@2025"; // Changed to local empty password
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Try to connect with database selection in one step
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("Successfully connected to database: " . $this->db_name);
        } catch(PDOException $exception) {
            // Log the error but don't output it to the user
            error_log("Database connection error: " . $exception->getMessage());
            
            // Try to create the database if it doesn't exist
            $this->createDatabase();
            
            // Try to reconnect after creating database
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->exec("set names utf8");
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                error_log("Successfully connected to newly created database");
            } catch(PDOException $e) {
                error_log("Failed to connect after creating database: " . $e->getMessage());
                return null;
            }
        }

        return $this->conn;
    }
    
    // Create the database using the SQL file
    private function createDatabase() {
        try {
            // Connect without specifying a database
            $tempConn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create the database if it doesn't exist
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "`");
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
            } else {
                error_log("SQL file not found: " . $sqlFile);
            }
            
        } catch (PDOException $e) {
            error_log("Error creating database: " . $e->getMessage());
        }
    }
    
    // For local development/testing when remote DB fails
    public function useLocalConnection() {
        // Just use the same connection since we're already configuring for local use
        return $this->getConnection();
    }
}
?>