<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Daniel\Vote\Google\Client;
use Daniel\Vote\Model;
use Daniel\Vote\Model\Paginator;
use Daniel\Vote\Model\Category;

// Create model...
$model = new Model(
    new PDO("sqlite:/data/voted.db"),
    new Client(
        (string) getenv('GOOGLE_CLIENT_ID'),
        (string) getenv('GOOGLE_CLIENT_SECRET'),
        (string) getenv('GOOGLE_REDIRECT_URI')
    )
);

// Create app...
$app = AppFactory::create();

// View component on container
$view = function (array $container) {
    return new PhpRenderer(__DIR__ . '/../templates/');
};

// Middlewares...
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
// "Connection: close" header...
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response->withHeader('Connection', 'close');
});

// Define app routes
$app->get('/', function (Request $request, Response $response, $args) use ($model, $view) {
    $page = (int) (isset($_GET[Paginator::PARAM]) && is_numeric($_GET[Paginator::PARAM]) ? $_GET[Paginator::PARAM] : 1);
    $pageSize = 5;
    $questions = $model->getQuestions($pageSize, $page, isset($_GET[Category::PARAM]) ? $_GET[Category::PARAM] : []);
    $hasNext = count((array) $questions) > $pageSize;
    $paginator = new Paginator($model->urlFor($request, 'index'), $_SERVER['QUERY_STRING']);
    return $view([])->render($response, 'index.html', [
        'questions' => $questions,
        'pageSize' => $pageSize,
        'hasNext' => $hasNext,
        'page' => $page,
        'url' => [
            'auth' => $model->getAuthUrl(),
            'login' => $model->urlFor($request, 'login'),
            'logout' => $model->urlFor($request, 'logout'),
            'prev' => $paginator->url(-1),
            'next' => $paginator->url(1),
        ],
    ]);
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
    return $response->withHeader('Location', $model->urlFor($request, 'index'))->withStatus(302);
})->setName('login');

$app->get('/login/google', function (Request $request, Response $response, $args) use ($model) {
    $model->clientLogin();
    return $response->withHeader('Location', $model->urlFor($request, 'index'))->withStatus(302);
})->setName('glogin');

$app->get('/logout', function (Request $request, Response $response, $args) use ($model) {
    $model->logout();
    return $response->withHeader('Location', $model->urlFor($request, 'index'))->withStatus(302);
})->setName('logout');

$app->post('/q', function (Request $request, Response $response, $args) use ($model) {
    return $response->withHeader('Location', $model->urlFor($request, 'question', [
        'question' => $model->createQuestion($request),
    ]))->withStatus(302);
})->setName('create-question');

$app->get('/q/{question}', function (Request $request, Response $response, $args) use ($view, $model) {
    return $view([])->render($response, 'question.html', $model->viewQuestion($request, $args['question']));
})->setName('question');

$app->post('/q/{question}', function (Request $request, Response $response, $args) use ($model) {
    if (!empty(trim($_POST['answer']))) {
        $model->createAnswer($request, $args['question'], $_POST['answer']);
    }
    return $response->withHeader('Location', $model->urlFor($request, 'question', [
        'question' => $args['question'],
    ]))->withStatus(302);

})->setName('create-answer');

$app->post('/vote', function (Request $request, Response $response, $args) use ($model) {
    if (!$model->isLogin()) {
        throw new Exception('Please login first!');
    }
    $url = $model->vote($request, $_POST['question'], $_POST['answer']);
    return $response->withHeader('Location', $url)->withStatus(302);
})->setName('vote');

// Run app
session_cache_limiter('');
session_start();
$app->run();
