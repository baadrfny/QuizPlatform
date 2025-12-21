<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";
include __DIR__ . "/../includes/header.php";

/* --- PROTECTION AUTHENTIFICATION --- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

$enseignant_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* --- 1. RÉCUPÉRATION DES DONNÉES DU QUIZ --- */
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ? AND enseignant_id = ?");
$stmt->bind_param("ii", $quiz_id, $enseignant_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    die("Quiz non trouvé ou accès refusé.");
}

/* --- 2. LOGIQUE DE MISE À JOUR (BACKEND) --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quiz'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $categorie_id = (int) $_POST['categorie_id'];

    // A. Mise à jour du Quiz
    $up_stmt = $conn->prepare("UPDATE quizzes SET titre = ?, description = ?, categorie_id = ? WHERE id = ?");
    $up_stmt->bind_param("ssii", $titre, $description, $categorie_id, $quiz_id);
    $up_stmt->execute();

    // B. Gestion des questions (Approche simple : Supprimer et ré-insérer)
    $conn->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quiz_id]);
    
    $qstmt = $conn->prepare("INSERT INTO questions (quiz_id, question, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($_POST['questions'] as $q) {
        if (empty($q['question']) || empty($q['option1'])) continue;
        $correct = (int) $q['correct'];
        $qstmt->bind_param("issssss", $quiz_id, $q['question'], $q['option1'], $q['option2'], $q['option3'], $q['option4'], $correct);
        $qstmt->execute();
    }

    header("Location: add_quiz.php?msg=updated");
    exit;
}

// Récupérer les questions actuelles
$q_res = $conn->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$q_res->bind_param("i", $quiz_id);
$q_res->execute();
$questions_actuelles = $q_res->get_result();

// Catégories
$categories = $conn->query("SELECT id, nom FROM categories");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Quiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg border-t-4 border-blue-600">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Modifier le Quiz : <?= htmlspecialchars($quiz['titre']) ?></h2>
        <a href="add_quiz.php" class="text-gray-500 hover:text-gray-700 text-sm">← Retour à la liste</a>
    </div>

    <form method="POST">
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-semibold mb-2">Titre du Quiz</label>
                <input type="text" name="titre" value="<?= htmlspecialchars($quiz['titre']) ?>" required 
                       class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Catégorie</label>
                <select name="categorie_id" required class="w-full border p-3 rounded-lg">
                    <?php while($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == $quiz['categorie_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-semibold mb-2">Description</label>
            <textarea name="description" class="w-full border p-3 rounded-lg h-24"><?= htmlspecialchars($quiz['description']) ?></textarea>
        </div>

        <div class="border-t pt-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-indigo-600">Questions</h3>
                <button type="button" onclick="addQuestion()" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 shadow-sm">
                    + Ajouter une question
                </button>
            </div>

            <div id="questionsContainer" class="space-y-6">
                <?php 
                $index = 0;
                while($q = $questions_actuelles->fetch_assoc()): 
                ?>
                <div class="p-5 border rounded-xl bg-gray-50 relative shadow-sm border-gray-200">
                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-3 right-4 text-red-500 font-bold hover:scale-110">X</button>
                    <div class="mb-4 font-bold text-gray-600">Question</div>
                    <input type="text" name="questions[<?= $index ?>][question]" value="<?= htmlspecialchars($q['question']) ?>" required 
                           class="w-full border p-2 mb-3 rounded shadow-sm">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="questions[<?= $index ?>][option1]" value="<?= htmlspecialchars($q['option1']) ?>" placeholder="Option 1" required class="border p-2 rounded text-sm">
                        <input type="text" name="questions[<?= $index ?>][option2]" value="<?= htmlspecialchars($q['option2']) ?>" placeholder="Option 2" required class="border p-2 rounded text-sm">
                        <input type="text" name="questions[<?= $index ?>][option3]" value="<?= htmlspecialchars($q['option3']) ?>" placeholder="Option 3" class="border p-2 rounded text-sm">
                        <input type="text" name="questions[<?= $index ?>][option4]" value="<?= htmlspecialchars($q['option4']) ?>" placeholder="Option 4" class="border p-2 rounded text-sm">
                    </div>

                    <select name="questions[<?= $index ?>][correct]" required class="w-full border p-2 mt-4 rounded bg-white font-medium text-blue-700 border-blue-200">
                        <option value="1" <?= $q['correct_option'] == 1 ? 'selected' : '' ?>>Option 1 est correcte</option>
                        <option value="2" <?= $q['correct_option'] == 2 ? 'selected' : '' ?>>Option 2 est correcte</option>
                        <option value="3" <?= $q['correct_option'] == 3 ? 'selected' : '' ?>>Option 3 est correcte</option>
                        <option value="4" <?= $q['correct_option'] == 4 ? 'selected' : '' ?>>Option 4 est correcte</option>
                    </select>
                </div>
                <?php 
                $index++;
                endwhile; 
                ?>
            </div>
        </div>

        <button type="submit" name="update_quiz" class="w-full mt-10 bg-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition-all shadow-lg">
            Enregistrer les modifications
        </button>
    </form>
</div>

<script>
let qIndex = <?= $index ?>;
function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const html = `
    <div class="p-5 border rounded-xl bg-blue-50 relative shadow-sm border-blue-100">
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-3 right-4 text-red-500 font-bold">X</button>
        <div class="mb-4 font-bold text-blue-600">Nouvelle Question</div>
        <input type="text" name="questions[${qIndex}][question]" placeholder="Votre question..." required class="w-full border p-2 mb-3 rounded shadow-sm">
        <div class="grid grid-cols-2 gap-4">
            <input type="text" name="questions[${qIndex}][option1]" placeholder="Option 1" required class="border p-2 rounded text-sm">
            <input type="text" name="questions[${qIndex}][option2]" placeholder="Option 2" required class="border p-2 rounded text-sm">
            <input type="text" name="questions[${qIndex}][option3]" placeholder="Option 3" class="border p-2 rounded text-sm">
            <input type="text" name="questions[${qIndex}][option4]" placeholder="Option 4" class="border p-2 rounded text-sm">
        </div>
        <select name="questions[${qIndex}][correct]" required class="w-full border p-2 mt-4 rounded bg-white text-blue-600 font-bold">
            <option value="">Sélectionner la bonne réponse</option>
            <option value="1">Option 1</option>
            <option value="2">Option 2</option>
            <option value="3">Option 3</option>
            <option value="4">Option 4</option>
        </select>
    </div>`;
    container.insertAdjacentHTML('afterbegin', html);
    qIndex++;
}
</script>

</body>
</html>