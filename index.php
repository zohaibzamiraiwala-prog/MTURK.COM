<?php
session_start();
include 'db.php';
 
// Fetch featured tasks (open tasks, limit 5)
$stmt = $conn->prepare("SELECT t.id, t.title, t.description, t.payment, u.username AS requester FROM tasks t JOIN users u ON t.requester_id = u.id WHERE t.status = 'open' LIMIT 5");
$stmt->execute();
$featured_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroTask Platform - Home</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); color: #333; }
        header { background: #4a90e2; color: white; padding: 20px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        h1 { font-size: 2.5em; margin: 0; }
        p { font-size: 1.2em; line-height: 1.6; }
        .btn { display: inline-block; padding: 12px 24px; background: #50e3c2; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s, transform 0.3s; }
        .btn:hover { background: #48c7a8; transform: translateY(-2px); }
        .signup-options { display: flex; justify-content: center; gap: 20px; margin: 20px 0; }
        .featured { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .task-card { background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: box-shadow 0.3s; }
        .task-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .task-card h3 { margin: 0 0 10px; color: #4a90e2; }
        footer { text-align: center; padding: 10px; background: #4a90e2; color: white; margin-top: 20px; }
        @media (max-width: 768px) { .container { padding: 10px; } h1 { font-size: 2em; } .btn { padding: 10px 20px; } }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to MicroTask Platform</h1>
        <p>Earn money by completing small tasks or post tasks to get things done efficiently!</p>
    </header>
    <div class="container">
        <section>
            <h2>How It Works</h2>
            <p>As a worker, browse tasks, complete them, and get paid. As a requester, post tasks and pay only when completed satisfactorily.</p>
            <div class="signup-options">
                <a href="signup.php?type=requester" class="btn">Sign Up as Requester</a>
                <a href="signup.php?type=worker" class="btn">Sign Up as Worker</a>
                <a href="login.php" class="btn" style="background: #7ed321;">Login</a>
            </div>
        </section>
        <section>
            <h2>Featured Tasks</h2>
            <div class="featured">
                <?php foreach ($featured_tasks as $task): ?>
                    <div class="task-card">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars(substr($task['description'], 0, 100))); ?>...</p>
                        <p>Payment: $<?php echo number_format($task['payment'], 2); ?></p>
                        <p>By: <?php echo htmlspecialchars($task['requester']); ?></p>
                        <a href="marketplace.php" class="btn" style="font-size: 0.9em; padding: 8px 16px;">View More</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <footer>&copy; 2025 MicroTask Platform</footer>
</body>
</html>
