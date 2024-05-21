<?php

namespace Daniel;

use PDO;

class Model {
    /**
     * PDO $pdo
     */
    protected $pdo = null;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    static public function generateId(...$data) {
        $data[] = time();
        return md5(implode(":", $data));
    }

    static public function login($email) {
        $_SESSION['email'] = $email;
        session_regenerate_id();
    }
}