<?php
session_start();
include 'db.php';
 
$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($type, ['requester', 'worker'])) {
    header('Location: index.php');
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
 
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $email, $type);
    if ($stmt->execute()) {
        echo "<script>alert('Signup successful! Redirecting to login...'); location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error: Username or email already exists.');</script>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up as <?php echo ucfirst($type); ?></title>
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
        <h1>Sign Up as <?php echo ucfirst($type); ?></h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="hidden" name="type" value="<?php echo $type; ?>">
            <button type="submit">Sign Up</button>
        </form>
        <p style="text-align: center;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
