<?php
// end_election.php

session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $election_id = $_POST['election_id'];

    // Проверка, что выборы принадлежат факультету администратора
    $faculty = $_SESSION['admin_faculty'];
    $stmt_check = $pdo->prepare("SELECT * FROM elections WHERE id = ? AND faculty = ?");
    $stmt_check->execute([$election_id, $faculty]);
    $election = $stmt_check->fetch();

    if ($election && $election['status'] == 'active') {
        // Обновление статуса выборов на 'completed' и установка времени окончания
        $stmt_update = $pdo->prepare("UPDATE elections SET status = 'completed', end_time = NOW() WHERE id = ?");
        $stmt_update->execute([$election_id]);

        echo "Голосование завершено.";
    } else {
        echo "Неверный запрос или выборы уже завершены.";
    }
}

header('Location: admin_dashboard.php');
exit();
?>
