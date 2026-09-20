<?php

require_once __DIR__ . '/../includes/auth.php';
requireTeacher();

require_once __DIR__ . '/../includes/db.php';

$testId = isset($_GET['test_id'])
    ? (int)$_GET['test_id']
    : (int)($_POST['test_id'] ?? 0);

if ($testId <= 0) {
    header('Location: questions.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Test
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        duration_minutes,
        total_marks,
        negative_marks
    FROM tests
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$testId]);

$test = $stmt->fetch();

if (!$test) {
    die('Test not found.');
}


/*
|--------------------------------------------------------------------------
| Current Question Count
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM test_questions
    WHERE test_id = ?
");

$stmt->execute([$testId]);

$questionCount = (int)$stmt->fetchColumn();


$error = '';
$success = '';


/*
|--------------------------------------------------------------------------
| Add Question
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $questionText = trim($_POST['question_text'] ?? '');

    $optionA = trim($_POST['option_a'] ?? '');
    $optionB = trim($_POST['option_b'] ?? '');
    $optionC = trim($_POST['option_c'] ?? '');
    $optionD = trim($_POST['option_d'] ?? '');

    $correctOption = strtoupper(
        trim($_POST['correct_option'] ?? '')
    );

    $marks = (float)($_POST['marks'] ?? 0);

    $negativeMarks = (float)($_POST['negative_marks'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($questionText === '') {

        $error = 'Please enter the question.';

    } elseif ($optionA === '') {

        $error = 'Please enter option A.';

    } elseif ($optionB === '') {

        $error = 'Please enter option B.';

    } elseif ($optionC === '') {

        $error = 'Please enter option C.';

    } elseif ($optionD === '') {

        $error = 'Please enter option D.';

    } elseif (!in_array(
        $correctOption,
        ['A', 'B', 'C', 'D'],
        true
    )) {

        $error = 'Please select the correct option.';

    } elseif ($marks <= 0) {

        $error = 'Marks must be greater than 0.';

    } elseif ($negativeMarks < 0) {

        $error = 'Negative marks cannot be negative.';

    } else {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Insert Question
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO questions
                (
                    question_text,
                    option_a,
                    option_b,
                    option_c,
                    option_d,
                    correct_option,
                    marks,
                    negative_marks
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $questionText,
                $optionA,
                $optionB,
                $optionC,
                $optionD,
                $correctOption,
                $marks,
                $negativeMarks
            ]);


            $questionId = (int)$pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Question Order
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(question_order), 0) + 1
                FROM test_questions
                WHERE test_id = ?
            ");

            $stmt->execute([$testId]);

            $questionOrder = (int)$stmt->fetchColumn();


            /*
            |--------------------------------------------------------------------------
            | Attach Question To Test
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO test_questions
                (
                    test_id,
                    question_id,
                    question_order
                )
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $testId,
                $questionId,
                $questionOrder
            ]);


            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Redirect Back To Questions
            |--------------------------------------------------------------------------
            */

            header(
                'Location: questions.php?test_id=' .
                $testId
            );

            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Could not save question: ' .
                $e->getMessage();
        }

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

<title>Add Question - MODUS CBT</title>

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
    max-width: 900px;
    margin: 35px auto;
    padding: 0 20px;
}

.header {
    margin-bottom: 20px;
}

.header h1 {
    margin-bottom: 8px;
}

.header p {
    color: #666;
}

.test-info {
    background: white;
    padding: 18px;
    border-radius: 10px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.05);
}

.test-info strong {
    font-size: 18px;
}

.form-box {
    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.05);
}

label {
    display: block;

    margin-top: 18px;
    margin-bottom: 7px;

    font-size: 14px;
    font-weight: 600;
}

textarea,
input,
select {

    width: 100%;

    padding: 12px;

    border: 1px solid #ddd;

    border-radius: 7px;

    font-family: Arial, sans-serif;

    font-size: 15px;
}

textarea {
    min-height: 130px;
    resize: vertical;
}

textarea:focus,
input:focus,
select:focus {

    outline: none;

    border-color: #111;
}

.options {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;
}

.marks {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;
}

.error {

    background: #fff0f0;

    color: #a00000;

    padding: 13px;

    border-radius: 7px;

    margin-bottom: 20px;
}

.buttons {

    display: flex;

    gap: 10px;

    margin-top: 25px;
}

button,
.back {

    padding: 12px 18px;

    border-radius: 7px;

    font-size: 14px;

    text-decoration: none;
}

button {

    background: #111;

    color: white;

    border: none;

    cursor: pointer;
}

button:hover {
    background: #333;
}

.back {

    background: white;

    color: #111;

    border: 1px solid #ddd;
}

@media(max-width:700px) {

    .container {
        padding: 15px;
    }

    .options,
    .marks {
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

        <h1>Add Question</h1>

        <p>
            Add a question to the selected test.
        </p>

    </div>


    <div class="test-info">

        <strong>
            <?= htmlspecialchars($test['title']) ?>
        </strong>

        <br><br>

        Questions currently added:
        <strong><?= $questionCount ?></strong>

    </div>


    <?php if ($error): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <div class="form-box">


        <form method="POST">


            <input
                type="hidden"
                name="test_id"
                value="<?= $testId ?>"
            >


            <label>
                Question
            </label>

            <textarea
                name="question_text"
                placeholder="Enter the question here..."
                required
            ></textarea>


            <div class="options">


                <div>

                    <label>
                        Option A
                    </label>

                    <input
                        type="text"
                        name="option_a"
                        placeholder="Option A"
                        required
                    >

                </div>


                <div>

                    <label>
                        Option B
                    </label>

                    <input
                        type="text"
                        name="option_b"
                        placeholder="Option B"
                        required
                    >

                </div>


                <div>

                    <label>
                        Option C
                    </label>

                    <input
                        type="text"
                        name="option_c"
                        placeholder="Option C"
                        required
                    >

                </div>


                <div>

                    <label>
                        Option D
                    </label>

                    <input
                        type="text"
                        name="option_d"
                        placeholder="Option D"
                        required
                    >

                </div>


            </div>


            <label>
                Correct Option
            </label>

            <select
                name="correct_option"
                required
            >

                <option value="">
                    -- Select Correct Option --
                </option>

                <option value="A">
                    A
                </option>

                <option value="B">
                    B
                </option>

                <option value="C">
                    C
                </option>

                <option value="D">
                    D
                </option>

            </select>


            <div class="marks">


                <div>

                    <label>
                        Marks
                    </label>

                    <input
                        type="number"
                        name="marks"
                        value="1"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>


                <div>

                    <label>
                        Negative Marks
                    </label>

                    <input
                        type="number"
                        name="negative_marks"
                        value="0"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


            </div>


            <div class="buttons">

                <button type="submit">
                    Save Question
                </button>

                <a
                    href="questions.php?test_id=<?= $testId ?>"
                    class="back"
                >
                    Cancel
                </a>

            </div>


        </form>


    </div>


</div>

</body>

</html>