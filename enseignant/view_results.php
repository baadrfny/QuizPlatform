<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";

/* Auth protection */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* Fetch results of quizzes created by this teacher */
$stmt = $conn->prepare(
    "SELECT 
        r.score,
        r.total_questions,
        r.completed_at,
        u.nom AS etudiant_nom,
        q.titre AS quiz_titre
     FROM results r 
     JOIN users u ON u.id = r.etudiant_id
     JOIN quizzes q ON q.id = r.quiz_id
     WHERE q.enseignant_id = ?
     ORDER BY r.completed_at DESC"
);

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow">
    <h2 class="text-2xl font-bold mb-6">Quiz Results</h2>

    <?php if ($results->num_rows === 0): ?>
        <p class="text-gray-500 text-center">No results available yet</p>
    <?php else: ?>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="p-3 border">Student</th>
                    <th class="p-3 border">Quiz</th>
                    <th class="p-3 border">Score</th>
                    <th class="p-3 border">Total Questions</th>
                    <th class="p-3 border">Completed At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border"><?= htmlspecialchars($row['etudiant_nom']) ?></td>
                    <td class="p-3 border"><?= htmlspecialchars($row['quiz_titre']) ?></td>
                    <td class="p-3 border font-semibold text-indigo-600">
                        <?= $row['score'] ?>
                    </td>
                    <td class="p-3 border"><?= $row['total_questions'] ?></td>
                    <td class="p-3 border">
                        <?= date("Y-m-d H:i", strtotime($row['completed_at'])) ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

</body>
</html>
