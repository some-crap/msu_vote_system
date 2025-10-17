<?php
// send_emails.php

ini_set('log_errors', 'On');
ini_set('error_log', 'php-error.log');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'Off');

require 'db_connection.php';
require 'vendor/autoload.php'; // Подключаем PHPMailer и другие зависимости

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (php_sapi_name() !== 'cli') {
    die('Этот скрипт можно запускать только из командной строки.');
}

// Получаем ID выборов из аргументов командной строки
$options = getopt("e:");
$election_id = $options['e'] ?? null;

if (!$election_id) {
    die('Не указан ID выборов.');
}

// Получаем список избирателей и их токены для голосования
$stmt = $pdo->prepare("
    SELECT students.email, tokens.token, elections.course, elections.faculty 
    FROM students 
    JOIN tokens ON students.id = tokens.student_id 
    JOIN elections ON tokens.election_id = elections.id 
    WHERE elections.id = ? AND tokens.used = 0
");
$stmt->execute([$election_id]);
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверяем, что есть избиратели
if (empty($voters)) {
    die("Нет избирателей для выборов с ID $election_id.");
}

foreach ($voters as $voter) {
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
        $mail->addAddress($voter['email']);

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
            <!-- Замените src на путь к вашему логотипу -->
            <img src="https://vote.studsovet.msu.ru/logo.png" alt="Логотип Студенческого Совета">
        </div>
        <h1>Уважаемый избиратель, здравствуйте!</h1>
        <p>На вашем курсе проводятся выборы в студенческий совет.</p>
        <p>Для голосования используйте следующую ссылку:</p>
        <div class="button-container">
            <a href="https://vote.studsovet.msu.ru/vote.php?token='.$voter["token"].'" class="button">Проголосовать</a>
        </div>
        <p>С уважением,<br>Избирательная комиссия Студенческого совета МГУ<br>и комитет по цифровому развитию</p>
        <div class="footer">
            <p>Связь с нами:</p>
            <p><a href="https://vk.com/">Технические вопросы</a></p>
            <p><a href="https://t.me/">Вопросы о проведении выборов/Сообщить о нарушениях</a></p>
        </div>
    </div>
</body>
</html>
'
/*"
            <html>
            <head>
                <title>Выборы в студенческий совет</title>
            </head>
            <body>
                <p>Уважаемый избиратель, здравствуйте!</p>
                <p>На вашем курсе проводятся выборы в студенческий совет.</p>
                <p>Для голосования используйте следующую ссылку:</p>
                <a href='https://vote.studsovet.msu.ru/vote.php?token={$voter['token']}' style='background-color: rgb(49, 36, 229); color: white; padding: 10px 20px; text-decoration: none;'>Проголосовать</a>
                <p>С уважением,<br>избирательная комиссия Студенческого совета МГУ</p>
            </body>
            </html>
        "*/;

        // Устанавливаем HTML-формат письма
        $mail->isHTML(true);

        // Отправляем письмо
        $mail->send();
        echo "Письмо отправлено: {$voter['email']}\n";
    } catch (Exception $e) {
        echo "Ошибка при отправке письма: {$voter['email']} ({$mail->ErrorInfo})\n";
    }
}
?>
