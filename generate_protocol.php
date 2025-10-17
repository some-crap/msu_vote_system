<?php
// generate_protocol_auto.php

session_start();
require 'db_connection.php';
require 'vendor/autoload.php'; // Подключаем PHPWord

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;


if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

$role = $_SESSION['admin_role'];
$faculty = $_SESSION['admin_faculty'];
$is_superuser = $_SESSION['is_superuser'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $election_id = $_POST['election_id'];

    // Проверка прав доступа
    if (!$is_superuser && $role != 'commission') {
        $stmt_check = $pdo->prepare("SELECT * FROM elections WHERE id = ? AND faculty = ?");
        $stmt_check->execute([$election_id, $faculty]);
    } else {
        $stmt_check = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt_check->execute([$election_id]);
    }

    $election = $stmt_check->fetch();

    if (!$election) {
        echo "Нет доступа к данному протоколу.";
        exit();
    }

    // Собираем данные для протокола
    $course = $election['course'];
    $faculty = $election['faculty'];
    $quota = $election['quota'];
    $total_students = $election['students_total'];
    $real_quota = $election['real_quota'];
    
    // Рассчитываем количество избирателей и явку
    $stmt_total_voters = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE election_id = ?");
    $stmt_total_voters->execute([$election_id]);
    $total_voters = $stmt_total_voters->fetchColumn();
    $total_voters = $total_students;

    $stmt_voted_students = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE election_id = ? AND used = 1");
    $stmt_voted_students->execute([$election_id]);
    $voted_students = $stmt_voted_students->fetchColumn();

    $turnout_percentage = ($total_voters > 0) ? round(($voted_students / $total_students) * 100, 2) : 'не указано';

    // Получаем результаты голосования
    $stmt_results = $pdo->prepare("SELECT c.name, COUNT(v.id) AS vote_count FROM votes v JOIN candidates c ON v.candidate_id = c.id WHERE v.election_id = ? GROUP BY v.candidate_id ORDER BY vote_count DESC");
    $stmt_results->execute([$election_id]);
    $results = $stmt_results->fetchAll();

    // Получаем количество воздержавшихся (если применимо)
    $stmt_abstain = $pdo->prepare("SELECT COUNT(*) AS abstain_count FROM vote_options WHERE election_id = ? AND option_name = 'Воздержаться'");
    $stmt_abstain->execute([$election_id]);
    $abstain_count = $stmt_abstain->fetchColumn();

    $stmt_against_all = $pdo->prepare("SELECT COUNT(*) AS abstain_count FROM vote_options WHERE election_id = ? AND option_name = 'Против всех'");
    $stmt_against_all->execute([$election_id]);
    $against_all_count = $stmt_against_all->fetchColumn();

    $abstain_percentage = ($voted_students > 0) ? round(($abstain_count / $voted_students) * 100, 2) : 0;
    $against_all_percentage = ($voted_students > 0) ? round(($against_all_count / $voted_students) * 100, 2) : 0;

    // Генерируем документ Word
    $phpWord = new PhpWord();

    // Устанавливаем шрифт по умолчанию для всего документа
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(12);

    // Устанавливаем одинаковые поля
    $sectionStyle = [
        'orientation' => 'portrait',
        'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
        'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
        'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
        'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2)
    ];
    $section = $phpWord->addSection($sectionStyle);

    // Добавляем верхний колонтитул с нумерацией страниц
$header = $section->addHeader();
$headerStyle = ['alignment' => Jc::RIGHT];
$header->addPreserveText('Лист {PAGE}/{NUMPAGES}', ['name' => 'Times New Roman', 'size' => 12], $headerStyle);


    // Добавляем содержимое с отступами
    $paragraphStyleCenter = ['alignment' => 'center', 'spaceAfter' => 200];
    $paragraphStyleLeft = ['alignment' => 'left', 'spaceAfter' => 150, 'indentation' => ['firstLine' => 480]]; // Отступ первой строки

    $section->addText(
        'ПРОТОКОЛ', 
        ['bold' => true, 'size' => 16], 
        $paragraphStyleCenter
    );
    $section->addText(
        "об итогах выборов студенческого совета факультета",//.mb_strtolower($faculty)." (сделать правильный падеж)", 
        ['bold' => true, 'size' => 14], 
        $paragraphStyleCenter
    );
    $section->addText(
        "$faculty", 
        ['bold' => true, 'size' => 14], 
        $paragraphStyleCenter
    );
    $section->addText(
        "$course", 
        ['bold' => false, 'size' => 14], 
        $paragraphStyleCenter
    );

    /*$section->addTextBreak(1);
    $section->addText(
        'Счётная комиссия/ответственные за подсчет установила(-и):', 
        ['bold' => true], 
        $paragraphStyleLeft
    );*/

    // Таблица с результатами подсчета
    $countTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'alignment' => 'center', 'cellMargin' => 80]);
    $cellFontStyle = [];
    $cellParagraphStyle = ['alignment' => 'left', 'spaceAfter' => 100];

    $countTable->addRow();
    $countTable->addCell(8000)->addText('Общее количество избирателей данного избирательного объединения:', $cellFontStyle, $cellParagraphStyle);
    $countTable->addCell(2000)->addText($total_voters, $cellFontStyle, $cellParagraphStyle);

    $countTable->addRow();
    $countTable->addCell(8000)->addText('Количество студентов избирательного объединения, проголосовавших на выборах:', $cellFontStyle, $cellParagraphStyle);
    $countTable->addCell(2000)->addText($voted_students, $cellFontStyle, $cellParagraphStyle);

    $countTable->addRow();
    $countTable->addCell(8000)->addText('Явка избирателей (в процентах):', $cellFontStyle, $cellParagraphStyle);
    $countTable->addCell(2000)->addText($turnout_percentage . '%', $cellFontStyle, $cellParagraphStyle);

    $countTable->addRow();
    $countTable->addCell(8000)->addText('Количество мест в Студенческом совете для данного избирательного объединения:', $cellFontStyle, $cellParagraphStyle);
    $countTable->addCell(2000)->addText($real_quota, $cellFontStyle, $cellParagraphStyle);

    $section->addTextBreak(1);
    $section->addText('Результат подсчёта:', ['bold' => true], $paragraphStyleLeft);

    // Таблица с результатами
    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'alignment' => 'center', 'cellMargin' => 80]);
    $tableFontStyle = ['bold' => true];
    $tableParagraphStyle = ['alignment' => 'center', 'spaceAfter' => 100];

    $table->addRow();
    $table->addCell(6000)->addText('Кандидат', $tableFontStyle, $tableParagraphStyle);
    $table->addCell(2000)->addText('Голосов', $tableFontStyle, $tableParagraphStyle);
    $table->addCell(2000)->addText('Процентов', $tableFontStyle, $tableParagraphStyle);

    foreach ($results as $result) {
        $vote_percentage = ($voted_students > 0) ? round(($result['vote_count'] / $voted_students) * 100, 2) : 0;
        $table->addRow();
        $table->addCell(6000)->addText($result['name'], null, $cellParagraphStyle);
        $table->addCell(2000)->addText($result['vote_count'], null, $cellParagraphStyle);
        $table->addCell(2000)->addText("$vote_percentage%", null, $cellParagraphStyle);
    }

    if ($abstain_count > 0) {
        $table->addRow();
        $table->addCell(6000)->addText('Воздержаться', null, $cellParagraphStyle);
        $table->addCell(2000)->addText($abstain_count, null, $cellParagraphStyle);
        $table->addCell(2000)->addText("$abstain_percentage%", null, $cellParagraphStyle);
    }

    if ($against_all_count > 0) {
        $table->addRow();
        $table->addCell(6000)->addText('Против всех', null, $cellParagraphStyle);
        $table->addCell(2000)->addText($against_all_count, null, $cellParagraphStyle);
        $table->addCell(2000)->addText("$against_all_percentage%", null, $cellParagraphStyle);
    }
    
    //$section->addTextBreak(1);

