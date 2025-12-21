# 🎓 Système de Gestion de Quiz Professionnel

Ce projet est une plateforme complète de gestion de quiz (e-Learning) conçue pour les enseignants. Elle permet une gestion autonome du contenu pédagogique, de la création des catégories jusqu'à l'analyse des résultats des étudiants.

## 📌 Fonctionnalités du Livrable

### 1. Gestion des Catégories
- **CRUD Complet :** Création, lecture, mise à jour et suppression des catégories.
- **Suppression Intelligente :** Implémentation de la suppression en cascade au niveau du Backend. Lorsqu'une catégorie est supprimée, tous les quiz et questions associés sont automatiquement nettoyés pour éviter les données orphelines.

### 2. Gestion Dynamique des Quiz
- **Interface Tout-en-un :** Création de quiz et affichage de la liste sur la même page pour une productivité accrue.
- **Questions Flexibles :** Ajout dynamique de questions et d'options de réponse via JavaScript (Frontend).
- **Modification Avancée :** Page dédiée (`edit_quiz.php`) permettant de modifier le titre, la catégorie, ou de réorganiser les questions d'un quiz existant.

### 3. Tableau de Bord des Résultats
- **Suivi en temps réel :** Visualisation des scores des étudiants, du nombre de questions réussies et de la date exacte de passage.
- **Analyse de Performance :** Calcul automatique des pourcentages de réussite.
- **Filtrage Ciblé :** Possibilité de charger les résultats pour un quiz spécifique ou de voir l'ensemble des performances globales.

## 🔒 Sécurité & Architecture (Backend)

Le projet a été développé avec une priorité absolue sur la sécurité des données :

* **Protection CSRF :** Tous les formulaires (`POST`) sont sécurisés par des jetons (tokens) uniques pour empêcher les attaques par falsification de requête intersites.
* **Sécurisation SQL :** Utilisation systématique de **requêtes préparées (Prepared Statements)** avec MySQLi pour bloquer toute tentative d'injection SQL.
* **Contrôle d'Accès (IDOR) :** Un enseignant ne peut accéder (voir, modifier, supprimer) qu'aux données qu'il a lui-même créées. L'accès est vérifié via la session utilisateur sur chaque requête sensible.
* **Protection des Rôles :** Accès aux pages restreint strictement au rôle `enseignant`.



## 🛠️ Stack Technique
- **Langage :** PHP 8.x
- **Base de données :** MySQL
- **Design :** Tailwind CSS (Interface moderne et entièrement responsive)
- **Logique :** JavaScript (Vanilla) pour l'ajout dynamique de champs.

## 📂 Structure du Code
- `add_quiz.php` : Interface principale de création et liste des quiz.
- `edit_quiz.php` : Logique de mise à jour des quiz existants.
- `view_result.php` : Tableau de bord des scores étudiants.
- `categories.php` : Gestionnaire de thématiques.
- `/config/database.php` : Connexion sécurisée à la base de données.

## 🚀 Installation

1. Clonez le dépôt.
2. Importez la base de données MySQL jointe.
3. Configurez vos accès dans le dossier `config`.
4. Connectez-vous avec un compte ayant le rôle `enseignant`.

---
*Projet réalisé dans le cadre du module de développement Web dynamique.*
