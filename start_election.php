<?php
// start_election.php

session_start();
require 'db_connection.php';
require 'vendor/autoload.php'; // Подключение PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    if ($election && $election['status'] == 'inactive') {
        // Обновление статуса выборов на 'active' и установка времени начала
        $stmt_update = $pdo->prepare("UPDATE elections SET status = 'active', start_time = NOW() WHERE id = ?");
        $stmt_update->execute([$election_id]);

        $command = "php send_emails.php -e $election_id > /dev/null 2>&1 &";
        exec($command);

        $_SESSION['message'] = "Выборы созданы, рассылка писем начата в фоне.";
        header('Location: admin_dashboard.php');
    exit();
    }
}

header('Location: admin_dashboard.php');
exit();
?>