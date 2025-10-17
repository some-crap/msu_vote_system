<?php
// submit_vote.php
//ini_set('log_errors', 'On');
//ini_set('error_log', 'php-error.log');
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 'Off');
require 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (empty($token) || empty($csrf_token)) {
        echo "Недействительный запрос.";
        exit();
    }

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo "Недействительный CSRF-токен.";
        exit();
    }

    // Проверка токена
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE token = ? AND used = FALSE");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch();

    if (!$tokenData) {
        echo "Токен недействителен или уже использован.";
        exit();
    }

    $election_id = $tokenData['election_id'];

    // Получение информации о выборах
    $stmt_election = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt_election->execute([$election_id]);
    $election = $stmt_election->fetch();

    // Проверка статуса выборов
    if ($election['status'] != 'active') {
        echo "Голосование недоступно.";
        exit();
    }

    // Обработка выбора
    $candidate_ids = $_POST['candidate_ids'] ?? [];
    $option = $_POST['option'] ?? '';

    // Проверка на наличие одновременного выбора кандидатов и опции "воздержаться" или "против всех"
    if ($option && !empty($candidate_ids)) {
        echo "Вы не можете выбрать кандидатов и опцию 'воздержаться' или 'против всех' одновременно.";
        exit();
    }

    // Проверка на отсутствие выбора
    if (empty($option) && empty($candidate_ids)) {
        echo "Вы не сделали выбор.";
        exit();
    }

    // Проверка квоты голосования
    if (count($candidate_ids) > $election['quota']) {
        echo "Вы превысили квоту голосования.";
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Обработка голосования за кандидатов
        if (!empty($candidate_ids)) {
            $stmt_vote = $pdo->prepare("INSERT INTO votes (election_id, candidate_id) VALUES (?, ?)");
            foreach ($candidate_ids as $candidate_id) {
                $stmt_vote->execute([$election_id, $candidate_id]);
            }
        } elseif ($option === 'abstain') {
            // Обработка опции "Воздержаться"
            $stmt_abstain_vote = $pdo->prepare("INSERT INTO vote_options (election_id, option_name) VALUES (?, 'Воздержаться')");
            $stmt_abstain_vote->execute([$election_id]);
        } elseif ($option === 'against_all') {
            // Обработка опции "Против всех"
            $stmt_against_all_vote = $pdo->prepare("INSERT INTO vote_options (election_id, option_name) VALUES (?, 'Против всех')");
            $stmt_against_all_vote->execute([$election_id]);
        }

        // Отмечаем токен как использованный
        $stmt_update_token = $pdo->prepare("UPDATE tokens SET used = TRUE WHERE token = ?");
        $stmt_update_token->execute([$token]);

        $pdo->commit();

        echo "Ваш голос успешно принят. Спасибо за участие!";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Ошибка при обработке голоса: " . $e->getMessage());
        echo "Произошла ошибка при обработке вашего голоса. Пожалуйста, попробуйте позже.";
    }
}
?>
