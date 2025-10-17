<?php
// vote.php

require 'db_connection.php';
session_start();

// Получение токена из URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "Недействительная ссылка.";
    exit();
}

// Проверка токена
$stmt = $pdo->prepare("SELECT * FROM tokens WHERE token = ? AND used = FALSE");
$stmt->execute([$token]);
$tokenData = $stmt->fetch();

if (!$tokenData) {
    echo "Токен недействителен или уже использован. Почему это могло произойти? <br> - Голосование ещё не началось или уже закончено<br> - Опечатка в ссылке <br> - Вы уже проголосовали";
    exit();
}

$election_id = $tokenData['election_id'];

// Получение информации о выборах
$stmt_election = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
$stmt_election->execute([$election_id]);
$election = $stmt_election->fetch();

// Проверка статуса выборов
if ($election['status'] != 'active') {
    echo "Голосование недоступно. Почему это могло произойти? <br> - Голосование ещё не началось или уже закончено<br> - Опечатка в ссылке";
    exit();
}

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Получение списка кандидатов
$stmt_candidates = $pdo->prepare("
    SELECT c.id, c.name 
    FROM candidates c 
    JOIN election_candidates ec ON c.id = ec.candidate_id 
    WHERE ec.election_id = ?
    ORDER BY c.name
");
$stmt_candidates->execute([$election_id]);
$candidates = $stmt_candidates->fetchAll();

// Проверка, включены ли опции "воздержаться" и "против всех" для этих выборов
$allow_abstain = $election['allow_abstain'];
$against_all = $election['against_all'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Голосование</title>
    <style>
        /* Стили оформления с фирменными цветами */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            text-align: center;
            padding: 50px;
        }

        h2 {
            color: rgb(49, 36, 229);
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin: 15px 0;
            font-size: 1.1em;
            text-align: left;
        }

        input[type="checkbox"] {
            margin-right: 10px;
        }

        input[type="submit"] {
            background-color: rgb(49, 36, 229);
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background-color: rgb(111, 66, 244);
        }

        .info {
            font-size: 0.9em;
            color: #777;
            margin-top: 15px;
        }
    </style>
    <script>
        function validateVote() {
            const maxVotes = <?php echo $election['quota']; ?>;
            const candidateCheckboxes = document.querySelectorAll('input[name="candidate_ids[]"]:checked');
            const abstainCheckbox = document.querySelector('input[name="option"][value="abstain"]');
            const againstAllCheckbox = document.querySelector('input[name="option"][value="against_all"]');

            if (candidateCheckboxes.length > maxVotes) {
                alert('Вы не можете выбрать более ' + maxVotes + ' кандидатов.');
                return false;
            }

            if ((abstainCheckbox && abstainCheckbox.checked) || (againstAllCheckbox && againstAllCheckbox.checked)) {
                if (candidateCheckboxes.length > 0) {
                    alert('Вы не можете выбрать кандидатов и опцию "воздержаться" или "против всех" одновременно.');
                    return false;
                }
            }

            if (candidateCheckboxes.length === 0 && !abstainCheckbox?.checked && !againstAllCheckbox?.checked) {
                alert('Вы должны сделать выбор.');
                return false;
            }

            return true;
        }

        function handleOptionChange(option) {
            const candidateCheckboxes = document.querySelectorAll('input[name="candidate_ids[]"]');
            const otherOptions = document.querySelectorAll('input[name="option"]');

            if (option.checked) {
                // Если выбрана опция "воздержаться" или "против всех", снимаем выбор всех кандидатов
                candidateCheckboxes.forEach(cb => cb.checked = false);
                otherOptions.forEach(opt => {
                    if (opt !== option) opt.checked = false;
                });
            }
        }

        function handleCandidateChange() {
            const abstainCheckbox = document.querySelector('input[name="option"][value="abstain"]');
            const againstAllCheckbox = document.querySelector('input[name="option"][value="against_all"]');

            if (abstainCheckbox) abstainCheckbox.checked = false;
            if (againstAllCheckbox) againstAllCheckbox.checked = false;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Проголосуйте за кандидатов</h2>
        <form action="submit_vote.php" method="post" onsubmit="return validateVote();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <p>Выберите не более <?php echo $election['quota']; ?> кандидатов:</p>
            <?php foreach ($candidates as $candidate): ?>
                <label>
                    <input type="checkbox" name="candidate_ids[]" value="<?php echo $candidate['id']; ?>" onchange="handleCandidateChange()">
                    <?php echo htmlspecialchars($candidate['name']); ?>
                </label>
            <?php endforeach; ?>
            
            <!-- Опции "воздержаться" и "против всех", если они включены для выборов -->
            <?php if ($allow_abstain || $against_all): ?>
            <p>Или выберите одну из опций:</p>
            <?php if ($allow_abstain): ?>
            <label>
                <input type="checkbox" name="option" value="abstain" onchange="handleOptionChange(this)"> Воздержаться
            </label>
            <?php endif; ?>
            <?php if ($against_all): ?>
            <label>
                <input type="checkbox" name="option" value="against_all" onchange="handleOptionChange(this)"> Против всех
            </label>
            <?php endif; ?>
            <?php endif; ?>

            <input type="submit" value="Проголосовать">
            <div class="info">Ваш голос анонимен и будет учтен только один раз.</div>
        </form>
    </div>
</body>
</html>
