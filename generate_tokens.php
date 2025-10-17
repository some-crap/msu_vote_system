<?php
// generate_tokens.php

session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Получение ID выборов
$election_id = $_POST['election_id'];

// Получение списка студентов для выборов
$stmt_students = $pdo->prepare("SELECT * FROM students WHERE faculty = ? AND course = ?");
$stmt_students->execute([$_SESSION['admin_faculty'], $election['course']]);
$students = $stmt_students->fetchAll();

// Генерация токенов для студентов
$stmt_token = $pdo->prepare("INSERT INTO tokens (token, student_id, election_id) VALUES (?, ?, ?)");

foreach ($students as $student) {
    $token = bin2hex(random_bytes(32));
    $stmt_token->execute([$token, $student['id'], $election_id]);
}

// Перенаправление обратно на панель администратора
header('Location: admin_dashboard.php');
exit();
?>
