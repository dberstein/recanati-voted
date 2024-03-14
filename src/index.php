<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$container = $app->getContainer();

// Register component on container
$view = function ($container) {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates/');
};
$container['view'] = $view;

$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$pdo = new PDO("sqlite:/data/voted.db");

// Define app routes
$app->get('/', function (Request $request, Response $response, $args) use ($pdo, $view) {
    $sql = <<<EOS
    SELECT q.* FROM question q INNER JOIN answer a ON a.q = q.id
  GROUP BY q.id HAVING COUNT(a.id) > 1
  ORDER BY q.id LIMIT 10
EOS;
$sql = 'SELECT * FROM question ORDER BY id';
    $stmt = $pdo->query($sql);

    return $view([])->render($response, 'index.html', [
        'questions' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
})->setName('index');

$app->post('/q', function (Request $request, Response $response, $args) use ($pdo, $view){
    die(var_dump($_REQUEST));
});

$app->get('/q/{question}', function (Request $request, Response $response, $args) use ($pdo, $view){
    $q = $args['question'];

    $sqlQuestion = "SELECT * FROM question WHERE id = :q";
    $stmtQuestion = $pdo->prepare($sqlQuestion);
    $stmtQuestion->execute([':q'=>$q]);

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
    }

    return $view([])->render($response, 'question.html', [
        'question' => $stmtQuestion->fetch(PDO::FETCH_ASSOC),
        'answers' => $answers,
    ]);
})->setName('question');

$app->get('/q/{question}/{answer}', function (Request $request, Response $response, $args) {
    $q = $args['question'];
    $ans = $args['answer'];
    return $response;
})->setName('answer');

$app->post('/q/{question}/{answer}', function (Request $request, Response $response, $args) {
    $q = $args['question'];
    $ans = $args['answer'];
    return $response;
})->setName('vote');

// Run app
$app->run();