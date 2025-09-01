<?php
session_start();
include 'db.php';
 
if (!isset($_SESSION['user_id'])) {
    echo "<script>location.href='login.php';</script>";
    exit;
}
 
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
 
// Handle post task if requester
if ($user_type == 'requester' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $deadline = $_POST['deadline'];
    $payment = $_POST['payment'];
 
    $stmt = $conn->prepare("INSERT INTO tasks (title, description, category, deadline, payment, requester_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdi", $title, $description, $category, $deadline, $payment, $user_id);
    if ($stmt->execute()) {
        echo "<script>alert('Task posted successfully!'); location.href='marketplace.php';</script>";
    } else {
        echo "<script>alert('Error posting task.');</script>";
    }
    $stmt->close();
}
 
// Handle apply to task if worker
if ($user_type == 'worker' && isset($_GET['apply'])) {
    $task_id = intval($_GET['apply']);
    // Check if task open and not already applied
    $check_stmt = $conn->prepare("SELECT status FROM tasks WHERE id = ? AND status = 'open'");
    $check_stmt->bind_param("i", $task_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $assign_stmt = $conn->prepare("INSERT INTO task_assignments (task_id, worker_id, status) VALUES (?, ?, 'accepted')"); // Auto accept for simplicity
        $assign_stmt->bind_param("ii", $task_id, $user_id);
        if ($assign_stmt->execute()) {
            $update_stmt = $conn->prepare("UPDATE tasks SET status = 'assigned' WHERE id = ?");
            $update_stmt->bind_param("i", $task_id);
            $update_stmt->execute();
            $update_stmt->close();
            echo "<script>alert('Task assigned to you!'); location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Already applied or error.');</script>";
        }
        $assign_stmt->close();
    } else {
        echo "<script>alert('Task not available.');</script>";
    }
    $check_stmt->close();
}
 
// Fetch tasks: all open for workers, all for requesters
if ($user_type == 'worker') {
    $stmt = $conn->prepare("SELECT t.id, t.title, t.description, t.category, t.deadline, t.payment, u.username AS requester FROM tasks t JOIN users u ON t.requester_id = u.id WHERE t.status = 'open'");
} else {
    $stmt = $conn->prepare("SELECT t.id, t.title, t.description, t.category, t.deadline, t.payment, t.status FROM tasks t WHERE requester_id = ?");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Marketplace</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); color: #333; }
        header { background: #4a90e2; color: white; padding: 15px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .nav { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #50e3c2; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #48c7a8; }
        form { display: flex; flex-direction: column; gap: 15px; max-width: 600px; margin: 0 auto; }
        input, select, textarea { padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; }
        button { padding: 12px; background: #50e3c2; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .tasks { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .task-card { background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .task-card h3 { margin: 0 0 10px; color: #4a90e2; }
        @media (max-width: 768px) { .container { padding: 10px; } .tasks { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <h1>Task Marketplace</h1>
    </header>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php" class="btn">Dashboard</a>
            <a href="logout.php" class="btn" style="background: #ff4d4d;">Logout</a>
        </div>
        <?php if ($user_type == 'requester'): ?>
            <section>
                <h2>Post a New Task</h2>
                <form method="POST">
                    <input type="text" name="title" placeholder="Task Title" required>
                    <textarea name="description" placeholder="Description" required></textarea>
                    <select name="category" required>
                        <option value="data_entry">Data Entry</option>
                        <option value="surveys">Surveys</option>
                        <option value="transcription">Transcription</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="date" name="deadline" required>
                    <input type="number" name="payment" placeholder="Payment Amount" step="0.01" required>
                    <button type="submit" name="post_task">Post Task</button>
                </form>
            </section>
        <?php endif; ?>
        <section>
            <h2>Available Tasks</h2>
            <div class="tasks">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                        <p>Category: <?php echo ucfirst($task['category']); ?></p>
                        <p>Deadline: <?php echo $task['deadline']; ?></p>
                        <p>Payment: $<?php echo number_format($task['payment'], 2); ?></p>
                        <?php if (isset($task['requester'])): ?>
                            <p>Requester: <?php echo htmlspecialchars($task['requester']); ?></p>
                        <?php endif; ?>
                        <?php if ($user_type == 'worker' && $task['status'] == 'open'): ?>
                            <a href="marketplace.php?apply=<?php echo $task['id']; ?>" class="btn" style="font-size: 0.9em; padding: 8px 16px;">Apply</a>
                        <?php elseif ($user_type == 'requester'): ?>
                            <p>Status: <?php echo ucfirst($task['status']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</body>
</html>
