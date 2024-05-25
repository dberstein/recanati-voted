<?php
require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;

use Daniel\Vote\Google\Client;
use Daniel\Vote\Model;

$app = AppFactory::create();

// Register component on container
$view = function ($container) {
    return new PhpRenderer(__DIR__ . '/../templates/');
};

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$pdo = new PDO("sqlite:/data/voted.db");
$model = new Model($pdo);
$googleClient = new Client(
    getenv('GOOGLE_CLIENT_ID'),
    getenv('GOOGLE_CLIENT_SECRET'),
    getenv('GOOGLE_REDIRECT_URI')
);

// Define app routes
$app->get('/', function (Request $request, Response $response, $args) use ($pdo, $view, $googleClient) {
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $view([])->render($response, 'index.html', [
        'authUrl' => $googleClient->getAuthUrl(),
        'questions' => Model::getQuestions($pdo),
        'url' => [
            'login' => $routeParser->urlFor('login'),
            'logout' => $routeParser->urlFor('logout'),
        ],
    ]);
})->setName('index');

$app->get('/login', function (Request $request, Response $response, $args) use ($view) {
    return $view([])->render($response, 'login.html', []);
});

$app->post('/login', function (Request $request, Response $response, $args) use ($view) {
    $email = array_key_exists('email', $_POST) ? trim($_POST['email']) : '';
    if (!preg_match('/.+@.+\..+$/', $email)) {
        throw new Exception('Invalid email!');
    }
    Model::login($email);

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
})->setName('login');

$app->get('/login/google', function (Request $request, Response $response, $args) use ($view, $googleClient) {
    $googleClient->login();

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
})->setName('glogin');

$app->get('/logout', function (Request $request, Response $response, $args) use ($view) {
    session_destroy();

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
})->setName('logout');

$app->post('/q', function (Request $request, Response $response, $args) use ($pdo, $view) {
    $data = Model::getRequestData($request);

    $stmt = $pdo->prepare('INSERT INTO question (id, text, created_by) VALUES (:id, :text, :email);');
    $data = [
        ":id" => Model::generateId($data['q']),
        ":text" => $data['q'],
    ];
    if (array_key_exists('email', $_SESSION)) {
        $data[":email"] = $_SESSION['email'];
    }

    $stmt->execute($data);

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
});

$app->get('/q/{question}', function (Request $request, Response $response, $args) use ($pdo, $view){
    $q = $args['question'];

    $stmtQuestion = $pdo->prepare('SELECT * FROM question WHERE id = :q');
    $stmtQuestion->execute([':q'=>$q]);

    $stmtAnswer = $pdo->prepare('SELECT a FROM vote WHERE created_by = :email AND q = :question');
    $stmtAnswer->execute([
        ':question' => $q,
        ':email' => array_key_exists('email', $_SESSION) ? $_SESSION['email'] : '',
    ]);
    $answer = $stmtAnswer->fetch(PDO::FETCH_ASSOC);
    $voted = false;
    if ($answer) {
        $voted = $answer['a'];
    }

    $sqlAnswers = <<<EOS
SELECT a.*, (
    SELECT COUNT(*) FROM vote v WHERE v.q = :q AND v.a = a.id
    ) AS cnt
    FROM answer a
   WHERE a.q = :q
ORDER BY a.text
EOS;
    $stmtAnswers = $pdo->prepare($sqlAnswers);
    $stmtAnswers->execute([':q' => $q]);
    $answers = $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);
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

    $isLogin = array_key_exists('email', $_SESSION);
    $question = $stmtQuestion->fetch(PDO::FETCH_ASSOC);
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $view([])->render($response, 'question.html', [
        'question' => $question,
        'answers' => $answers,
        'is_login' => $isLogin,
        'is_owner' => $isLogin ? $question['created_by'] == $_SESSION['email'] : false,
        'voted' => $voted,
        'url' => [
            'login' => $routeParser->urlFor('login'),
            'logout' => $routeParser->urlFor('logout'),
        ],
    ]);
})->setName('question');

$app->post('/q/{question}', function (Request $request, Response $response, $args) use ($pdo, $view){
    $q = $args['question'];

    if (empty(trim($_POST['answer']))) {
        throw new Exception('Missing answer!');
    }

    $stmt = $pdo->prepare('INSERT INTO answer (id, q, text) VALUES (:id, :q, :text);');
    $stmt->execute([
        ':id' => Model::generateId($q, $_POST['answer']),
        ':q' => $q,
        ':text' => $_POST['answer'],
    ]);

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('question', [
            'question' => $q,
        ]))
        ->withStatus(302);

})->setName('create-answer');

$app->post('/vote', function (Request $request, Response $response, $args) use ($pdo) {
    if (!array_key_exists('email', $_SESSION)) {
        throw new Exception('Please login first!');
    }

    $q = $_POST['question'];
    $ans = $_POST['answer'];

    $stmt = $pdo->prepare('SELECT * FROM vote WHERE q=:q AND created_by=:email');
    $stmt->execute([
        ':q' => $q,
        ':email' => $_SESSION['email'],
    ]);
    if ($stmt->fetch()) {
        $sql = 'UPDATE vote SET a=:a WHERE q=:q AND created_by=:email;';
    } else {
        $sql = 'INSERT INTO vote (q, a, created_by) VALUES (:q, :a, :email);';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':q' => $q,
        ':a' => $ans,
        ':email' => $_SESSION['email'],
    ]);

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('question', [
            'question' => $q,
        ]))
        ->withStatus(302);
})->setName('vote');


// Run app
session_cache_limiter(false);
session_start();
$app->run();