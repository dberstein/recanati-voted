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
$app->addErrorMiddleware(true, true, true);

$pdo = new PDO("sqlite:/data/voted.db");
$model = new Model($pdo);
$client = new Client(
    getenv('GOOGLE_CLIENT_ID'),
    getenv('GOOGLE_CLIENT_SECRET'),
    getenv('GOOGLE_REDIRECT_URI')
);

// Define app routes
$app->get('/', function (Request $request, Response $response, $args) use ($model, $view, $client) {
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $renderData = [
        'authUrl' => $client->getAuthUrl(),
        'questions' => $model->getQuestions(),
        'url' => [
            'login' => $routeParser->urlFor('login'),
            'logout' => $routeParser->urlFor('logout'),
        ],
    ];
    return $view([])->render($response, 'index.html', $renderData);
})->setName('index');

$app->get('/login', function (Request $request, Response $response, $args) use ($view) {
    return $view([])->render($response, 'login.html', []);
});

$app->post('/login', function (Request $request, Response $response, $args) use ($model) {
    $email = array_key_exists('email', $_POST) ? trim($_POST['email']) : '';
    if (!$model->isValidEmail($email)) {
        throw new Exception('Invalid email!');
    }
    $model->login($email);
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
})->setName('login');

$app->get('/login/google', function (Request $request, Response $response, $args) use ($client, $model) {
    $client->login($model);
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
})->setName('glogin');

$app->get('/logout', function (Request $request, Response $response, $args) use ($model) {
    $model->logout();
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    return $response
        ->withHeader('Location', $routeParser->urlFor('index'))
        ->withStatus(302);
})->setName('logout');

$app->post('/q', function (Request $request, Response $response, $args) use ($pdo, $model) {
    $data = Model::getRequestData($request);
    $stmt = $pdo->prepare('INSERT INTO question (id, text, created_by) VALUES (:id, :text, :email);');
    $data = [
        ":id" => $model->generateId($data['q']),
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

$app->get('/q/{question}', function (Request $request, Response $response, $args) use ($pdo, $view, $model) {
    $q = $args['question'];

    $stmtQuestion = $pdo->prepare('SELECT * FROM question WHERE id = :q');
    $stmtQuestion->execute([':q' => $q]);

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

    $answers = $model->getAnswers($q);

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

    $isLogin = $model->isLogin();
    $question = $stmtQuestion->fetch(PDO::FETCH_ASSOC);
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $renderData = [
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
    return $view([])->render($response, 'question.html', $renderData);
})->setName('question');

$app->post('/q/{question}', function (Request $request, Response $response, $args) use ($pdo, $model) {
    $q = $args['question'];

    if (empty(trim($_POST['answer']))) {
        throw new Exception('Missing answer!');
    }

    $stmt = $pdo->prepare('INSERT INTO answer (id, q, text) VALUES (:id, :q, :text);');
    $stmt->execute([
        ':id' => $model->generateId($q, $_POST['answer']),
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

$app->post('/vote', function (Request $request, Response $response, $args) use ($pdo, $model) {
    if (!$model->isLogin()) {
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
header('Connection: close');
session_cache_limiter(false);
session_start();
$app->run();