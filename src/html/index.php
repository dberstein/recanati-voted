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
    return $response->withHeader('Location', RouteContext::fromRequest($request)->getRouteParser()
        ->urlFor('index'))
        ->withStatus(302);
})->setName('login');

$app->get('/login/google', function (Request $request, Response $response, $args) use ($client, $model) {
    $client->login($model);
    return $response->withHeader('Location', RouteContext::fromRequest($request)->getRouteParser()
        ->urlFor('index'))
        ->withStatus(302);
})->setName('glogin');

$app->get('/logout', function (Request $request, Response $response, $args) use ($model) {
    $model->logout();
    return $response->withHeader('Location', RouteContext::fromRequest($request)->getRouteParser()
        ->urlFor('index'))
        ->withStatus(302);
})->setName('logout');

$app->post('/q', function (Request $request, Response $response, $args) use ($model) {
    return $response->withHeader('Location', RouteContext::fromRequest($request)->getRouteParser()
        ->urlFor('question', [
            'question' => $model->createQuestion($request),
        ]))->withStatus(302);
});

$app->get('/q/{question}', function (Request $request, Response $response, $args) use ($view, $model) {
    return $view([])->render($response, 'question.html', $model->viewQuestion($request, $args['question']));
})->setName('question');

$app->post('/q/{question}', function (Request $request, Response $response, $args) use ($model) {
    if (!empty (trim($_POST['answer']))) {
        $model->createAnswer($request, $args['question'], $_POST['answer']);
    }
    return $response->withHeader('Location', RouteContext::fromRequest($request)->getRouteParser()
        ->urlFor('question', [
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
header('Connection: close');
session_cache_limiter(false);
session_start();
$app->run();