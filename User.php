<?php
/**
 * File: User.php
 * Deskripsi: Kelas untuk mengelola operasi terkait pengguna.
 */
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password; // This will hold the hashed password from the DB
    public $role;
    public $created_at;
    public $status; 
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                    SET username=:username, email=:email, password=:password, role=:role, created_at=NOW()";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT); // Hash password

        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password); // Bind the hashed password
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login() {
        // Query untuk mengambil user berdasarkan username atau email
        // Menggunakan placeholder yang berbeda untuk username dan email, meskipun nilainya sama
        $query = "SELECT id, username, email, password, role, status, last_login 
                  FROM " . $this->table_name . " 
                  WHERE username = :username_param OR email = :email_param
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        
        // Sanitize input. We assume the login form field (e.g., 'username')
        // can accept either a username or an email.
        $identifier_input = htmlspecialchars(strip_tags($this->username)); // Use a local variable for clarity

        // Bind values by passing an associative array to execute()
        // Provide distinct keys for each placeholder.
        if (!$stmt->execute([
            ':username_param' => $identifier_input,
            ':email_param' => $identifier_input
        ])) { // This is now line 64
            // Log the error if execution fails
            error_log("Login query execution failed: " . implode(" ", $stmt->errorInfo()));
            return false;
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Populate object properties with data from the database
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password']; // This is the hashed password from the database
            $this->role = $row['role'];
            $this->status = $row['status'] ?? 'active'; // Default to 'active' if status is null in DB
            $this->last_login = $row['last_login'];

            // Verify password and check user status
            // The plain text password comes from $_POST['password'] in your login script
            if (password_verify($_POST['password'], $this->password)) {
                // Ensure user is active before successful login
                if ($this->status === 'active') {
                    // Update last_login timestamp
                    $update_query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(':id', $this->id);
                    
                    if ($update_stmt->execute()) {
                        return true; // Login successful and last_login updated
                    } else {
                        // Log error if last_login update fails, but still allow login
                        error_log("Failed to update last_login for user ID: " . $this->id . " Error: " . implode(" ", $update_stmt->errorInfo()));
                        return true; // Login successful despite update failure
                    }
                } else {
                    // User is not active
                    return false;
                }
            }
        }
        return false; // Username/email not found or password incorrect
    }

    // Check if username or email exists
    public function userExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                    WHERE username = :username OR email = :email LIMIT 0,1"; 
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Pass an associative array to execute for consistency and correctness
        if (!$stmt->execute([':username' => $this->username, ':email' => $this->email])) {
            error_log("User exists query failed: " . implode(" ", $stmt->errorInfo()));
            return false;
        }
        
        return $stmt->rowCount() > 0;
    }

    // Fetch user by ID
    public function getById($id) {
        $query = "SELECT id, username, email, role, status, created_at, last_login FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        
        if (!$stmt->execute()) {
            error_log("Get user by ID query failed: " . implode(" ", $stmt->errorInfo()));
            return false;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            return true;
        }
        return false;
    }
}
?>
