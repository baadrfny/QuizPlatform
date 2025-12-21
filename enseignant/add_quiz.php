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

/* --- GÉNÉRATION DU TOKEN CSRF --- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$enseignant_id = $_SESSION['user_id'];

/* --- 1. LOGIQUE DE SUPPRESSION (BACKEND) --- */
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Étape A: Supprimer les questions liées au quiz (Suppression en cascade manuelle)
    $stmt1 = $conn->prepare("DELETE FROM questions WHERE quiz_id = ?");
    $stmt1->bind_param("i", $delete_id);
    $stmt1->execute();

    // Étape B: Supprimer le quiz lui-même
    $stmt2 = $conn->prepare("DELETE FROM quizzes WHERE id = ? AND enseignant_id = ?");
    $stmt2->bind_param("ii", $delete_id, $enseignant_id);
    $stmt2->execute();

    header("Location: add_quiz.php?msg=deleted");
    exit;
}

/* --- 2. LOGIQUE D'AJOUT DE QUIZ --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité CSRF");
    }

    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $categorie_id = (int) $_POST['categorie_id'];

    if (!empty($titre) && $categorie_id > 0 && !empty($_POST['questions'])) {
        
        // Insertion du Quiz
        $stmt = $conn->prepare("INSERT INTO quizzes (titre, description, categorie_id, enseignant_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssii", $titre, $description, $categorie_id, $enseignant_id);
        $stmt->execute();
        $quiz_id = $conn->insert_id;

        // Insertion des Questions
        $qstmt = $conn->prepare("INSERT INTO questions (quiz_id, question, option1, option2, option3, option4, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($_POST['questions'] as $q) {
            if (empty($q['question']) || empty($q['option1'])) continue;
            $correct = (int) $q['correct'];
            $qstmt->bind_param("issssss", $quiz_id, $q['question'], $q['option1'], $q['option2'], $q['option3'], $q['option4'], $correct);
            $qstmt->execute();
        }
        header("Location: add_quiz.php?msg=success");
        exit;
    }
}

/* --- 3. RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE --- */
// Catégories pour le select
$categories = $conn->query("SELECT id, nom FROM categories");

// Liste des quiz de l'enseignant
$quizzes_list = $conn->prepare("SELECT q.*, c.nom as cat_name FROM quizzes q LEFT JOIN categories c ON q.categorie_id = c.id WHERE q.enseignant_id = ? ORDER BY q.created_at DESC");
$quizzes_list->bind_param("i", $enseignant_id);
$quizzes_list->execute();
$result = $quizzes_list->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Quiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<div class="p-28">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg h-fit border-t-4 border-indigo-600">
        <h2 class="text-xl font-bold mb-6">Créer un Quiz</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Titre</label>
                <input type="text" name="titre" required class="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-400 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Catégorie</label>
                <select name="categorie_id" required class="w-full border p-2 rounded">
                    <option value="">Choisir...</option>
                    <?php while($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" class="w-full border p-2 rounded h-20"></textarea>
            </div>

            <div class="flex justify-between items-center mb-4">
                <span class="font-bold text-indigo-600">Questions</span>
                <button type="button" onclick="addQuestion()" class="text-xs bg-green-500 text-white px-2 py-1 rounded">+ Ajouter</button>
            </div>

            <div id="questionsContainer" class="space-y-4 max-h-96 overflow-y-auto p-2 border-l-2 border-gray-100">
                </div>

            <button type="submit" name="create_quiz" class="w-full mt-6 bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
                Publier le Quiz
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg border-t-4 border-gray-400">
        <h2 class="text-xl font-bold mb-6 text-gray-700">Mes Quiz existants</h2>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded text-sm font-medium">
                Opération effectuée avec succès !
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-3 text-xs uppercase text-gray-500">Titre</th>
                        <th class="p-3 text-xs uppercase text-gray-500">Catégorie</th>
                        <th class="p-3 text-xs uppercase text-gray-500 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if($result->num_rows > 0): ?>
                        <?php while($quiz = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($quiz['titre']) ?></td>
                            <td class="p-3"><span class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded text-xs"><?= htmlspecialchars($quiz['cat_name']) ?></span></td>
                            <td class="p-3 text-center space-x-2">
                                <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="text-blue-500 hover:text-blue-700 text-sm font-bold">Modifier</a>
                                <a href="?delete_id=<?= $quiz['id'] ?>" onclick="return confirm('Attention: Cela supprimera aussi toutes les questions !')" class="text-red-500 hover:text-red-700 text-sm font-bold">Supprimer</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="p-10 text-center text-gray-400">Vous n'avez pas encore créé de quiz.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>


<script>
let qIndex = 0;
function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const html = `
    <div class="p-3 border rounded bg-gray-50 relative">
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-1 right-2 text-red-500 text-xs">X</button>
        <input type="text" name="questions[${qIndex}][question]" placeholder="Question..." required class="w-full text-sm border p-2 mb-2 rounded">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" name="questions[${qIndex}][option1]" placeholder="Opt 1" required class="text-xs border p-1 rounded">
            <input type="text" name="questions[${qIndex}][option2]" placeholder="Opt 2" required class="text-xs border p-1 rounded">
            <input type="text" name="questions[${qIndex}][option3]" placeholder="Opt 3" class="text-xs border p-1 rounded">
            <input type="text" name="questions[${qIndex}][option4]" placeholder="Opt 4" class="text-xs border p-1 rounded">
        </div>
        <select name="questions[${qIndex}][correct]" required class="w-full text-xs border p-1 rounded bg-white">
            <option value="">Correct ?</option>
            <option value="1">Option 1</option>
            <option value="2">Option 2</option>
            <option value="3">Option 3</option>
            <option value="4">Option 4</option>
        </select>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    qIndex++;
}


        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');

            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleDropdown') === -1) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    
</script>

</body>
</html>