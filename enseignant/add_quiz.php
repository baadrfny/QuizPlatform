<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";

/* Auth protection */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Handle form submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF check */
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $categorie_id = (int) $_POST['categorie_id'];
    $enseignant_id = $_SESSION['user_id'];

    if (empty($titre) || $categorie_id <= 0 || empty($_POST['questions'])) {
        die("Invalid data");
    }

    /* Insert quiz */
    $stmt = $conn->prepare(
        "INSERT INTO quizzes (titre, description, categorie_id, enseignant_id, created_at)
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("ssii", $titre, $description, $categorie_id, $enseignant_id);
    $stmt->execute();

    $quiz_id = $conn->insert_id;

    /* Prepare question insert */
    $qstmt = $conn->prepare(
        "INSERT INTO questions
        (quiz_id, question, option1, option2, option3, option4, correct_option)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($_POST['questions'] as $q) {

        if (
            empty($q['question']) ||
            empty($q['option1']) ||
            empty($q['option2']) ||
            empty($q['option3']) ||
            empty($q['option4']) ||
            empty($q['correct'])
        ) {
            continue;
        }

        /* Convert correct option */

        $correct = (int) $q['correct'];
        // $correct = match ($q['correct']) {
        //     '1' => 'a',
        //     '2' => 'b',
        //     '3' => 'c',
        //     '4' => 'd',
        //     default => null
        // };

        // if (!$correct) continue;

        $qstmt->bind_param(
            "issssss",
            $quiz_id,
            $q['question'],
            $q['option1'],
            $q['option2'],
            $q['option3'],
            $q['option4'],
            $correct
        );
        $qstmt->execute();
    }

    header("Location: add_quiz.php");
    exit;
}

/* Fetch categories */
$cat_stmt = $conn->prepare("SELECT id, nom FROM categories WHERE created_by = ?");
$cat_stmt->bind_param("i", $_SESSION['user_id']);
$cat_stmt->execute();
$categories = $cat_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Quiz</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow">
<h2 class="text-2xl font-bold mb-6">Create Quiz</h2>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<div class="grid md:grid-cols-2 gap-4 mb-4">
    <input type="text" name="titre" placeholder="Quiz title" required
        class="border p-3 rounded w-full">

    <select name="categorie_id" required class="border p-3 rounded w-full">
        <option value="">Select category</option>
        <?php while ($c = $categories->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
        <?php endwhile; ?>
    </select>
</div>

<textarea name="description" placeholder="Description"
    class="border p-3 rounded w-full mb-6"></textarea>

<hr class="my-6">

<div class="flex justify-between mb-4">
    <h3 class="text-xl font-bold">Questions</h3>
    <button type="button" onclick="addQuestion()"
        class="bg-green-600 text-white px-4 py-2 rounded">
        Add question
    </button>
</div>

<div id="questionsContainer"></div>

<button type="submit"
    class="mt-6 w-full bg-indigo-600 text-white py-3 rounded font-semibold">
    Create Quiz
</button>
</form>
</div>

<script>
let qIndex = 0;

/* Add question block */
function addQuestion() {
    const container = document.getElementById('questionsContainer');

    const html = `
    <div class="border rounded-lg p-4 mb-4 bg-gray-50">
        <div class="flex justify-between mb-2">
            <strong>Question ${qIndex + 1}</strong>
            <button type="button" onclick="this.parentElement.parentElement.remove()"
                class="text-red-600">Remove</button>
        </div>

        <input type="text" name="questions[${qIndex}][question]"
            placeholder="Question text" required
            class="border p-2 rounded w-full mb-3">

        <div class="grid grid-cols-2 gap-2">
            <input type="text" name="questions[${qIndex}][option1]" placeholder="Option 1" required class="border p-2 rounded">
            <input type="text" name="questions[${qIndex}][option2]" placeholder="Option 2" required class="border p-2 rounded">
            <input type="text" name="questions[${qIndex}][option3]" placeholder="Option 3" required class="border p-2 rounded">
            <input type="text" name="questions[${qIndex}][option4]" placeholder="Option 4" required class="border p-2 rounded">
        </div>

        <select name="questions[${qIndex}][correct]" required
            class="border p-2 rounded w-full mt-3">
            <option value="">Correct option</option>
            <option value="1">Option 1</option>
            <option value="2">Option 2</option>
            <option value="3">Option 3</option>
            <option value="4">Option 4</option>
        </select>
    </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    qIndex++;
}
</script>

</body>
</html>
