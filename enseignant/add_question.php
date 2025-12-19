<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";

/* Login protection */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* Generate CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Handle add question */
if (isset($_POST['add_question'])) {

    /* CSRF check */
    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF token");
    }

    $quiz_id = (int) $_POST['quiz_id'];
    $question = trim($_POST['question']);
    $option1 = trim($_POST['option1']);
    $option2 = trim($_POST['option2']);
    $option3 = trim($_POST['option3']);
    $option4 = trim($_POST['option4']);
    $correct_option = (int) $_POST['correct_option'];

    /* Validation */
    if (
        $quiz_id <= 0 ||
        empty($question) ||
        empty($option1) ||
        empty($option2) ||
        empty($option3) ||
        empty($option4) ||
        $correct_option < 1 ||
        $correct_option > 4
    ) {
        $_SESSION['error'] = "All fields are required";
        header("Location: add_question.php");
        exit;
    }

    /* Insert question */
    $stmt = $conn->prepare(
        "INSERT INTO questions
        (quiz_id, question, option1, option2, option3, option4, correct_option)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssssi",
        $quiz_id,
        $question,
        $option1,
        $option2,
        $option3,
        $option4,
        $correct_option
    );

    $stmt->execute();

    $_SESSION['success'] = "Question added successfully";
    header("Location: add_question.php");
    exit;
}

/* Fetch teacher quizzes */
$quiz_stmt = $conn->prepare(
    "SELECT id, titre FROM quizzes WHERE enseignant_id = ?"
);
$quiz_stmt->bind_param("i", $_SESSION['user_id']);
$quiz_stmt->execute();
$quizzes = $quiz_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Add Question</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-md mx-auto mt-10">

    <h2 class="text-2xl font-bold mb-6 text-center">Add Question</h2>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<div class='bg-red-100 text-red-700 p-3 rounded mb-4 text-center'>".$_SESSION['error']."</div>";
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo "<div class='bg-green-100 text-green-700 p-3 rounded mb-4 text-center'>".$_SESSION['success']."</div>";
        unset($_SESSION['success']);
    }
    ?>

    <form method="POST" class="space-y-4">

        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <select name="quiz_id" required
            class="w-full px-4 py-3 border rounded-xl">
            <option value="">Select quiz</option>
            <?php while ($row = $quizzes->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['titre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <textarea name="question" placeholder="Question text" required
            class="w-full px-4 py-3 border rounded-xl"></textarea>

        <input type="text" name="option1" placeholder="Option A" required class="w-full px-4 py-3 border rounded-xl">
        <input type="text" name="option2" placeholder="Option B" required class="w-full px-4 py-3 border rounded-xl">
        <input type="text" name="option3" placeholder="Option C" required class="w-full px-4 py-3 border rounded-xl">
        <input type="text" name="option4" placeholder="Option D" required class="w-full px-4 py-3 border rounded-xl">

        <select name="correct_option" required
            class="w-full px-4 py-3 border rounded-xl">
            <option value="">Correct option</option>
            <option value="1">A</option>
            <option value="2">B</option>
            <option value="3">C</option>
            <option value="4">D</option>
        </select>

        <button type="submit" name="add_question"
            class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
            Add
        </button>
    </form>
</div>

</body>
</html>
