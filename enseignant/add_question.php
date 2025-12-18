<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";
include __DIR__ . "/../includes/header.php";

/* protection login */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* check quiz id */
if (!isset($_GET['quiz_id'])) {
    header("Location: quizzes.php");
    exit;
}

$quiz_id = (int) $_GET['quiz_id'];

/* verify quiz ownership */
$check = $conn->prepare(
    "SELECT id FROM quizzes WHERE id = ? AND created_by = ?"
);
$check->bind_param("ii", $quiz_id, $_SESSION['user_id']);
$check->execute();
$check->store_result();

if ($check->num_rows !== 1) {
    header("Location: quizzes.php");
    exit;
}

/* ADD QUESTION */
if (isset($_POST['add_question'])) {

    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF token");
    }

    $question = trim($_POST['question']);
    $a = trim($_POST['a']);
    $b = trim($_POST['b']);
    $c = trim($_POST['c']);
    $d = trim($_POST['d']);
    $correct = $_POST['correct'];

    if (!empty($question)) {
        $stmt = $conn->prepare(
            "INSERT INTO questions 
            (quiz_id, question, option_a, option_b, option_c, option_d, correct_option)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "issssss",
            $quiz_id,
            $question,
            $a,
            $b,
            $c,
            $d,
            $correct
        );
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Question</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 py-10">

<div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-lg mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Ajouter Question</h2>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <input type="text" name="question" placeholder="Question" class="w-full border p-3 rounded" required>
        <input type="text" name="a" placeholder="Option A" class="w-full border p-3 rounded">
        <input type="text" name="b" placeholder="Option B" class="w-full border p-3 rounded">
        <input type="text" name="c" placeholder="Option C" class="w-full border p-3 rounded">
        <input type="text" name="d" placeholder="Option D" class="w-full border p-3 rounded">

        <select name="correct" class="w-full border p-3 rounded">
            <option value="a">A</option>
            <option value="b">B</option>
            <option value="c">C</option>
            <option value="d">D</option>
        </select>

        <button
            type="submit"
            name="add_question"
            class="w-full py-3 bg-indigo-600 text-white rounded-xl"
        >
            Ajouter
        </button>
    </form>
</div>

</body>
</html>
