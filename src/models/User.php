<?php
class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUsername($username)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function create($username, $password, $email)
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password, email) VALUES (?, ?, ?)');
        return $stmt->execute([$username, $password, $email]);
    }
}
