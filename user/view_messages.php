<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit();
}

// Fetch all messages sent by the user
$stmt = $pdo->prepare("SELECT m.*, u.full_name as receiver_name, u.role as receiver_role, u.email as receiver_email 
                      FROM messages m 
                      JOIN users u ON m.receiver_id = u.id 
                      WHERE m.sender_id = ? 
                      ORDER BY m.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$sent_messages = $stmt->fetchAll();

// Fetch all messages received by the user
$stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name, u.role as sender_role, u.email as sender_email 
                      FROM messages m 
                      JOIN users u ON m.sender_id = u.id 
                      WHERE m.receiver_id = ? 
                      ORDER BY m.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$received_messages = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="messagesTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="received-tab" data-bs-toggle="tab" href="#received" role="tab">Received Messages</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="sent-tab" data-bs-toggle="tab" href="#sent" role="tab">Sent Messages</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="messagesContent">
                <div class="tab-pane fade show active" id="received" role="tabpanel">
                    <?php if (empty($received_messages)): ?>
                        <p class="text-muted">No messages received.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($received_messages as $message): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($message['message'], 0, 100))); ?>...</p>
                                    <small class="text-muted">
                                        From: <?php 
                                            echo $message['sender_role'] === 'doctor' ? 'Dr. ' : '';
                                            echo htmlspecialchars($message['sender_name']); 
                                            echo $message['sender_role'] === 'admin' ? ' (Admin)' : '';
                                        ?>
                                    </small>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                                data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>

                                <!-- Message Modal -->
                                <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>From:</strong> 
                                                    <?php 
                                                        echo $message['sender_role'] === 'doctor' ? 'Dr. ' : '';
                                                        echo htmlspecialchars($message['sender_name']); 
                                                        echo $message['sender_role'] === 'admin' ? ' (Admin)' : '';
                                                    ?>
                                                    <br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($message['sender_email']); ?>
                                                    <br>
                                                    <strong>Date:</strong> <?php echo date('F d, Y H:i:s', strtotime($message['created_at'])); ?>
                                                </div>
                                                <div class="message-content">
                                                    <strong>Message:</strong>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="sent" role="tabpanel">
                    <?php if (empty($sent_messages)): ?>
                        <p class="text-muted">No messages sent.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($sent_messages as $message): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($message['message'], 0, 100))); ?>...</p>
                                    <small class="text-muted">
                                        To: <?php 
                                            echo $message['receiver_role'] === 'doctor' ? 'Dr. ' : '';
                                            echo htmlspecialchars($message['receiver_name']); 
                                            echo $message['receiver_role'] === 'admin' ? ' (Admin)' : '';
                                        ?>
                                    </small>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                                data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>

                                <!-- Message Modal -->
                                <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>To:</strong> 
                                                    <?php 
                                                        echo $message['receiver_role'] === 'doctor' ? 'Dr. ' : '';
                                                        echo htmlspecialchars($message['receiver_name']); 
                                                        echo $message['receiver_role'] === 'admin' ? ' (Admin)' : '';
                                                    ?>
                                                    <br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($message['receiver_email']); ?>
                                                    <br>
                                                    <strong>Date:</strong> <?php echo date('F d, Y H:i:s', strtotime($message['created_at'])); ?>
                                                </div>
                                                <div class="message-content">
                                                    <strong>Message:</strong>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>