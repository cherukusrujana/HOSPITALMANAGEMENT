<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'user') {
    header('Location: /hospital/auth/login.php');
    exit();
}

// Fetch user's medical history including appointments and prescriptions
$stmt = $pdo->prepare("SELECT a.*, d.specialization, u.full_name as doctor_name,
                             p.medication, p.dosage, p.instructions
                      FROM appointments a
                      LEFT JOIN doctors d ON a.doctor_id = d.id
                      LEFT JOIN users u ON d.user_id = u.id
                      LEFT JOIN prescriptions p ON a.id = p.appointment_id
                      WHERE a.user_id = ?
                      ORDER BY a.appointment_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$medical_history = $stmt->fetchAll();
?>