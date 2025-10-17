<?php
// admin_dashboard.php
ini_set('log_errors', 'On');
ini_set('error_log', 'php-error.log');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'Off');
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /');
    exit();
}

// Если это суперпользователь, перенаправляем на панель суперпользователя
if ($_SESSION['is_superuser']) {
    header('Location: superuser_dashboard.php');
    exit();
}

$faculty = $_SESSION['admin_faculty'];
$role = $_SESSION['admin_role'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        /* Ваши стили оформления */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            color: #333;
            text-align: center;
            padding: 20px;
        }

        h1, h2 {
            color: rgb(49, 36, 229);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .button {
            background-color: rgb(49, 36, 229);
            color: #fff;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 10px;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: rgb(49, 36, 229);
            color: #fff;
        }

        .logout {
            float: right;
            margin-top: -50px;
        }

        .message {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchVotingData() {
            $.ajax({
                url: 'fetch_voting_data.php',
                method: 'GET',
                success: function(data) {
                    try {
                        // Попробуем распарсить JSON
                        var response = JSON.parse(data);
                        
                        // Выводим ответ для отладки
                        console.log(response);

                        // Проверяем наличие данных и обновляем таблицу
                        for (var election_id in response) {
                            if (response.hasOwnProperty(election_id)) {
                                $('#voters_' + election_id).text(response[election_id].total_voters);
                                $('#voted_' + election_id).text(response[election_id].voted);
                            }
                        }
                    } catch (e) {
                        console.error('Ошибка при парсинге JSON:', e);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Ошибка AJAX-запроса:', textStatus, errorThrown);
                }
            });
        }

        // Обновляем данные каждые 5 секунд
        setInterval(fetchVotingData, 5000);
    </script>
</head>
<body>
    <div class="container">
        
        <h1>Панель администратора</h1> <a href="admin_logout.php" class="logout button">Выйти</a>

        <?php
        // Выводим сообщения, если есть
        if (isset($_SESSION['message'])) {
            echo '<p class="message">' . $_SESSION['message'] . '</p>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<p class="error">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
        <?php if ($role != "commission"): ?>
        <div class="section">
            <h2>Создать новые выборы</h2>
            <a href="set_election_form.php" class="button">Создать выборы</a>
        </div>
        <?php endif; ?>
        <div class="section">
            <h2>Управление выборами</h2>
            <?php
            // Получение выборов данного факультета
            if ($role == "commission"){
                $stmt_elections = $pdo->prepare("SELECT * FROM elections WHERE 1");
                $stmt_elections->execute();
                $elections = $stmt_elections->fetchAll();
            } else {
                $stmt_elections = $pdo->prepare("SELECT * FROM elections WHERE faculty = ?");
                $stmt_elections->execute([$faculty]);
                $elections = $stmt_elections->fetchAll();
            }

            if ($elections):
            ?>
                <table>
                    <tr>
                        <th>Выборы (ID)</th>
                        <th>Курс</th>
                        <th>Квота</th>
                        <th>Статус</th>
                        <th>Кандидаты</th>
                        <th>Всего избирателей</th>
                        <th>Проголосовало</th>
                        <?php if ($role == "commission"): ?>
                        <th>Факультет</th>
                        <?php else: ?>
                        <th>Действия</th>
                        <?php endif; ?>
                    </tr>
                    <?php foreach ($elections as $election): ?>
                        <?php
                        // Получаем количество избирателей для выборов
                        $election_id = $election['id'];
                        $stmt_total_voters = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE election_id = ?");
                        $stmt_total_voters->execute([$election_id]);
                        $total_voters = $stmt_total_voters->fetchColumn();

                        // Получаем количество проголосовавших
                        $stmt_voted = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE election_id = ? AND used = TRUE");
                        $stmt_voted->execute([$election_id]);
                        $voted = $stmt_voted->fetchColumn();

                        // Получаем список кандидатов для выборов
                        $stmt_candidates = $pdo->prepare("
                            SELECT candidates.name 
                            FROM candidates 
                            JOIN election_candidates 
                            ON candidates.id = election_candidates.candidate_id 
                            WHERE election_candidates.election_id = ?");
                        $stmt_candidates->execute([$election_id]);
                        $candidates = $stmt_candidates->fetchAll(PDO::FETCH_ASSOC);

                        // Формируем список кандидатов
                        $candidates_list = array_map(function($candidate) {
                            return $candidate['name'];
                        }, $candidates);
                        $candidates_display = implode(', ', $candidates_list);
                        ?>
                        <tr>
                            <td><?php echo $election['id']; ?></td>
                            <td><?php echo $election['course']; ?></td>
                            <td><?php echo $election['real_quota']; ?></td>
                            <td><?php echo $election['status']; ?></td>
                            <td><?php echo $candidates_display; ?></td> <!-- Список кандидатов -->
                            <?php $total_voters_temp = (int)$total_voters; $voted_temp = (int)$voted;?>

                            <td id="voters_<?php echo $election['id']; ?>"><?php echo "Почт загружено: ".$total_voters_temp."<br>Реально студентов: ".$election['students_total']; echo '<br><br>Для кворума необходимо: '.(round(($election['students_total']/2), 0, PHP_ROUND_HALF_DOWN) + 1.0); ?></td>
                            <td id="voted_<?php echo $election['id']; ?>">
                            <?php echo $voted_temp; 
                            if ((round(($election['students_total']/2), 0, PHP_ROUND_HALF_DOWN) + 1.0) <= $voted_temp){
                                echo '<br><br>Кворум набран';
                                }
                            else{
                                echo '<br><br>Кворум не набран';
                                }
                            ?></td>
                            <td>
                                <?php if ($role == "commission"): ?>
                                <?php echo $election['faculty'];?>
                                <?else:?>
                                <?php if ($election['status'] == 'inactive'): ?>
                                    <form action="start_election.php" method="post" style="display:inline;">
                                        <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                        <input type="submit" value="Начать" class="button">
                                    </form>
                                <?php elseif ($election['status'] == 'active'): ?>
                                    <form action="end_election.php" method="post" style="display:inline;">
                                        <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                        <input type="submit" value="Завершить" class="button">
                                    </form>
                                <?php elseif ($election['status'] == 'completed'): ?>
                                    <span>Завершено</span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Нет созданных выборов.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Просмотр отчетов</h2>
            <a href="view_reports.php" class="button">Посмотреть отчеты</a>
        </div>
    </div>
</body>
</html>
