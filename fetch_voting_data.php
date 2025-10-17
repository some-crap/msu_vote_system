<?php
// fetch_voting_data.php

session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

$faculty = $_SESSION['admin_faculty'];

// Получаем все выборы для данного факультета
$stmt_elections = $pdo->prepare("SELECT id FROM elections WHERE faculty = ?");
$stmt_elections->execute([$faculty]);
$elections = $stmt_elections->fetchAll(PDO::FETCH_ASSOC);

$response = [];

foreach ($elections as $election) {
    $election_id = $election['id'];

    // Общее количество избирателей для данного голосования
    $stmt_total_voters = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE election_id = ?");
    $stmt_total_voters->execute([$election_id]);
    $total_voters = $stmt_total_voters->fetchColumn();

    // Количество проголосовавших
    $stmt_voted = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE election_id = ? AND used = TRUE");
    $stmt_voted->execute([$election_id]);
    $voted = $stmt_voted->fetchColumn();

    // Формируем массив данных для ответа
    $response[$election_id] = [
        'total_voters' => $total_voters,
        'voted' => $voted
    ];
}

// Возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
