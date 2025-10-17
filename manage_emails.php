<?php
// manage_emails.php - доступно только суперпользователю
ini_set('log_errors', 'On');
ini_set('error_log', 'php-error.log');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'Off');

require 'db_connection.php';
require 'vendor/autoload.php'; // подключение PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['is_superuser']) {
    header('Location: admin_login.php');
    exit();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']; // Действие (добавить, заменить, повторная отправка)
    $new_email = $_POST['new_email'] ?? '';
    $old_email = $_POST['old_email'] ?? '';
    $election_id = $_POST['election_id'] ?? null;

    if ($action == 'add') {
        // Проверка, что новый email заполнен
        if (empty($new_email) || empty($election_id)) {
            echo "Введите новый email и ID голосования.";
            exit();
        }

        // Добавление нового студента в базу данных
        $stmt_add_student = $pdo->prepare("INSERT INTO students (email) VALUES (?)");
        $stmt_add_student->execute([$new_email]);
        $student_id = $pdo->lastInsertId();

        // Генерация нового токена для нового студента
        $token = bin2hex(random_bytes(16));

        // Добавление токена для голосования
        $stmt_add_token = $pdo->prepare("INSERT INTO tokens (election_id, student_id, token, used) VALUES (?, ?, ?, FALSE)");
        $stmt_add_token->execute([$election_id, $student_id, $token]);

        // Отправка письма с ссылкой для голосования
        sendVotingEmail($new_email, $token);
        echo "Новая почта добавлена и письмо отправлено.";

    } elseif ($action == 'replace') {
        // Замена старого email на новый
        if (empty($old_email) || empty($new_email)) {
            echo "Введите старую и новую почту для замены.";
            exit();
        }

        // Поиск студента с указанной старой почтой
        $stmt_find_student = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt_find_student->execute([$old_email]);
        $student = $stmt_find_student->fetch();

        if ($student) {
            $student_id = $student['id'];

            // Обновление email студента
            $stmt_update_email = $pdo->prepare("UPDATE students SET email = ? WHERE id = ?");
            $stmt_update_email->execute([$new_email, $student_id]);

            // Получение существующего токена для студента и голосования
            $stmt_token = $pdo->prepare("SELECT token FROM tokens WHERE student_id = ?");
            $stmt_token->execute([$student_id]);
            $tokenData = $stmt_token->fetch();

            if ($tokenData) {
                // Отправка письма с новой ссылкой для голосования
                sendVotingEmail($new_email, $tokenData['token']);
                echo "Почта успешно заменена и письмо отправлено.";
            } else {
                echo "Ошибка при получении токена для нового email.";
            }
        } else {
            echo "Старый email не найден.";
        }

    } elseif ($action == 'resend') {
        // Повторная отправка письма на существующий email
        if (empty($new_email)) {
            echo "Введите email для повторной отправки.";
            exit();
        }

        // Поиск студента с указанным email
        $stmt_find_student = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt_find_student->execute([$new_email]);
        $student = $stmt_find_student->fetch();

        if ($student) {
            $student_id = $student['id'];

            // Получение существующего токена для студента
            $stmt_token = $pdo->prepare("SELECT token FROM tokens WHERE student_id = ?");
            $stmt_token->execute([$student_id]);
            $tokenData = $stmt_token->fetch();

            if ($tokenData) {
                // Отправка письма с существующей ссылкой для голосования
                sendVotingEmail($new_email, $tokenData['token']);
                echo "Письмо повторно отправлено.";
            } else {
                echo "Токен не найден для указанного email.";
            }
        } else {
            echo "Email не найден.";
        }
    }
}

// Функция для отправки письма с голосованием с использованием PHPMailer
function sendVotingEmail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        // Настройки отправки (используйте свои настройки SMTP)
        $mail->isSMTP();
        $mail->Host = 'smtp.mail.selcloud.ru';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 1126;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Адрес и имя отправителя
        $mail->setFrom('no-reply@vote.studsovet.msu.ru', 'Студенческий Совет МГУ');

        // Получатель
        $mail->addAddress($email);

        // Тема письма
        $mail->Subject = 'Ваше приглашение на выборы в студенческий совет';

        // Тело письма с кнопкой для голосования
        $mail->Body = '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 100px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 24px;
            color: #333333;
            text-align: center;
        }

        p {
            font-size: 16px;
            color: #333333;
            line-height: 1.6;
        }

        .button-container {
            text-align: center;
            margin: 20px 0;
        }

        .button {
            background-color: rgb(49, 36, 229);
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
        }

        .button:hover {
            background-color: rgb(111, 66, 244);
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #777777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="https://vote.studsovet.msu.ru/logo.png" alt="Логотип Студенческого Совета">
        </div>
        <h1>Уважаемый избиратель, здравствуйте!</h1>
        <p>На вашем курсе проводятся выборы в студенческий совет.</p>
        <p>Для голосования используйте следующую ссылку:</p>
        <div class="button-container">
            <a href="https://vote.studsovet.msu.ru/vote.php?token=' . $token . '" class="button">Проголосовать</a>
        </div>
        <p>С уважением,<br>Избирательная комиссия Студенческого совета МГУ<br>и комитет по цифровому развитию</p>
        <div class="footer">
            <p>Связь с нами:</p>
            <p><a href="https://vk.com/">Технические вопросы</a></p>
            <p><a href="https://t.me/">Вопросы о проведении выборов/Сообщить о нарушениях</a></p>
        </div>
    </div>
</body>
</html>';

        // Устанавливаем HTML-формат письма
        $mail->isHTML(true);

        // Отправляем письмо
        $mail->send();
        echo "Письмо отправлено: {$email}\n";
    } catch (Exception $e) {
        echo "Ошибка при отправке письма: {$email} ({$mail->ErrorInfo})\n";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление email-адресами</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            text-align: center;
            padding: 50px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"], input[type="email"] {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            background-color: rgb(49, 36, 229);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: rgb(111, 66, 244);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Добавить новую почту для голосования</h2>
        <form action="" method="POST">
            <label for="election_id">ID голосования:</label><br>
            <input type="text" id="election_id" name="election_id" required><br><br>
            <input type="email" name="new_email" placeholder="Новая почта" required><br>
            <input type="hidden" name="action" value="add">
            <input type="submit" value="Добавить и отправить письмо">
        </form>
    </div>

    <div class="container">
        <h2>Заменить старую почту на новую</h2>
        <form action="" method="POST">
            <input type="email" name="old_email" placeholder="Старая почта" required><br>
            <input type="email" name="new_email" placeholder="Новая почта" required><br>
            <input type="hidden" name="action" value="replace">
            <input type="submit" value="Заменить и отправить письмо">
        </form>
    </div>

    <div class="container">
        <h2>Повторная отправка письма</h2>
        <form action="" method="POST">
            <input type="email" name="new_email" placeholder="Email для повторной отправки" required><br>
            <input type="hidden" name="action" value="resend">
            <input type="submit" value="Повторно отправить письмо">
        </form>
    </div>
</body>
</html>