// Добавляем разрыв страницы
$section->addPageBreak();
    $section->addText('В Студенческий совет избраны:', ['bold' => true], $paragraphStyleLeft);

    foreach ($results as $index => $result) {
            if ($index < $real_quota){
                $section->addText(($index + 1) . ". " . $result['name'], null, $paragraphStyleLeft);
            }
    }
    //$section->addText("Скорректировать по квоте (отсортировано по убыванию количества голосов - просто удалить лишние строки).", null, $paragraphStyleLeft);
$section->addTextBreak(2);

// Добавляем разрыв страницы
$section->addPageBreak();
    $section->addTextBreak(2);
    $section->addText('Лица, ответственные за проведение выборов студенческого совета факультета:', ['bold' => true], $paragraphStyleLeft);
    $section->addText('Фамилия И.О. / _________________', null, $paragraphStyleLeft);
    $section->addText('Фамилия И.О. / _________________', null, $paragraphStyleLeft);

    $section->addTextBreak(2);
    $section->addText('Координатор(-ы) выборов от Избирательной комиссии Студенческого совета МГУ:', ['bold' => true], $paragraphStyleLeft);
    $section->addText('Фамилия И.О. / _________________', null, $paragraphStyleLeft);
    $section->addText('Фамилия И.О. / _________________', null, $paragraphStyleLeft);

    $section->addTextBreak(1);
    //$section->addText('Счётная комиссия/ответственные за подсчет:', ['bold' => true], $paragraphStyleLeft);
    //$section->addText('/ Ю. А. Матвеев', null, $paragraphStyleLeft);
    $section->addText('Протокол сгенерирован электронной системой автоматически по результатам голосования.', null, $paragraphStyleLeft);

    $section->addTextBreak(2);
    $section->addText('Протокол составлен:', ['bold' => true], $paragraphStyleLeft);
    $currentDate = date('d.m.Y');
    $section->addText($currentDate, null, $paragraphStyleLeft);

    // Сохранение документа в буфер
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="protocol_election_' . $election_id . '.docx"');
    header('Cache-Control: max-age=0');

    $objWriter->save('php://output');
    exit();
}
?>
