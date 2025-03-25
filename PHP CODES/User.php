<?php 
class User {
    private $conn;
    private $table_name = "users";
    public $id, $firstname, $middlename, $lastname, $email, $mobile_number, $password, $role, $status;

    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (firstname, middlename, lastname, email, mobile_number, password, role, status)
                VALUES (:firstname, :middlename, :lastname, :email, :mobile_number, :password, :role, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":middlename", $this->middlename); // Optional field
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":mobile_number", $this->mobile_number);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "User registered successfully. Please verify your account."];
        } else {
            return ["success" => false, "message" => "User registration failed. Please try again."];
        }
    }

    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>