<?php

require_once __DIR__ . '/../includes/auth.php';
requireTeacher();

require_once __DIR__ . '/../includes/db.php';

$tests = $pdo->query("
    SELECT id, title
    FROM tests
    ORDER BY id DESC
")->fetchAll();

$selectedTestId = isset($_GET['test_id'])
    ? (int)$_GET['test_id']
    : 0;

$selectedTest = null;
$questions = [];

if ($selectedTestId > 0) {

    $stmt = $pdo->prepare("
        SELECT id, title, duration_minutes, total_marks, negative_marks
        FROM tests
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$selectedTestId]);

    $selectedTest = $stmt->fetch();

    if ($selectedTest) {

        $stmt = $pdo->prepare("
            SELECT
                q.id,
                q.question_text,
                q.option_a,
                q.option_b,
                q.option_c,
                q.option_d,
                q.correct_option,
                q.marks,
                q.negative_marks,
                tq.question_order
            FROM test_questions tq

            INNER JOIN questions q
                ON q.id = tq.question_id

            WHERE tq.test_id = ?

            ORDER BY tq.question_order ASC
        ");

        $stmt->execute([$selectedTestId]);

        $questions = $stmt->fetchAll();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Questions - MODUS CBT</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f6f8;
    color: #111;
}

.topbar {
    height: 60px;
    background: #111;
    color: white;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 25px;
}

.logo {
    font-size: 20px;
    font-weight: 700;
}

.topbar a {
    color: white;
    text-decoration: none;
}

.container {
    padding: 30px;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    margin: 0 0 8px 0;
}

.header p {
    color: #666;
}

.test-selector {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;

    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

select {
    width: 100%;
    max-width: 500px;

    padding: 12px;

    border: 1px solid #ddd;
    border-radius: 7px;

    font-size: 15px;
}

.question-box {
    background: white;
    border-radius: 10px;

    padding: 20px;
    margin-bottom: 15px;

    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.question-number {
    font-size: 13px;
    color: #777;
    margin-bottom: 8px;
}

.question-text {
    font-weight: 600;
    margin-bottom: 15px;
}

.options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.option {
    background: #f7f7f7;
    padding: 10px;
    border-radius: 6px;
}

.correct {
    border: 1px solid #198754;
    background: #eaf7ef;
}

.add-button {
    display: inline-block;

    margin-top: 20px;

    padding: 12px 18px;

    background: #111;
    color: white;

    text-decoration: none;

    border-radius: 7px;
}

.empty {
    background: white;
    padding: 50px;

    text-align: center;

    border-radius: 10px;
    color: #777;
}

.info {
    display: flex;
    gap: 20px;

    margin-top: 15px;

    color: #666;
    font-size: 14px;
}

@media(max-width:700px) {

    .container {
        padding: 15px;
    }

    .options {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>


<div class="topbar">

    <div class="logo">
        MODUS CBT
    </div>

    <div>

        <a href="dashboard.php">
            Dashboard
        </a>

        &nbsp; | &nbsp;

        <a href="../logout.php">
            Logout
        </a>

    </div>

</div>


<div class="container">


    <div class="header">

        <h1>Questions</h1>

        <p>
            Select a test to manage its questions.
        </p>

    </div>


    <div class="test-selector">

        <form method="GET">

            <label>
                <strong>Select Test</strong>
            </label>

            <br><br>

            <select
                name="test_id"
                onchange="this.form.submit()"
            >

                <option value="">
                    -- Select Test --
                </option>

                <?php foreach ($tests as $test): ?>

                    <option
                        value="<?= $test['id'] ?>"
                        <?= $selectedTestId == $test['id'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($test['title']) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </form>

    </div>


    <?php if ($selectedTest): ?>

        <div class="question-box">

            <h2>
                <?= htmlspecialchars($selectedTest['title']) ?>
            </h2>

            <div class="info">

                <span>
                    Duration:
                    <?= htmlspecialchars($selectedTest['duration_minutes']) ?>
                    min
                </span>

                <span>
                    Total Marks:
                    <?= htmlspecialchars($selectedTest['total_marks']) ?>
                </span>

                <span>
                    Negative:
                    <?= htmlspecialchars($selectedTest['negative_marks']) ?>
                </span>

                <span>
                    Questions:
                    <?= count($questions) ?>
                </span>

            </div>

        </div>


        <?php if (count($questions) > 0): ?>

            <?php foreach ($questions as $index => $question): ?>

                <div class="question-box">

                    <div class="question-number">
                        Question <?= $index + 1 ?>
                    </div>

                    <div class="question-text">

                        <?= nl2br(
                            htmlspecialchars(
                                $question['question_text']
                            )
                        ) ?>

                    </div>


                    <div class="options">

                        <div class="
                            option
                            <?= $question['correct_option'] === 'A'
                                ? 'correct'
                                : '' ?>
                        ">

                            <strong>A.</strong>
                            <?= htmlspecialchars($question['option_a']) ?>

                        </div>


                        <div class="
                            option
                            <?= $question['correct_option'] === 'B'
                                ? 'correct'
                                : '' ?>
                        ">

                            <strong>B.</strong>
                            <?= htmlspecialchars($question['option_b']) ?>

                        </div>


                        <div class="
                            option
                            <?= $question['correct_option'] === 'C'
                                ? 'correct'
                                : '' ?>
                        ">

                            <strong>C.</strong>
                            <?= htmlspecialchars($question['option_c']) ?>

                        </div>


                        <div class="
                            option
                            <?= $question['correct_option'] === 'D'
                                ? 'correct'
                                : '' ?>
                        ">

                            <strong>D.</strong>
                            <?= htmlspecialchars($question['option_d']) ?>

                        </div>

                    </div>


                    <div class="info">

                        <span>
                            Marks:
                            <?= htmlspecialchars($question['marks']) ?>
                        </span>

                        <span>
                            Negative:
                            <?= htmlspecialchars($question['negative_marks']) ?>
                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">

                <h3>No questions yet</h3>

                <p>
                    Add questions to this test.
                </p>

            </div>

        <?php endif; ?>


        <a
            href="add_question.php?test_id=<?= $selectedTestId ?>"
            class="add-button"
        >
            + Add Question
        </a>


    <?php endif; ?>


</div>

</body>

</html>