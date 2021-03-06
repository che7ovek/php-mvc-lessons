<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
$userPath = __DIR__.'/templates/users/files/users.json';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
});
$container->set('flash', function() {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('<h1>Welcome to Slim!</h1>');
    $response->getBody()->write('<p>Wow!</p>');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
//     return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term') ?: '';
    if ($term) {
        $resultUsers = array_filter($users, function ($value) use ($term) {
            return str_contains($value, $term);
        });
    } else {
        $resultUsers = $users;
    }
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users' => $resultUsers,
        'term' => $term,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/{id:[0-9]+}', function ($request, $response, array $args) {
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    if (empty($users)) {
        return $response->withStatus(404);
    }

    $userId = $args['id'];
    if (!array_key_exists($userId, $users)) {
        return $response->withStatus(404);
    }

    $user = $users[$userId];
    $user['id'] = $userId;
    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'nickname' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users-new');

$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    $errors = [];
    if (empty($user['name'])) {
        $errors['name'] = 'Empty name';
    }
    if (empty($user['nickname'])) {
        $errors['nickname'] = 'Empty nickname';
    }
    if (count($errors) === 0) {
        $users = json_decode($request->getCookieParam('users', json_encode([])), true);
        $users[] = $user;
        $encodedUsers = json_encode($users);

        $this->get('flash')->addMessage('success', 'User added');
        return $response
            ->withHeader('Set-Cookie', "users={$encodedUsers}")
            ->withRedirect($router->urlFor('users'), 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params)->withStatus(422);
})->setName('users');;

$app->get('/users/{id:[0-9]+}/edit', function ($request, $response, array $args) {
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    if (empty($users)) {
        return $response->withStatus(404);
    }

    $userId = $args['id'];
    if (!array_key_exists($userId, $users)) {
        return $response->withStatus(404)->getBody()->write('Page not found');
    }

    $user = $users[$args['id']];
    $user['id'] = $userId;
    $params = ['id' => $args['id'], 'user' => $user];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('users-edit');

// $app->put
$app->patch('/users/{id:[0-9]+}', function ($request, $response, array $args) use ($router) {
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    if (empty($users)) {
        return $response->withStatus(404);
    }

    $userId = $args['id'];
    if (!array_key_exists($userId, $users)) {
        return $response->withStatus(404);
    }

    $user = $request->getParsedBodyParam('user');
    $user['id'] = $userId;
    $errors = [];
    if (empty($user['name'])) {
        $errors['name'] = 'Empty name';
    }

    if (count($errors) === 0) {
        $users[$userId]['name'] = $user['name'];
        $encodedUsers = json_encode($users);
        $this->get('flash')->addMessage('success', 'User updated');
        return $response
            ->withHeader('Set-Cookie', "users={$encodedUsers}")
            ->withRedirect($router->urlFor('users', ['id' => $userId]), 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params)->withStatus(422);
})->setName('users');

$app->get('/users/{id:[0-9]+}/delete', function ($request, $response, array $args) use ($router) {
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    if (empty($users)) {
        return $response->withStatus(404);
    }

    $userId = $args['id'];
    if (!array_key_exists($userId, $users)) {
        return $response->withStatus(404);
    }

    $user = $users[$userId];
    $user['id'] = $userId;
    $params = [
        'user' => $user
    ];
    return $this->get('renderer')->render($response, 'users/remove.phtml', $params);
})->setName('users-delete');

$app->delete('/users/{id:[0-9]+}', function ($request, $response, array $args) use ($router) {
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    if (empty($users)) {
        return $response->withStatus(404);
    }

    $userId = $args['id'];
    if (!array_key_exists($userId, $users)) {
        return $response->withStatus(404);
    }

    unset($users[$userId]);
    $encodedUsers = json_encode($users);

    $this->get('flash')->addMessage('success', 'User deleted');
    return $response
        ->withHeader('Set-Cookie', "users={$encodedUsers}")
        ->withRedirect($router->urlFor('users'), 302);
})->setName('users');

$app->get('/auth', function ($request, $response) {
    $isAuth = array_key_exists('IS_AUTH', $_SESSION);
    if (!$isAuth) {
        return $this->get('renderer')->render($response, 'auth/index.phtml');
    } else {
        return $this->get('renderer')->render($response, 'auth/logout.phtml');
    }
})->setName('auth');

$app->post('/auth', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    if (isset($user['email'])) {
        $_SESSION['IS_AUTH'] = true;
        return $response
            ->withRedirect($router->urlFor('auth'), 302);
    }
    return $this->get('renderer')->render($response, 'auth/index.phtml')->withStatus(422);
})->setName('auth');

$app->delete('/auth', function ($request, $response) use ($router) {
    $_SESSION = [];
    session_destroy();
    return $response
        ->withRedirect($router->urlFor('auth'), 302);
});

$app->run();