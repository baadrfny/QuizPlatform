<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";

/* protection login (ila user makach enseignant raj3o login) */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* generate CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* handle form submit */
if (isset($_POST['add_quiz'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $categorie_id = (int) $_POST['categorie_id'];
    $enseignant_id = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');
    $is_active = 1; // default active

    if (empty($titre) || $categorie_id <= 0) {
        $_SESSION['error'] = "Titre et catégorie sont obligatoires";
        header("Location: add_quiz.php");
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO quizzes (titre, description, categorie_id, enseignant_id, created_at, is_active)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssissi", $titre, $description, $categorie_id, $enseignant_id, $created_at, $is_active);
    $stmt->execute();

    $_SESSION['success'] = "Quiz ajouté avec succès";
    header("Location: add_quiz.php");
    exit;
}

/* fetch categories pour le select */
$cat_stmt = $conn->prepare("SELECT id, nom FROM categories WHERE created_by = ?");
$cat_stmt->bind_param("i", $_SESSION['user_id']);
$cat_stmt->execute();
$categories = $cat_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Quiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-10">

<div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Ajouter Quiz</h2>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<div class='bg-red-100 text-red-700 p-3 rounded mb-4 text-center'>{$_SESSION['error']}</div>";
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo "<div class='bg-green-100 text-green-700 p-3 rounded mb-4 text-center'>{$_SESSION['success']}</div>";
        unset($_SESSION['success']);
    }
    ?>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <input type="text" name="titre" placeholder="Titre du quiz"
               class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
               required>

        <textarea name="description" placeholder="Description"
                  class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>

        <select name="categorie_id" required
                class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
            <option value="">Sélectionner une catégorie</option>
            <?php while ($row = $categories->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nom']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="add_quiz"
                class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
            Ajouter
        </button>
    </form>
</div>

</body>
</html>
