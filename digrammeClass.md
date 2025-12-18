```mermaid

classDiagram
    class User {
        -int id
        -string nom
        -string email
        -string password
        -string role
        +register()
        +login()
        +logout()
    }

    class Category {
        -int id
        -string nom
        -string description
        -int id_enseignant
        +create()
        +readAll()
        +update()
        +delete()
    }

    class Quiz {
        -int id
        -string titre
        -string description
        -int id_categorie
        -int id_enseignant
        +addQuiz()
        +getQuizzesByCategory()
        +deleteQuiz()
    }

    class Question {
        -int id
        -int id_quiz
        -string question
        -string option1
        -string option2
        -string option3
        -string option4
        -int correct_option
        +addQuestion()
        +getQuestionsByQuiz()
    }

    class Result {
        -int id
        -int id_quiz
        -int id_etudiant
        -int score
        -date date_passage
        +submitScore()
        +getHistory()
    }

    %%  (Relations)
    User "1" --> "*" Category : Crée (Enseignant)
    User "1" --> "*" Quiz : Crée (Enseignant)
    User "1" --> "*" Result : Passe (Etudiant)
    
    Category "1" *-- "*" Quiz : Contient
    Quiz "1" *-- "*" Question : Contient
    Quiz "1" --> "*" Result : Avoir
