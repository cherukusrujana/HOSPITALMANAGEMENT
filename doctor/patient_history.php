<?php
require_once '../includes/header.php';

if (!is_logged_in() || $_SESSION['role'] !== 'doctor') {
    header('Location: /hospital/auth/login.php');
    exit();
}

$patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_SANITIZE_NUMBER_INT);

// Fetch patient's appointment history
$stmt = $pdo->prepare("SELECT a.*, u.full_name, u.email 
                      FROM appointments a
                      JOIN users u ON a.user_id = u.id
                      WHERE a.user_id = ? AND a.doctor_id = ?
                      ORDER BY a.appointment_date DESC");
$stmt->execute([$patient_id, $doctor_id]);
$appointments = $stmt->fetchAll();
?>