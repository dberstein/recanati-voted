<?php declare(strict_types=1);

namespace Daniel\Vote;

use Exception;
use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Daniel\Vote\Google\Client;

class Model
{
    /**
     * PDO $pdo
     */
    protected $pdo = null;

    /**
     * Client $client
     */
    protected $client = null;

    public function __construct(PDO $pdo, Client $client)
    {
        $this->pdo = $pdo;
        $this->client = $client;
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

    public function isRequestJson(Request $request)
    {
        return $request->getHeader('Content-Type')
            && "application/json" == $request->getHeader('Content-Type')[0];
    }

    public function getRequestData(Request $request)
    {
        return $this->isRequestJson($request) ? $request->getParsedBody() : $_REQUEST;
    }

    public function createQuestion(Request $request): string
    {
        $requestData = $this->getRequestData($request);
        $stmt = $this->pdo->prepare('INSERT INTO question (id, text, created_by) VALUES (:id, :text, :email);');
        $q = $this->generateId($requestData['q']);
        $data = [
            ":id" => $q,
            ":text" => $requestData['q'],
        ];
        if (array_key_exists('email', $_SESSION)) {
            $data[":email"] = $_SESSION['email'];
        }
        $stmt->execute($data);

        foreach ($requestData['category'] as $category) {
            $stmt = $this->pdo->prepare("INSERT INTO question_cat (q, cat) VALUES (:q, :cat);");
            $stmt->execute([
                ':q' => $q,
                ':cat' => $category,
            ]);
        }

        // $this->pdo->commit();
        return $q;
    }

    public function viewQuestion(Request $request, $q): array
    {
        $stmtQuestion = $this->pdo->prepare('SELECT * FROM question WHERE id = :q');
        $stmtQuestion->execute([':q' => $q]);

        $stmtAnswer = $this->pdo->prepare('SELECT a FROM vote WHERE created_by = :email AND q = :question');
        $stmtAnswer->execute([
            ':question' => $q,
            ':email' => array_key_exists('email', $_SESSION) ? $_SESSION['email'] : '',
        ]);
        $answer = $stmtAnswer->fetch(PDO::FETCH_ASSOC);
        $voted = false;
        if ($answer) {
            $voted = $answer['a'];
        }

        $answers = $this->getAnswers($q);

        // Calculate percentages
        $total = 0;
        foreach ($answers as $a) {
            $total += $a['cnt'];
        }
        foreach ($answers as &$a) {
            $pct = 0;
            if ($total != 0) {
                $pct = 100 * ($a['cnt'] / $total);
            }

            $a['pct'] = $pct;
            $a['voted'] = ($a['id'] == $voted);
        }

        $isLogin = $this->isLogin();
        $question = $stmtQuestion->fetch(PDO::FETCH_ASSOC);
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return [
            'question' => $question,
            'answers' => $answers,
            'is_login' => $isLogin,
            'is_owner' => $isLogin ? $question['created_by'] == $_SESSION['email'] : false,
            'voted' => $voted,
            'url' => [
                'login' => $routeParser->urlFor('login'),
                'logout' => $routeParser->urlFor('logout'),
            ],
        ];
    }

    public function getQuestions($pageSize, $page, $categories = null): array|false
    {
        $offset = ($page - 1) * $pageSize;
        if ($categories) {
            $cats = implode(",", array_map(function ($x) {
                return $this->pdo->quote($x);
            }, (array) $categories));

            $sql = <<<EOS
  SELECT q.*,
         (SELECT COUNT(*) FROM vote v WHERE v.q = q.id) AS votes
    FROM question q INNER JOIN question_cat qc ON qc.q = q.id AND qc.cat IN ($cats) 
GROUP BY q.id
ORDER BY seq DESC
   LIMIT $offset, $pageSize + 1
EOS;
        } else {
            $sql = <<<EOS
  SELECT q.*,
         (SELECT COUNT(*) FROM vote v WHERE v.q = q.id) AS votes
    FROM question q
ORDER BY seq DESC
   LIMIT $offset, $pageSize + 1
EOS;
        }

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAnswers($q): array|false
    {
        $sqlAnswers = <<<EOS
        SELECT a.*, (
                  SELECT COUNT(*) FROM vote v WHERE v.q = :q AND v.a = a.id
               ) AS cnt
          FROM answer a
         WHERE a.q = :q
      -- ORDER BY a.text
      EOS;

        $stmtAnswers = $this->pdo->prepare($sqlAnswers);
        $stmtAnswers->execute([':q' => $q]);
        return $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAnswer(Request $request, $q, $a): void
    {
        if (empty(trim($q))) {
            throw new Exception('Missing question!');
        }
        if (empty(trim($q))) {
            throw new Exception('Missing answer!');
        }

        $stmt = $this->pdo->prepare('INSERT INTO answer (id, q, text) VALUES (:id, :q, :text);');
        $stmt->execute([
            ':id' => $this->generateId($q, $a),
            ':q' => $q,
            ':text' => $a,
        ]);
        // $this->pdo->commit();
    }

    public function vote(Request $request, $q, $a): string
    {
        // UPDATE or INSERT user's vote?
        $stmt = $this->pdo->prepare('SELECT * FROM vote WHERE q=:q AND created_by=:email');
        $stmt->execute([
            ':q' => $q,
            ':email' => $_SESSION['email'],
        ]);
        if ($stmt->fetch()) {
            $sql = 'UPDATE vote SET a=:a WHERE q=:q AND created_by=:email;';
        } else {
            $sql = 'INSERT INTO vote (q, a, created_by) VALUES (:q, :a, :email);';
        }

        // Execute vote...
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':q' => $q,
            ':a' => $a,
            ':email' => $_SESSION['email'],
        ]);
        // $this->pdo->commit();

        return $this->urlFor($request, 'question', [
            'question' => $q,
        ]);
    }

    public function urlFor(Request $request, $name, array $args = null)
    {
        if (is_null($args)) {
            $args = [];
        }
        return RouteContext::fromRequest($request)->getRouteParser()->urlFor($name, $args);
    }

    public function getAuthUrl()
    {
        return $this->client->getAuthUrl();
    }

    public function clientLogin()
    {
        return $this->client->login($this);
    }
}