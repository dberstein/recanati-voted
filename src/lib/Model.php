<?php

declare(strict_types=1);

namespace Daniel\Vote;

use Daniel\Vote\Dto\Record;
use Exception;
use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Daniel\Vote\Google\Client;
use Daniel\Vote\Model\Category;
use Daniel\Vote\Model\Vote;

class Model
{
    /**
     * PDO $pdo
     */
    protected PDO $pdo;

    /**
     * Client $client
     */
    protected Client $client;

    /**
     * @param PDO $pdo
     * @param Client $client
     */
    public function __construct(PDO $pdo, Client $client)
    {
        $this->pdo = $pdo;
        $this->client = $client;
    }

    /**
     * @param string ...$data
     * @return string
     */
    public function generateId(string ...$data): string
    {
        $data[] = time();
        return md5(implode(":", $data));
    }

    /**
     * @param string $email
     */
    public function login(string $email): void
    {
        $_SESSION['email'] = $email;
        session_regenerate_id();
    }

    public function logout(): void
    {
        session_destroy();
    }

    /**
     * @return bool
     */
    public function isLogin(): bool
    {
        return array_key_exists('email', $_SESSION)
            && !empty(trim($_SESSION['email']));
    }

    /**
     * @param string $email
     * @return string|false
     */
    public function isValidEmail(string $email): string|false
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isRequestJson(Request $request): bool
    {
        return $request->getHeader('Content-Type')
            && "application/json" == $request->getHeader('Content-Type')[0];
    }

    /* @phpstan-ignore missingType.iterableValue */
    public function getRequestData(Request $request): array|object|null
    {
        return $this->isRequestJson($request) ? $request->getParsedBody() : $_REQUEST;
    }

    public function createQuestion(Request $request): string
    {
        $requestData = (array) $this->getRequestData($request);
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

        foreach ($requestData[Category::PARAM] as $category) {
            $stmt = $this->pdo->prepare("INSERT INTO question_cat (q, cat) VALUES (:q, :cat);");
            $stmt->execute([
                ':q' => $q,
                ':cat' => $category,
            ]);
        }

        // $this->pdo->commit();
        return $q;
    }

    /**
     * @param Request $request
     * @param string $q
     * @phpstan-ignore missingType.iterableValue
    */
    public function viewQuestion(Request $request, string $q): array
    {
        $stmtQuestion = $this->pdo->prepare('SELECT * FROM question WHERE id = :q');
        $stmtQuestion->execute([':q' => $q]);

        $stmtAnswer = $this->pdo->prepare('SELECT a FROM vote WHERE created_by = :email AND q = :question');
        $stmtAnswer->execute([
            ':question' => $q,
            ':email' => array_key_exists('email', $_SESSION) ? $_SESSION['email'] : '',
        ]);
        $answer = (array) $stmtAnswer->fetch(PDO::FETCH_ASSOC);
        $voted = false;
        if ($answer) {
            $voted = $answer['a'];
        }

        $answers = (array) $this->getAnswers($q);

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
        $question = (array) $stmtQuestion->fetch(PDO::FETCH_ASSOC);
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

    /**
     * @param int $pageSize
     * @param int $page
     * @param array<string> $categories
     * @return array<Record>
    */
    public function getQuestions(int $pageSize, int $page, array $categories = null): array
    {
        $offset = ($page - 1) * $pageSize;
        $join = null;
        if ($categories) {
            $cats = implode(",", array_map(function ($x) {
                return $this->pdo->quote($x);
            }, (array) $categories));
            $join = " INNER JOIN question_cat qc ON qc.q = q.id AND qc.cat IN ($cats)";
        }
        $sql = <<<EOS
  SELECT q.*,
         (SELECT COUNT(*) FROM vote v WHERE v.q = q.id) AS votes
    FROM question q $join
ORDER BY seq DESC
   LIMIT $offset, $pageSize + 1
EOS;
        $questions = [];
        $stmt = $this->pdo->query($sql);
        if (!$stmt) {
            return $$questions;
        }
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $record) {
            $questions[] = new Record($record);
        }
        return $questions;
    }

    /**
     * @param string $q
     * @phpstan-ignore missingType.iterableValue
     */
    public function getAnswers(string $q): array|false
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

    /**
     * @param Request $request
     * @param string $q
     * @param string $a
     */
    public function createAnswer(Request $request, string $q, string $a): void
    {
        if (empty(trim($q))) {
            throw new Exception('Missing question!');
        }
        if (empty(trim($a))) {
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

    /**
     * @param Request $request
     * @param string $q
     * @param string $a
     * @return string
     */
    public function vote(Request $request, string $q, string $a): string
    {
        $vote = new Vote($this->pdo, $q, $a);
        return match ($vote()) {
            true => $this->urlFor($request, 'question', [
                'question' => $q,
            ]),
            false => '',
        };
    }

    /**
     * @param Request $request
     * @param string $name
     * @param array<string> $args
     */
    public function urlFor(Request $request, string $name, array $args = null): string
    {
        if (is_null($args)) {
            $args = [];
        }
        return RouteContext::fromRequest($request)->getRouteParser()->urlFor($name, $args);
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->client->getAuthUrl();
    }

    public function clientLogin(): void
    {
        $this->client->login($this);
    }
}
