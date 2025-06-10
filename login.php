<?php
// login.php - Login Form
?>
<?php
// Tambahkan baris ini di awal file register.php setelah <?php
include_once 'config.php';
include_once 'User.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Multi Access System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #333;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="form-title">Login</h2>
        
        <?php
        session_start();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            include_once 'config.php';
            
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            $user->username = $_POST['username'];
            $user->password = $_POST['password'];
            
            if ($user->login()) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['user_role'] = $user->role;
                
                // Redirect based on role
                switch ($user->role) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'vendor':
                        header("Location: vendor_dashboard.php");
                        break;
                    case 'user':
                    default:
                        header("Location: user_dashboard.php");
                        break;
                }
                exit();
            } else {
                echo '<div class="alert alert-error">Username/Email atau password salah!</div>';
            }
        }
        ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username atau Email:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="link">
            <p>Belum punya akun? <a href="register.php">Register disini</a></p>
        </div>
    </div>
</body>
</html>










