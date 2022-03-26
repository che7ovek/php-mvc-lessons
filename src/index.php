<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

$nums = [];
for ($i = 0; $i < 10; $i++) {
    $nums[] = $i;
}

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('<h1>Welcome to Slim!</h1>');
    $response->getBody()->write('<p>Wow!</p>');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
//     return $response->write('Welcome to Slim!');
});

$app->get('/user/{id:[0-9]+}', function ($request, $response, array $args) {
    $response->write('User: '.$args['id']);
    // collect это из Laravel
//    $company = collect([
//        ['id' => 100, 'name' => 'Инфа сотка'],
//        ['id' => 228, 'name' => 'Роскомнадзор'],
//    ])->firstWhere('id', $args['id']);
//    if ($company) {
//        $response->write($company['name']);
//    }
    return $response->withStatus(200);
});

$app->get('/users', function ($request, $response) use ($nums){
    return $response->write(json_encode($nums));
});

$app->get('/users/{id}', function ($request, $response, array $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});
$app->run();