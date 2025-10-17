<?php
// set_election.php
ini_set('log_errors', 'On');
ini_set('error_log', 'php-error.log');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'Off');

session_start();
require 'db_connection.php';
require 'vendor/autoload.php'; // Для работы с Excel-файлами

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course = $_POST['course'];
    $quota = $_POST['quota'];
    $candidates_input = $_POST['candidates'];
    $faculty = $_SESSION['admin_faculty'];
    $amount_of_students_real = $_POST['amount_of_students_real'];
    $real_quota = $_POST['real_quota'];
    $allow_abstain = isset($_POST['abstain_option']) ? 1 : 0;
    $against_all = isset($_POST['against_all_option']) ? 1 : 0;

    // Вставка данных в таблицу 'elections'
    $stmt = $pdo->prepare("INSERT INTO elections (course, faculty, quota, status, allow_abstain, students_total, real_quota, against_all) VALUES (?, ?, ?, 'inactive', ?, ?, ?, ?)");
    $stmt->execute([$course, $faculty, $quota, $allow_abstain, $amount_of_students_real, $real_quota, $against_all]);

    // Получаем ID созданных выборов
    $election_id = $pdo->lastInsertId();

    // Обработка списка кандидатов
    $candidates = explode("\n", trim($candidates_input));

    $stmt_candidate = $pdo->prepare("INSERT INTO candidates (name, course, faculty) VALUES (?, ?, ?)");
    $stmt_election_candidate = $pdo->prepare("INSERT INTO election_candidates (election_id, candidate_id) VALUES (?, ?)");

    foreach ($candidates as $candidate_name) {
        $candidate_name = trim($candidate_name);
        if (!empty($candidate_name)) {
            // Сохраняем кандидата
            $stmt_candidate->execute([$candidate_name, $course, $faculty]);
            $candidate_id = $pdo->lastInsertId();

            // Связываем кандидата с выбором
            $stmt_election_candidate->execute([$election_id, $candidate_id]);
        }
    }

    // Обработка файла избирателей
    if (isset($_FILES['voters_file']) && $_FILES['voters_file']['error'] == 0) {
        $fileTmpPath = $_FILES['voters_file']['tmp_name'];
        $fileExtension = strtolower(pathinfo($_FILES['voters_file']['name'], PATHINFO_EXTENSION));

        $allowedExtensions = ['xls', 'xlsx'];

        if (in_array($fileExtension, $allowedExtensions)) {
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $worksheet = $spreadsheet->getActiveSheet();

                $stmt_student = $pdo->prepare('INSERT INTO students (email, name, course, faculty) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), course = VALUES(course)');
                $stmt_token = $pdo->prepare("INSERT INTO tokens (token, student_id, election_id) VALUES (?, ?, ?)");

                foreach ($worksheet->getRowIterator(2) as $row) {
                    $email = $worksheet->getCell('A' . $row->getRowIndex())->getValue();
                    $name = $worksheet->getCell('B' . $row->getRowIndex())->getValue();

                    $email = trim($email);
                    $name = trim($name);

                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // Сохраняем студента
                        $stmt_student->execute([$email, $name, $course, $faculty]);
                        $student_id = $pdo->lastInsertId();

                        // Генерируем токен для голосования
                        $token = md5(random_bytes(32).time().$email);
                        $stmt_token->execute([$token, $student_id, $election_id]);
                    }
                }

                $_SESSION['message'] = "Выборы успешно созданы, кандидаты добавлены, избиратели загружены.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Ошибка при обработке файла избирателей: " . $e->getMessage()." Ищи ошибку тут: ".$email. " " .$name. " " .$course. " " .$faculty;
            }
        } else {
            $_SESSION['error'] = "Недопустимый тип файла избирателей. Пожалуйста, загрузите файл Excel (.xls или .xlsx).";
        }
    } else {
        $_SESSION['error'] = "Ошибка при загрузке файла избирателей.";
    }

    header('Location: admin_dashboard.php');
    exit();
}
?>
