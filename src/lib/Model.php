<?php

namespace Daniel\Vote;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Model
{
    /**
     * PDO $pdo
     */
    protected $pdo = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateId(...$data)
    {
        $data[] = time();
        return md5(implode(":", $data));
    }

    public function login($email)
    {
        $_SESSION['email'] = $email;
        session_regenerate_id();
    }

    public function logout()
    {
        session_destroy();
    }

    public function isLogin()
    {
        return array_key_exists('email', $_SESSION)
            && !empty(trim($_SESSION['email']));
    }

    public function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    static public function isRequestJson(Request $request)
    {
        return $request->getHeader('Content-Type')
            && "application/json" == $request->getHeader('Content-Type')[0];
    }

    static public function getRequestData(Request $request)
    {
        return self::isRequestJson($request) ? $request->getParsedBody() : $_REQUEST;
    }

    public function getQuestions()
    {
        $sql = <<<EOS
        SELECT q.*, (SELECT COUNT(*) FROM vote v WHERE v.q = q.id) AS votes
        FROM question q
      --  INNER JOIN answer a ON a.q = q.id
      -- GROUP BY q.id HAVING COUNT(a.id) > 1
      LIMIT 10
      EOS;
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAnswers($q)
    {
        $sqlAnswers = <<<EOS
        SELECT a.*, (
                  SELECT COUNT(*) FROM vote v WHERE v.q = :q AND v.a = a.id
               ) AS cnt
          FROM answer a
         WHERE a.q = :q
      ORDER BY a.text
      EOS;

        $stmtAnswers = $this->pdo->prepare($sqlAnswers);
        $stmtAnswers->execute([':q' => $q]);
        return $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);
    }
}