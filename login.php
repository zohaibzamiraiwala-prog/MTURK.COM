<?php
session_start();
include 'db.php';
 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
 
    $stmt = $conn->prepare("SELECT id, password, type FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            echo "<script>location.href='marketplace.php';</script>";
        } else {
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); color: #333; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        h1 { text-align: center; color: #4a90e2; }
        form { display: flex; flex-direction: column; gap: 15px; }
        input { padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; }
        button { padding: 12px; background: #50e3c2; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #48c7a8; }
        @media (max-width: 768px) { .container { margin: 20px; padding: 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p style="text-align: center;">Don't have an account? <a href="index.php">Sign Up</a></p>
    </div>
</body>
</html>
