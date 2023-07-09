<?php


use TuCreusesOu\Controller\Controller;
use TuCreusesOu\Controller\IndexController;

include_once '../vendor/autoload.php';
include_once '../config.php';

$requestUri = $_SERVER['REQUEST_URI'];

if (!session_start()) {
    echo "Quelque chose s'est très mal passé...";
    http_response_code(500);
    die;
}

$controllersFiles = scandir('../src/Controller');
$controllers = [];
foreach ($controllersFiles as $controller) {
    if (strpos($controller, 'Controller.php') > 0) {
        $controllerName = str_replace('Controller.php', '', $controller);
        $controllers[strtolower($controllerName)] = '\TuCreusesOu\Controller\\' . $controllerName . 'Controller';
    }
}
$request = explode('/', explode('#', explode('?', $requestUri)[0])[0]);
if (array_key_exists($request[1], $controllers)) {
    /**
     * @var Controller $controller
     */
    $controller = new $controllers[$request[1]]();
    if (count($request) > 2) {
        $methodName = $request[2] . 'Action';
        if (method_exists($controller, $methodName)) {
            unset($request[0]);
            unset($request[1]);
            unset($request[2]);
            $controller->$methodName(...$request);
        } else {
            $controller->indexAction();
        }
    } else {
        $controller->indexAction();
    }
} else {
    $controller = new IndexController();
    $controller->indexAction();
}