<?php
session_start();
include 'db.php';
 
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'requester') {
    echo "<script>location.href='login.php';</script>";
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = intval($_POST['task_id']);
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'];
 
    // Check if task is completed and belongs to requester
    $check_stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND requester_id = ? AND status = 'completed'");
    $check_stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO reviews (task_id, reviewer_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $task_id, $_SESSION['user_id'], $rating, $comment);
        if ($stmt->execute()) {
            echo "<script>alert('Review submitted!'); location.href='marketplace.php';</script>";
        } else {
            echo "<script>alert('Error submitting review.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid task.');</script>";
    }
    $check_stmt->close();
}
 
// To use: link from requester's marketplace or dashboard, but since no requester dashboard, assume called with ?task_id=ID
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
if (!$task_id) {
    echo "<script>location.href='marketplace.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); color: #333; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        h1 { text-align: center; color: #4a90e2; }
        form { display: flex; flex-direction: column; gap: 15px; }
        select, textarea { padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; }
        button { padding: 12px; background: #50e3c2; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #48c7a8; }
        @media (max-width: 768px) { .container { margin: 20px; padding: 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Submit Review for Task #<?php echo $task_id; ?></h1>
        <form method="POST">
            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
            <select name="rating" required>
                <option value="">Select Rating</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
            <textarea name="comment" placeholder="Comment (optional)"></textarea>
            <button type="submit">Submit Review</button>
        </form>
    </div>
</body>
</html>
