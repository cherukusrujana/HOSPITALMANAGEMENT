<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'doctor') {
    header('Location: /hospital/auth/login.php');
    exit();
}

$success_message = $error_message = '';

// Handle reply submission
if (isset($_POST['reply_message']) && isset($_POST['sender_id']) && isset($_POST['subject']) && isset($_POST['message'])) {
    $sender_id = filter_input(INPUT_POST, 'sender_id', FILTER_SANITIZE_NUMBER_INT);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (empty($subject) || empty($message)) {
        $error_message = 'Subject and message are required.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $sender_id, $subject, $message])) {
                $pdo->commit();
                $success_message = 'Reply sent successfully!';
            } else {
                throw new PDOException('Failed to send reply');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Error sending reply: ' . $e->getMessage());
            $error_message = 'Failed to send reply. Please try again.';
        }
    }
}

// Mark message as read
if (isset($_POST['mark_read']) && isset($_POST['message_id'])) {
    $message_id = filter_input(INPUT_POST, 'message_id', FILTER_SANITIZE_NUMBER_INT);
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
        if ($stmt->execute([$message_id, $_SESSION['user_id']])) {
            $success_message = 'Message marked as read.';
        }
    } catch (PDOException $e) {
        error_log('Error marking message as read: ' . $e->getMessage());
        $error_message = 'Failed to update message status.';
    }
}

// Fetch all messages for the doctor
try {
    $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name, u.email as sender_email
                          FROM messages m 
                          JOIN users u ON m.sender_id = u.id 
                          WHERE m.receiver_id = ? 
                          ORDER BY m.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching messages: ' . $e->getMessage());
    $error_message = 'Failed to load messages.';
    $messages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Messages</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #007bff, #0056b3);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-header {
            color: white;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 2.5em;
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            margin-bottom: 40px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            padding: 25px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 30px;
        }

        .list-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .list-group-item {
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .list-group-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .list-group-item-primary {
            background: rgba(0, 123, 255, 0.05);
            border-left: 4px solid #007bff;
        }

        .message-subject {
            color: #003366;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .message-time {
            color: #6c757d;
            font-size: 14px;
        }

        .message-content {
            color: #444;
            line-height: 1.6;
            margin: 15px 0;
        }

        .message-sender {
            color: #666;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            margin-right: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 6px rgba(23, 162, 184, 0.2);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(23, 162, 184, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            border: none;
            padding: 20px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        @media (max-width: 768px) {
            .welcome-header {
                font-size: 2em;
            }

            .card-header {
                padding: 20px;
            }

            .card-body {
                padding: 20px;
            }

            .list-group-item {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="welcome-header">Messages</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">My Messages</div>
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <p class="text-muted">No messages found.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($messages as $message): ?>
                            <div class="list-group-item <?php echo $message['is_read'] ? '' : 'list-group-item-primary'; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                    <small class="message-time">
                                        <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="message-content"><?php echo nl2br(htmlspecialchars(substr($message['message'], 0, 100))); ?>...</p>
                                <small class="message-sender">From: <?php echo htmlspecialchars($message['sender_name']); ?></small>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                            data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                        View Details
                                    </button>
                                    <?php if (!$message['is_read']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-primary">Mark as Read</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Message Modals -->
    <?php foreach ($messages as $message): ?>
        <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo htmlspecialchars($message['subject']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="message-content"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        <p class="message-sender">From: <?php echo htmlspecialchars($message['sender_name']); ?></p>
                        <small class="message-time"><?php echo date('F d, Y H:i:s', strtotime($message['created_at'])); ?></small>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3">Reply to this message</h6>
                        <form method="POST">
                            <input type="hidden" name="sender_id" value="<?php echo $message['sender_id']; ?>">
                            <div class="mb-3">
                                <label for="subject<?php echo $message['id']; ?>" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject<?php echo $message['id']; ?>" 
                                       name="subject" value="Re: <?php echo htmlspecialchars($message['subject']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="message<?php echo $message['id']; ?>" class="form-label">Message</label>
                                <textarea class="form-control" id="message<?php echo $message['id']; ?>" 
                                          name="message" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="reply_message" class="btn btn-primary">Send Reply</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <style>
        /* Add these styles to your existing CSS */
        .modal-body hr {
            border-color: rgba(0, 0, 0, 0.1);
            margin: 25px 0;
        }

        .form-label {
            color: #003366;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background-color: white;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .mb-3 {
            margin-bottom: 20px;
        }
    </style>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>