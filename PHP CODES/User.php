<?php 
class User {
    private $conn;
    private $table_name = "users";
    public $id, $firstname, $middlename, $lastname, $email, $mobile_number, $password, $role, $status;
    public $employee_no, $sss_no, $pagibig_no, $philhealth_no;  // Added new employee fields

    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        // Extended query to include new employee fields (though they'll be NULL for regular users)
        $query = "INSERT INTO " . $this->table_name . " 
                (firstname, middlename, lastname, email, mobile_number, password, role, status, 
                employee_no, sss_no, pagibig_no, philhealth_no)
                VALUES 
                (:firstname, :middlename, :lastname, :email, :mobile_number, :password, :role, :status,
                :employee_no, :sss_no, :pagibig_no, :philhealth_no)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":middlename", $this->middlename); // Optional field
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":mobile_number", $this->mobile_number);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":employee_no", $this->employee_no);
        $stmt->bindParam(":sss_no", $this->sss_no);
        $stmt->bindParam(":pagibig_no", $this->pagibig_no);
        $stmt->bindParam(":philhealth_no", $this->philhealth_no);

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