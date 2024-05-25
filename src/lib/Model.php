<?php

namespace Daniel\Vote;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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

    static public function isRequestJson(Request $request) {
        return $request->getHeader('Content-Type')
            && "application/json" == $request->getHeader('Content-Type')[0];
    }

    static public function getRequestData(Request $request) {
        return self::isRequestJson($request) ? $request->getParsedBody() : $_REQUEST;
    }

    static public function getQuestions(PDO $pdo) {
        $sql = <<<EOS
        SELECT q.*, (SELECT COUNT(*) FROM vote v WHERE v.q = q.id) AS votes
        FROM question q
      --  INNER JOIN answer a ON a.q = q.id
      -- GROUP BY q.id HAVING COUNT(a.id) > 1
      LIMIT 10
      EOS;
        $stmt = $pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}