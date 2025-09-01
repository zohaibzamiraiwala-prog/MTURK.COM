<?php
session_start();
include 'db.php';
 
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'worker') {
    echo "<script>location.href='login.php';</script>";
    exit;
}
 
$user_id = $_SESSION['user_id'];
 
// Handle complete task
if (isset($_GET['complete'])) {
    $assignment_id = intval($_GET['complete']);
    $stmt = $conn->prepare("SELECT ta.task_id, t.payment, t.requester_id FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.id = ? AND ta.worker_id = ? AND ta.status = 'accepted'");
    $stmt->bind_param("ii", $assignment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($task = $result->fetch_assoc()) {
        // Update assignment to completed
        $update_assign = $conn->prepare("UPDATE task_assignments SET status = 'completed', completion_date = NOW() WHERE id = ?");
        $update_assign->bind_param("i", $assignment_id);
        $update_assign->execute();
        $update_assign->close();
 
        // Update task to completed
        $update_task = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
        $update_task->bind_param("i", $task['task_id']);
        $update_task->execute();
        $update_task->close();
 
        // Add earning to worker balance
        $update_balance = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $update_balance->bind_param("di", $task['payment'], $user_id);
        $update_balance->execute();
        $update_balance->close();
 
        // Subtract from requester (assume they have balance)
        $update_requester = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $update_requester->bind_param("di", $task['payment'], $task['requester_id']);
        $update_requester->execute();
        $update_requester->close();
 
        // Log transaction
        $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, 'earning')");
        $trans_stmt->bind_param("id", $user_id, $task['payment']);
        $trans_stmt->execute();
        $trans_stmt->close();
 
        echo "<script>alert('Task completed! Earnings added.'); location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Invalid task.');</script>";
    }
    $stmt->close();
}
 
// Handle withdrawal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw'])) {
    $amount = floatval($_POST['amount']);
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $balance = $stmt->get_result()->fetch_assoc()['balance'];
    $stmt->close();
 
    if ($amount > 0 && $amount <= $balance) {
        $update = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $update->bind_param("di", $amount, $user_id);
        $update->execute();
        $update->close();
 
        $trans = $conn->prepare("INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, 'withdrawal')");
        $trans->bind_param("id", $user_id, $amount);
        $trans->execute();
        $trans->close();
 
        echo "<script>alert('Withdrawal processed!'); location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Invalid amount.');</script>";
    }
}
 
// Fetch worker's tasks
$stmt = $conn->prepare("SELECT ta.id AS assign_id, ta.status, t.id AS task_id, t.title, t.payment, t.deadline, r.rating, r.comment FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id LEFT JOIN reviews r ON t.id = r.task_id AND r.reviewer_id = t.requester_id WHERE ta.worker_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
 
// Fetch balance and earnings summary
$balance_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$balance_stmt->bind_param("i", $user_id);
$balance_stmt->execute();
$balance = $balance_stmt->get_result()->fetch_assoc()['balance'];
$balance_stmt->close();
 
$earnings_stmt = $conn->prepare("SELECT SUM(amount) AS total_earnings FROM transactions WHERE user_id = ? AND type = 'earning'");
$earnings_stmt->bind_param("i", $user_id);
$earnings_stmt->execute();
$total_earnings = $earnings_stmt->get_result()->fetch_assoc()['total_earnings'] ?? 0;
$earnings_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); color: #333; }
        header { background: #4a90e2; color: white; padding: 15px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .nav { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #50e3c2; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #48c7a8; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .summary div { background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); text-align: center; width: 30%; }
        .tasks { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .task-card { background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .task-card h3 { margin: 0 0 10px; color: #4a90e2; }
        form { max-width: 400px; margin: 20px auto; display: flex; flex-direction: column; gap: 15px; }
        input { padding: 12px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 12px; background: #50e3c2; color: white; border: none; border-radius: 5px; cursor: pointer; }
        @media (max-width: 768px) { .summary { flex-direction: column; gap: 15px; } .summary div { width: 100%; } .tasks { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <h1>Worker Dashboard</h1>
    </header>
    <div class="container">
        <div class="nav">
            <a href="marketplace.php" class="btn">Marketplace</a>
            <a href="logout.php" class="btn" style="background: #ff4d4d;">Logout</a>
        </div>
        <section class="summary">
            <div>
                <h2>Balance</h2>
                <p>$<?php echo number_format($balance, 2); ?></p>
            </div>
            <div>
                <h2>Total Earnings</h2>
                <p>$<?php echo number_format($total_earnings, 2); ?></p>
            </div>
        </section>
        <section>
            <h2>Withdraw Earnings</h2>
            <form method="POST">
                <input type="number" name="amount" placeholder="Amount to Withdraw" step="0.01" required>
                <button type="submit" name="withdraw">Withdraw</button>
            </form>
        </section>
        <section>
            <h2>Your Tasks</h2>
            <div class="tasks">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p>Payment: $<?php echo number_format($task['payment'], 2); ?></p>
                        <p>Deadline: <?php echo $task['deadline']; ?></p>
                        <p>Status: <?php echo ucfirst($task['status']); ?></p>
                        <?php if ($task['status'] == 'accepted'): ?>
                            <a href="dashboard.php?complete=<?php echo $task['assign_id']; ?>" class="btn" style="font-size: 0.9em; padding: 8px 16px;">Complete Task</a>
                        <?php endif; ?>
                        <?php if ($task['status'] == 'completed' && $task['rating']): ?>
                            <p>Rating: <?php echo $task['rating']; ?>/5</p>
                            <p>Comment: <?php echo htmlspecialchars($task['comment'] ?? 'No comment'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</body>
</html>
