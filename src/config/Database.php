<?php
class Database {

    private $host = 'localhost';
    private $db   = 'course';
    private $user = 'admin';
    private $pass = 'password123';

    public $pdo;

    public function getConnection() {
        $this->pdo = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";

            $this->pdo = new PDO($dsn, $this->user, $this->pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }

        return $this->pdo;
    }
}
?>
