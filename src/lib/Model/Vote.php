<?php

namespace Daniel\Vote\Model;

use PDO;

class Vote
{
    protected PDO $pdo;
    protected string $question;
    protected string $answer;

    /**
     * @param PDO $pdo
     * @param string $question
     * @param string $answer
     */
    public function __construct(PDO $pdo, string $question, string $answer)
    {
        $this->pdo = $pdo;
        $this->question = $question;
        $this->answer = $answer;
    }

    /**
     * @return bool
     */
    public function __invoke(): bool
    {
        // UPDATE or INSERT user's vote?
        $stmt = $this->pdo->prepare('SELECT * FROM vote WHERE q=:q AND created_by=:email');
        $stmt->execute([
            ':q' => $this->question,
            ':email' => $_SESSION['email'],
        ]);
        if ($stmt->fetch()) {
            $sql = 'UPDATE vote SET a=:a WHERE q=:q AND created_by=:email;';
        } else {
            $sql = 'INSERT INTO vote (q, a, created_by) VALUES (:q, :a, :email);';
        }

        // Execute vote...
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':q' => $this->question,
            ':a' => $this->answer,
            ':email' => $_SESSION['email'],
        ]);
    }
}
