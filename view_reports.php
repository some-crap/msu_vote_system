<?php
// view_reports.php

session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

$faculty = $_SESSION['admin_faculty'];
$role = $_SESSION['admin_role'];
if ($role == "commission"){
    $stmt_elections = $pdo->prepare("SELECT * FROM elections WHERE 1");
    $stmt_elections->execute();
    $elections = $stmt_elections->fetchAll();
} else {
$stmt_elections = $pdo->prepare("SELECT * FROM elections WHERE faculty = ?");
$stmt_elections->execute([$faculty]);
$elections = $stmt_elections->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёты по выборам</title>
    <style>
        /* Стили оформления */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }

        h1, h2, h3 {
            color: rgb(49, 36, 229);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: rgb(49, 36, 229);
            color: #fff;
        }

        .button {
            background-color: rgb(49, 36, 229);
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            margin: 20px 0;
            cursor: pointer;
            border-radius: 5px;
            display: inline-block;
        }

        .button:hover {
            background-color: rgb(111, 66, 244);
        }
    </style>
</head>
<body>
    <h1>Отчёты по выборам</h1>
    <?php foreach ($elections as $election): ?>
        <?php if ($election['status'] != 'completed') {
            echo "<h2>Выборы ID {$election['id']} (Курс {$election['course']}), Факультет {$election['faculty']}</h2>";
            echo "<p>Результаты будут доступны после завершения голосования.</p>";
            continue;
        } ?>
        <h2>Выборы ID <?php echo htmlspecialchars($election['id']); ?> (Курс <?php echo htmlspecialchars($election['course']); ?>), Факультет <?php echo htmlspecialchars($election['faculty']); ?>)</h2>
        <?php
        $election_id = $election['id'];

        // Общее количество проголосовавших
        $stmt_votes = $pdo->prepare("SELECT COUNT(*) AS total_votes FROM tokens WHERE election_id = ? AND used = TRUE");
        $stmt_votes->execute([$election_id]);
        $total_votes = $stmt_votes->fetchColumn();
        echo "<p><strong>Общее количество проголосовавших:</strong> $total_votes</p>";

        // Голоса за кандидатов
        $stmt_results = $pdo->prepare("
            SELECT c.name, COUNT(v.id) AS vote_count
            FROM votes v
            JOIN candidates c ON v.candidate_id = c.id
            WHERE v.election_id = ?
            GROUP BY v.candidate_id
            ORDER BY vote_count DESC
        ");
        $stmt_results->execute([$election_id]);
        $results = $stmt_results->fetchAll();
        if($election['allow_abstain'] == "1"){
        $stmt_results_no_vote = $pdo->prepare("
            SELECT COUNT(option_name) AS no_vote
            FROM vote_options
            WHERE election_id = ? AND option_name = 'Воздержаться'

        ");
        $stmt_results_no_vote->execute([$election_id]);
        $results_no_vote = $stmt_results_no_vote->fetchAll();
        }

        if($election['against_all'] == "1"){
        $stmt_results_against_all = $pdo->prepare("
            SELECT COUNT(option_name) AS no_vote
            FROM vote_options
            WHERE election_id = ? AND option_name = 'Против всех'
        ");
        $stmt_results_against_all->execute([$election_id]);
        $results_against_all = $stmt_results_against_all->fetchAll();
        }

        if ($results):
        ?>
            <h3>Результаты по кандидатам</h3>
            <table>
                <tr>
                    <th>Кандидат</th>
                    <th>Голосов</th>
                </tr>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['name']); ?></td>
                        <td><?php echo $result['vote_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if($election['allow_abstain'] == "1"): ?>
                    <tr>
                    <?php foreach ($results_no_vote as $result_no_vote): ?>
                        <td>Воздержались</td>
                        <td><?php echo $result_no_vote['no_vote']; ?></td>
                    <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
                <?php if($election['against_all'] == "1"): ?>
                    <tr>
                    <?php foreach ($results_against_all as $result_against_all): ?>
                        <td>Против всех</td>
                        <td><?php echo $result_against_all['no_vote']; ?></td>
                    <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </table>
        <?php else: ?>
            <p>Нет голосов за кандидатов.</p>
        <?php endif; ?>

        <!-- Кнопка для скачивания протокола -->
        <form action="generate_protocol.php" method="post">
            <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
            <input type="submit" value="Скачать протокол" class="button">
        </form>

    <?php endforeach; ?>
</body>
</html>
